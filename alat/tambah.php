<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

$pageTitle = 'Tambah Alat';
$active = 'alat';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_alat'] ?? '');
    $idKategori = (int)($_POST['id_kategori'] ?? 0);
    $stok = (int)($_POST['jumlah_stok'] ?? 0);
    $kondisi = trim($_POST['kondisi'] ?? 'Baik');

    if ($nama === '' || $idKategori <= 0 || $stok < 0) {
        $error = 'Lengkapi semua field dengan benar.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO tb_alat (id_kategori, nama_alat, jumlah_stok, kondisi) VALUES (:kat, :nama, :stok, :kondisi)');
        $stmt->execute([':kat' => $idKategori, ':nama' => $nama, ':stok' => $stok, ':kondisi' => $kondisi]);
        log_aktivitas($pdo, "Menambah alat: $nama");
        set_flash('success', 'Alat berhasil ditambahkan.');
        redirect('alat/index.php');
    }
}

$kategoriList = $pdo->query('SELECT id_kategori, nama_kategori FROM tb_kategori ORDER BY nama_kategori')->fetchAll();
require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:480px">
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Nama Alat</label>
    <input type="text" name="nama_alat" required value="<?= e($_POST['nama_alat'] ?? '') ?>">

    <label>Kategori</label>
    <select name="id_kategori" required>
      <option value="">-- Pilih Kategori --</option>
      <?php foreach ($kategoriList as $k): ?>
        <option value="<?= (int)$k['id_kategori'] ?>"><?= e($k['nama_kategori']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Jumlah Stok</label>
    <input type="number" name="jumlah_stok" min="0" required value="<?= e($_POST['jumlah_stok'] ?? '0') ?>">

    <label>Kondisi</label>
    <select name="kondisi">
      <option>Baik</option>
      <option>Rusak Ringan</option>
      <option>Rusak Berat</option>
    </select>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Simpan</button>
      <a href="index.php" class="btn btn-muted">Batal</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
