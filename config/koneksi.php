<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "dudikapolimdo";

error_reporting(0);
ini_set('display_errors', 0);

$koneksi = new mysqli($host, $username, $password, $database);

// Periksa koneksi
if ($koneksi->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi database gagal: ' . $koneksi->connect_error
    ]);
    exit;
}

$koneksi->set_charset("utf8mb4");

?>