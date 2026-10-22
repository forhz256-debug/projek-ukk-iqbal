<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/fungsi.php';
require_role(['Petugas', 'Peminjam']);

$pageTitle = 'Pengembalian Alat';
$active = 'pengembalian';
$role = current_role();
$filterDenda = $_GET['denda'] ?? '';
$riwayat = [];
$totalBelumBayar = 0;

if ($role === 'Peminjam') {
    $stmt = $pdo->prepare(
        "SELECT p.*, GROUP_CONCAT(CONCAT(a.nama_alat,' (',d.jumlah_pinjam,')') SEPARATOR ', ') AS daftar_alat,
                (SELECT COUNT(*) FROM tb_pengembalian x WHERE x.id_peminjaman = p.id_peminjaman) AS sudah_diajukan
         FROM tb_peminjaman p
         LEFT JOIN tb_detail_peminjaman d ON d.id_peminjaman = p.id_peminjaman
         LEFT JOIN tb_alat a ON a.id_alat = d.id_alat
         WHERE p.id_user = :u AND p.status_peminjaman = 'Dipinjam'
         GROUP BY p.id_peminjaman ORDER BY p.tanggal_rencana_kembali"
    );
    $stmt->execute([':u' => current_user_id()]);
    $rows = $stmt->fetchAll();

    // Riwayat pengembalian + denda milik peminjam ini
    $stmt = $pdo->prepare(
        "SELECT r.*, p.tanggal_rencana_kembali,
                GROUP_CONCAT(CONCAT(a.nama_alat,' (',d.jumlah_pinjam,')') SEPARATOR ', ') AS daftar_alat
         FROM tb_pengembalian r
         JOIN tb_peminjaman p ON p.id_peminjaman = r.id_peminjaman
         LEFT JOIN tb_detail_peminjaman d ON d.id_peminjaman = p.id_peminjaman
         LEFT JOIN tb_alat a ON a.id_alat = d.id_alat
         WHERE r.id_user = :u
         GROUP BY r.id_pengembalian ORDER BY r.tanggal_kembali DESC, r.id_pengembalian DESC"
    );
    $stmt->execute([':u' => current_user_id()]);
    $riwayat = $stmt->fetchAll();
    foreach ($riwayat as $h) {
        if ($h['status_denda'] === 'Belum Dibayar') $totalBelumBayar += (int)$h['denda'];
    }
} else {
    $sql = "SELECT r.*, u.nama_lengkap AS nama_peminjam, p.tanggal_rencana_kembali
            FROM tb_pengembalian r
            JOIN tb_peminjaman p ON p.id_peminjaman = r.id_peminjaman
            JOIN tb_user u ON u.id_user = r.id_user";
    $params = [];
    if (in_array($filterDenda, ['Belum Dibayar', 'Lunas', 'Tidak Ada'], true)) {
        $sql .= ' WHERE r.status_denda = :sd';
        $params[':sd'] = $filterDenda;
    }
    $sql .= ' ORDER BY r.status ASC, r.tanggal_kembali DESC, r.id_pengembalian DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $totalBelumBayar = (int)$pdo->query("SELECT COALESCE(SUM(denda),0) FROM tb_pengembalian WHERE status_denda='Belum Dibayar'")->fetchColumn();
}

require __DIR__ . '/../config/partial_header.php';
?>
<?php if ($role === 'Peminjam'): ?>
<?php if ($totalBelumBayar > 0): ?>
  <div class="alert alert-error">Anda memiliki denda yang belum dibayar sebesar <strong><?= rupiah($totalBelumBayar) ?></strong>. Silakan bayar ke petugas; peminjaman baru tidak bisa diajukan sebelum denda lunas.</div>
