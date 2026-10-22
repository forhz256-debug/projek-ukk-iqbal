<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('alat/index.php');

$id = (int)($_POST['id_alat'] ?? 0);
try {
    $stmt = $pdo->prepare('DELETE FROM tb_alat WHERE id_alat = :id');
    $stmt->execute([':id' => $id]);
    log_aktivitas($pdo, "Menghapus alat id #$id");
    set_flash('success', 'Alat berhasil dihapus.');
} catch (PDOException $e) {
    set_flash('error', 'Alat tidak bisa dihapus karena masih memiliki riwayat peminjaman.');
}
redirect('alat/index.php');
