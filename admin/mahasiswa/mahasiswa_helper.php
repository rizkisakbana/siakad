<?php

function mahasiswa_count($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return 0;
    }

    $row = mysqli_fetch_assoc($q);
    return (int)($row['total'] ?? 0);
}

function mahasiswa_query_one($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q || mysqli_num_rows($q) < 1) {
        return null;
    }

    return mysqli_fetch_assoc($q);
}

function mahasiswa_fetch_all($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    $items = [];

    if (!$q) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($q)) {
        $items[] = $row;
    }

    return $items;
}

function mahasiswa_query_exists($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return null;
    }

    return mysqli_num_rows($q) > 0;
}

function mahasiswa_execute($conn, $sql)
{
    return mysqli_query($conn, $sql);
}

function mahasiswa_db_value($conn, $value)
{
    if ($value === null || $value === '') {
        return "NULL";
    }

    return "'" . mysqli_real_escape_string($conn, (string)$value) . "'";
}

function mahasiswa_ref_options($conn, $jenis_ref)
{
    $jenis_ref = mysqli_real_escape_string($conn, $jenis_ref);
    return mahasiswa_fetch_all($conn, "
        SELECT *
        FROM ref_pddikti
        WHERE jenis_ref = '$jenis_ref'
        AND status = 'aktif'
        ORDER BY nama_ref ASC
    ");
}

function mahasiswa_ref_name($conn, $jenis_ref, $id_feeder)
{
    if (empty($id_feeder)) {
        return '';
    }

    $jenis_ref = mysqli_real_escape_string($conn, $jenis_ref);
    $id_feeder = mysqli_real_escape_string($conn, $id_feeder);
    $row = mahasiswa_query_one($conn, "
        SELECT nama_ref
        FROM ref_pddikti
        WHERE jenis_ref = '$jenis_ref'
        AND id_feeder = '$id_feeder'
        LIMIT 1
    ");

    return $row['nama_ref'] ?? '';
}

function mahasiswa_table_columns($conn, $table)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    $rows = mahasiswa_fetch_all($conn, "SHOW COLUMNS FROM `$table`");
    $columns = [];

    foreach ($rows as $row) {
        $columns[] = $row['Field'];
    }

    return $columns;
}
