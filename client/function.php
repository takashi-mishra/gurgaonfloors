<?php

date_default_timezone_set('Asia/Kolkata');

$hostname = getenv('GF_DB_HOST') ?: 'localhost';
$database = getenv('GF_DB_NAME');
$username = getenv('GF_DB_USER');
$password = getenv('GF_DB_PASSWORD');

if ($database === false || $database === '' || $username === false || $username === '' || $password === false || $password === '') {
    throw new RuntimeException('Database environment variables are not configured.');
}

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = new mysqli($hostname, $username, $password, $database);

if ($mysqli->connect_errno) {
    throw new RuntimeException('Database connection failed: ' . $mysqli->connect_error);
}

if (!$mysqli->set_charset('utf8mb4')) {
    throw new RuntimeException('Could not set the database connection charset.');
}
