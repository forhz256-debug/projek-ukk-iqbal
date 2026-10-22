<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Edit Alat';
$active = 'alat';
$id = (int)($_GET['id'] ?? $_POST['id_alat'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM tb_alat WHERE id_alat = :id');
$stmt->execute([':id' => $id]);
$alat = $stmt->fetch();
if (!$alat) { set_flash('error', 'Alat tidak ditemukan.'); redirect('alat/index.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_alat'] ?? '');
    $idKategori = (int)($_POST['id_kategori'] ?? 0);
    $stok = (int)($_POST['jumlah_stok'] ?? 0);
    $kondisi = trim($_POST['kondisi'] ?? 'Baik');

    if ($nama === '' || $idKategori <= 0 || $stok < 0) {
        $error = 'Lengkapi semua field dengan benar.';
    } else {
        $upd = $pdo->prepare('UPDATE tb_alat SET id_kategori=:kat, nama_alat=:nama, jumlah_stok=:stok, kondisi=:kondisi WHERE id_alat=:id');
        $upd->execute([':kat' => $idKategori, ':nama' => $nama, ':stok' => $stok, ':kondisi' => $kondisi, ':id' => $id]);
        log_aktivitas($pdo, "Mengubah alat: $nama");
        set_flash('success', 'Alat berhasil diperbarui.');
        redirect('alat/index.php');
    }
    $alat = array_merge($alat, $_POST);
}

$kategoriList = $pdo->query('SELECT id_kategori, nama_kategori FROM tb_kategori ORDER BY nama_kategori')->fetchAll();
require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:480px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="id_alat" value="<?= (int)$id ?>">
    <label>Nama Alat</label>
    <input type="text" name="nama_alat" required value="<?= e($alat['nama_alat']) ?>">

    <label>Kategori</label>
    <select name="id_kategori" required>
      <?php foreach ($kategoriList as $k): ?>
        <option value="<?= (int)$k['id_kategori'] ?>" <?= $k['id_kategori'] == $alat['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Jumlah Stok</label>
    <input type="number" name="jumlah_stok" min="0" required value="<?= e((string)$alat['jumlah_stok']) ?>">

    <label>Kondisi</label>
    <select name="kondisi">
      <?php foreach (['Baik', 'Rusak Ringan', 'Rusak Berat'] as $opt): ?>
        <option <?= $alat['kondisi'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
