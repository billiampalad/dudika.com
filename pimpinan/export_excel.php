<?php
// Aktifkan error reporting untuk debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Tangkap error dan output buffer
ob_start();

try {
    require_once '../config/koneksi.php';
    
    // Router sederhana untuk aksi export
    $action = $_REQUEST['action'] ?? '';
    switch ($action) {
        case 'export_mitra':
            export_mitra($koneksi);
            break;
        case 'export_jenis_ks':
            export_jenis_ks($koneksi);
            break;
        case 'export_unit_pelaksana':
            export_unit_pelaksana($koneksi);
            break;
        case 'export_program_kerjasam':
            export_unit_pelaksana($koneksi);
            break;
        case 'export_hasil_capaian':
            export_hasil_capaian($koneksi);
            break;
        case 'export_evaluasi_kinerja':
            export_evaluasi_kinerja($koneksi);
            break;
        case 'export_permasalahan_solusi':
            export_permasalahan_solusi($koneksi);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Aksi export tidak valid.']);
    }
    $koneksi->close();
} catch (Exception $e) {
    // Bersihkan output buffer jika ada error
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

// Pastikan hanya output yang diinginkan yang dikirim
ob_end_flush();

// --- FUNGSI-FUNGSI EXPORT ---

function export_mitra($koneksi) {
    $sql = "SELECT IdMitraDudika, txtNamaMitraDudika, txtAlamatMitra, txtEmailMitra, txtNamaKepalaDudika FROM tblmitradudika ORDER BY txtNamaMitraDudika ASC";
    $result = $koneksi->query($sql);
    
    $filename = "Data_Mitra_Kerjasama_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM untuk UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['ID Mitra', 'Nama Mitra', 'Alamat', 'Email', 'Nama Kepala Dudika']);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['IdMitraDudika'],
            $row['txtNamaMitraDudika'],
            $row['txtAlamatMitra'],
            $row['txtEmailMitra'],
            $row['txtNamaKepalaDudika']
        ]);
    }
    
    fclose($output);
    exit;
}

function export_jenis_ks($koneksi) {
    $sql = "SELECT IdJenisKS, txtNamaJenisKS FROM tbljenisks ORDER BY IdJenisKS ASC";
    $result = $koneksi->query($sql);
    
    $filename = "Data_Jenis_Kerjasama_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM untuk UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['ID Jenis KS', 'Nama Jenis Kerjasama']);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['IdJenisKS'],
            $row['txtNamaJenisKS']
        ]);
    }
    
    fclose($output);
    exit;
}

function export_unit_pelaksana($koneksi) {
    $sql = "SELECT IdUnitPelaksana, txtNamaUnitPelaksPolimdo, txtNamaStafAdminUnit FROM tblunitpelaksana ORDER BY IdUnitPelaksana ASC";
    $result = $koneksi->query($sql);
    
    $filename = "Data_Unit_Pelaksana_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM untuk UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['ID Unit', 'Nama Unit Pelaksana', 'Staf Admin']);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['IdUnitPelaksana'],
            $row['txtNamaUnitPelaksPolimdo'],
            $row['txtNamaStafAdminUnit']
        ]);
    }
    
    fclose($output);
    exit;
}

function export_hasil_capaian($koneksi) {
    $sql = "SELECT hc.idHslDanCap, k.txtNamaKegiatanKS, hc.txtHasilLangsung, hc.txtDampakJangkaMenengah, 
            hc.txtManfaatBgMhsw, hc.txtManfaatBgPolimdo, hc.txtManfaatBgDudika
            FROM tblhasildancapaian hc
            JOIN tblnamakegiatanks k ON hc.IdKKS = k.IdKKS
            ORDER BY k.dtMOU DESC";
    $result = $koneksi->query($sql);
    
    $filename = "Data_Hasil_Capaian_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM untuk UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['ID Hasil Capaian', 'Nama Kegiatan', 'Hasil Langsung', 'Dampak Jangka Menengah', 
                      'Manfaat Bagi Mahasiswa', 'Manfaat Bagi Polimdo', 'Manfaat Bagi DUDIKA']);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['idHslDanCap'],
            $row['txtNamaKegiatanKS'],
            $row['txtHasilLangsung'],
            $row['txtDampakJangkaMenengah'],
            $row['txtManfaatBgMhsw'],
            $row['txtManfaatBgPolimdo'],
            $row['txtManfaatBgDudika']
        ]);
    }
    
    fclose($output);
    exit;
}

function export_evaluasi_kinerja($koneksi) {
    $sql = "SELECT e.IdEvKinerja, k.txtNamaKegiatanKS, e.txtSesuaiRencana, e.txtKualitasPelaks, 
            e.txtKeterlibatanMtra, e.txtEfisiensiPenggSbDya, e.txtKepuasanPhkTerkait
            FROM tblevaluasikinerja e
            JOIN tblnamakegiatanks k ON e.IdKKS = k.IdKKS
            ORDER BY k.dtMOU DESC";
    $result = $koneksi->query($sql);
    
    $filename = "Data_Evaluasi_Kinerja_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM untuk UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['ID Evaluasi', 'Nama Kegiatan', 'Kesesuaian Rencana', 'Kualitas Pelaksanaan', 
                      'Keterlibatan Mitra', 'Efisiensi Sumber Daya', 'Kepuasan Pihak Terkait']);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['IdEvKinerja'],
            $row['txtNamaKegiatanKS'],
            $row['txtSesuaiRencana'],
            $row['txtKualitasPelaks'],
            $row['txtKeterlibatanMtra'],
            $row['txtEfisiensiPenggSbDya'],
            $row['txtKepuasanPhkTerkait']
        ]);
    }
    
    fclose($output);
    exit;
}

function export_permasalahan_solusi($koneksi) {
    $sql = "SELECT p.IdMslhDanSolusi, k.txtNamaKegiatanKS, p.txtKendala, p.txtUpayaUtkAtasiMslh, 
            p.txtRekomUtkPerbaikan, p.urgensi, p.status
            FROM tblpermasalahandansolusi p
            JOIN tblnamakegiatanks k ON p.IdKKS = k.IdKKS
            ORDER BY FIELD(p.status, 'diproses', 'selesai'), FIELD(p.urgensi, 'tinggi', 'sedang', 'rendah')";
    $result = $koneksi->query($sql);
    
    $filename = "Data_Permasalahan_Solusi_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM untuk UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['ID Permasalahan', 'Nama Kegiatan', 'Kendala', 'Upaya Penyelesaian', 
                      'Rekomendasi Perbaikan', 'Urgensi', 'Status']);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['IdMslhDanSolusi'],
            $row['txtNamaKegiatanKS'],
            $row['txtKendala'],
            $row['txtUpayaUtkAtasiMslh'],
            $row['txtRekomUtkPerbaikan'],
            ucfirst($row['urgensi']),
            ucfirst($row['status'])
        ]);
    }
    
    fclose($output);
    exit;
}
?> 