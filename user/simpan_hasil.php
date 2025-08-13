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
$manfaatMitra = isset($_POST['txtManfaatBgDudika']) ? $koneksi->real_escape_string($_POST['txtManfaatBgDudika']) : '';
$dampak = isset($_POST['txtDampakJangkaMenengah']) ? $koneksi->real_escape_string($_POST['txtDampakJangkaMenengah']) : '';

if (empty($idKKS)) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan pilih kegiatan terlebih dahulu.']);
    exit;
}

// Generate ID unik untuk tabel hasil dan capaian
$idHslDanCap = 'HDC' . str_pad((rand(100, 999)), 3, '0', STR_PAD_LEFT);

// Teks default untuk kolom lain yang tidak ada di form
$hasilLangsung = "Hasil dilaporkan oleh Polimdo.";
$manfaatMhsw = "Manfaat dilaporkan oleh Polimdo.";
$manfaatPolimdo = "Manfaat dilaporkan oleh Polimdo.";

$stmt = $koneksi->prepare(
    "INSERT INTO tblhasildancapaian 
    (idHslDanCap, txtHasilLangsung, txtDampakJangkaMenengah, txtManfaatBgMhsw, txtManfaatBgPolimdo, txtManfaatBgDudika, IdKKS) 
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssssss", $idHslDanCap, $hasilLangsung, $dampak, $manfaatMhsw, $manfaatPolimdo, $manfaatMitra, $idKKS);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
}

$stmt->close();
$koneksi->close();
?>