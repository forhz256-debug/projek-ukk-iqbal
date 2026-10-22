<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Petugas']);

$pageTitle = 'Konfirmasi Pembayaran Denda';
$active = 'pengembalian';
$id = (int)($_GET['id'] ?? $_POST['id_pengembalian'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT r.*, p.tanggal_rencana_kembali, u.nama_lengkap, pb.nama_lengkap AS nama_petugas_bayar
     FROM tb_pengembalian r
     JOIN tb_peminjaman p ON p.id_peminjaman = r.id_peminjaman
     JOIN tb_user u ON u.id_user = r.id_user
     LEFT JOIN tb_user pb ON pb.id_user = r.id_petugas_bayar
     WHERE r.id_pengembalian = :id AND r.status = 'Selesai' AND r.denda > 0"
);
$stmt->execute([':id' => $id]);
$rj = $stmt->fetch();
if (!$rj) { set_flash('error', 'Data denda tidak ditemukan.'); redirect('pengembalian/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'lunas' && $rj['status_denda'] === 'Belum Dibayar') {
        $upd = $pdo->prepare(
            "UPDATE tb_pengembalian SET status_denda='Lunas', tanggal_bayar=NOW(), id_petugas_bayar=:pt
             WHERE id_pengembalian=:id AND status_denda='Belum Dibayar'"
        );
        $upd->execute([':pt' => current_user_id(), ':id' => $id]);
        log_aktivitas($pdo, "Konfirmasi denda LUNAS " . rupiah((int)$rj['denda']) . " peminjaman #{$rj['id_peminjaman']} ({$rj['nama_lengkap']})");
        set_flash('success', 'Denda ' . rupiah((int)$rj['denda']) . ' ditandai sudah dibayar.');
    } elseif ($aksi === 'batal' && $rj['status_denda'] === 'Lunas') {
        $upd = $pdo->prepare(
            "UPDATE tb_pengembalian SET status_denda='Belum Dibayar', tanggal_bayar=NULL, id_petugas_bayar=NULL
             WHERE id_pengembalian=:id AND status_denda='Lunas'"
        );
        $upd->execute([':id' => $id]);
        log_aktivitas($pdo, "Membatalkan konfirmasi lunas denda peminjaman #{$rj['id_peminjaman']} ({$rj['nama_lengkap']})");
        set_flash('success', 'Status denda dikembalikan menjadi belum dibayar.');
    } else {
        set_flash('error', 'Aksi tidak valid untuk status denda saat ini.');
    }
    redirect('pengembalian/index.php');
}

require __DIR__ . '/../config/partial_header.php';
?>
<div class="card" style="max-width:560px">
  <p><strong>Peminjam:</strong> <?= e($rj['nama_lengkap']) ?> &middot; Peminjaman #<?= (int)$rj['id_peminjaman'] ?></p>
  <p><strong>Kondisi Alat:</strong> <?= e($rj['kondisi_alat_kembali']) ?> &middot;
     <strong>Status Denda:</strong> <span class="badge <?= badge_denda($rj['status_denda']) ?>"><?= e($rj['status_denda']) ?></span></p>

  <table class="rincian">
    <tr><td>Denda keterlambatan</td><td style="text-align:right"><?= rupiah((int)$rj['denda_telat']) ?></td></tr>
    <tr><td>Denda kerusakan / kehilangan</td><td style="text-align:right"><?= rupiah((int)$rj['denda_kerusakan']) ?></td></tr>
    <tr class="total"><td>Total denda</td><td style="text-align:right"><?= rupiah((int)$rj['denda']) ?></td></tr>
  </table>
  <?php if (!empty($rj['catatan_denda'])): ?><p class="hint">Catatan: <?= e($rj['catatan_denda']) ?></p><?php endif; ?>

  <?php if ($rj['status_denda'] === 'Lunas'): ?>
    <p class="hint">Dibayar pada <?= e($rj['tanggal_bayar']) ?><?= $rj['nama_petugas_bayar'] ? ', dikonfirmasi oleh ' . e($rj['nama_petugas_bayar']) : '' ?>.</p>
  <?php endif; ?>

  <form method="post" style="margin-top:16px">
    <input type="hidden" name="id_pengembalian" value="<?= (int)$id ?>">
    <div class="form-actions">
      <?php if ($rj['status_denda'] === 'Belum Dibayar'): ?>
        <button type="submit" name="aksi" value="lunas" class="btn btn-success"
                onclick="return confirm('Pastikan uang denda sudah diterima. Tandai LUNAS?')">Konfirmasi Sudah Dibayar</button>
      <?php else: ?>
        <button type="submit" name="aksi" value="batal" class="btn btn-danger"
                onclick="return confirm('Kembalikan status menjadi belum dibayar?')">Tandai Belum Dibayar</button>
      <?php endif; ?>
      <a href="index.php" class="btn btn-muted">Kembali</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
