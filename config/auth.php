<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function require_login(): void
{
    if (empty($_SESSION['id_user'])) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}


function require_role(array $allowedRoles): void
{
    require_login();
    if (!in_array($_SESSION['level_user'], $allowedRoles, true)) {
        http_response_code(403);
        echo '<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center">
                <h2>Akses ditolak</h2>
                <p>Akun anda (' . htmlspecialchars($_SESSION['level_user']) . ') tidak memiliki izin untuk membuka halaman ini.</p>
                <a href="' . base_url('dashboard.php') . '">Kembali ke Dashboard</a>
              </div>';
        exit;
    }
}


function base_url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function current_role(): string
{
    return $_SESSION['level_user'] ?? '';
}

function current_user_id(): int
{
    return (int)($_SESSION['id_user'] ?? 0);
}

/** Catat log aktivitas (dipakai fitur "Log Aktifitas" milik Admin) */
function log_aktivitas(PDO $pdo, string $aktivitas): void
{
    if (empty($_SESSION['id_user'])) return;
    $stmt = $pdo->prepare(
        "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu) VALUES (:id_user, :aktivitas, NOW())"
    );
    $stmt->execute([
        ':id_user'    => $_SESSION['id_user'],
        ':aktivitas'  => $aktivitas,
    ]);
}
