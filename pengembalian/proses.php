<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Petugas']);

$pageTitle = 'Verifikasi Pengembalian';
$active = 'pengembalian';
$id = (int)($_GET['id'] ?? $_POST['id_pengembalian'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT r.*, p.tanggal_rencana_kembali, p.id_peminjaman, u.nama_lengkap
     FROM tb_pengembalian r
     JOIN tb_peminjaman p ON p.id_peminjaman = r.id_peminjaman
     JOIN tb_user u ON u.id_user = r.id_user
     WHERE r.id_pengembalian = :id AND r.status = 'Menunggu'"
);
$stmt->execute([':id' => $id]);
$rj = $stmt->fetch();
if (!$rj) { set_flash('error', 'Data pengembalian tidak ditemukan atau sudah diproses.'); redirect('pengembalian/index.php'); }

$items = $pdo->prepare(
    'SELECT d.*, a.nama_alat FROM tb_detail_peminjaman d JOIN tb_alat a ON a.id_alat=d.id_alat WHERE d.id_peminjaman=:id'
);
$items->execute([':id' => $rj['id_peminjaman']]);
$items = $items->fetchAll();
$totalAlat = (int)array_sum(array_column($items, 'jumlah_pinjam'));

$kondisiList = array_keys(tarif_kerusakan());
$dendaTelatOtomatis = hitung_denda($rj['tanggal_rencana_kembali'], $rj['tanggal_kembali'], $totalAlat, TARIF_TELAT_PER_HARI);
$hariTelat = max(0, (int)(new DateTime($rj['tanggal_rencana_kembali']))->diff(new DateTime($rj['tanggal_kembali']))->format('%r%a'));
$dendaRusakOtomatis = hitung_denda_kerusakan($rj['kondisi_alat_kembali'], $totalAlat);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kondisiFinal = $_POST['kondisi_final'] ?? $rj['kondisi_alat_kembali'];
    if (!in_array($kondisiFinal, $kondisiList, true)) $kondisiFinal = $rj['kondisi_alat_kembali'];

    $dendaTelat = max(0, (int)($_POST['denda_telat'] ?? 0));
    $dendaRusak = max(0, (int)($_POST['denda_kerusakan'] ?? 0));
    $dendaTotal = $dendaTelat + $dendaRusak;
    $statusDenda = $dendaTotal > 0 ? 'Belum Dibayar' : 'Tidak Ada';
    $catatan = trim($_POST['catatan_denda'] ?? '');

    try {
        $pdo->beginTransaction();

        // Stok hanya dikembalikan kalau alat memang tidak hilang total
        if ($kondisiFinal !== 'Hilang') {
            $tambah = $pdo->prepare('UPDATE tb_alat SET jumlah_stok = jumlah_stok + :j WHERE id_alat = :id');
            foreach ($items as $it) {
                $tambah->execute([':j' => $it['jumlah_pinjam'], ':id' => $it['id_alat']]);
            }
        }

        $updR = $pdo->prepare(
            "UPDATE tb_pengembalian
             SET kondisi_alat_kembali=:k, denda=:d, denda_telat=:dt, denda_kerusakan=:dr,
                 status_denda=:sd, catatan_denda=:c, status='Selesai', id_petugas=:pt
             WHERE id_pengembalian=:id AND status='Menunggu'"
        );
        $updR->execute([
            ':k' => $kondisiFinal, ':d' => $dendaTotal, ':dt' => $dendaTelat, ':dr' => $dendaRusak,
            ':sd' => $statusDenda, ':c' => $catatan !== '' ? $catatan : null,
            ':pt' => current_user_id(), ':id' => $id,
        ]);

        $updP = $pdo->prepare("UPDATE tb_peminjaman SET status_peminjaman='Selesai' WHERE id_peminjaman=:id");
        $updP->execute([':id' => $rj['id_peminjaman']]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('error', 'Gagal memproses: ' . $e->getMessage());
        redirect('pengembalian/proses.php?id=' . $id);
    }

    log_aktivitas($pdo, "Memverifikasi pengembalian peminjaman #{$rj['id_peminjaman']} (kondisi $kondisiFinal), denda " . rupiah($dendaTotal) . " [$statusDenda]");
    set_flash('success', $dendaTotal > 0
        ? 'Pengembalian diverifikasi. Denda ' . rupiah($dendaTotal) . ' menunggu pembayaran peminjam.'
        : 'Pengembalian berhasil diverifikasi, tanpa denda.');
    redirect('pengembalian/index.php');
}

require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:600px">
  <p><strong>Peminjam:</strong> <?= e($rj['nama_lengkap']) ?></p>
  <p><strong>Rencana Kembali:</strong> <?= e($rj['tanggal_rencana_kembali']) ?> &middot;
     <strong>Tgl Kembali Aktual:</strong> <?= e($rj['tanggal_kembali']) ?>
     <?php if ($hariTelat > 0): ?><span class="badge badge-ditolak">Telat <?= $hariTelat ?> hari</span><?php endif; ?></p>
  <table>
    <thead><tr><th>Alat</th><th>Jumlah</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr><td><?= e($it['nama_alat']) ?></td><td><?= (int)$it['jumlah_pinjam'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <form method="post" style="margin-top:16px">
    <input type="hidden" name="id_pengembalian" value="<?= (int)$id ?>">

    <label>Kondisi Alat (dicek petugas)</label>
    <select name="kondisi_final" id="kondisi_final">
      <?php foreach ($kondisiList as $opt): ?>
        <option <?= $rj['kondisi_alat_kembali'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <p class="hint">Diajukan peminjam sebagai: <strong><?= e($rj['kondisi_alat_kembali']) ?></strong>. Ubah bila hasil pemeriksaan berbeda.</p>

    <label>Denda Keterlambatan (Rp) &mdash; <?= rupiah(TARIF_TELAT_PER_HARI) ?>/hari/alat</label>
    <input type="number" name="denda_telat" id="denda_telat" min="0" value="<?= (int)$dendaTelatOtomatis ?>">

    <label>Denda Kerusakan / Kehilangan (Rp) &mdash; otomatis menurut kondisi &times; <?= $totalAlat ?> alat</label>
    <input type="number" name="denda_kerusakan" id="denda_kerusakan" min="0" value="<?= (int)$dendaRusakOtomatis ?>">
    <p class="hint">Tarif per alat:
      <?php $t = []; foreach (tarif_kerusakan() as $k => $v) { if ($v > 0) $t[] = e($k) . ' ' . rupiah($v); } echo implode(', ', $t); ?>.
      Nilai bisa disesuaikan manual.</p>

    <label>Catatan Denda (opsional)</label>
    <input type="text" name="catatan_denda" maxlength="150" placeholder="mis. Lensa kamera retak">

    <p style="margin-top:14px"><strong>Total Denda: <span id="total_denda"><?= rupiah($dendaTelatOtomatis + $dendaRusakOtomatis) ?></span></strong></p>

    <div class="form-actions">
      <button type="submit" class="btn btn-success">Verifikasi &amp; Simpan</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<script>
const tarif = <?= json_encode(tarif_kerusakan()) ?>;
const jumlahAlat = <?= max(1, $totalAlat) ?>;
const kondisi = document.getElementById('kondisi_final');
const dTelat = document.getElementById('denda_telat');
const dRusak = document.getElementById('denda_kerusakan');
const total  = document.getElementById('total_denda');
function fmt(n){ return 'Rp ' + n.toLocaleString('id-ID'); }
function hitungTotal(){ total.textContent = fmt((parseInt(dTelat.value)||0) + (parseInt(dRusak.value)||0)); }
kondisi.addEventListener('change', () => { dRusak.value = (tarif[kondisi.value] || 0) * jumlahAlat; hitungTotal(); });
dTelat.addEventListener('input', hitungTotal);
dRusak.addEventListener('input', hitungTotal);
</script>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
