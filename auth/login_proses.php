<?php
require_once "../config/database.php";
require_once "../includes/session.php";
require_once "../includes/log_aktivitas.php";

function simpan_login_log($conn, $id_user, $username, $status_login)
{
    $id_user_sql = $id_user ? intval($id_user) : "NULL";
    $username = mysqli_real_escape_string($conn, $username);
    $status_login = mysqli_real_escape_string($conn, $status_login);
    $ip_address = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
    $user_agent = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT'] ?? '');

    mysqli_query($conn, "
        INSERT INTO login_log 
        (id_user, username, status_login, ip_address, user_agent)
        VALUES 
        ($id_user_sql, '$username', '$status_login', '$ip_address', '$user_agent')
    ");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = mysqli_prepare($conn, "
    SELECT users.*, roles.nama_role 
    FROM users 
    JOIN roles ON users.id_role = roles.id_role
    WHERE users.username = ?
    LIMIT 1
");

if (!$stmt) {
    error_log("Login query prepare failed: " . mysqli_error($conn));
    header("Location: login.php?error=database");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $username);

if (!mysqli_stmt_execute($stmt)) {
    error_log("Login query execute failed: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    header("Location: login.php?error=database");
    exit;
}

$query = mysqli_stmt_get_result($stmt);

if (!$query) {
    error_log("Login query result failed: " . mysqli_error($conn));
    mysqli_stmt_close($stmt);
    header("Location: login.php?error=database");
    exit;
}

if (mysqli_num_rows($query) === 1) {
    $user = mysqli_fetch_assoc($query);

    if ($user['status'] !== 'aktif') {
        simpan_login_log($conn, $user['id_user'], $username, 'gagal');

        header("Location: login.php?error=nonaktif");
        exit;
    }

    if (password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['nama_role'];

        mysqli_query($conn, "
            UPDATE users 
            SET last_login = NOW() 
            WHERE id_user = '{$user['id_user']}'
        ");

        simpan_login_log($conn, $user['id_user'], $username, 'berhasil');

        simpan_log(
            $conn,
            $user['id_user'],
            "Login ke sistem",
            "Autentikasi"
        );

        if ($user['nama_role'] == 'super_admin' || $user['nama_role'] == 'admin_akademik') {
            header("Location: ../admin/dashboard.php");
        } elseif ($user['nama_role'] == 'admin_keuangan') {
            header("Location: ../admin/dashboard.php");
        } elseif ($user['nama_role'] == 'mahasiswa') {
            header("Location: ../mahasiswa/dashboard.php");
        } elseif ($user['nama_role'] == 'dosen') {
            header("Location: ../dosen/dashboard.php");
        } elseif ($user['nama_role'] == 'kaprodi') {
            header("Location: ../kaprodi/dashboard.php");
        } elseif ($user['nama_role'] == 'pimpinan') {
            header("Location: ../pimpinan/dashboard.php");
        } else {
            simpan_log(
                $conn,
                $user['id_user'],
                "Login berhasil tetapi role tidak dikenali",
                "Autentikasi"
            );

            header("Location: login.php?error=role");
        }

        exit;
    } else {
        simpan_login_log($conn, $user['id_user'], $username, 'gagal');
    }
} else {
    simpan_login_log($conn, null, $username, 'gagal');
}

mysqli_stmt_close($stmt);

header("Location: login.php?error=1");
exit;
?>