<?php endif; ?>
<div class="card">
  <h3 style="margin-top:0">Alat yang Sedang Dipinjam</h3>
  <table>
    <thead><tr><th>#</th><th>Alat Dipinjam</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="5">Tidak ada alat yang sedang dipinjam.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id_peminjaman'] ?></td>
          <td><?= e($r['daftar_alat']) ?></td>
          <td><?= e($r['tanggal_pinjam']) ?></td>
          <td><?= e($r['tanggal_rencana_kembali']) ?></td>
          <td>
            <?php if ((int)$r['sudah_diajukan'] > 0): ?>
              <span class="badge badge-diajukan">Menunggu diproses petugas</span>
            <?php else: ?>
              <a href="ajukan.php?id=<?= (int)$r['id_peminjaman'] ?>" class="btn btn-primary btn-sm">Kembalikan</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h3 style="margin-top:0">Riwayat Pengembalian &amp; Denda</h3>
  <table>
    <thead><tr><th>#</th><th>Alat</th><th>Tgl Kembali</th><th>Kondisi</th><th>Rincian Denda</th><th>Total</th><th>Status Denda</th></tr></thead>
    <tbody>
      <?php if (!$riwayat): ?><tr><td colspan="7">Belum ada riwayat pengembalian.</td></tr><?php endif; ?>
      <?php foreach ($riwayat as $h): ?>
        <tr>
          <td>#<?= (int)$h['id_peminjaman'] ?></td>
          <td><?= e($h['daftar_alat']) ?></td>
          <td><?= e($h['tanggal_kembali']) ?></td>
          <td><?= e($h['kondisi_alat_kembali']) ?></td>
          <td>
            <?php if ($h['status'] !== 'Selesai'): ?>
              <span class="hint">Menunggu verifikasi petugas</span>
            <?php elseif ((int)$h['denda'] === 0): ?>-
            <?php else: ?>
              Telat: <?= rupiah((int)$h['denda_telat']) ?><br>Kerusakan: <?= rupiah((int)$h['denda_kerusakan']) ?>
              <?php if (!empty($h['catatan_denda'])): ?><br><span class="hint"><?= e($h['catatan_denda']) ?></span><?php endif; ?>
            <?php endif; ?>
          </td>
          <td><?= $h['status'] === 'Selesai' ? rupiah((int)$h['denda']) : '-' ?></td>
          <td>
            <?php if ($h['status'] !== 'Selesai'): ?>
              <span class="badge badge-diajukan">Menunggu</span>
            <?php else: ?>
              <span class="badge <?= badge_denda($h['status_denda']) ?>"><?= e($h['status_denda']) ?></span>
              <?php if ($h['status_denda'] === 'Lunas' && $h['tanggal_bayar']): ?><br><span class="hint"><?= e($h['tanggal_bayar']) ?></span><?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php else: ?>
<div class="toolbar">
  <form method="get" class="search-box">
    <select name="denda" onchange="this.form.submit()">
      <option value="">Semua Status Denda</option>
      <?php foreach (['Belum Dibayar', 'Lunas', 'Tidak Ada'] as $s): ?>
        <option value="<?= e($s) ?>" <?= $filterDenda === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <div>Total denda belum dibayar: <strong><?= rupiah($totalBelumBayar) ?></strong></div>
</div>
<div class="card">
  <table>
    <thead><tr><th>#</th><th>Peminjam</th><th>Tgl Kembali</th><th>Kondisi</th><th>Denda Telat</th><th>Denda Rusak</th><th>Total</th><th>Verifikasi</th><th>Status Denda</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="10">Belum ada data pengembalian.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id_peminjaman'] ?></td>
          <td><?= e($r['nama_peminjam']) ?></td>
          <td><?= e($r['tanggal_kembali']) ?></td>
          <td><?= e($r['kondisi_alat_kembali']) ?></td>
          <td><?= rupiah((int)$r['denda_telat']) ?></td>
          <td><?= rupiah((int)$r['denda_kerusakan']) ?></td>
          <td><strong><?= rupiah((int)$r['denda']) ?></strong></td>
          <td><span class="badge <?= $r['status'] === 'Selesai' ? 'badge-selesai' : 'badge-diajukan' ?>"><?= e($r['status']) ?></span></td>
          <td>
            <?php if ($r['status'] === 'Selesai'): ?>
              <span class="badge <?= badge_denda($r['status_denda']) ?>"><?= e($r['status_denda']) ?></span>
            <?php else: ?>-<?php endif; ?>
          </td>
          <td>
            <?php if ($r['status'] === 'Menunggu'): ?>
              <a href="proses.php?id=<?= (int)$r['id_pengembalian'] ?>" class="btn btn-success btn-sm">Verifikasi</a>
            <?php elseif ($r['status_denda'] === 'Belum Dibayar'): ?>
              <a href="bayar.php?id=<?= (int)$r['id_pengembalian'] ?>" class="btn btn-primary btn-sm">Konfirmasi Bayar</a>
            <?php elseif ($r['status_denda'] === 'Lunas'): ?>
              <a href="bayar.php?id=<?= (int)$r['id_pengembalian'] ?>" class="btn btn-muted btn-sm">Detail</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../config/partial_footer.php'; ?>
