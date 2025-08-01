<?php
session_start();
include __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'nama' => 'Pimpinan', // Bisa diambil dari database tblusers jika ada
        'role' => 'Administrator',
        'foto' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face'
    ];
}
$user = $_SESSION['user'];

$query_notifikasi = "SELECT ps.txtKendala, nk.txtNamaKegiatanKS
                    FROM tblpermasalahandansolusi ps
                    JOIN tblnamakegiatanks nk ON ps.IdKKS = nk.IdKKS
                    ORDER BY ps.IdMslhDanSolusi DESC
                    LIMIT 3";
$result_notifikasi = mysqli_query($koneksi, $query_notifikasi);
$notifikasi_list = [];
if ($result_notifikasi) {
    while ($row = mysqli_fetch_assoc($result_notifikasi)) {
        $notifikasi_list[] = $row;
    }
}

$notif_count = count($notifikasi_list); // Jumlah notifikasi sesuai data yang diambil

// --- Logika Navigasi (Tidak diubah) ---
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$valid_pages = ['dashboard', 'mitra', 'jenis_kerjasama', 'unit_pelaksana', 'program_kerjasama', 'jurusan', 'hasil_capaian', 'evaluasi_kinerja', 'permasalahan_solusi'];
if (!in_array($current_page, $valid_pages)) {
    $current_page = 'dashboard';
}

$data_master_pages = ['mitra', 'jenis_kerjasama', 'unit_pelaksana'];
$is_data_master_page_active = in_array($current_page, $data_master_pages);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DUDIKA - Sistem Informasi Kerjasama Polimdo & DUDIKA</title>

    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="asset/pimpinan/css/style.css">
</head>

