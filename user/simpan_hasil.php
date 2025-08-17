<?php
header('Content-Type: application/json');

// Pastikan semua output adalah JSON yang valid
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error PHP di output

require_once '../config/koneksi.php';
session_start();

// 1. Validasi Sesi Pengguna
// Periksa apakah user sudah login
if (!isset($_SESSION['nik'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
    exit;
}

// Tangkap semua error dan konversi ke JSON
try {

// 2. Ambil dan Validasi Data Input
$idKKS = isset($_POST['IdKKS']) ? trim($_POST['IdKKS']) : '';
$manfaatMitra = isset($_POST['txtManfaatBgDudika']) ? trim($_POST['txtManfaatBgDudika']) : '';
$dampak = isset($_POST['txtDampakJangkaMenengah']) ? trim($_POST['txtDampakJangkaMenengah']) : '';

if (empty($idKKS)) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan pilih program kerjasama terlebih dahulu.']);
    exit;
}
if (empty($manfaatMitra)) {
    echo json_encode(['status' => 'error', 'message' => 'Kolom "Manfaat yang Dirasakan Mitra" wajib diisi.']);
    exit;
}
if (strlen($manfaatMitra) > 1000) { // Contoh validasi panjang karakter
    echo json_encode(['status' => 'error', 'message' => 'Deskripsi manfaat terlalu panjang (maks 1000 karakter).']);
    exit;
}

// 3. Generate ID Unik yang Lebih Andal
// Menggunakan kombinasi uniqid() dan md5() untuk mengurangi kemungkinan duplikasi
$idHslDanCap = 'HDC' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));

// Teks default untuk kolom lain yang tidak ada di form
$hasilLangsung = "Hasil dilaporkan oleh Polimdo.";
$manfaatMhsw = "Manfaat dilaporkan oleh Polimdo.";
$manfaatPolimdo = "Manfaat dilaporkan oleh Polimdo.";

// 4. Gunakan Prepared Statement untuk Keamanan (Mencegah SQL Injection)
$stmt = $koneksi->prepare(
    "INSERT INTO tblhasildancapaian 
    (idHslDanCap, txtHasilLangsung, txtDampakJangkaMenengah, txtManfaatBgMhsw, txtManfaatBgPolimdo, txtManfaatBgDudika, IdKKS) 
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if ($stmt === false) {
    // Gagal saat prepare statement
    error_log("Prepare failed: " . $koneksi->error);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
    exit;
}

$stmt->bind_param("sssssss", $idHslDanCap, $hasilLangsung, $dampak, $manfaatMhsw, $manfaatPolimdo, $manfaatMitra, $idKKS);

// 5. Eksekusi dan Berikan Respon
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Laporan hasil berhasil disimpan!']);
} else {
    error_log("Execute failed: " . $stmt->error); // Log error untuk admin
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ke database.']);
}

$stmt->close();
$koneksi->close();

} catch (Exception $e) {
    // Tangkap semua error dan kembalikan sebagai JSON
    error_log("Error in simpan_hasil.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
?>
