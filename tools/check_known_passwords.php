<?php

require __DIR__ . '/../config/database.php';

$guesses = [
    'admin',
    'admin123',
    'Admin12345',
    'password',
    '123456',
    'Dosen12345',
    'Mahasiswa12345',
];

$usernames = ['admin', '202610001', '2257508006', 'dosen_tes'];

foreach ($usernames as $username) {
    $safeUsername = mysqli_real_escape_string($conn, $username);
    $result = mysqli_query($conn, "SELECT username, password FROM users WHERE username = '{$safeUsername}' LIMIT 1");
    $row = $result ? mysqli_fetch_assoc($result) : null;

    $matches = [];
    foreach ($guesses as $guess) {
        if ($row && password_verify($guess, $row['password'])) {
            $matches[] = $guess;
        }
    }

    echo $username . ': ' . ($matches ? implode(', ', $matches) : '-') . PHP_EOL;
}
