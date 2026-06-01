<?php
function kirim_notifikasi($conn, $id_user, $judul, $pesan, $tipe = 'info', $link = null)
{
    $id_user = intval($id_user);
    $judul = mysqli_real_escape_string($conn, $judul);
    $pesan = mysqli_real_escape_string($conn, $pesan);
    $tipe = mysqli_real_escape_string($conn, $tipe);
    $link = mysqli_real_escape_string($conn, $link);

    $query = "
        INSERT INTO notifikasi 
        (id_user, judul, pesan, tipe, link)
        VALUES 
        ('$id_user', '$judul', '$pesan', '$tipe', '$link')
    ";

    return mysqli_query($conn, $query);
}

function jumlah_notifikasi_belum_dibaca($conn, $id_user)
{
    $id_user = intval($id_user);

    $query = mysqli_query($conn, "
        SELECT COUNT(*) AS total 
        FROM notifikasi 
        WHERE id_user = '$id_user' 
        AND status_baca = 'belum'
    ");

    if (!$query) {
        return 0;
    }

    $data = mysqli_fetch_assoc($query);
    return (int) ($data['total'] ?? 0);
}

function tandai_notifikasi_dibaca($conn, $id_notifikasi, $id_user = null, $paksa = false)
{
    $id_notifikasi = intval($id_notifikasi);

    if ($id_notifikasi <= 0) {
        return false;
    }

    $where_user = "";
    if (!$paksa) {
        $id_user = intval($id_user);
        if ($id_user <= 0) {
            return false;
        }
        $where_user = " AND id_user = '$id_user'";
    }

    return mysqli_query($conn, "
        UPDATE notifikasi
        SET status_baca = 'sudah',
            dibaca_pada = COALESCE(dibaca_pada, NOW())
        WHERE id_notifikasi = '$id_notifikasi'
        $where_user
        AND status_baca = 'belum'
    ");
}

function tandai_semua_notifikasi_dibaca($conn, $id_user = null, $paksa = false)
{
    $where_user = "";
    if (!$paksa) {
        $id_user = intval($id_user);
        if ($id_user <= 0) {
            return false;
        }
        $where_user = " AND id_user = '$id_user'";
    }

    return mysqli_query($conn, "
        UPDATE notifikasi
        SET status_baca = 'sudah',
            dibaca_pada = COALESCE(dibaca_pada, NOW())
        WHERE status_baca = 'belum'
        $where_user
    ");
}
?>
