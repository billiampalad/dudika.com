<?php
// Tampilkan semua error PHP untuk membantu menemukan masalah
error_reporting(E_ALL);
ini_set('display_errors', 1);

class DashboardMitraController
{
    private $koneksi;

    public function __construct($koneksi)
    {
        $this->koneksi = $koneksi;
    }

    public function getDashboardData($nik)
    {
        try {
            // 1. Ambil data mitra
            $mitra = $this->getMitraData($nik);

            if (!$mitra) {
                return $this->getDefaultData("Data mitra tidak ditemukan untuk NIK tersebut.");
            }

            // 2. Ambil semua kegiatan terkait
            $kegiatan = $this->getKegiatanMitra($mitra['IdMitraDudika']);

            // 3. Hitung statistik
            $stats = $this->calculateStats($kegiatan, $mitra['IdMitraDudika']);

            // 4. Ambil aktivitas terkini
            $aktivitas = $this->getAktivitasTerkini($mitra['IdMitraDudika']);

            // 5. Ambil MOU yang akan berakhir
            $mouAkanBerakhir = $this->getMouAkanBerakhir($mitra['IdMitraDudika']);

            // 6. Kembalikan semua data yang sudah terkumpul
            return [
                'mitra' => $mitra,
                'stats' => $stats,
                'kegiatan' => $kegiatan,
                'aktivitas' => $aktivitas,
                'mouAkanBerakhir' => $mouAkanBerakhir
            ];
        } catch (Exception $e) {
            error_log("Error in getDashboardData: " . $e->getMessage());
            return $this->getDefaultData("Terjadi kesalahan sistem");
        }
    }

