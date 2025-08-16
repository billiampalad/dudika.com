<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Path ke file koneksi
require_once 'config/koneksi.php';

// Verifikasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Validasi data yang diperlukan
if (!isset($_POST['pelaksanaanId']) || empty($_POST['pelaksanaanId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Kegiatan diperlukan']);
    exit;
}

// Ambil data dari form
$pelaksanaanId = $_POST['pelaksanaanId'];
$deskripsiKeg = $_POST['txtDeskripsiKeg'] ?? '';
$cakupanDanSkalaKeg = $_POST['txtCakupanDanSkalaKeg'] ?? '';
$jumlahPeserta = intval($_POST['intJumlahPeserta'] ?? 0);
$sumberDaya = $_POST['txtSumberDaya'] ?? '';

// Cek apakah data pelaksanaan sudah ada
$checkQuery = "SELECT IdPelaksanaanKeg FROM tblpelaksanaankeg WHERE IdKKS = ?";
$checkStmt = $koneksi->prepare($checkQuery);
$checkStmt->bind_param("s", $pelaksanaanId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

// Jika data sudah ada, update. Jika belum, insert baru
if ($checkResult->num_rows > 0) {
    $row = $checkResult->fetch_assoc();
    $idPelaksanaan = $row['IdPelaksanaanKeg'];
    
    $query = "UPDATE tblpelaksanaankeg SET 
              txtDeskripsiKeg = ?,
              txtCakupanDanSkalaKeg = ?,
              intJumlahPeserta = ?,
              txtSumberDaya = ?,
              dtUpdated = NOW()
              WHERE IdPelaksanaanKeg = ?";
              
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("ssisi", $deskripsiKeg, $cakupanDanSkalaKeg, $jumlahPeserta, $sumberDaya, $idPelaksanaan);
} else {
    $query = "INSERT INTO tblpelaksanaankeg 
              (IdKKS, txtDeskripsiKeg, txtCakupanDanSkalaKeg, intJumlahPeserta, txtSumberDaya, dtCreated) 
              VALUES (?, ?, ?, ?, ?, NOW())";
              
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("sssis", $pelaksanaanId, $deskripsiKeg, $cakupanDanSkalaKeg, $jumlahPeserta, $sumberDaya);
}

// Eksekusi query
$success = $stmt->execute();

// Proses upload file jika ada
if ($success && isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == 0) {
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
    $fileType = $_FILES['fileUpload']['type'];
    
    if (in_array($fileType, $allowedTypes) && $_FILES['fileUpload']['size'] <= 5242880) { // 5MB max
        $uploadDir = 'uploads/dokumentasi/';
        
        // Buat direktori jika belum ada
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = 'Dokumentasi_' . $pelaksanaanId . '_' . time() . '_' . basename($_FILES['fileUpload']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $filePath)) {
            // Simpan referensi file ke database
            $fileQuery = "INSERT INTO tbldokumentasi (pathFoto, dtCreated) VALUES (?, NOW())";
            $fileStmt = $koneksi->prepare($fileQuery);
            $fileStmt->bind_param("s", $filePath);
            
            if ($fileStmt->execute()) {
                $idDokumentasi = $koneksi->insert_id;
                
                // Update referensi dokumentasi di tabel kegiatan
                $updateQuery = "UPDATE tblnamakegiatanks SET idDokumentasi = ? WHERE IdKKS = ?";
                $updateStmt = $koneksi->prepare($updateQuery);
                $updateStmt->bind_param("is", $idDokumentasi, $pelaksanaanId);
                $updateStmt->execute();
            }
        }
    }
}

// Kembalikan response
if ($success) {
    echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $stmt->error]);
}

// Tutup statement dan koneksi
$stmt->close();
$koneksi->close();
?>