<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Tambah Kategori';
$active = 'kategori';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_kategori'] ?? '');
    $lokasi = trim($_POST['lokasi_penyimpanan'] ?? '');
    $pj = trim($_POST['penanggung_jawab'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');

    if ($nama === '' || $lokasi === '' || $pj === '') {
        $error = 'Lengkapi semua field wajib.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO tb_kategori (nama_kategori, lokasi_penyimpanan, penanggung_jawab, keterangan) VALUES (:n,:l,:p,:k)');
        $stmt->execute([':n' => $nama, ':l' => $lokasi, ':p' => $pj, ':k' => $ket]);
        log_aktivitas($pdo, "Menambah kategori: $nama");
        set_flash('success', 'Kategori berhasil ditambahkan.');
        redirect('kategori/index.php');
    }
}
require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:480px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Nama Kategori</label>
    <input type="text" name="nama_kategori" required value="<?= e($_POST['nama_kategori'] ?? '') ?>">
    <label>Lokasi Penyimpanan</label>
    <input type="text" name="lokasi_penyimpanan" required value="<?= e($_POST['lokasi_penyimpanan'] ?? '') ?>">
    <label>Penanggung Jawab</label>
    <input type="text" name="penanggung_jawab" required value="<?= e($_POST['penanggung_jawab'] ?? '') ?>">
    <label>Keterangan</label>
    <textarea name="keterangan" rows="3"><?= e($_POST['keterangan'] ?? '') ?></textarea>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
