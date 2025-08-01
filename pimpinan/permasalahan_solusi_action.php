<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

// Router sederhana untuk aksi
$action = $_REQUEST['action'] ?? '';
switch ($action) {
    case 'add_problem':
        add_problem($koneksi);
        break;
    case 'get_single_problem':
        get_single_problem($koneksi);
        break;
    case 'update_problem':
        update_problem($koneksi);
        break;
    case 'mark_as_done':
        mark_as_done($koneksi);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
}
$koneksi->close();

// --- FUNGSI-FUNGSI AKSI ---

function generate_id($koneksi) {
    $query = "SELECT MAX(IdMslhDanSolusi) as max_id FROM tblpermasalahandansolusi";
    $result = $koneksi->query($query);
    $row = $result->fetch_assoc();
    $max_id = $row['max_id'];
    $num = (int) substr($max_id, 2) + 1;
    return 'MS' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

function add_problem($koneksi) {
    $id_baru = generate_id($koneksi);
    $sql = "INSERT INTO tblpermasalahandansolusi (IdMslhDanSolusi, IdKKS, urgensi, txtKendala, txtUpayaUtkAtasiMslh, txtRekomUtkPerbaikan) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('ssssss', $id_baru, $_POST['IdKKS'], $_POST['urgensi'], $_POST['txtKendala'], $_POST['txtUpayaUtkAtasiMslh'], $_POST['txtRekomUtkPerbaikan']);
    $success = $stmt->execute();
    $stmt->close();
    
    echo $success ? json_encode(['status' => 'success']) : json_encode(['status' => 'error', 'message' => 'Gagal menambah data.']);
}

function get_single_problem($koneksi) {
    $id = $_GET['id'] ?? '';
    if (empty($id)) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit; }

    $stmt = $koneksi->prepare("SELECT * FROM tblpermasalahandansolusi WHERE IdMslhDanSolusi = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    echo $data ? json_encode(['status' => 'success', 'data' => $data]) : json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
}

function update_problem($koneksi) {
    $id = $_POST['IdMslhDanSolusi'] ?? '';
    if (empty($id)) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit; }

    $sql = "UPDATE tblpermasalahandansolusi SET IdKKS = ?, urgensi = ?, txtKendala = ?, txtUpayaUtkAtasiMslh = ?, txtRekomUtkPerbaikan = ? WHERE IdMslhDanSolusi = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('ssssss', $_POST['IdKKS'], $_POST['urgensi'], $_POST['txtKendala'], $_POST['txtUpayaUtkAtasiMslh'], $_POST['txtRekomUtkPerbaikan'], $id);
    $success = $stmt->execute();
    $stmt->close();

    echo $success ? json_encode(['status' => 'success']) : json_encode(['status' => 'error', 'message' => 'Gagal update data.']);
}

function mark_as_done($koneksi) {
    $id = $_POST['id'] ?? '';
    if (empty($id)) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']); exit; }

    $stmt = $koneksi->prepare("UPDATE tblpermasalahandansolusi SET status = 'selesai' WHERE IdMslhDanSolusi = ?");
    $stmt->bind_param('s', $id);
    $success = $stmt->execute();
    $stmt->close();

    echo $success ? json_encode(['status' => 'success']) : json_encode(['status' => 'error', 'message' => 'Gagal update status.']);
}
?>