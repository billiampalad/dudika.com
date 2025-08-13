<?php
// Aktifkan error reporting untuk debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Tangkap error dan output buffer
ob_start();

try {
    header('Content-Type: application/json');
    require_once '../config/koneksi.php';

    // Validasi method request
    $method = $_SERVER['REQUEST_METHOD'];

    // Router sederhana untuk aksi
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            if ($method !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Method harus POST']);
                exit;
            }
            add_jenis_ks($koneksi);
            break;
        case 'update':
            if ($method !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Method harus POST']);
                exit;
            }
            update_jenis_ks($koneksi);
            break;
        case 'delete':
            if ($method !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Method harus POST']);
                exit;
            }
            delete_jenis_ks($koneksi);
            break;
        case 'search':
            if ($method !== 'GET') {
                echo json_encode(['status' => 'error', 'message' => 'Method harus GET']);
                exit;
            }
            search_jenis_ks($koneksi);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
    }
    
    $koneksi->close();
} catch (Exception $e) {
    // Bersihkan output buffer jika ada error
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

// Pastikan hanya JSON yang dikirim
ob_end_flush();

// --- FUNGSI-FUNGSI AKSI ---

function add_jenis_ks($koneksi) {
    // Generate ID otomatis dengan format JKSxxx
    $result = $koneksi->query("SELECT MAX(CAST(SUBSTRING(IdJenisKS, 4) AS UNSIGNED)) as max_id FROM tbljenisks");
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    $new_id = 'JKS' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    
    $stmt = $koneksi->prepare("INSERT INTO tbljenisks (IdJenisKS, txtNamaJenisKS) VALUES (?, ?)");
    $stmt->bind_param('ss', 
        $new_id,
        $_POST['txtNamaJenisKS']
    );
    $success = $stmt->execute();
    $stmt->close();
    
    echo $success ? json_encode(['status' => 'success']) : json_encode(['status' => 'error', 'message' => 'Gagal menambahkan data: ' . $koneksi->error]);
}

function update_jenis_ks($koneksi) {
    $id = $_POST['IdJenisKS'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit;
    }
    $stmt = $koneksi->prepare("UPDATE tbljenisks SET txtNamaJenisKS = ? WHERE IdJenisKS = ?");
    $stmt->bind_param('ss', 
        $_POST['txtNamaJenisKS'], 
        $id
    );
    $success = $stmt->execute();
    $stmt->close();
    
    echo $success ? json_encode(['status' => 'success']) : json_encode(['status' => 'error', 'message' => 'Gagal update data: ' . $koneksi->error]);
}

function delete_jenis_ks($koneksi) {
    $id = $_POST['IdJenisKS'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit;
    }
    
    // Cek apakah ID digunakan di tabel lain
    $check_sql = $koneksi->prepare("SELECT COUNT(*) as count FROM tblnamakegiatanks WHERE IdJenisKS = ?");
    $check_sql->bind_param("s", $id);
    $check_sql->execute();
    $is_in_use = $check_sql->get_result()->fetch_assoc()['count'] > 0;
    $check_sql->close();
    
    if ($is_in_use) {
        echo json_encode(['status' => 'error', 'message' => 'Jenis kerjasama sedang digunakan dan tidak dapat dihapus.']); exit;
    }
    
    $stmt = $koneksi->prepare("DELETE FROM tbljenisks WHERE IdJenisKS = ?");
    $stmt->bind_param('s', $id);
    $success = $stmt->execute();
    $stmt->close();
    
    echo $success ? json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']) : json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $koneksi->error]);
}

function search_jenis_ks($koneksi) {
    $keyword = $_GET['keyword'] ?? '';
    
    // Validasi keyword
    if (empty($keyword)) {
        echo json_encode(['status' => 'error', 'message' => 'Keyword pencarian tidak boleh kosong.']);
        exit;
    }
    
    $sql = "SELECT IdJenisKS, txtNamaJenisKS FROM tbljenisks 
            WHERE txtNamaJenisKS LIKE ? 
            ORDER BY txtNamaJenisKS ASC";
    
    $stmt = $koneksi->prepare($sql);
    $searchTerm = "%$keyword%";
    $stmt->bind_param("s", $searchTerm);
    
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal melakukan pencarian: ' . $stmt->error]);
        exit;
    }
    
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'total' => count($data)
    ]);
}