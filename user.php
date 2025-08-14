<?php
session_start();
include __DIR__ . '/config/koneksi.php';

$user = null;
if (isset($_SESSION['nik'])) {
    $query = "SELECT nama_lengkap, role FROM tbluser WHERE nik = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("s", $_SESSION['nik']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Menangani navigasi
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$valid_pages = ['dashboard', 'program_kerjasama', 'pelaksana_kegiatan', 'evaluasi_kinerja', 'laporan'];
if (!in_array($current_page, $valid_pages)) {
    $current_page = 'dashboard';
}
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
    <link rel="stylesheet" href="asset/user/css/style.css">

</head>

<body class="overflow-hidden">
    <nav class="fixed w-full top-0 z-50 shadow-lg bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <!-- Logo & Title -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <!-- Mobile menu button -->
                    <button id="sidebarToggle" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fa-solid fa-bars-staggered text-base sm:text-lg"></i>
                    </button>

                    <!-- Logo -->
                    <div class="logo-modern p-1 sm:p-2">
                        <img src="asset/logo.png" alt="Logo" class="w-7 h-7 sm:w-8 sm:h-8">
                    </div>

                    <!-- Title -->
                    <div class="hidden sm:flex flex-col">
                        <h1 class="text-sm sm:text-lg md:text-xl font-bold bg-gradient-to-r from-red-500 to-green-500 bg-clip-text text-transparent leading-tight">
                            POLIMDO & DUDIKA
                        </h1>
                        <p class="text-xs text-gray-500 font-medium leading-tight">
                            Sistem Informasi Kerjasama
                        </p>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button id="darkModeToggle" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full hover:bg-gray-100 transition-colors flex items-center justify-center">
                        <i class="fas fa-moon text-gray-600 text-sm sm:text-base"></i>
                    </button>

                    <!-- User Info & Logout -->
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <!-- User Info - Always visible, optimized spacing -->
                        <div class="text-right">
                            <span class="text-xs sm:text-sm font-medium text-gray-800 leading-tight">Mitra Partner</span>
                            <p class="text-[11px] sm:text-xs leading-tight truncate max-w-[100px] sm:max-w-none px-2 py-1 rounded-full bg-cyan-100 text-green-800 dark:bg-green-500 dark:text-white hover:bg-green-500 dark:hover:bg-green-600 transition-colors">
                                <?php
                                if (isset($user['nama_lengkap']) && !empty($user['nama_lengkap'])) {
                                    echo htmlspecialchars($user['nama_lengkap']);
                                } else {
                                    echo 'Nama tidak tersedia';
                                }
                                ?>
                            </p>
                        </div>
                        <!-- Logout Button -->
                        <button onclick="logout()" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-red-500 hover:bg-red-50 transition-colors">
                            <i class="fas fa-sign-out-alt text-sm sm:text-base"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="pt-16 sm:pt-15 flex">
        <aside id="sidebar" class="fixed top-16 sm:top-20 left-0 z-40 w-50 sm:w-65 h-[calc(100vh-4rem)] sm:h-[calc(100vh-5rem)] bg-white shadow-xl overflow-y-auto transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0">
            <div class="px-5 space-y-3 pt-5">
                <a href="?page=dashboard" class="menu-item-modern flex items-start space-x-3 px-4 py-2 <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                    <div class="menu-icon w-8 h-8 flex items-center justify-center">
                        <i class="fas fa-chart-pie text-sm"></i>
                    </div>
                    <div>
                        <span class="font-semibold text-xs">Dashboard</span>
                        <p class="text-xs opacity-70">Overview & Analytics</p>
                    </div>
                </a>

                <a href="?page=program_kerjasama" class="menu-item-modern flex items-start space-x-3 px-4 py-2 <?php echo ($current_page == 'program_kerjasama') ? 'active' : ''; ?>">
                    <div class="menu-icon w-8 h-8 flex items-center justify-center">
                        <i class="fas fa-handshake text-sm"></i>
                    </div>
                    <div>
                        <span class="font-semibold text-xs">Program Kerjasama</span>
                        <p class="text-xs opacity-70">Partnership Programs</p>
                    </div>
                </a>

                <a href="?page=pelaksana_kegiatan" class="menu-item-modern flex items-start space-x-3 px-4 py-2 <?php echo ($current_page == 'pelaksana_kegiatan') ? 'active' : ''; ?>">
                    <div class="menu-icon w-8 h-8 flex items-center justify-center">
                        <i class="fas fa-tasks text-sm"></i>
                    </div>
                    <div>
                        <span class="font-semibold text-xs">Pelaksanaan Kegiatan</span>
                        <p class="text-xs opacity-70">Activity Implementation</p>
                    </div>
                </a>

                <a href="?page=evaluasi_kinerja" class="menu-item-modern flex items-start space-x-3 px-4 py-2 <?php echo ($current_page == 'evaluasi_kinerja') ? 'active' : ''; ?>">
                    <div class="menu-icon w-8 h-8 flex items-center justify-center">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    <div>
                        <span class="font-semibold text-xs">Evaluasi Kinerja</span>
                        <p class="text-xs opacity-70">Performance Evaluation</p>
                    </div>
                </a>

                <a href="?page=laporan" class="menu-item-modern flex items-start space-x-3 px-4 py-2 <?php echo ($current_page == 'laporan') ? 'active' : ''; ?>">
                    <div class="menu-icon w-8 h-8 flex items-center justify-center">
                        <i class="fas fa-file-alt text-sm"></i>
                    </div>
                    <div>
                        <span class="font-semibold text-xs">Laporan</span>
                        <p class="text-xs opacity-70">Reports & Constraints</p>
                    </div>
                </a>
            </div>

            <div class="px-6 mt-20">
                <div class="card-modern p-4">
                    <h4 class="font-bold text-sm text-gray-800 mb-3">Statistik Singkat</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-800">Aktif</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-200 text-green-900">
                                1
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-800">Selesai</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-200 text-blue-900">
                                2
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-800">Akan Berakhir</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-200 text-yellow-900">
                                0
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-800">Belum Mulai</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-200 text-red-900">
                                2
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 ml-0 lg:ml-64 pt-3 sm:pt-4 px-3 sm:px-4 lg:px-6 overflow-y-auto h-[calc(100vh-4rem)] sm:h-[calc(100vh-5rem)] transition-all duration-300 ease-in-out">
            <div class="h-full pt-6">
                <?php
                switch ($current_page) {
                    case 'dashboard':
                        include 'user/dashboard.php';
                        break;
                    case 'program_kerjasama':
                        include 'user/program_kerjasama.php';
                        break;
                    case 'pelaksana_kegiatan':
                        include 'user/pelaksana_kegiatan.php';
                        break;
                    case 'evaluasi_kinerja':
                        include 'user/evaluasi_kinerja.php';
                        break;
                    case 'laporan':
                        include 'user/laporan.php';
                        break;
                    default:
                        include 'user/dashboard.php';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <script>
        function logout() {
            Swal.fire({
                title: '<i class="fas fa-sign-out-alt text-red-500 mr-2"></i>Konfirmasi Logout',
                html: "<p class='text-gray-700 text-sm'>Apakah Anda yakin ingin keluar dari sistem?</p>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check-circle mr-1"></i> Ya, Logout',
                cancelButtonText: '<i class="fas fa-times-circle mr-1"></i> Batal',
                customClass: {
                    popup: 'text-sm max-w-md p-6 shadow-xl rounded-xl border border-gray-200',
                    title: 'text-lg font-semibold text-gray-800 flex items-center',
                    htmlContainer: 'mt-2',
                    actions: 'space-x-4 mt-5',
                    confirmButton: 'text-sm px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow transition-all',
                    cancelButton: 'text-sm px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-all'
                },
                buttonsStyling: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // --- Mobile Sidebar Toggle ---
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            function toggleSidebar() {
                sidebar.classList.toggle('translate-x-0');
                sidebar.classList.toggle('-translate-x-full');
                sidebarOverlay.classList.toggle('hidden');
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }

            // --- Dark Mode Toggle Logic ---
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (darkModeToggle) {
                const themeIcon = darkModeToggle.querySelector('i');

                const applyTheme = (theme) => {
                    if (theme === 'dark') {
                        document.documentElement.setAttribute('data-theme', 'dark');
                        themeIcon.classList.replace('fa-moon', 'fa-sun');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.documentElement.removeAttribute('data-theme');
                        themeIcon.classList.replace('fa-sun', 'fa-moon');
                        localStorage.setItem('theme', 'light');
                    }
                };

                darkModeToggle.addEventListener('click', () => {
                    const currentTheme = localStorage.getItem('theme') || 'light';
                    applyTheme(currentTheme === 'light' ? 'dark' : 'light');
                });

                // Apply saved theme
                applyTheme(localStorage.getItem('theme') || 'light');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>