<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

// Tentukan direktori dan nama file untuk catatan
$notes_dir = __DIR__ . '/notes';
$note_file = $notes_dir . '/pimpinan_catatan.txt';

// Buat direktori jika belum ada
if (!is_dir($notes_dir)) {
    mkdir($notes_dir, 0755, true);
}

// Router sederhana untuk aksi
$action = $_REQUEST['action'] ?? '';
switch ($action) {
    case 'get_single_capaian':
        get_single_capaian($koneksi);
        break;
    case 'update_capaian':
        update_capaian($koneksi);
        break;
    case 'get_chart_data':
        get_chart_data($koneksi);
        break;
    case 'save_note':
        save_note($note_file);
        break;
    case 'get_note':
        get_note($note_file);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
}
$koneksi->close();

// --- FUNGSI-FUNGSI AKSI ---

function get_single_capaian($koneksi) {
    $id = $_GET['id'] ?? 0;
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan.']); exit;
    }
    $stmt = $koneksi->prepare("SELECT * FROM tblhasildancapaian WHERE idHslDanCap = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    echo $data ? json_encode(['status' => 'success', 'data' => $data]) : json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
}

function update_capaian($koneksi) {
    $id = $_POST['idHslDanCap'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit;
    }
    $sql = "UPDATE tblhasildancapaian SET txtHasilLangsung = ?, txtDampakJangkaMenengah = ?, txtManfaatBgMhsw = ?, txtManfaatBgPolimdo = ?, txtManfaatBgDudika = ? WHERE idHslDanCap = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('ssssss', 
        $_POST['txtHasilLangsung'], $_POST['txtDampakJangkaMenengah'], $_POST['txtManfaatBgMhsw'], 
        $_POST['txtManfaatBgPolimdo'], $_POST['txtManfaatBgDudika'], $id
    );
    $success = $stmt->execute();
    $stmt->close();
    
    echo $success ? json_encode(['status' => 'success']) : json_encode(['status' => 'error', 'message' => 'Gagal update data.']);
}

function get_chart_data($koneksi) {
    // Kita gunakan panjang karakter sebagai proksi "jumlah" manfaat
    $sql = "SELECT 
                AVG(CHAR_LENGTH(txtManfaatBgMhsw)) as mahasiswa,
                AVG(CHAR_LENGTH(txtManfaatBgPolimdo)) as polimdo,
                AVG(CHAR_LENGTH(txtManfaatBgDudika)) as dudika
            FROM tblhasildancapaian";
    $result = $koneksi->query($sql);
    $data = $result->fetch_assoc();

    // Pastikan data tidak null jika tabel kosong
    $chart_data = [
        'mahasiswa' => $data['mahasiswa'] ?? 0,
        'polimdo' => $data['polimdo'] ?? 0,
        'dudika' => $data['dudika'] ?? 0,
    ];

    echo json_encode(['status' => 'success', 'data' => $chart_data]);
}

function save_note($file_path) {
    $note = $_POST['note'] ?? '';
    if (file_put_contents($file_path, $note) !== false) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan catatan.']);
    }
}

function get_note($file_path) {
    if (file_exists($file_path)) {
        echo json_encode(['status' => 'success', 'data' => file_get_contents($file_path)]);
    } else {
        echo json_encode(['status' => 'success', 'data' => '']); // Kirim string kosong jika file belum ada
    }
}
?>