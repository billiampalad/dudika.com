<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

$idKKS = isset($_GET['id']) ? $koneksi->real_escape_string($_GET['id']) : '';

error_log("ID yang diterima: " . $idKKS);

if (empty($idKKS)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Kegiatan tidak valid.']);
    exit;
}

// Gabungkan data dari evaluasi dan masalah/solusi (untuk rekomendasi)
$sql = "
    SELECT 
        txtSesuaiRencana,
        txtKualitasPelaks,
        txtKeterlibatanMtra,
        txtEfisiensiPenggSbDya,
        txtKepuasanPhkTerkait
    FROM tblevaluasikinerja 
    WHERE IdKKS = '$idKKS'
";

$result = $koneksi->query($sql);

if (!$result) {
    error_log("Error query: " . $koneksi->error);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $koneksi->error]);
    exit;
}

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $data]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan untuk ID: ' . $idKKS]);
}

$koneksi->close();
