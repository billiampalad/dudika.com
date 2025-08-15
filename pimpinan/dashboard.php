<?php

// FUNGSI UNTUK MENJALANKAN QUERY DAN MENGAMBIL SATU NILAI
function getSingleValue($koneksi, $sql, $default = 0)
{
    $result = $koneksi->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0] ?? $default;
    }
    return $default;
}

// FUNGSI UNTUK MENJALANKAN QUERY DAN MENGAMBIL BANYAK BARIS
function getMultipleRows($koneksi, $sql)
{
    $result = $koneksi->query($sql);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// -- 1. DATA UNTUK STATS CARDS --
$sql_kerjasama_aktif = "SELECT COUNT(*) FROM tblnamakegiatanks WHERE CURDATE() BETWEEN dtMulaiPelaksanaan AND dtSelesaiPelaksanaan";
$total_kerjasama_aktif = getSingleValue($koneksi, $sql_kerjasama_aktif);

$sql_jumlah_mitra = "SELECT COUNT(*) FROM tblmitradudika";
$jumlah_mitra = getSingleValue($koneksi, $sql_jumlah_mitra);

$sql_rata_rata_skor = "SELECT AVG((txtSesuaiRencana + txtKualitasPelaks + txtKeterlibatanMtra + txtEfisiensiPenggSbDya + txtKepuasanPhkTerkait) / 5) FROM tblevaluasikinerja";
$rata_rata_skor_evaluasi = getSingleValue($koneksi, $sql_rata_rata_skor, 0);

$sql_mou_kadaluarsa = "SELECT COUNT(*) FROM tblnamakegiatanks WHERE dtSelesaiPelaksanaan BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 YEAR)";
$mou_akan_kadaluarsa = getSingleValue($koneksi, $sql_mou_kadaluarsa);

// -- 2. DATA UNTUK FEED --
$sql_aktivitas_terbaru = "SELECT TNK.txtNamaKegiatanKS, TMD.txtNamaMitraDudika FROM tblnamakegiatanks TNK JOIN tblmitradudika TMD ON TNK.IdMitraDudika = TMD.IdMitraDudika ORDER BY TNK.dtMulaiPelaksanaan DESC LIMIT 2";
$aktivitas_terbaru = getMultipleRows($koneksi, $sql_aktivitas_terbaru);

$sql_notifikasi_mou = "SELECT TMD.txtNamaMitraDudika, TNK.dtSelesaiPelaksanaan, DATEDIFF(TNK.dtSelesaiPelaksanaan, CURDATE()) as sisa_hari FROM tblnamakegiatanks TNK JOIN tblmitradudika TMD ON TNK.IdMitraDudika = TMD.IdMitraDudika WHERE TNK.dtSelesaiPelaksanaan BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 YEAR) ORDER BY TNK.dtSelesaiPelaksanaan ASC LIMIT 2";
$notifikasi_mou = getMultipleRows($koneksi, $sql_notifikasi_mou);


// -- 3. DATA UNTUK CHART --
// Trend Kerjasama (Line Chart) - DIPERBAIKI
$sql_trend = "SELECT DATE_FORMAT(dtMulaiPelaksanaan, '%b %Y') as bulan, COUNT(*) as jumlah 
              FROM tblnamakegiatanks 
              WHERE dtMulaiPelaksanaan >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
              GROUP BY DATE_FORMAT(dtMulaiPelaksanaan, '%Y-%m'), DATE_FORMAT(dtMulaiPelaksanaan, '%b %Y')
              ORDER BY DATE_FORMAT(dtMulaiPelaksanaan, '%Y-%m') ASC";
$trend_kerjasama = getMultipleRows($koneksi, $sql_trend);

// Distribusi Jenis Kerjasama (Pie Chart)
$sql_distribusi = "SELECT TJK.txtNamaJenisKS, COUNT(TNK.IdKKS) as jumlah FROM tblnamakegiatanks TNK JOIN tbljenisks TJK ON TNK.IdJenisKS = TJK.IdJenisKS GROUP BY TJK.txtNamaJenisKS";
$distribusi_jenis = getMultipleRows($koneksi, $sql_distribusi);

// Top 5 Mitra (Bar Chart)
$sql_top_mitra = "SELECT TMD.txtNamaMitraDudika, COUNT(TNK.IdKKS) as jumlah FROM tblnamakegiatanks TNK JOIN tblmitradudika TMD ON TNK.IdMitraDudika = TMD.IdMitraDudika GROUP BY TMD.txtNamaMitraDudika ORDER BY jumlah DESC LIMIT 5";
$top_5_mitra = getMultipleRows($koneksi, $sql_top_mitra);

// Menutup koneksi setelah semua query selesai
$koneksi->close();

// Mengubah data PHP menjadi format JSON agar bisa dibaca JavaScript
$json_trend_kerjasama = json_encode($trend_kerjasama);
$json_distribusi_jenis = json_encode($distribusi_jenis);
$json_top_5_mitra = json_encode($top_5_mitra);
?>

<main>
    <div class="space-y-8">
        <header class="bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800 tracking-tight text-center sm:text-left">Dashboard Overview</h2>
                <p class="text-sm text-gray-500 mt-1">Ringkasan statistik dan aktivitas terbaru</p>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <div class="relative inline-block text-left group">
                    <!-- Tombol Tambah Data -->
                    <button
                        id="addDataBtn"
                        class="btn-primary bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-all flex items-center space-x-2 shadow hover:shadow-md active:scale-[0.98] w-full sm:w-auto justify-center">
                        <i class="fas fa-eye mr-2 text-sm"></i>
                        Lihat Data
                    </button>

                    <!-- Dropdown menu saat hover -->
                    <div
                        class="absolute mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                        <a href="http://localhost/wd4/pimpinan.php?page=mitra" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Tambah Mitra Kerjasama</a>
                        <a href="http://localhost/wd4/pimpinan.php?page=jenis_kerjasama" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Tambah Jenis Kerjasama</a>
                        <a href="http://localhost/wd4/pimpinan.php?page=jenis_kerjasama" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Tambah Unit Pelaksana</a>
                        <a href="http://localhost/wd4/pimpinan.php?page=program_kerjasama" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Tambah Program Kerjasama</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="stats-card group p-5 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 bg-gradient-to-br from-blue-500 to-blue-600">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-11 h-11 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm shadow-inner">
                        <i class="fas fa-tasks text-lg text-white"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-white"><?= htmlspecialchars($total_kerjasama_aktif); ?></p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-white text-sm mb-1">Total Kerjasama Aktif</p>
                    <p class="text-xs text-white/90 font-medium">Berdasarkan periode pelaksanaan</p>
                </div>
            </div>

            <div class="stats-card group p-5 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 bg-gradient-to-br from-emerald-500 to-teal-600">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-11 h-11 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm shadow-inner">
                        <i class="fas fa-building text-lg text-white"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-white"><?= htmlspecialchars($jumlah_mitra); ?></p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-white text-sm mb-1">Jumlah Mitra</p>
                    <p class="text-xs text-white/90 font-medium">Total mitra terdaftar</p>
                </div>
            </div>

            <div class="stats-card group p-5 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 bg-gradient-to-br from-violet-500 to-purple-600">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-11 h-11 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm shadow-inner">
                        <i class="fas fa-star-half-alt text-lg text-white"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-white"><?= number_format($rata_rata_skor_evaluasi, 1); ?><span class="text-base font-semibold text-white/90">/5</span></p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-white text-sm mb-1">Rata-rata Skor Evaluasi</p>
                    <p class="text-xs text-white/90 font-medium">Kualitas pelaksanaan kerjasama</p>
                </div>
            </div>

            <div class="stats-card group p-5 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 bg-gradient-to-br from-orange-500 to-red-600">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-11 h-11 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm shadow-inner">
                        <i class="fas fa-exclamation-triangle text-lg text-white"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-white"><?= htmlspecialchars($mou_akan_kadaluarsa); ?></p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-white text-sm mb-1">MOU Akan Kadaluarsa</p>
                    <p class="text-xs text-white/90 font-medium">Dalam 1 tahun ke depan</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-md">
                    <h2 class="font-semibold text-gray-800 mb-4">Trend Kerjasama (Per Bulan)</h2>
                    <div class="h-64">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-md">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Distribusi Jenis Kerjasama</h3>
                        <div class="h-60 flex items-center justify-center">
                            <canvas id="distribusiChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-md">
                        <h3 class="text-base font-semibold text-gray-800 mb-4">Top 5 Mitra Kerjasama</h3>
                        <div class="h-60">
                            <canvas id="topMitraChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-800">Aktivitas Terbaru</h3>
                        <a href="#" class="text-blue-500 hover:text-blue-700 text-sm font-medium">Lihat Semua</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($aktivitas_terbaru)): ?>
                            <p class="text-sm text-gray-500 p-3 text-center">Tidak ada aktivitas terbaru.</p>
                        <?php else: ?>
                            <?php foreach ($aktivitas_terbaru as $aktivitas): ?>
                                <div class="group flex items-start space-x-3 p-2.5 rounded-lg transition-all hover:bg-gray-50">
                                    <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center text-blue-500 mt-0.5 flex-shrink-0">
                                        <i class="fas fa-plus text-xs"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800 text-sm leading-tight"><?= htmlspecialchars($aktivitas['txtNamaKegiatanKS']); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">dengan <span class="font-semibold text-gray-600"><?= htmlspecialchars($aktivitas['txtNamaMitraDudika']); ?></span></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 shadow-sm border-l-4 border-amber-400">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-800">Notifikasi MOU</h3>
                        <a href="#" class="text-amber-500 hover:text-amber-700 text-sm font-medium">Tindakan</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($notifikasi_mou)): ?>
                            <p class="text-sm text-gray-500 p-3 text-center">Tidak ada MOU yang akan berakhir.</p>
                        <?php else: ?>
                            <?php foreach ($notifikasi_mou as $notif): ?>
                                <div class="flex items-start space-x-3 p-2 rounded-lg bg-amber-50/50">
                                    <i class="fas fa-file-signature text-amber-500 mt-1.5 text-lg"></i>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($notif['txtNamaMitraDudika']); ?></p>
                                        <p class="text-xs text-gray-500 mt-1">Berakhir: <span class="font-bold text-red-600"><?= date('d M Y', strtotime($notif['dtSelesaiPelaksanaan'])); ?></span> • <span class="text-amber-600 font-semibold"><?= htmlspecialchars($notif['sisa_hari']); ?> hari lagi</span></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mengambil data dari PHP
        const trendData = <?= $json_trend_kerjasama; ?>;
        const distribusiData = <?= $json_distribusi_jenis; ?>;
        const topMitraData = <?= $json_top_5_mitra; ?>;

        // --- 1. Line Chart: Trend Kerjasama ---
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendLabels = trendData.map(item => item.bulan);
        const trendValues = trendData.map(item => item.jumlah);

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Jumlah Kerjasama Baru',
                    data: trendValues,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // --- 2. Pie Chart: Distribusi Jenis Kerjasama ---
        const distribusiCtx = document.getElementById('distribusiChart').getContext('2d');
        const distribusiLabels = distribusiData.map(item => item.txtNamaJenisKS);
        const distribusiValues = distribusiData.map(item => item.jumlah);

        new Chart(distribusiCtx, {
            type: 'pie',
            data: {
                labels: distribusiLabels,
                datasets: [{
                    label: 'Distribusi Kerjasama',
                    data: distribusiValues,
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(249, 115, 22)',
                        'rgb(139, 92, 246)',
                        'rgb(239, 68, 68)'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // --- 3. Bar Chart: Top 5 Mitra ---
        const topMitraCtx = document.getElementById('topMitraChart').getContext('2d');
        const topMitraLabels = topMitraData.map(item => item.txtNamaMitraDudika);
        const topMitraValues = topMitraData.map(item => item.jumlah);

        new Chart(topMitraCtx, {
            type: 'bar',
            data: {
                labels: topMitraLabels,
                datasets: [{
                    label: 'Jumlah Kerjasama',
                    data: topMitraValues,
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Membuat bar menjadi horizontal
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>