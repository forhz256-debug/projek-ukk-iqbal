<?php

function set_flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function show_flash(): void
{
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    $class = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
    echo '<div class="alert ' . $class . '">' . htmlspecialchars($f['msg']) . '</div>';
    unset($_SESSION['flash']);
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}


function hitung_denda(string $tanggalRencana, string $tanggalKembali, int $jumlahAlat, int $tarifPerHari = 2000): int
{
    $rencana = new DateTime($tanggalRencana);
    $aktual  = new DateTime($tanggalKembali);
    if ($aktual <= $rencana) {
        return 0;
    }
    $telat = (int)$rencana->diff($aktual)->days;
    return $telat * $tarifPerHari * max(1, $jumlahAlat);
}

function e(?string $val): string
{
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}


/* ==================== DENDA (telat + kerusakan) ==================== */

/** Tarif denda keterlambatan per hari per alat (Rp) */
const TARIF_TELAT_PER_HARI = 2000;

/** Tarif denda kerusakan / kehilangan per unit alat (Rp). Ubah di sini bila kebijakan berubah. */
function tarif_kerusakan(): array
{
    return [
        'Baik'         => 0,
        'Rusak Ringan' => 10000,
        'Rusak Berat'  => 50000,
        'Hilang'       => 100000,
    ];
}

function hitung_denda_kerusakan(string $kondisi, int $jumlahAlat): int
{
    $tarif = tarif_kerusakan();
    return ($tarif[$kondisi] ?? 0) * max(1, $jumlahAlat);
}

function rupiah(int $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/** Kelas CSS badge untuk kolom status_denda */
function badge_denda(string $status): string
{
    if ($status === 'Lunas') return 'badge-selesai';
    if ($status === 'Belum Dibayar') return 'badge-ditolak';
    return 'badge-none';
}
