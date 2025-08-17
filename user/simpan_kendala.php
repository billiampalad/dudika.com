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
$kendala = isset($_POST['txtKendala']) ? trim($_POST['txtKendala']) : '';
$solusi = isset($_POST['txtUpayaUtkAtasiMslh']) ? trim($_POST['txtUpayaUtkAtasiMslh']) : '';
$urgensi = isset($_POST['urgensi']) ? trim($_POST['urgensi']) : 'rendah';

// Validasi urgensi agar nilainya sesuai yang diharapkan
$allowed_urgensi = ['rendah', 'sedang', 'tinggi'];
if (!in_array($urgensi, $allowed_urgensi)) {
    $urgensi = 'rendah'; // Default jika nilai tidak valid
}

if (empty($idKKS)) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan pilih program kerjasama terlebih dahulu.']);
    exit;
}
if (empty($kendala)) {
    echo json_encode(['status' => 'error', 'message' => 'Kolom "Deskripsikan Kendala" wajib diisi.']);
    exit;
}
if (strlen($kendala) > 1000) { // Contoh validasi panjang karakter
    echo json_encode(['status' => 'error', 'message' => 'Deskripsi kendala terlalu panjang (maks 1000 karakter).']);
    exit;
}

// 3. Generate ID Unik yang Lebih Andal
$idMslhDanSolusi = 'MSL' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));

// Teks default untuk kolom yang tidak ada di form
$rekomendasi = "Menunggu diskusi lebih lanjut.";
$status = "diproses"; // Status default saat laporan baru dibuat

// 4. Gunakan Prepared Statement untuk Keamanan
$stmt = $koneksi->prepare(
    "INSERT INTO tblpermasalahandansolusi 
    (IdMslhDanSolusi, txtKendala, txtUpayaUtkAtasiMslh, txtRekomUtkPerbaikan, IdKKS, urgensi, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if ($stmt === false) {
    error_log("Prepare failed: " . $koneksi->error);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
    exit;
}

$stmt->bind_param("sssssss", $idMslhDanSolusi, $kendala, $solusi, $rekomendasi, $idKKS, $urgensi, $status);

// 5. Eksekusi dan Berikan Respon
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Laporan kendala berhasil dikirim!']);
} else {
    error_log("Execute failed: " . $stmt->error);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data ke database.']);
}

$stmt->close();
$koneksi->close();

} catch (Exception $e) {
    // Tangkap semua error dan kembalikan sebagai JSON
    error_log("Error in simpan_kendala.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
?>
