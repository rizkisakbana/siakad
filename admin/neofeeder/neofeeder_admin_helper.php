<?php

function nf_count($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return 0;
    }

    $row = mysqli_fetch_assoc($q);
    return (int) ($row['total'] ?? 0);
}

function nf_query_one($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q || mysqli_num_rows($q) < 1) {
        return null;
    }

    return mysqli_fetch_assoc($q);
}

function nf_fetch_all($conn, $sql)
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

function nf_exists($conn, $sql)
{
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        return null;
    }

    return mysqli_num_rows($q) > 0;
}

function nf_execute($conn, $sql)
{
    return mysqli_query($conn, $sql);
}

function nf_table_exists($conn, $table)
{
    $table = mysqli_real_escape_string($conn, preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table));
    return nf_exists($conn, "SHOW TABLES LIKE '$table'");
}

function nf_column_exists($conn, $table, $column)
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
    $column = mysqli_real_escape_string($conn, preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column));
    return nf_exists($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
}
