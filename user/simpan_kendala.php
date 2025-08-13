<?php
header('Content-Type: application/json');
require_once '/config/koneksi.php';
session_start();

if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'polimdo') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

// Ambil dan bersihkan data
$idKKS = isset($_POST['IdKKS']) ? $koneksi->real_escape_string($_POST['IdKKS']) : '';
$kendala = isset($_POST['txtKendala']) ? $koneksi->real_escape_string($_POST['txtKendala']) : '';
$solusi = isset($_POST['txtUpayaUtkAtasiMslh']) ? $koneksi->real_escape_string($_POST['txtUpayaUtkAtasiMslh']) : '';

if (empty($idKKS) || empty($kendala)) {
    echo json_encode(['status' => 'error', 'message' => 'Kegiatan dan deskripsi kendala wajib diisi.']);
    exit;
}

// Generate ID unik
$idMslhDanSolusi = 'MSL' . str_pad((rand(100, 999)), 3, '0', STR_PAD_LEFT);

// Teks default untuk kolom yang tidak ada di form
$rekomendasi = "Menunggu diskusi lebih lanjut.";

$stmt = $koneksi->prepare(
    "INSERT INTO tblpermasalahandansolusi 
    (IdMslhDanSolusi, txtKendala, txtUpayaUtkAtasiMslh, txtRekomUtkPerbaikan, IdKKS) 
    VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssss", $idMslhDanSolusi, $kendala, $solusi, $rekomendasi, $idKKS);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
}

$stmt->close();
$koneksi->close();
?>