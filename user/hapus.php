<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('user/index.php');
$id = (int)($_POST['id_user'] ?? 0);

if ($id === current_user_id()) {
    set_flash('error', 'Tidak bisa menghapus akun yang sedang login.');
    redirect('user/index.php');
}
try {
    $stmt = $pdo->prepare('DELETE FROM tb_user WHERE id_user = :id');
    $stmt->execute([':id' => $id]);
    log_aktivitas($pdo, "Menghapus user id #$id");
    set_flash('success', 'User berhasil dihapus.');
} catch (PDOException $e) {
    set_flash('error', 'User tidak bisa dihapus karena masih memiliki riwayat peminjaman.');
}
redirect('user/index.php');
