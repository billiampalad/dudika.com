<?php
header('Content-Type: application/json');
require_once '../config/koneksi.php';

// Router sederhana untuk aksi
$action = $_REQUEST['action'] ?? '';
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
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
}
$koneksi->close();

// --- FUNGSI-FUNGSI AKSI ---

function get_single_evaluation($koneksi)
{
    $id = $_GET['id'] ?? 0;
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan.']);
        exit;
    }

    // Ambil data evaluasi dan nama kegiatan
    $stmt1 = $koneksi->prepare("SELECT e.*, k.txtNamaKegiatanKS FROM tblevaluasikinerja e JOIN tblnamakegiatanks k ON e.IdKKS = k.IdKKS WHERE e.IdEvKinerja = ?");
    $stmt1->bind_param('s', $id);
    $stmt1->execute();
    $eval_result = $stmt1->get_result();
    if ($eval_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data evaluasi tidak ditemukan.']);
        exit;
    }
    $eval_data = $eval_result->fetch_assoc();
    $stmt1->close();

    // Ambil data rekomendasi
    $stmt2 = $koneksi->prepare("SELECT txtRekomUtkPerbaikan FROM tblpermasalahandansolusi WHERE IdKKS = ?");
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
}

function update_evaluation($koneksi)
{
    $id_eval = $_POST['IdEvKinerja'] ?? '';
    if (empty($id_eval)) {
        echo json_encode(['status' => 'error', 'message' => 'ID Evaluasi tidak valid.']);
        exit;
    }

    $koneksi->begin_transaction();
    try {
        // Update tabel evaluasi
        $sql1 = "UPDATE tblevaluasikinerja SET txtSesuaiRencana = ?, txtKualitasPelaks = ?, txtKeterlibatanMtra = ?, txtEfisiensiPenggSbDya = ?, txtKepuasanPhkTerkait = ? WHERE IdEvKinerja = ?";
        $stmt1 = $koneksi->prepare($sql1);
        $stmt1->bind_param(
            'iiiiiis',
            $_POST['txtSesuaiRencana'],
            $_POST['txtKualitasPelaks'],
            $_POST['txtKeterlibatanMtra'],
            $_POST['txtEfisiensiPenggSbDya'],
            $_POST['txtKepuasanPhkTerkait'],
            $id_eval
        );
        $stmt1->execute();
        $stmt1->close();

        // Ambil IdKKS untuk update tabel rekomendasi
        $id_kks_res = $koneksi->query("SELECT IdKKS FROM tblevaluasikinerja WHERE IdEvKinerja = '$id_eval'");
        $id_kks = $id_kks_res->fetch_assoc()['IdKKS'];

        // Update tabel masalah dan solusi (rekomendasi)
        $sql2 = "UPDATE tblpermasalahandansolusi SET txtRekomUtkPerbaikan = ? WHERE IdKKS = ?";
        $stmt2 = $koneksi->prepare($sql2);
        $stmt2->bind_param('ss', $_POST['txtRekomUtkPerbaikan'], $id_kks);
        $stmt2->execute();
        $stmt2->close();

        $koneksi->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diperbarui.']);
    } catch (mysqli_sql_exception $exception) {
        $koneksi->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $exception->getMessage()]);
    }
}

function get_radar_chart_data($koneksi)
{
    // Ambil 5 program kerjasama terbaru yang memiliki evaluasi
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

    if (!$result || $result->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tidak ada data evaluasi yang ditemukan.'
        ]);
        exit;
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
}
