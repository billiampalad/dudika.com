<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';
session_start();

// Validasi login
if (!isset($_SESSION['nik']) || $_SESSION['role'] !== 'mitra') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Anda harus login.']);
    exit;
}

// Cek koneksi database
if (!$koneksi) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal']);
    exit;
}

// Ambil dan bersihkan data dari POST
$idKKS = isset($_POST['IdKKS']) ? $koneksi->real_escape_string($_POST['IdKKS']) : '';
$sesuaiRencana = isset($_POST['txtSesuaiRencana']) ? intval($_POST['txtSesuaiRencana']) : 0;
$kualitasPelaks = isset($_POST['txtKualitasPelaks']) ? intval($_POST['txtKualitasPelaks']) : 0;
$keterlibatanMitra = isset($_POST['txtKeterlibatanMtra']) ? intval($_POST['txtKeterlibatanMtra']) : 0;
$efisiensi = isset($_POST['txtEfisiensiPenggSbDya']) ? intval($_POST['txtEfisiensiPenggSbDya']) : 0;
$kepuasan = isset($_POST['txtKepuasanPhkTerkait']) ? intval($_POST['txtKepuasanPhkTerkait']) : 0;

// Validasi input
if (empty($idKKS)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Kegiatan tidak valid.']);
    exit;
}

if ($sesuaiRencana == 0 || $kualitasPelaks == 0 || $keterlibatanMitra == 0 || $efisiensi == 0 || $kepuasan == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Semua field rating wajib diisi dengan nilai 1-5.']);
    exit;
}

// Validasi range nilai (1-5)
if (
    $sesuaiRencana < 1 || $sesuaiRencana > 5 ||
    $kualitasPelaks < 1 || $kualitasPelaks > 5 ||
    $keterlibatanMitra < 1 || $keterlibatanMitra > 5 ||
    $efisiensi < 1 || $efisiensi > 5 ||
    $kepuasan < 1 || $kepuasan > 5
) {
    echo json_encode(['status' => 'error', 'message' => 'Nilai rating harus antara 1-5.']);
    exit;
}

// Cek apakah evaluasi untuk kegiatan ini sudah ada
$checkQuery = "SELECT IdEvKinerja FROM tblevaluasikinerja WHERE IdKKS = ?";
$checkStmt = $koneksi->prepare($checkQuery);
if (!$checkStmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $koneksi->error]);
    exit;
}

$checkStmt->bind_param("s", $idKKS);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Evaluasi untuk kegiatan ini sudah pernah dilakukan.']);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Generate ID unik untuk evaluasi
$result = $koneksi->query("SELECT MAX(CAST(SUBSTRING(IdEvKinerja, 4) AS UNSIGNED)) as max_id FROM tblevaluasikinerja");
if ($result) {
    $row = $result->fetch_assoc();
    $nextId = ($row['max_id'] ?? 0) + 1;
    $idEvKinerja = 'EVK' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
} else {
    // Fallback jika query gagal
    $idEvKinerja = 'EVK' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
}

// Mulai transaksi
$koneksi->begin_transaction();

try {
    // Simpan evaluasi ke database
    $insertQuery = "INSERT INTO tblevaluasikinerja 
                (IdEvKinerja, txtSesuaiRencana, txtKualitasPelaks, txtKeterlibatanMtra, 
                 txtEfisiensiPenggSbDya, txtKepuasanPhkTerkait, dtEvaluasi, IdKKS) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt->bind_param(
        "siiiiiss",
        $idEvKinerja,
        $sesuaiRencana,
        $kualitasPelaks,
        $keterlibatanMitra,
        $efisiensi,
        $kepuasan,
        $tanggalEvaluasi,
        $idKKS
    );
    if (!$stmt) {
        throw new Exception('Prepare statement gagal: ' . $koneksi->error);
    }

    $stmt->bind_param("siiiiis", $idEvKinerja, $sesuaiRencana, $kualitasPelaks, $keterlibatanMitra, $efisiensi, $kepuasan, $idKKS);

    if (!$stmt->execute()) {
        throw new Exception('Execute statement gagal: ' . $stmt->error);
    }

    $stmt->close();

    // Commit transaksi
    $koneksi->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Evaluasi berhasil disimpan.',
        'data' => [
            'IdEvKinerja' => $idEvKinerja,
            'IdKKS' => $idKKS
        ]
    ]);
} catch (Exception $e) {
    // Rollback jika terjadi error
    $koneksi->rollback();
    error_log("Error simpan evaluasi: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan evaluasi: ' . $e->getMessage()]);
}

$koneksi->close();
