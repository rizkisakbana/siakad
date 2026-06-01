<?php

if (!function_exists('pengguna_count')) {
    function pengguna_count(mysqli $conn, string $sql): int
    {
        try {
            $result = mysqli_query($conn, $sql);
        } catch (mysqli_sql_exception $e) {
            return 0;
        }

        if (!$result) {
            return 0;
        }

        $row = mysqli_fetch_assoc($result);

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('pengguna_one')) {
    function pengguna_one(mysqli $conn, string $sql): ?array
    {
        try {
            $result = mysqli_query($conn, $sql);
        } catch (mysqli_sql_exception $e) {
            return null;
        }

        if (!$result || mysqli_num_rows($result) < 1) {
            return null;
        }

        return mysqli_fetch_assoc($result);
    }
}

if (!function_exists('pengguna_all')) {
    function pengguna_all(mysqli $conn, string $sql): array
    {
        try {
            $result = mysqli_query($conn, $sql);
        } catch (mysqli_sql_exception $e) {
            return $rows;
        }
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

if (!function_exists('pengguna_execute')) {
    function pengguna_execute(mysqli $conn, string $sql): bool
    {
        try {
            return (bool) mysqli_query($conn, $sql);
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
}

if (!function_exists('pengguna_sql_value')) {
    function pengguna_sql_value(mysqli $conn, ?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return "NULL";
        }

        return "'" . mysqli_real_escape_string($conn, $value) . "'";
    }
}
