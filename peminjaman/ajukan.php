<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Peminjam']);

$pageTitle = 'Ajukan Peminjaman';
$active = 'peminjaman';
$error = '';

// Peminjam yang masih punya denda belum dibayar tidak boleh mengajukan peminjaman baru
$cekDenda = $pdo->prepare("SELECT COALESCE(SUM(denda),0) FROM tb_pengembalian WHERE id_user=:u AND status_denda='Belum Dibayar'");
$cekDenda->execute([':u' => current_user_id()]);
$tunggakan = (int)$cekDenda->fetchColumn();
if ($tunggakan > 0) {
    set_flash('error', 'Anda masih memiliki denda belum dibayar sebesar ' . rupiah($tunggakan) . '. Lunasi dulu ke petugas sebelum meminjam lagi.');
    redirect('pengembalian/index.php');
}

$alatList = $pdo->query('SELECT id_alat, nama_alat, jumlah_stok FROM tb_alat WHERE jumlah_stok > 0 ORDER BY nama_alat')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tglPinjam = $_POST['tanggal_pinjam'] ?? '';
    $tglKembali = $_POST['tanggal_rencana_kembali'] ?? '';
    $idAlatArr = $_POST['id_alat'] ?? [];
    $jumlahArr = $_POST['jumlah_pinjam'] ?? [];
    $keperluanArr = $_POST['keterangan_keperluan'] ?? [];

    if ($tglPinjam === '' || $tglKembali === '' || $tglKembali < $tglPinjam) {
        $error = 'Tanggal pinjam / rencana kembali tidak valid.';
    } elseif (empty($idAlatArr)) {
        $error = 'Tambahkan minimal satu alat yang ingin dipinjam.';
    } else {
        try {
            $pdo->beginTransaction();

            $ins = $pdo->prepare(
                'INSERT INTO tb_peminjaman (id_user, tanggal_pinjam, tanggal_rencana_kembali, status_peminjaman)
                 VALUES (:u, :tp, :tk, "Diajukan")'
            );
            $ins->execute([':u' => current_user_id(), ':tp' => $tglPinjam, ':tk' => $tglKembali]);
            $idPeminjaman = (int)$pdo->lastInsertId();

            $insDetail = $pdo->prepare(
                'INSERT INTO tb_detail_peminjaman (id_peminjaman, id_alat, jumlah_pinjam, keterangan_keperluan)
                 VALUES (:p, :a, :j, :k)'
            );
            $adaItem = false;
            foreach ($idAlatArr as $i => $idAlat) {
                $idAlat = (int)$idAlat;
                $jumlah = (int)($jumlahArr[$i] ?? 0);
                $ket = trim($keperluanArr[$i] ?? '');
                if ($idAlat <= 0 || $jumlah <= 0) continue;
                $adaItem = true;
                $insDetail->execute([':p' => $idPeminjaman, ':a' => $idAlat, ':j' => $jumlah, ':k' => $ket ?: '-']);
            }

            if (!$adaItem) {
                throw new RuntimeException('Tidak ada alat yang valid dipilih.');
            }

            $pdo->commit();
            log_aktivitas($pdo, "Mengajukan peminjaman #$idPeminjaman");
            set_flash('success', 'Pengajuan peminjaman berhasil dikirim, menunggu persetujuan petugas.');
            redirect('peminjaman/index.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan pengajuan: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:720px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" id="formAjukan">
    <label>Tanggal Pinjam</label>
    <input type="date" name="tanggal_pinjam" required value="<?= e($_POST['tanggal_pinjam'] ?? date('Y-m-d')) ?>">
    <label>Rencana Tanggal Kembali</label>
    <input type="date" name="tanggal_rencana_kembali" required value="<?= e($_POST['tanggal_rencana_kembali'] ?? '') ?>">

    <label style="margin-top:18px">Daftar Alat yang Dipinjam</label>
    <table id="tabelItem">
      <thead><tr><th>Alat</th><th style="width:90px">Jumlah</th><th>Keperluan</th><th></th></tr></thead>
      <tbody>
        <tr>
          <td>
            <select name="id_alat[]" required>
              <option value="">-- pilih --</option>
              <?php foreach ($alatList as $a): ?>
                <option value="<?= (int)$a['id_alat'] ?>">
                  <?= e($a['nama_alat']) ?> (stok <?= (int)$a['jumlah_stok'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="number" name="jumlah_pinjam[]" min="1" value="1" required></td>
          <td><input type="text" name="keterangan_keperluan[]" placeholder="mis. Praktikum Jaringan"></td>
          <td><button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this)">x</button></td>
        </tr>
      </tbody>
    </table>
    <button type="button" class="btn btn-muted btn-sm" style="margin-top:8px" onclick="tambahBaris()">+ Tambah Baris Alat</button>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<script>
function tambahBaris(){
  const tbody = document.querySelector('#tabelItem tbody');
  const baris = tbody.rows[0].cloneNode(true);
  baris.querySelectorAll('input').forEach(i => i.value = i.type === 'number' ? 1 : '');
  baris.querySelector('select').selectedIndex = 0;
  tbody.appendChild(baris);
}
function hapusBaris(btn){
  const tbody = document.querySelector('#tabelItem tbody');
  if (tbody.rows.length > 1) btn.closest('tr').remove();
}
</script>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
