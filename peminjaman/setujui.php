<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Petugas']);

$pageTitle = 'Proses Persetujuan Peminjaman';
$active = 'peminjaman';
$id = (int)($_GET['id'] ?? $_POST['id_peminjaman'] ?? 0);

$stmt = $pdo->prepare('SELECT p.*, u.nama_lengkap FROM tb_peminjaman p JOIN tb_user u ON u.id_user=p.id_user WHERE p.id_peminjaman=:id');
$stmt->execute([':id' => $id]);
$peminjaman = $stmt->fetch();
if (!$peminjaman || $peminjaman['status_peminjaman'] !== 'Diajukan') {
    set_flash('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
    redirect('peminjaman/index.php');
}

$itemStmt = $pdo->prepare(
    'SELECT d.*, a.nama_alat, a.jumlah_stok
     FROM tb_detail_peminjaman d JOIN tb_alat a ON a.id_alat=d.id_alat
     WHERE d.id_peminjaman = :id'
);
$itemStmt->execute([':id' => $id]);
$items = $itemStmt->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $catatan = trim($_POST['catatan_petugas'] ?? '');

    if ($aksi === 'tolak') {
        $upd = $pdo->prepare("UPDATE tb_peminjaman SET status_peminjaman='Ditolak', id_petugas=:pt, catatan_petugas=:c WHERE id_peminjaman=:id");
        $upd->execute([':pt' => current_user_id(), ':c' => $catatan, ':id' => $id]);
        log_aktivitas($pdo, "Menolak peminjaman #$id");
        set_flash('success', 'Pengajuan ditolak.');
        redirect('peminjaman/index.php');
    } elseif ($aksi === 'setuju') {
        try {
            $pdo->beginTransaction();
            // Cek stok tiap alat dulu sebelum mengurangi apa pun -> hindari stok jadi minus di tengah transaksi
            $cekStok = $pdo->prepare('SELECT jumlah_stok, nama_alat FROM tb_alat WHERE id_alat=:id FOR UPDATE');
            foreach ($items as $it) {
                $cekStok->execute([':id' => $it['id_alat']]);
                $row = $cekStok->fetch();
                if (!$row || $row['jumlah_stok'] < $it['jumlah_pinjam']) {
                    throw new RuntimeException("Stok {$it['nama_alat']} tidak mencukupi.");
                }
            }
            $kurangi = $pdo->prepare('UPDATE tb_alat SET jumlah_stok = jumlah_stok - :j WHERE id_alat = :id');
            foreach ($items as $it) {
                $kurangi->execute([':j' => $it['jumlah_pinjam'], ':id' => $it['id_alat']]);
            }
            $upd = $pdo->prepare("UPDATE tb_peminjaman SET status_peminjaman='Dipinjam', id_petugas=:pt, catatan_petugas=:c WHERE id_peminjaman=:id");
            $upd->execute([':pt' => current_user_id(), ':c' => $catatan, ':id' => $id]);

            $pdo->commit();
            log_aktivitas($pdo, "Menyetujui peminjaman #$id");
            set_flash('success', 'Pengajuan disetujui, stok alat telah dikurangi.');
            redirect('peminjaman/index.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:600px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <p><strong>Peminjam:</strong> <?= e($peminjaman['nama_lengkap']) ?><br>
     <strong>Tanggal:</strong> <?= e($peminjaman['tanggal_pinjam']) ?> s/d <?= e($peminjaman['tanggal_rencana_kembali']) ?></p>
  <table>
    <thead><tr><th>Alat</th><th>Jumlah Diminta</th><th>Stok Tersedia</th><th>Keperluan</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e($it['nama_alat']) ?></td>
          <td><?= (int)$it['jumlah_pinjam'] ?></td>
          <td><?= (int)$it['jumlah_stok'] ?></td>
          <td><?= e($it['keterangan_keperluan']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <form method="post" style="margin-top:16px">
    <input type="hidden" name="id_peminjaman" value="<?= (int)$id ?>">
    <label>Catatan (opsional)</label>
    <input type="text" name="catatan_petugas" placeholder="Catatan untuk peminjam">
    <div class="form-actions">
      <button type="submit" name="aksi" value="setuju" class="btn btn-success">Setujui &amp; Kurangi Stok</button>
      <button type="submit" name="aksi" value="tolak" class="btn btn-danger">Tolak</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
