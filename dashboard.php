<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/fungsi.php';
require_login();

$role = current_role();
$pageTitle = 'Dashboard';
$active = 'dashboard';

$stats = [];

if ($role === 'Admin') {
    $stats['Total User']    = $pdo->query('SELECT COUNT(*) FROM tb_user')->fetchColumn();
    $stats['Total Alat']    = $pdo->query('SELECT COALESCE(SUM(jumlah_stok),0) FROM tb_alat')->fetchColumn();
    $stats['Total Kategori']= $pdo->query('SELECT COUNT(*) FROM tb_kategori')->fetchColumn();
    $stats['Peminjaman Aktif'] = $pdo->query("SELECT COUNT(*) FROM tb_peminjaman WHERE status_peminjaman='Dipinjam'")->fetchColumn();
    $stats['Denda Belum Dibayar'] = rupiah((int)$pdo->query("SELECT COALESCE(SUM(denda),0) FROM tb_pengembalian WHERE status_denda='Belum Dibayar'")->fetchColumn());
} elseif ($role === 'Petugas') {
    $stats['Menunggu Persetujuan'] = $pdo->query("SELECT COUNT(*) FROM tb_peminjaman WHERE status_peminjaman='Diajukan'")->fetchColumn();
    $stats['Sedang Dipinjam']      = $pdo->query("SELECT COUNT(*) FROM tb_peminjaman WHERE status_peminjaman='Dipinjam'")->fetchColumn();
    $stats['Menunggu Diproses Kembali'] = $pdo->query("SELECT COUNT(*) FROM tb_pengembalian WHERE status='Menunggu'")->fetchColumn();
    $stats['Denda Belum Dibayar'] = rupiah((int)$pdo->query("SELECT COALESCE(SUM(denda),0) FROM tb_pengembalian WHERE status_denda='Belum Dibayar'")->fetchColumn());
    $stats['Selesai Bulan Ini'] = $pdo->query("SELECT COUNT(*) FROM tb_peminjaman WHERE status_peminjaman='Selesai' AND MONTH(tanggal_pinjam)=MONTH(CURDATE())")->fetchColumn();
} else { // Peminjam
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_peminjaman WHERE id_user=:u AND status_peminjaman='Diajukan'");
    $stmt->execute([':u' => current_user_id()]);
    $stats['Pengajuan Menunggu'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_peminjaman WHERE id_user=:u AND status_peminjaman='Dipinjam'");
    $stmt->execute([':u' => current_user_id()]);
    $stats['Sedang Dipinjam'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_peminjaman WHERE id_user=:u AND status_peminjaman='Selesai'");
    $stmt->execute([':u' => current_user_id()]);
    $stats['Riwayat Selesai'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(denda),0) FROM tb_pengembalian WHERE id_user=:u AND status_denda='Belum Dibayar'");
    $stmt->execute([':u' => current_user_id()]);
    $stats['Denda Belum Dibayar'] = rupiah((int)$stmt->fetchColumn());
}

require __DIR__ . '/config/partial_header.php';
?>
<div class="stat-grid">
  <?php foreach ($stats as $label => $num): ?>
    <div class="stat">
      <div class="num"><?= e((string)$num) ?></div>
      <div class="label"><?= e($label) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <?php if ($role === 'Admin'): ?>
    <p>Selamat datang, Admin. Anda dapat mengelola <strong>User</strong>, <strong>Alat</strong>, <strong>Kategori</strong>, memantau <strong>Log Aktifitas</strong>, dan mencetak <strong>Laporan</strong> peminjaman.</p>
  <?php elseif ($role === 'Petugas'): ?>
    <p>Selamat datang, Petugas. Anda dapat mengelola data <strong>Peminjaman</strong> (termasuk menyetujui/menolak pengajuan) dan memantau proses <strong>Pengembalian</strong> alat.</p>
  <?php else: ?>
    <p>Selamat datang. Anda dapat melihat daftar alat, mengajukan peminjaman, dan mengembalikan alat yang sedang anda pinjam.</p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/config/partial_footer.php'; ?>
