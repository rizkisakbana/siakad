<?php

function jadwal_count($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return 0;
    }

    $row = mysqli_fetch_assoc($q);
    return (int)($row['total'] ?? 0);
}

function jadwal_query_one($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q || mysqli_num_rows($q) < 1) {
        return null;
    }

    return mysqli_fetch_assoc($q);
}

function jadwal_fetch_all($conn, $sql)
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

function jadwal_query_exists($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return null;
    }

    return mysqli_num_rows($q) > 0;
}
