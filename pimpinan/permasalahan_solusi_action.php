<?php
// Set header sebagai JSON dan panggil koneksi
header('Content-Type: application/json');
require_once '../config/koneksi.php'; // Pastikan path ini benar

// Router sederhana untuk setiap aksi
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_problems_list':
        get_problems_list($koneksi);
        break;
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

// Tutup koneksi setelah semua selesai
$koneksi->close();

// === FUNGSI-FUNGSI AKSI ===

function get_problems_list($koneksi)
{
    // --- Logika Filter & Paginasi ---
    $limit = 5;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $search = $_GET['search'] ?? '';
    $filter_urgensi = $_GET['urgensi'] ?? '';

    // --- Bangun query dinamis ---
    $where_clauses = [];
    $params = [];
    $types = '';

    if (!empty($search)) {
        $where_clauses[] = "(k.txtNamaKegiatanKS LIKE ? OR p.txtKendala LIKE ?)";
        $search_term = '%' . $search . '%';
        array_push($params, $search_term, $search_term);
        $types .= 'ss';
    }

    if (!empty($filter_urgensi)) {
        $where_clauses[] = "p.urgensi = ?";
        $params[] = $filter_urgensi;
        $types .= 's';
    }

    $where_sql = !empty($where_clauses) ? " WHERE " . implode(' AND ', $where_clauses) : "";

    // --- Query untuk menghitung total data (untuk paginasi) ---
    $sql_count = "SELECT COUNT(*) as total FROM tblpermasalahandansolusi p JOIN tblnamakegiatanks k ON p.IdKKS = k.IdKKS" . $where_sql;
    $stmt_count = $koneksi->prepare($sql_count);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_data = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_data / $limit);
    $stmt_count->close();

    // --- Query utama untuk mengambil data per halaman ---
    $sql_data = "SELECT p.*, k.txtNamaKegiatanKS FROM tblpermasalahandansolusi p JOIN tblnamakegiatanks k ON p.IdKKS = k.IdKKS" . $where_sql . " ORDER BY FIELD(p.status, 'diproses', 'selesai'), FIELD(p.urgensi, 'tinggi', 'sedang', 'rendah') LIMIT ? OFFSET ?";

    // Tambahkan parameter untuk LIMIT dan OFFSET ke array params dan types
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt_data = $koneksi->prepare($sql_data);
    $stmt_data->bind_param($types, ...$params);
    $stmt_data->execute();
    $result = $stmt_data->get_result();
    $problems_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt_data->close();

    // Kembalikan hasil dalam format JSON
    echo json_encode([
        'status' => 'success',
        'data' => $problems_data,
        'pagination' => [
            'total_pages' => $total_pages,
            'current_page' => $page,
            'total_data' => $total_data
        ]
    ]);
}


function generate_id($koneksi)
{
    // ID Generator yang lebih aman dari race condition sederhana
    $query = "SELECT IdMslhDanSolusi FROM tblpermasalahandansolusi WHERE IdMslhDanSolusi LIKE 'MS%' ORDER BY IdMslhDanSolusi DESC LIMIT 1";
    $result = $koneksi->query($query);
    if ($result->num_rows > 0) {
        $max_id = $result->fetch_assoc()['max_id'];
        $num = (int) substr($max_id, 2) + 1;
    } else {
        $num = 1;
    }
    return 'MS' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

function add_problem($koneksi)
{
    $id_baru = generate_id($koneksi);
    $sql = "INSERT INTO tblpermasalahandansolusi (IdMslhDanSolusi, IdKKS, urgensi, txtKendala, txtUpayaUtkAtasiMslh, txtRekomUtkPerbaikan, status) VALUES (?, ?, ?, ?, ?, ?, 'diproses')";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('ssssss', $id_baru, $_POST['IdKKS'], $_POST['urgensi'], $_POST['txtKendala'], $_POST['txtUpayaUtkAtasiMslh'], $_POST['txtRekomUtkPerbaikan']);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil ditambahkan.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambah data: ' . $stmt->error]);
    }
    $stmt->close();
}

function get_single_problem($koneksi)
{
    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
        return;
    }
    $stmt = $koneksi->prepare("SELECT IdMslhDanSolusi, IdKKS, urgensi, txtKendala, txtUpayaUtkAtasiMslh, txtRekomUtkPerbaikan FROM tblpermasalahandansolusi WHERE IdMslhDanSolusi = ?");
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if ($data) {
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
    }
}

function update_problem($koneksi)
{
    $id = $_POST['IdMslhDanSolusi'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
        return;
    }
    $sql = "UPDATE tblpermasalahandansolusi SET IdKKS = ?, urgensi = ?, txtKendala = ?, txtUpayaUtkAtasiMslh = ?, txtRekomUtkPerbaikan = ? WHERE IdMslhDanSolusi = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param('ssssss', $_POST['IdKKS'], $_POST['urgensi'], $_POST['txtKendala'], $_POST['txtUpayaUtkAtasiMslh'], $_POST['txtRekomUtkPerbaikan'], $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $stmt->error]);
    }
    $stmt->close();
}

function mark_as_done($koneksi)
{
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
        return;
    }
    $stmt = $koneksi->prepare("UPDATE tblpermasalahandansolusi SET status = 'selesai' WHERE IdMslhDanSolusi = ?");
    $stmt->bind_param('s', $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Status berhasil diperbarui.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status: ' . $stmt->error]);
    }
    $stmt->close();
}
