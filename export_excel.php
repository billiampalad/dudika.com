<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Path ke file koneksi
require_once 'config/koneksi.php';

// Set header untuk file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Laporan_Pelaksanaan_Kegiatan.xls"');
header('Cache-Control: max-age=0');

// Query untuk mendapatkan data
$query = "SELECT
    nk.IdKKS,
    nk.txtNamaKegiatanKS,
    nk.dtMulaiPelaksanaan,
    nk.dtSelesaiPelaksanaan,
    SUM(p.intJumlahPeserta) as jumlah_peserta,
    p.txtDeskripsiKeg,
    p.txtCakupanDanSkalaKeg,
    p.txtSumberDaya,
    jk.txtNamaJenisKS
FROM tblnamakegiatanks nk
LEFT JOIN tblpelaksanaankeg p ON nk.IdKKS = p.IdKKS
LEFT JOIN tbljenisks jk ON nk.IdJenisKS = jk.IdJenisKS
GROUP BY nk.IdKKS
ORDER BY nk.dtMulaiPelaksanaan DESC;";

$result = mysqli_query($koneksi, $query);

// Format tanggal
function formatExcelDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Output Excel content
echo "<table border='1'>";
echo "<tr style='background-color: #f2f2f2; font-weight: bold;'>";
echo "<th>No</th>";
echo "<th>Nama Kegiatan</th>";
echo "<th>Jenis Kegiatan</th>";
echo "<th>Periode Pelaksanaan</th>";
echo "<th>Jumlah Peserta</th>";
echo "<th>Deskripsi Kegiatan</th>";
echo "<th>Cakupan & Skala</th>";
echo "<th>Sumber Daya</th>";
echo "</tr>";

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . htmlspecialchars($row['txtNamaKegiatanKS']) . "</td>";
    echo "<td>" . htmlspecialchars($row['txtNamaJenisKS']) . "</td>";
    echo "<td>" . formatExcelDate($row['dtMulaiPelaksanaan']) . " - " . formatExcelDate($row['dtSelesaiPelaksanaan']) . "</td>";
    echo "<td>" . ($row['jumlah_peserta'] ?? 0) . "</td>";
    echo "<td>" . htmlspecialchars($row['txtDeskripsiKeg'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['txtCakupanDanSkalaKeg'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['txtSumberDaya'] ?? '-') . "</td>";
    echo "</tr>";
}

echo "</table>";
?>