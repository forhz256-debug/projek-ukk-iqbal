<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Edit Kategori';
$active = 'kategori';
$id = (int)($_GET['id'] ?? $_POST['id_kategori'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM tb_kategori WHERE id_kategori = :id');
$stmt->execute([':id' => $id]);
$kat = $stmt->fetch();
if (!$kat) { set_flash('error', 'Kategori tidak ditemukan.'); redirect('kategori/index.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_kategori'] ?? '');
    $lokasi = trim($_POST['lokasi_penyimpanan'] ?? '');
    $pj = trim($_POST['penanggung_jawab'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');

    if ($nama === '' || $lokasi === '' || $pj === '') {
        $error = 'Lengkapi semua field wajib.';
    } else {
        $upd = $pdo->prepare('UPDATE tb_kategori SET nama_kategori=:n, lokasi_penyimpanan=:l, penanggung_jawab=:p, keterangan=:k WHERE id_kategori=:id');
        $upd->execute([':n' => $nama, ':l' => $lokasi, ':p' => $pj, ':k' => $ket, ':id' => $id]);
        log_aktivitas($pdo, "Mengubah kategori: $nama");
        set_flash('success', 'Kategori berhasil diperbarui.');
        redirect('kategori/index.php');
    }
    $kat = array_merge($kat, $_POST);
}
require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:480px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="id_kategori" value="<?= (int)$id ?>">
    <label>Nama Kategori</label>
    <input type="text" name="nama_kategori" required value="<?= e($kat['nama_kategori']) ?>">
    <label>Lokasi Penyimpanan</label>
    <input type="text" name="lokasi_penyimpanan" required value="<?= e($kat['lokasi_penyimpanan']) ?>">
    <label>Penanggung Jawab</label>
    <input type="text" name="penanggung_jawab" required value="<?= e($kat['penanggung_jawab']) ?>">
    <label>Keterangan</label>
    <textarea name="keterangan" rows="3"><?= e($kat['keterangan']) ?></textarea>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
