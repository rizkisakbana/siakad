<?php

if (!function_exists('notifikasi_count')) {
    function notifikasi_count(mysqli $conn, string $sql): int
    {
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            return 0;
        }

        $row = mysqli_fetch_assoc($result);

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('notifikasi_one')) {
    function notifikasi_one(mysqli $conn, string $sql): ?array
    {
        $result = mysqli_query($conn, $sql);

        if (!$result || mysqli_num_rows($result) < 1) {
            return null;
        }

        return mysqli_fetch_assoc($result);
    }
}

if (!function_exists('notifikasi_all')) {
    function notifikasi_all(mysqli $conn, string $sql): array
    {
        $result = mysqli_query($conn, $sql);
        $rows = [];

        if (!$result) {
            return $rows;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('notifikasi_execute')) {
    function notifikasi_execute(mysqli $conn, string $sql): bool
    {
        return (bool) mysqli_query($conn, $sql);
    }
}
