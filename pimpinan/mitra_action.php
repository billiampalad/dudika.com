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
    if ($method !== 'POST' && $method !== 'GET') {
        throw new Exception('Method tidak diizinkan');
    }

    // Router sederhana untuk aksi
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'get':
            get_single_mitra($koneksi);
            break;
        case 'add':
            if ($method !== 'POST') throw new Exception('Method harus POST');
            add_mitra($koneksi);
            break;
        case 'update':
            if ($method !== 'POST') throw new Exception('Method harus POST');
            update_mitra($koneksi);
            break;
        case 'delete':
            if ($method !== 'POST') throw new Exception('Method harus POST');
            delete_mitra($koneksi);
            break;
        case 'search':
            if ($method !== 'GET') throw new Exception('Method harus GET');
            search_mitra($koneksi);
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

function get_single_mitra($koneksi) {
    $id = $_POST['id_mitra'] ?? $_GET['id_mitra'] ?? '';
    if (empty($id)) {
        throw new Exception('ID tidak ditemukan.');
    }
    
    $stmt = $koneksi->prepare("SELECT * FROM tblmitradudika WHERE IdMitraDudika = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if (!$data) {
        throw new Exception('Data tidak ditemukan.');
    }
    
    echo json_encode(['status' => 'success', 'data' => $data]);
}

function add_mitra($koneksi) {
    // Validasi input
    $required_fields = ['txtNamaMitraDudika', 'txtAlamatMitra', 'txtEmailMitra', 'txtNamaKepalaDudika', 'nik'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field $field harus diisi.");
        }
    }

    // Generate ID otomatis dengan format MTxxx
    $result = $koneksi->query("SELECT MAX(CAST(SUBSTRING(IdMitraDudika, 3) AS UNSIGNED)) as max_id FROM tblmitradudika");
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    $new_id = 'MT' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

    $stmt = $koneksi->prepare("INSERT INTO tblmitradudika 
        (IdMitraDudika, txtNamaMitraDudika, txtAlamatMitra, txtEmailMitra, txtNamaKepalaDudika, nik) 
        VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param(
        'ssssss',
        $new_id,
        $_POST['txtNamaMitraDudika'],
        $_POST['txtAlamatMitra'],
        $_POST['txtEmailMitra'],
        $_POST['txtNamaKepalaDudika'],
        $_POST['nik']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal menambahkan data: ' . $stmt->error);
    }
    
    echo json_encode(['status' => 'success', 'id' => $new_id]);
    $stmt->close();
}

function update_mitra($koneksi) {
    $id = $_POST['id_mitra'] ?? '';
    if (empty($id)) {
        throw new Exception('ID tidak valid.');
    }

    // Validasi input
    $required_fields = ['txtNamaMitraDudika', 'txtAlamatMitra', 'txtEmailMitra', 'txtNamaKepalaDudika', 'nik'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field $field harus diisi.");
        }
    }

    $stmt = $koneksi->prepare("UPDATE tblmitradudika SET 
        txtNamaMitraDudika = ?, 
        txtAlamatMitra = ?, 
        txtEmailMitra = ?, 
        txtNamaKepalaDudika = ?,
        nik = ?
        WHERE IdMitraDudika = ?");
    
    $stmt->bind_param(
        'ssssss',
        $_POST['txtNamaMitraDudika'],
        $_POST['txtAlamatMitra'],
        $_POST['txtEmailMitra'],
        $_POST['txtNamaKepalaDudika'],
        $_POST['nik'],
        $id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal update data: ' . $stmt->error);
    }
    
    echo json_encode(['status' => 'success']);
    $stmt->close();
}

function delete_mitra($koneksi) {
    $id = $_POST['id_mitra'] ?? '';
    if (empty($id)) {
        throw new Exception('ID tidak valid.');
    }

    $stmt = $koneksi->prepare("DELETE FROM tblmitradudika WHERE IdMitraDudika = ?");
    $stmt->bind_param('s', $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal menghapus data: ' . $stmt->error);
    }
    
    echo json_encode(['status' => 'success']);
    $stmt->close();
}

function search_mitra($koneksi) {
    $keyword = $_GET['keyword'] ?? '';
    
    $sql = "SELECT IdMitraDudika, txtNamaMitraDudika, txtAlamatMitra, txtEmailMitra, txtNamaKepalaDudika, nik 
            FROM tblmitradudika
            WHERE txtNamaMitraDudika LIKE ?
            ORDER BY txtNamaMitraDudika ASC";
    
    $stmt = $koneksi->prepare($sql);
    $searchTerm = "%$keyword%";
    $stmt->bind_param("s", $searchTerm);
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal melakukan pencarian: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'total' => count($data)
    ]);
    $stmt->close();
}