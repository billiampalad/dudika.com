<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

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
            add_unit_pelaksana($koneksi);
            break;
        case 'update':
            if ($method !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Method harus POST']);
                exit;
            }
            update_unit_pelaksana($koneksi);
            break;
        case 'delete':
            if ($method !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Method harus POST']);
                exit;
            }
            delete_unit_pelaksana($koneksi);
            break;
        case 'search':
            if ($method !== 'GET') {
                echo json_encode(['status' => 'error', 'message' => 'Method harus GET']);
                exit;
            }
            search_unit_pelaksana($koneksi);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
    }
    
    $koneksi->close();
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

ob_end_flush();

// --- FUNGSI-FUNGSI AKSI ---

function add_unit_pelaksana($koneksi) {
    // Validasi input
    $required_fields = ['txtNamaUnitPelaksPolimdo', 'txtNamaStafAdminUnit'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'message' => "Field $field harus diisi."]);
            exit;
        }
    }

    // Generate ID otomatis dengan format UPxxx
    $result = $koneksi->query("SELECT MAX(CAST(SUBSTRING(IdUnitPelaksana, 3) AS UNSIGNED)) as max_id FROM tblunitpelaksana");
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    $new_id = 'UP' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    
    $stmt = $koneksi->prepare("INSERT INTO tblunitpelaksana (IdUnitPelaksana, txtNamaUnitPelaksPolimdo, txtNamaStafAdminUnit) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', 
        $new_id,
        $_POST['txtNamaUnitPelaksPolimdo'],
        $_POST['txtNamaStafAdminUnit']
    );
    
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan data: ' . $stmt->error]);
        exit;
    }
    
    echo json_encode(['status' => 'success', 'id' => $new_id]);
    $stmt->close();
}

function update_unit_pelaksana($koneksi) {
    $id = $_POST['IdUnitPelaksana'] ?? '';
    $required_fields = ['txtNamaUnitPelaksPolimdo', 'txtNamaStafAdminUnit'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'message' => "Field $field harus diisi."]);
            exit;
        }
    }

    $stmt = $koneksi->prepare("UPDATE tblunitpelaksana SET txtNamaUnitPelaksPolimdo = ?, txtNamaStafAdminUnit = ? WHERE IdUnitPelaksana = ?");
    $stmt->bind_param('sss', 
        $_POST['txtNamaUnitPelaksPolimdo'],
        $_POST['txtNamaStafAdminUnit'],
        $id
    );
    
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal update data: ' . $stmt->error]);
        exit;
    }
    
    echo json_encode(['status' => 'success']);
    $stmt->close();
}

function delete_unit_pelaksana($koneksi) {
    $id = $_POST['IdUnitPelaksana'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
        exit;
    }
    
    // Cek apakah ID digunakan di tabel lain
    $check_sql = $koneksi->prepare("SELECT COUNT(*) as count FROM tblnamakegiatanks WHERE IdUnitPelaksana = ?");
    $check_sql->bind_param("s", $id);
    $check_sql->execute();
    $is_in_use = $check_sql->get_result()->fetch_assoc()['count'] > 0;
    $check_sql->close();
    
    if ($is_in_use) {
        echo json_encode(['status' => 'error', 'message' => 'Unit pelaksana sedang digunakan dan tidak dapat dihapus.']);
        exit;
    }
    
    $stmt = $koneksi->prepare("DELETE FROM tblunitpelaksana WHERE IdUnitPelaksana = ?");
    $stmt->bind_param('s', $id);
    
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $stmt->error]);
        exit;
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
    $stmt->close();
}

function search_unit_pelaksana($koneksi) {
    $keyword = $_GET['keyword'] ?? '';
    
    // Validasi keyword
    if (empty($keyword)) {
        echo json_encode(['status' => 'error', 'message' => 'Keyword pencarian tidak boleh kosong.']);
        exit;
    }
    
    $sql = "SELECT IdUnitPelaksana, txtNamaUnitPelaksPolimdo, txtNamaStafAdminUnit 
            FROM tblunitpelaksana 
            WHERE txtNamaUnitPelaksPolimdo LIKE ? OR txtNamaStafAdminUnit LIKE ?
            ORDER BY txtNamaUnitPelaksPolimdo ASC";
    
    $stmt = $koneksi->prepare($sql);
    $searchTerm = "%$keyword%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    
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
?>