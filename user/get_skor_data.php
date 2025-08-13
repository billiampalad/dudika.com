<?php
session_start();
header('Content-Type: application/json');

// Pastikan koneksi dan NIK tersedia
if (!isset($_SESSION['nik'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}
include '../config/koneksi.php'; // Sesuaikan dengan nama file koneksi Anda
$loggedInNik = $_SESSION['nik'];

// Query untuk mengambil semua skor dari riwayat evaluasi pengguna ini,
// diurutkan berdasarkan tanggal selesai kegiatan.
$sql = "
    SELECT 
        k.dtSelesaiPelaksanaan,
        (e.txtSesuaiRencana + e.txtKualitasPelaks + e.txtKeterlibatanMtra + e.txtEfisiensiPenggSbDya + e.txtKepuasanPhkTerkait) / 5 AS rata_rata_skor
    FROM 
        tblevaluasikinerja e
    JOIN 
        tblnamakegiatanks k ON e.IdKKS = k.IdKKS
    JOIN 
        tblmitradudika m ON k.IdMitraDudika = m.IdMitraDudika
    WHERE 
        m.nik = ?
    ORDER BY 
        k.dtSelesaiPelaksanaan ASC
";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $loggedInNik);
$stmt->execute();
$result = $stmt->get_result();

$labels = []; // Untuk menampung tanggal (misal: "Ags 2025")
$scores = []; // Untuk menampung skor rata-rata

while ($row = $result->fetch_assoc()) {
    // Format tanggal menjadi "Bulan Tahun" untuk label grafik
    $labels[] = date('M Y', strtotime($row['dtSelesaiPelaksanaan']));
    // Tambahkan skor rata-rata ke array
    $scores[] = number_format($row['rata_rata_skor'], 2); // Format skor menjadi 2 desimal
}

$stmt->close();
$koneksi->close();

// Kembalikan data dalam format JSON
echo json_encode([
    'labels' => $labels,
    'scores' => $scores
]);
?>