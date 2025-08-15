<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

// Fungsi untuk mengembalikan response error
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// Validasi input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Router aksi
$action = $_REQUEST['action'] ?? '';
if (empty($action)) {
    sendError('Aksi tidak boleh kosong.');
}

// Pastikan koneksi database berhasil
if ($koneksi->connect_error) {
    sendError('Koneksi database gagal: ' . $koneksi->connect_error, 500);
}

try {
    switch ($action) {
        case 'get_single_evaluation':
            get_single_evaluation($koneksi);
            break;
        case 'update_evaluation':
            update_evaluation($koneksi);
            break;
        case 'get_radar_chart_data':
            get_radar_chart_data($koneksi);
            break;
        default:
            sendError('Aksi tidak valid.');
    }
} catch (Exception $e) {
    sendError('Terjadi kesalahan server: ' . $e->getMessage(), 500);
} finally {
    $koneksi->close();
}

// --- FUNGSI-FUNGSI AKSI ---

function get_single_evaluation($koneksi) {
    $id = $_GET['id'] ?? 0;
    if (empty($id)) {
        sendError('ID tidak ditemukan.');
    }

    // Validasi ID sebagai alfanumerik untuk mencegah SQL injection
    if (!preg_match('/^[a-zA-Z0-9]+$/', $id)) {
        sendError('Format ID tidak valid.');
    }

    try {
        // Ambil data evaluasi dan nama kegiatan
        $stmt1 = $koneksi->prepare("SELECT e.*, k.txtNamaKegiatanKS 
                                   FROM tblevaluasikinerja e 
                                   JOIN tblnamakegiatanks k ON e.IdKKS = k.IdKKS 
                                   WHERE e.IdEvKinerja = ?");
        $stmt1->bind_param('s', $id);
        $stmt1->execute();
        $eval_result = $stmt1->get_result();
        
        if ($eval_result->num_rows === 0) {
            sendError('Data evaluasi tidak ditemukan.', 404);
        }
        
        $eval_data = $eval_result->fetch_assoc();
        $stmt1->close();

        // Ambil data rekomendasi
        $stmt2 = $koneksi->prepare("SELECT txtRekomUtkPerbaikan 
                                   FROM tblpermasalahandansolusi 
                                   WHERE IdKKS = ?");
        $stmt2->bind_param('s', $eval_data['IdKKS']);
        $stmt2->execute();
        $rekom_result = $stmt2->get_result();
        $rekom_data = $rekom_result->fetch_assoc();
        $stmt2->close();

        $response = [
            'status' => 'success',
            'data' => [
                'evaluasi' => $eval_data,
                'rekomendasi' => $rekom_data['txtRekomUtkPerbaikan'] ?? '',
                'nama_kegiatan' => $eval_data['txtNamaKegiatanKS']
            ]
        ];
        echo json_encode($response);
    } catch (Exception $e) {
        sendError('Gagal mengambil data evaluasi: ' . $e->getMessage(), 500);
    }
}

function update_evaluation($koneksi) {
    // Validasi method request harus POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Method tidak diizinkan.', 405);
    }

    $id_eval = $_POST['IdEvKinerja'] ?? '';
    if (empty($id_eval)) {
        sendError('ID Evaluasi tidak valid.');
    }

    // Validasi input numerik
    $required_numeric_fields = [
        'txtSesuaiRencana', 
        'txtKualitasPelaks', 
        'txtKeterlibatanMtra', 
        'txtEfisiensiPenggSbDya', 
        'txtKepuasanPhkTerkait'
    ];
    
    foreach ($required_numeric_fields as $field) {
        if (!isset($_POST[$field]) || !is_numeric($_POST[$field])) {
            sendError("Field $field harus berupa angka.");
        }
    }

    $koneksi->begin_transaction();
    try {
        // Update tabel evaluasi
        $sql1 = "UPDATE tblevaluasikinerja 
                SET txtSesuaiRencana = ?, 
                    txtKualitasPelaks = ?, 
                    txtKeterlibatanMtra = ?, 
                    txtEfisiensiPenggSbDya = ?, 
                    txtKepuasanPhkTerkait = ? 
                WHERE IdEvKinerja = ?";
        $stmt1 = $koneksi->prepare($sql1);
        $stmt1->bind_param(
            'iiiiis',
            $_POST['txtSesuaiRencana'],
            $_POST['txtKualitasPelaks'],
            $_POST['txtKeterlibatanMtra'],
            $_POST['txtEfisiensiPenggSbDya'],
            $_POST['txtKepuasanPhkTerkait'],
            $id_eval
        );
        $stmt1->execute();
        
        if ($stmt1->affected_rows === 0) {
            throw new Exception("Tidak ada data yang diupdate pada tabel evaluasi.");
        }
        
        $stmt1->close();

        // Ambil IdKKS untuk update tabel rekomendasi
        $stmt_kks = $koneksi->prepare("SELECT IdKKS FROM tblevaluasikinerja WHERE IdEvKinerja = ?");
        $stmt_kks->bind_param('s', $id_eval);
        $stmt_kks->execute();
        $id_kks_res = $stmt_kks->get_result();
        
        if ($id_kks_res->num_rows === 0) {
            throw new Exception("ID KKS tidak ditemukan untuk evaluasi ini.");
        }
        
        $id_kks = $id_kks_res->fetch_assoc()['IdKKS'];
        $stmt_kks->close();

        // Update tabel masalah dan solusi (rekomendasi)
        $rekomendasi = sanitizeInput($_POST['txtRekomUtkPerbaikan'] ?? '');
        $sql2 = "UPDATE tblpermasalahandansolusi 
                SET txtRekomUtkPerbaikan = ? 
                WHERE IdKKS = ?";
        $stmt2 = $koneksi->prepare($sql2);
        $stmt2->bind_param('ss', $rekomendasi, $id_kks);
        $stmt2->execute();
        
        if ($stmt2->affected_rows === 0) {
            // Tidak error karena mungkin belum ada record rekomendasi
            // Bisa ditambahkan log jika diperlukan
        }
        
        $stmt2->close();

        $koneksi->commit();
        echo json_encode([
            'status' => 'success', 
            'message' => 'Data berhasil diperbarui.'
        ]);
    } catch (Exception $e) {
        $koneksi->rollback();
        sendError('Gagal memperbarui data: ' . $e->getMessage(), 500);
    }
}

function get_radar_chart_data($koneksi) {
    try {
        $sql = "SELECT 
                    k.IdKKS,
                    k.txtNamaKegiatanKS as name,
                    AVG(e.txtSesuaiRencana) as skor_rencana,
                    AVG(e.txtKualitasPelaks) as skor_kualitas,
                    AVG(e.txtKeterlibatanMtra) as skor_mitra,
                    AVG(e.txtEfisiensiPenggSbDya) as skor_efisiensi,
                    AVG(e.txtKepuasanPhkTerkait) as skor_kepuasan
                FROM tblevaluasikinerja e
                JOIN tblnamakegiatanks k ON e.IdKKS = k.IdKKS
                GROUP BY k.IdKKS
                ORDER BY k.dtMOU DESC
                LIMIT 5";

        $result = $koneksi->query($sql);

        if (!$result) {
            throw new Exception("Query gagal: " . $koneksi->error);
        }

        if ($result->num_rows === 0) {
            sendError('Tidak ada data evaluasi yang ditemukan.', 404);
        }

        $programs = [];
        while ($row = $result->fetch_assoc()) {
            $programs[] = [
                'name' => $row['name'],
                'scores' => [
                    (float)$row['skor_rencana'],
                    (float)$row['skor_kualitas'],
                    (float)$row['skor_mitra'],
                    (float)$row['skor_efisiensi'],
                    (float)$row['skor_kepuasan']
                ]
            ];
        }

        // Warna untuk chart
        $colors = [
            '255, 99, 132',   // Merah
            '54, 162, 235',    // Biru
            '255, 206, 86',    // Kuning
            '75, 192, 192',   // Teal
            '153, 102, 255'    // Ungu
        ];

        $labels = [
            'Kesesuaian Rencana',
            'Kualitas Pelaksanaan',
            'Keterlibatan Mitra',
            'Efisiensi Sumber Daya',
            'Kepuasan Pihak Terkait'
        ];

        echo json_encode([
            'status' => 'success',
            'data' => [
                'programs' => $programs,
                'colors' => $colors,
                'labels' => $labels
            ]
        ]);
    } catch (Exception $e) {
        sendError('Gagal mengambil data chart: ' . $e->getMessage(), 500);
    }
}