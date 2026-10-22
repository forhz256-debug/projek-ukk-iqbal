<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Peminjam']);

$idPeminjaman = (int)($_GET['id'] ?? $_POST['id_peminjaman'] ?? 0);
$pageTitle = 'Ajukan Pengembalian';
$active = 'pengembalian';

$stmt = $pdo->prepare("SELECT * FROM tb_peminjaman WHERE id_peminjaman=:id AND id_user=:u AND status_peminjaman='Dipinjam'");
$stmt->execute([':id' => $idPeminjaman, ':u' => current_user_id()]);
$peminjaman = $stmt->fetch();
if (!$peminjaman) { set_flash('error', 'Data peminjaman tidak valid.'); redirect('pengembalian/index.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cek = $pdo->prepare("SELECT COUNT(*) FROM tb_pengembalian WHERE id_peminjaman=:id");
    $cek->execute([':id' => $idPeminjaman]);
    if ($cek->fetchColumn() > 0) {
        set_flash('error', 'Pengembalian untuk peminjaman ini sudah pernah diajukan.');
        redirect('pengembalian/index.php');
    }
    $kondisi = trim($_POST['kondisi_alat_kembali'] ?? 'Baik');
    $ins = $pdo->prepare(
        "INSERT INTO tb_pengembalian (id_peminjaman, id_user, tanggal_kembali, kondisi_alat_kembali, status)
         VALUES (:p, :u, CURDATE(), :k, 'Menunggu')"
    );
    $ins->execute([':p' => $idPeminjaman, ':u' => current_user_id(), ':k' => $kondisi]);
    log_aktivitas($pdo, "Mengajukan pengembalian untuk peminjaman #$idPeminjaman");
    set_flash('success', 'Pengajuan pengembalian dikirim, menunggu verifikasi petugas.');
    redirect('pengembalian/index.php');
}

require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:480px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <p>Peminjaman #<?= (int)$idPeminjaman ?> &middot; rencana kembali <?= e($peminjaman['tanggal_rencana_kembali']) ?></p>
  <form method="post">
    <input type="hidden" name="id_peminjaman" value="<?= (int)$idPeminjaman ?>">
    <label>Kondisi Alat Saat Dikembalikan</label>
    <select name="kondisi_alat_kembali">
      <option>Baik</option>
      <option>Rusak Ringan</option>
      <option>Rusak Berat</option>
      <option>Hilang</option>
    </select>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Kirim Pengajuan Pengembalian</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
