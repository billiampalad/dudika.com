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

$query = "SELECT      
    nk.IdKKS,     
    nk.txtNamaKegiatanKS,     
    nk.dtMulaiPelaksanaan,     
    nk.dtSelesaiPelaksanaan,     
    p.txtDeskripsiKeg,     
    p.txtCakupanDanSkalaKeg,     
    p.intJumlahPeserta,     
    p.txtSumberDaya,     
    jk.txtNamaJenisKS,     
    md.txtNamaMitraDudika 
FROM tblnamakegiatanks nk 
LEFT JOIN tblpelaksanaankeg p ON nk.IdKKS = p.IdKKS 
LEFT JOIN tbljenisks jk ON nk.IdJenisKS = jk.IdJenisKS 
LEFT JOIN tblmitradudika md ON nk.IdMitraDudika = md.IdMitraDudika 
WHERE nk.IdKKS = ?";  

$stmt = mysqli_prepare($koneksi, $query); 
mysqli_stmt_bind_param($stmt, "s", $programId); 
mysqli_stmt_execute($stmt); 
$result = mysqli_stmt_get_result($stmt);  

if ($program = mysqli_fetch_assoc($result)) {     
    header('Content-Type: application/json');     
    echo json_encode($program); 
} else {     
    header('HTTP/1.1 404 Not Found');     
    echo json_encode(['error' => 'Program tidak ditemukan']); 
}  

mysqli_stmt_close($stmt); 
?>