<body>
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <nav class="fixed nav-gradient w-full top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <button id="sidebarToggle" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-colors hover:bg-gray-200/60 dark:hover:bg-slate-700/60 lg:hidden">
                        <i class="fa-solid fa-bars-staggered text-gray-600 dark:text-slate-300 text-sm sm:text-lg"></i>
                    </button>
                    <div class="logo-container background-icon w-8 h-8 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center">
                        <img src="asset/logo.png" alt="Logo" class="w-6 h-6 sm:w-8 sm:h-8">
                    </div>
                    <div class="hidden sm:block">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-800">POLIMDO & DUDIKA</h1>
                        <p class="text-xs text-gray-600 hidden md:block">Sistem Informasi Kerjasama</p>
                    </div>
                    <div class="block sm:hidden">
                        <h1 class="text-sm font-bold text-gray-800">POLIMDO</h1>
                    </div>
                </div>

                <div class="flex items-center space-x-1 sm:space-x-3">
                    <button id="darkModeToggle" class="nav-button w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center">
                        <i class="fas fa-moon text-gray-600 text-sm sm:text-base"></i>
                    </button>
                    
                    <div class="relative">
                        <button id="notificationBtn" class="nav-button w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center relative">
                            <i class="fas fa-bell text-gray-600 text-sm sm:text-base"></i>
                            <span class="notification-badge absolute -top-1 -right-1 w-4 h-4 sm:w-5 sm:h-5 rounded-full text-xs text-white flex items-center justify-center font-bold">
                                3
                            </span>
                        </button>
                        
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-2xl shadow-xl py-2 z-50 dropdown-animation">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-800">Notifikasi</h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                            <i class="fas fa-handshake text-white text-xs"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-800">Kerjasama Baru</p>
                                            <p class="text-xs text-gray-600">PT ABC mengajukan proposal</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-teal-500 rounded-full flex items-center justify-center">
                                            <i class="fas fa-check text-white text-xs"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-800">Evaluasi Selesai</p>
                                            <p class="text-xs text-gray-600">Evaluasi Q1 telah selesai</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-full flex items-center justify-center">
                                            <i class="fas fa-exclamation text-white text-xs"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-800">Deadline Mendekat</p>
                                            <p class="text-xs text-gray-600">Laporan harus diserahkan besok</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <div class="profile-container">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop&crop=face" 
                                alt="Profile" class="profile-img w-8 h-8 sm:w-10 sm:h-10 rounded-full">
                        </div>
                        <div class="hidden md:block">
                            <span class="text-sm font-semibold text-gray-800">Pimpinan</span>
                            <p class="text-xs text-gray-600">Administrator</p>
                        </div>
                        <button onclick="logout()" class="nav-button w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-red-500 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt text-sm sm:text-base"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex pt-16 sm:pt-20">
        <aside id="sidebar" class="nav-gradient w-64 sm:w-70 fixed h-screen overflow-y-auto py-4 sm:py-6 shadow-xl z-40 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0">
            <div class="px-2 space-y-1 sm:space-y-3">
                <a href="?page=dashboard" class="menu-item submenu-item block w-full text-left px-3 sm:px-4 py-3 sm:py-1.5 rounded-xl flex items-center space-x-2 sm:space-x-3 <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                    <div class="background-icon w-6 h-6 sm:w-7 sm:h-7 flex items-center justify-center rounded-xl shadow-sm">
                        <i class="fas fa-tachometer-alt text-sm sm:text-base w-4 sm:w-5 text-center text-white group-hover:text-orange-500"></i>
                    </div>
                    <span class="font-medium text-sm">Dashboard</span>
                </a>

                <div class="relative">
                    <button id="dataMasterBtn" class="menu-item submenu-item w-full text-left px-3 sm:px-4 py-3 sm:py-1.5 rounded-xl flex items-center justify-between <?php echo $is_data_master_page_active ? 'active' : ''; ?>">
                        <div class="flex items-center space-x-2 sm:space-x-3">
                            <div class="flex items-center justify-center background-icon w-6 h-6 sm:w-7 sm:h-7 bg-white rounded-xl shadow-sm">
                                <i class="fas fa-database text-white text-sm sm:text-base w-4 sm:w-5 text-center"></i>
                            </div>
                            <span class="font-medium text-sm">Data Master</span>
                        </div>
                        <i id="dataMasterIcon" class="fas fa-chevron-down icon-rotate transition-transform text-sm <?php echo $is_data_master_page_active ? 'rotated' : ''; ?>"></i>
                    </button>
                    
                    <div id="dataMasterDropdown" class="mt-1 sm:mt-2 ml-3 sm:ml-4 space-y-1 <?php echo !$is_data_master_page_active ? 'hidden' : ''; ?>">
                        <a href="?page=mitra" class="menu-item submenu-item block pl-2 sm:pl-3 pr-3 sm:pr-4 py-3 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-800 flex items-center space-x-2 sm:space-x-3 <?php echo ($current_page == 'mitra') ? 'active' : ''; ?>">
                            <div class="w-6 sm:w-8 flex justify-center">
                                <i class="fas fa-building text-xs w-3 text-center"></i>
                            </div>
                            <span class="text-sm">Mitra Kerjasama</span>
                        </a>
                        <a href="?page=jenis_kerjasama" class="menu-item submenu-item block pl-2 sm:pl-3 pr-3 sm:pr-4 py-3 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-800 flex items-center space-x-2 sm:space-x-3 <?php echo ($current_page == 'jenis_kerjasama') ? 'active' : ''; ?>">
                            <div class="w-6 sm:w-8 flex justify-center">
                                <i class="fas fa-project-diagram text-xs w-3 text-center"></i>
                            </div>
                            <span class="text-sm">Jenis Kerjasama</span>
                        </a>
                        <a href="?page=unit_pelaksana" class="menu-item submenu-item block pl-2 sm:pl-3 pr-3 sm:pr-4 py-3 sm:py-1.5 rounded-lg text-gray-600 hover:text-gray-800 flex items-center space-x-2 sm:space-x-3 <?php echo ($current_page == 'unit_pelaksana') ? 'active' : ''; ?>">
                            <div class="w-6 sm:w-8 flex justify-center">
                                <i class="fas fa-users-cog text-xs w-3 text-center"></i>
                            </div>
                            <span class="text-sm">Unit Pelaksana</span>
                        </a>
                    </div>
                </div>

                <a href="?page=program_kerjasama" class="menu-item submenu-item block w-full text-left px-3 sm:px-4 py-3 sm:py-1.5 rounded-xl flex items-center space-x-2 sm:space-x-3 <?php echo ($current_page == 'program_kerjasama') ? 'active' : ''; ?>">
                    <div class="flex items-center justify-center background-icon w-6 h-6 sm:w-7 sm:h-7 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-handshake text-white text-sm sm:text-base w-4 sm:w-5 text-center"></i>
                    </div>
                    <span class="font-medium text-sm">Program Kerjasama</span>
                </a>

                <a href="?page=hasil_capaian" class="menu-item submenu-item block w-full text-left px-3 sm:px-4 py-3 sm:py-1.5 rounded-xl flex items-center space-x-2 sm:space-x-3 <?php echo ($current_page == 'hasil_capaian') ? 'active' : ''; ?>">
                    <div class="flex items-center justify-center background-icon w-6 h-6 sm:w-7 sm:h-7 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-chart-line text-white text-sm sm:text-base w-4 sm:w-5 text-center"></i>
                    </div>
                    <span class="font-medium text-sm">Hasil & Capaian</span>
                </a>

                <a href="?page=evaluasi_kinerja" class="menu-item submenu-item block w-full text-left px-3 sm:px-4 py-3 sm:py-1.5 rounded-xl flex items-center space-x-2 sm:space-x-3 <?php echo ($current_page == 'evaluasi_kinerja') ? 'active' : ''; ?>">
                    <div class="flex items-center justify-center background-icon w-6 h-6 sm:w-7 sm:h-7 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-star-half-alt text-white text-sm sm:text-base w-4 sm:w-5 text-center"></i>
                    </div>
                    <span class="font-medium text-sm">Evaluasi Kinerja</span>
                </a>

                <a href="?page=permasalahan_solusi" class="menu-item submenu-item block w-full text-left px-3 sm:px-4 py-3 sm:py-1.5 rounded-xl flex items-center space-x-2 sm:space-x-3 <?php echo ($current_page == 'permasalahan_solusi') ? 'active' : ''; ?>">
                    <div class="flex items-center justify-center background-icon w-6 h-6 sm:w-7 sm:h-7 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-tools text-white text-sm sm:text-base w-4 sm:w-5 text-center"></i>
                    </div>
                    <span class="font-medium text-sm">Solusi & Masalah</span>
                </a>
            </div>
        </aside>

        <main class="lg:ml-64 flex-1 transition-all duration-300 ease-in-out">
            <div class="rounded-lg shadow p-3 sm:p-4 lg:p-6 mx-2 sm:mx-3 lg:mx-0">
                <?php
                switch($current_page) {
                    case 'dashboard':
                        include 'pimpinan/dashboard.php';
                        break;
                    case 'mitra':
                        include 'pimpinan/mitra.php';
                        break;
                    case 'jenis_kerjasama':
                        include 'pimpinan/jenis_kerjasama.php';
                        break;
                    case 'unit_pelaksana':
                        include 'pimpinan/unit_pelaksana.php';
                        break;
                    case 'program_kerjasama':
                        include 'pimpinan/program_kerjasama.php';
                        break;
                    case 'hasil_capaian':
                        include 'pimpinan/hasil_capaian.php';
                        break;
                    case 'evaluasi_kinerja':
                        include 'pimpinan/evaluasi_kinerja.php';
                        break;
                    case 'permasalahan_solusi':
                        include 'pimpinan/permasalahan_solusi.php';
                        break;
                    default:
                        include 'pimpinan/dashboard.php';
                }
                ?>
            </div>
        </main>
    </div>

    <script src="asset/pimpinan/js/index.js"></script>
</body>
</html>