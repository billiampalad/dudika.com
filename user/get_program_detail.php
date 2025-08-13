<?php
header('Content-Type: application/json');

// Debug: Tentukan base directory
define('BASE_DIR', __DIR__);
error_log("Base directory: " . BASE_DIR);

// Path ke file koneksi (sesuaikan dengan struktur folder Anda)
$koneksi_path = BASE_DIR . '/../config/koneksi.php'; // Jika config ada di folder parent
error_log("Mencoba load koneksi dari: " . $koneksi_path);

// Verifikasi file koneksi ada
if (!file_exists($koneksi_path)) {
    error_log("File koneksi tidak ditemukan");
    http_response_code(500);
    echo json_encode(['error' => 'Konfigurasi server tidak valid']);
    exit;
}

require $koneksi_path;

// Verifikasi koneksi berhasil
if (!$koneksi || !($koneksi instanceof mysqli)) {
    error_log("Koneksi database tidak valid");
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal']);
    exit;
}

// Tangani request
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter ID diperlukan']);
    exit;
}

$programId = $_GET['id'];

// Gunakan prepared statement untuk keamanan
$query = "SELECT 
    nk.IdKKS, 
    nk.txtNamaKegiatanKS, 
    jk.txtNamaJenisKS,
    nk.dtMulaiPelaksanaan,
    nk.dtSelesaiPelaksanaan,
    nk.txtNomorMOU,
    nk.dtMOU,
    tk.txtTujuanKS,
    tk.txtSasaranKS
FROM tblnamakegiatanks nk
JOIN tbljenisks jk ON nk.IdJenisKS = jk.IdJenisKS
JOIN tbltujuanks tk ON nk.IdKKS = tk.IdKKS
WHERE nk.IdKKS = ?";

$stmt = mysqli_prepare($koneksi, $query);
if (!$stmt) {
    error_log("Error prepare statement: " . mysqli_error($koneksi));
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($koneksi)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $programId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    error_log("Error query: " . mysqli_error($koneksi));
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($koneksi)]);
    exit;
}

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Program tidak ditemukan']);
    exit;
}

$program = mysqli_fetch_assoc($result);
echo json_encode($program);

mysqli_stmt_close($stmt);
?>