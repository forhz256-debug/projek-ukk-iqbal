<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Petugas']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('peminjaman/index.php');
$id = (int)($_POST['id_peminjaman'] ?? 0);


$stmt = $pdo->prepare("DELETE FROM tb_peminjaman WHERE id_peminjaman=:id AND status_peminjaman='Diajukan'");
$stmt->execute([':id' => $id]);

if ($stmt->rowCount() > 0) {
    log_aktivitas($pdo, "Membatalkan pengajuan peminjaman #$id");
    set_flash('success', 'Pengajuan berhasil dibatalkan.');
} else {
    set_flash('error', 'Pengajuan tidak bisa dibatalkan (sudah diproses).');
}
redirect('peminjaman/index.php');
