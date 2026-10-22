<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('kategori/index.php');
$id = (int)($_POST['id_kategori'] ?? 0);
try {
    $stmt = $pdo->prepare('DELETE FROM tb_kategori WHERE id_kategori = :id');
    $stmt->execute([':id' => $id]);
    log_aktivitas($pdo, "Menghapus kategori id #$id");
    set_flash('success', 'Kategori berhasil dihapus.');
} catch (PDOException $e) {
    set_flash('error', 'Kategori tidak bisa dihapus karena masih dipakai oleh data alat.');
}
redirect('kategori/index.php');