    private function getMitraData($nik)
    {
        try {
            $stmt = $this->koneksi->prepare("
                SELECT m.IdMitraDudika, m.txtNamaMitraDudika, m.txtAlamatMitra, 
                       m.txtEmailMitra, m.txtNamaKepalaDudika,
                       u.nama_lengkap, u.role
                FROM tblmitradudika m
                INNER JOIN tbluser u ON m.nik = u.nik
                WHERE u.nik = ? AND u.role = 'mitra'
                LIMIT 1 
            ");
            $stmt->bind_param("s", $nik);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        } catch (Exception $e) {
            error_log("Error in getMitraData: " . $e->getMessage());
            return false;
        }
    }

    private function getKegiatanMitra($idMitra)
    {
        try {
            $stmt = $this->koneksi->prepare("
                SELECT k.IdKKS, k.txtNamaKegiatanKS, k.dtMulaiPelaksanaan, k.dtSelesaiPelaksanaan,
                       k.txtNomorMOU,
                       COALESCE(j.txtNamaJenisKS, 'Belum Ditentukan') as txtNamaJenisKS,
                       TIMESTAMPDIFF(DAY, CURDATE(), k.dtSelesaiPelaksanaan) as hari_menuju_deadline,
                       (SELECT COALESCE(SUM(intJumlahPeserta), 0) FROM tblpelaksanaankeg p WHERE p.IdKKS = k.IdKKS) as total_peserta
                FROM tblnamakegiatanks k
                LEFT JOIN tbljenisks j ON k.IdJenisKS = j.IdJenisKS
                WHERE k.IdMitraDudika = ?
                ORDER BY k.dtSelesaiPelaksanaan DESC
            ");
            $stmt->bind_param("s", $idMitra); // FIX: Tipe data "s" untuk string
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } catch (Exception $e) {
            error_log("Error in getKegiatanMitra: " . $e->getMessage());
            return [];
        }
    }

    private function calculateStats($kegiatan, $idMitra)
    {
        try {
            $totalProyek = count($kegiatan);
            $totalPeserta = array_sum(array_column($kegiatan, 'total_peserta'));

            $stmt = $this->koneksi->prepare("
                SELECT AVG((COALESCE(e.txtSesuaiRencana, 0) + COALESCE(e.txtKualitasPelaks, 0) + COALESCE(e.txtKeterlibatanMtra, 0) + COALESCE(e.txtEfisiensiPenggSbDya, 0) + COALESCE(e.txtKepuasanPhkTerkait, 0)) / 5) as rata_rata
                FROM tblevaluasikinerja e
                INNER JOIN tblnamakegiatanks k ON e.IdKKS = k.IdKKS
                WHERE k.IdMitraDudika = ?
            ");
            $stmt->bind_param("s", $idMitra); // FIX: Tipe data "s" untuk string
            $stmt->execute();
            $result = $stmt->get_result();
            $evalData = $result->fetch_assoc();
            $rataEvaluasi = $evalData['rata_rata'] ?? 0;
            $stmt->close();

            return [
                'total_proyek' => $totalProyek,
                'total_peserta' => $totalPeserta,
                'rata_evaluasi' => $rataEvaluasi ? round($rataEvaluasi, 1) : 0
            ];
        } catch (Exception $e) {
            error_log("Error in calculateStats: " . $e->getMessage());
            return ['total_proyek' => 0, 'total_peserta' => 0, 'rata_evaluasi' => 0];
        }
    }

    private function getAktivitasTerkini($idMitra)
    {
        try {
            $stmt = $this->koneksi->prepare("
                (SELECT 'kegiatan' as tipe, IdKKS as id, txtNamaKegiatanKS as judul, dtMulaiPelaksanaan as tanggal, 'Kegiatan Dimulai' as keterangan
                 FROM tblnamakegiatanks 
                 WHERE IdMitraDudika = ? AND dtMulaiPelaksanaan >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))
                UNION
                (SELECT 'evaluasi' as tipe, e.IdEvKinerja as id, CONCAT('Evaluasi: ', COALESCE(k.txtNamaKegiatanKS, 'Kegiatan')) as judul, e.dtEvaluasi as tanggal, 'Evaluasi Selesai' as keterangan
                 FROM tblevaluasikinerja e
                 INNER JOIN tblnamakegiatanks k ON e.IdKKS = k.IdKKS
                 WHERE k.IdMitraDudika = ? AND e.dtEvaluasi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) 
                ORDER BY tanggal DESC
                LIMIT 5
            ");
            $stmt->bind_param("ss", $idMitra, $idMitra); // FIX: Tipe data "ss" untuk string
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        } catch (Exception $e) {
            error_log("Error in getAktivitasTerkini: " . $e->getMessage());
            return [];
        }
    }

    private function getMouAkanBerakhir($idMitra)
    {
        try {
            $stmt = $this->koneksi->prepare("
                SELECT IdKKS, txtNamaKegiatanKS, txtNomorMOU, dtSelesaiPelaksanaan,
                       TIMESTAMPDIFF(DAY, CURDATE(), dtSelesaiPelaksanaan) as hari_tersisa
                FROM tblnamakegiatanks
                WHERE IdMitraDudika = ? 
                AND dtSelesaiPelaksanaan BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                AND txtNomorMOU IS NOT NULL AND txtNomorMOU != ''
                ORDER BY dtSelesaiPelaksanaan ASC
                LIMIT 1
            ");
            $stmt->bind_param("s", $idMitra); // FIX: Tipe data "s" untuk string
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        } catch (Exception $e) {
            error_log("Error in getMouAkanBerakhir: " . $e->getMessage());
            return false;
        }
    }

    private function getDefaultData($message = 'Data Tidak Tersedia')
    {
        return [
            'mitra' => ['txtNamaMitraDudika' => $message, 'IdMitraDudika' => 0],
            'stats' => ['total_proyek' => 0, 'total_peserta' => 0, 'rata_evaluasi' => 0],
            'kegiatan' => [],
            'aktivitas' => [],
            'mouAkanBerakhir' => false
        ];
    }
}

// =========================================================================
// === EKSEKUSI SCRIPT UTAMA ===
// =========================================================================

// Validasi session NIK
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['nik']) || empty($_SESSION['nik'])) {
    echo '<div class="alert alert-danger">Session tidak valid. Silakan login kembali.</div>';
    return;
}

// Validasi koneksi database
// Diasumsikan variabel $koneksi sudah ada dari file yang meng-include file ini
if (!isset($koneksi) || !$koneksi) {
    echo '<div class="alert alert-danger">Koneksi database tidak tersedia.</div>';
    return;
}

// Inisialisasi controller
$controller = new DashboardMitraController($koneksi);

// Ambil data dari controller
$data = $controller->getDashboardData($_SESSION['nik']);

// =========================================================================
// === FUNGSI-FUNGSI HELPER UNTUK TAMPILAN ===
// =========================================================================

function formatTanggalIndo($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00' || $tanggal === '0000-00-00 00:00:00') {
        return 'Tidak diketahui';
    }
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    try {
        $timestamp = new DateTime($tanggal);
        return $timestamp->format('d') . ' ' . $bulan[(int)$timestamp->format('n')] . ' ' . $timestamp->format('Y');
    } catch (Exception $e) {
        return 'Format tanggal tidak valid';
    }
}

function formatBulanTahun($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00' || $tanggal === '0000-00-00 00:00:00') {
        return 'Tidak diketahui';
    }
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    try {
        $timestamp = new DateTime($tanggal);
        return $bulan[(int)$timestamp->format('n')] . ' ' . $timestamp->format('Y');
    } catch (Exception $e) {
        return 'Format tanggal tidak valid';
    }
}

