<?php
session_start();
include __DIR__ . '/config/koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['nik'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$cacheFile = __DIR__ . '/cache/stats_' . md5($_SESSION['nik']) . '.json';
$cacheTime = 300; // 5 menit

// Cek cache
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    die(file_get_contents($cacheFile));
}

$response = [];

try {
    // ... (kode query sama seperti sebelumnya)
    
    // Simpan ke cache
    file_put_contents($cacheFile, json_encode($response));
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>