function getProgressColor($progress)
{
    if ($progress >= 80) return 'bg-red-500';
    if ($progress >= 50) return 'bg-yellow-500';
    return 'bg-green-500';
}
?>

<div class="dashboard-container">
    <div class="mb-8 content-modern p-7">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-[var(--text-dark)] mb-1">Dashboard Mitra</h2>
                <p class="text-xs text-[var(--text-light)]">
                    Ringkasan aktivitas dan statistik kerjasama <?= htmlspecialchars($data['mitra']['txtNamaMitraDudika'] ?? 'Mitra') ?>
                </p>
            </div>
            <div class="mt-5 md:mt-0">
                <button onclick="window.location.href='user.php?page=pelaksana_kegiatan'"
                    class="text-sm px-3 py-2 bg-green-500 hover:bg-green-600 text-white border-green-600 focus:ring-green-300 rounded transition-colors">
                    <i class="fas fa-print mr-2 text-xs"></i>
                    Cetak Laporan
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-r from-red-600 to-red-400 text-white p-6 rounded-lg shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-white/20 rounded-lg">
                    <i class="fas fa-project-diagram text-xl"></i>
                </div>
                <div>
                    <p class="text-sm opacity-90">Proyek Aktif</p>
                    <p class="text-2xl font-bold mt-1"><?= $data['stats']['total_proyek'] ?></p>
                    <p class="text-xs opacity-80 mt-1">Total program kerjasama</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-600 to-green-400 text-white p-6 rounded-lg shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-white/20 rounded-lg">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div>
                    <p class="text-sm opacity-90">Nilai Rata-rata</p>
                    <p class="text-2xl font-bold mt-1"><?= $data['stats']['rata_evaluasi'] ?><span class="text-lg opacity-80">/5.0</span></p>
                    <p class="text-xs opacity-80 mt-1">Dari evaluasi kinerja</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-cyan-600 to-cyan-400 text-white p-6 rounded-lg shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-white/20 rounded-lg">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div>
                    <p class="text-sm opacity-90">Total Partisipan</p>
                    <p class="text-2xl font-bold mt-1"><?= number_format($data['stats']['total_peserta']) ?></p>
                    <p class="text-xs opacity-80 mt-1">Peserta dalam program</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-5">
        <div class="lg:col-span-2 space-y-8">
            <div class="card-modern p-6 border-l-4 border-green-500 rounded-lg">
                <h5 class="font-bold text-[var(--text-dark)] mb-5">Status Pelaksanaan Program</h5>
                <div class="space-y-6">
                    <?php if (empty($data['kegiatan'])): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-folder-open text-gray-400 text-3xl mb-3"></i>
                            <p class="text-[var(--text-light)]">Belum ada kegiatan yang terdaftar</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($data['kegiatan'], 0, 5) as $keg):
                            $today = new DateTime();
                            $deadline = new DateTime($keg['dtSelesaiPelaksanaan']);
                            $mulai = new DateTime($keg['dtMulaiPelaksanaan']);

                            // Perhitungan progress yang lebih akurat
                            if ($today < $mulai) {
                                $progress = 0;
                            } elseif ($today >= $deadline) {
                                $progress = 100;
                            } else {
                                $totalDuration = $mulai->diff($deadline)->days;
                                $passedDuration = $mulai->diff($today)->days;
                                $progress = $totalDuration > 0 ? min(100, ($passedDuration / $totalDuration) * 100) : 0;
                            }
                            
                            $colorClass = getProgressColor($progress);
                        ?>
                            <div class="border border-[var(--border)] rounded-lg p-4">
                                <div class="flex justify-between mb-2">
                                    <div>
                                        <span class="font-semibold text-[var(--text-dark)] text-sm">
                                            <?= htmlspecialchars($keg['txtNamaKegiatanKS'] ?? 'Kegiatan Tanpa Nama') ?>
                                        </span>
                                        <p class="text-xs text-[var(--text-light)] mt-1">
                                            <?= htmlspecialchars($keg['txtNamaJenisKS']) ?>
                                        </p>
                                    </div>
                                    <span class="text-sm font-medium text-[var(--text-dark)]"><?= round($progress) ?>%</span>
                                </div>
                                <div class="w-full bg-[var(--border)] rounded-full h-2.5 mb-2">
                                    <div class="<?= $colorClass ?> h-2.5 rounded-full transition-all duration-300" style="width: <?= $progress ?>%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-[var(--text-light)]">
                                    <span>Mulai: <?= formatTanggalIndo($keg['dtMulaiPelaksanaan']) ?></span>
                                    <span>Deadline: <?= formatTanggalIndo($keg['dtSelesaiPelaksanaan']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="card-modern p-6 border-l-4 border-cyan-500 rounded-lg">
                    <h4 class="text-center font-bold text-[var(--text-dark)] mb-4">Distribusi Kerjasama</h4>
                    <div class="h-48 flex flex-col items-center justify-center space-y-4">
                        <?php
                        $jenisCounts = [];
                        foreach ($data['kegiatan'] as $keg) {
                            $jenis = $keg['txtNamaJenisKS'] ?? 'Belum Ditentukan';
                            $jenisCounts[$jenis] = ($jenisCounts[$jenis] ?? 0) + 1;
                        }
                        $total = count($data['kegiatan']);
                        ?>

                        <?php if ($total > 0): ?>
                            <div class="relative w-32 h-32">
                                <?php
                                $colors = ['#FB4141', '#78C841', '#42a5f5', '#ff7043', '#8d6e63'];
                                $start = 0;
                                $gradientParts = [];
                                $i = 0;
                                foreach ($jenisCounts as $count) {
                                    $percent = ($count / $total) * 100;
                                    $gradientParts[] = $colors[$i % count($colors)] . ' ' . $start . '%, ' . $colors[$i % count($colors)] . ' ' . ($start + $percent) . '%';
                                    $start += $percent;
                                    $i++;
                                }
                                ?>
                                <div class="w-full h-full rounded-full" style="background: conic-gradient(<?= implode(', ', $gradientParts) ?>)"></div>
                                <div class="absolute inset-4 bg-[var(--surface)] rounded-full"></div>
                            </div>
                            <div class="flex flex-wrap justify-center gap-2 text-xs">
                                <?php
                                $i = 0;
                                foreach ($jenisCounts as $jenis => $count):
                                ?>
                                    <div class="flex items-center">
                                        <span class="w-3 h-3 rounded-full mr-2" style="background-color: <?= $colors[$i % count($colors)] ?>"></span>
                                        <span class="text-[var(--text-light)]"><?= htmlspecialchars($jenis) ?> (<?= $count ?>)</span>
                                    </div>
                                <?php
                                    $i++;
                                endforeach;
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <i class="fas fa-chart-pie text-gray-300 text-3xl mb-3"></i>
                                <p class="text-[var(--text-light)]">Belum ada data kerjasama</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-modern p-6 border-l-4 border-[var(--primary)] rounded-lg">
                    <h4 class="text-center font-bold text-[var(--text-dark)] mb-4">Kegiatan Mendatang</h4>
                    <div class="h-48 overflow-y-auto space-y-3">
                        <?php
                        $kegiatanMendatang = array_filter($data['kegiatan'], function ($keg) {
                            return (new DateTime($keg['dtMulaiPelaksanaan'])) > (new DateTime());
                        });
                        usort($kegiatanMendatang, function ($a, $b) {
                            return strtotime($a['dtMulaiPelaksanaan']) - strtotime($b['dtMulaiPelaksanaan']);
                        });
                        ?>

                        <?php if (empty($kegiatanMendatang)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-check text-green-500 text-2xl mb-2"></i>
                                <p class="text-sm text-[var(--text-light)]">Tidak ada kegiatan mendatang</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($kegiatanMendatang, 0, 4) as $keg):
                                $daysUntil = (new DateTime())->diff(new DateTime($keg['dtMulaiPelaksanaan']))->days;
                                $isUrgent = $daysUntil <= 7;
                            ?>
                                <div class="flex items-center p-3 <?= $isUrgent ? 'bg-orange-50 border border-orange-200' : 'bg-[var(--accent)] border border-[var(--border)]' ?> rounded-lg">
                                    <i class="fas <?= $isUrgent ? 'fa-exclamation-triangle text-orange-500' : 'fa-calendar text-[var(--secondary)]' ?> mr-3"></i>
                                    <div class="flex-1">
                                        <p class="font-medium text-xs text-[var(--text-dark)]">
                                            <?= htmlspecialchars($keg['txtNamaKegiatanKS'] ?? 'Kegiatan Tanpa Nama') ?>
                                        </p>
                                        <p class="text-xs text-[var(--text-light)]">
                                            <?= formatTanggalIndo($keg['dtMulaiPelaksanaan']) ?>
                                        </p>
                                        <p class="text-xs <?= $isUrgent ? 'text-orange-600 font-semibold' : 'text-[var(--text-light)]' ?>">
                                            <?= $daysUntil ?> hari lagi
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-8">
            <div class="card-modern p-6 border-l-4 border-cyan-500 rounded-lg">
                <h4 class="text-center font-bold text-[var(--text-dark)] mb-6">Aktivitas Terkini</h4>
                <div class="space-y-5">
                    <?php if (empty($data['aktivitas'])): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-clock text-gray-300 text-2xl mb-3"></i>
                            <p class="text-[var(--text-light)]">Belum ada aktivitas terkini</p>
                        </div>
                    <?php else: ?>
                        <div class="relative pl-5">
                            <div class="absolute left-2.5 top-2 bottom-2 w-0.5 bg-[var(--border)] rounded-full"></div>
                            <?php foreach ($data['aktivitas'] as $akt):
                                $icon = ($akt['tipe'] === 'kegiatan') ? 'fa-calendar-check' : 'fa-file-alt';
                                $color = ($akt['tipe'] === 'kegiatan') ? 'red' : 'green';
                            ?>
                                <div class="relative mb-5">
                                    <div class="absolute -left-[19px] top-0.5 w-6 h-6 bg-[var(--surface)] rounded-full flex items-center justify-center border border-[var(--border)]">
                                        <div class="w-4 h-4 bg-<?= $color ?>-500 rounded-full flex items-center justify-center text-white">
                                            <i class="fas <?= $icon ?> text-[8px]"></i>
                                        </div>
                                    </div>
                                    <div class="pl-4">
                                        <p class="font-semibold text-[var(--text-dark)] text-xs">
                                            <?= htmlspecialchars($akt['judul'] ?? 'Aktivitas Tanpa Judul') ?>
                                        </p>
                                        <p class="text-xs text-[var(--text-light)]">
                                            <?= formatTanggalIndo($akt['tanggal']) ?>
                                        </p>
                                        <?php if (isset($akt['keterangan'])): ?>
                                            <p class="text-xs text-<?= $color ?>-600 font-medium">
                                                <?= htmlspecialchars($akt['keterangan']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($data['mouAkanBerakhir']): ?>
                <div class="card-modern p-6 border-l-4 border-orange-500 rounded-lg">
                    <h4 class="text-center font-bold text-[var(--text-dark)] mb-5 text-sm">MOU Akan Berakhir</h4>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <i class="fas fa-file-contract text-orange-500 mt-1 text-lg"></i>
                            <div class="flex-1">
                                <p class="font-semibold text-xs text-[var(--text-dark)]">
                                    <?= htmlspecialchars($data['mouAkanBerakhir']['txtNamaKegiatanKS']) ?>
                                </p>
                                <p class="text-xs text-[var(--text-light)] mt-1">
                                    No: <?= htmlspecialchars($data['mouAkanBerakhir']['txtNomorMOU']) ?>
                                </p>
                                <p class="text-xs text-[var(--text-light)] mt-1">
                                    Berakhir pada: <?= formatTanggalIndo($data['mouAkanBerakhir']['dtSelesaiPelaksanaan']) ?>
                                </p>
                                <p class="text-sm font-bold text-orange-600 mt-2">
                                    <?= $data['mouAkanBerakhir']['hari_tersisa'] ?> hari lagi
                                </p>
                                <a href="?page=detail_kegiatan&id=<?= $data['mouAkanBerakhir']['IdKKS'] ?>" class="inline-block mt-3 text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-full hover:bg-orange-200 transition-colors">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>