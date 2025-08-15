<?php
session_start();
include __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['nik'])) {
    header('Location: index.php');
    exit;
}

$query = "SELECT nama_lengkap, role FROM tbluser WHERE nik = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("s", $_SESSION['nik']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$current_page = $_GET['page'] ?? 'dashboard';
$valid_pages = ['dashboard', 'mitra', 'jenis_kerjasama', 'unit_pelaksana', 'program_kerjasama', 'hasil_capaian', 'evaluasi_kinerja', 'permasalahan_solusi'];
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="asset/pimpinan/css/style.css">
</head>

<body>
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <nav class="fixed nav-gradient w-full top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <div class="flex items-center space-x-2 sm:space-x-3 md:space-x-4">
                    <button id="sidebarToggle"
                        class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none mr-2 sm:mr-0">
                        <i class="fa-solid fa-bars-staggered text-base sm:text-lg"></i>
                    </button>

                    <div class="logo-container background-icon w-9 h-9 sm:w-9 sm:h-9 md:w-10 md:h-10 rounded-xl flex items-center justify-center">
                        <img src="asset/logo.png" alt="Logo" class="w-7 h-7 sm:w-6 sm:h-6 md:w-7 md:h-7">
                    </div>

                    <div class="hidden sm:block">
                        <h1 class="text-xs sm:text-base md:text-lg font-bold text-gray-800">
                            POLIMDO & DUDIKA
                        </h1>
                        <p class="hidden sm:block text-xs text-gray-600">
                            Sistem Informasi Kerjasama
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-3 sm:space-x-3 md:space-x-4">
                    <button id="darkModeToggle"
                        class="nav-button w-9 h-9 sm:w-8 sm:h-8 md:w-9 md:h-9 rounded-full flex items-center justify-center">
                        <i class="fas fa-moon text-sm sm:text-sm md:text-lg"></i>
                    </button>

                    <div class="flex items-center space-x-3 sm:space-x-2 md:space-x-3">
                        <div class="flex flex-col items-center gap-1">
                            <div class="relative group">
                                <!-- Nama truncated di mobile -->
                                <span
                                    class="block max-w-[150px] sm:max-w-none truncate text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 px-3 py-1 rounded-full shadow-md cursor-pointer transition-all duration-300 group-hover:scale-105 group-hover:shadow-lg">
                                    <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                                </span>

                                <!-- Tooltip muncul saat hover -->
                                <div
                                    class="absolute top-1/2 right-full -translate-y-1/2 mr-2 opacity-0 group-hover:opacity-100 pointer-events-none bg-gray-800 text-white text-[10px] px-2 py-1 rounded-md shadow-md whitespace-nowrap transition-opacity duration-300 sm:hidden">
                                    <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                                </div>
                            </div>

                            <p class="text-[10px] sm:text-sm text-gray-600 font-medium">
                                Wakil Direktur 4 <span class="hidden sm:inline">(Polimdo)</span>
                            </p>
                        </div>

                        <button id="logoutBtn"
                            class="nav-button w-9 h-9 sm:w-8 sm:h-8 md:w-9 md:h-9 rounded-full flex items-center justify-center text-red-500 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt text-sm md:text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex pt-16 sm:pt-20">
        <aside id="sidebar" class="nav-gradient w-64 sm:w-70 fixed h-screen overflow-y-auto py-4 sm:py-6 shadow-xl z-40 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0">
            <div class="px-2 space-y-1 sm:space-y-1">
                <a href="?page=dashboard"
                    class="menu-item submenu-item block w-full text-left px-5 py-3 rounded-xl flex items-center space-x-3 <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                    <div class="background-icon w-7 h-7 flex items-center justify-center rounded-xl shadow-sm">
                        <i class="fas fa-tachometer-alt text-base w-5 text-center text-white group-hover:text-orange-500"></i>
                    </div>
                    <span class="font-medium text-sm">Dashboard</span>
                </a>

                <div class="relative">
                    <button id="dataMasterBtn"
                        class="menu-item submenu-item w-full text-left px-5 py-3 rounded-xl flex items-center justify-between <?php echo $is_data_master_page_active ? 'active' : ''; ?>">
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center justify-center background-icon w-7 h-7 bg-white rounded-xl shadow-sm">
                                <i class="fas fa-database text-white text-base w-5 text-center"></i>
                            </div>
                            <span class="font-medium text-sm">Data Master</span>
                        </div>
                        <i id="dataMasterIcon"
                            class="fas fa-chevron-down icon-rotate transition-transform text-sm <?php echo $is_data_master_page_active ? 'rotated' : ''; ?>"></i>
                    </button>

                    <div id="dataMasterDropdown"
                        class="mt-2 ml-10 <?php echo !$is_data_master_page_active ? 'hidden' : ''; ?>">
                        <a href="?page=mitra"
                            class="menu-item submenu-item block pl-5 pr-4 py-3 rounded-lg text-gray-600 hover:text-gray-800 flex items-center space-x-3 <?php echo ($current_page == 'mitra') ? 'active' : ''; ?>">
                            <div class="w-3 flex justify-center">
                                <i class="fas fa-building text-xs w-3 text-center"></i>
                            </div>
                            <span class="text-sm">Mitra Kerjasama</span>
                        </a>
                        <a href="?page=jenis_kerjasama"
                            class="menu-item submenu-item block pl-5 pr-4 py-3 rounded-lg text-gray-600 hover:text-gray-800 flex items-center space-x-3 <?php echo ($current_page == 'jenis_kerjasama') ? 'active' : ''; ?>">
                            <div class="w-3 flex justify-center">
                                <i class="fas fa-project-diagram text-xs w-3 text-center"></i>
                            </div>
                            <span class="text-sm">Jenis Kerjasama</span>
                        </a>
                        <a href="?page=unit_pelaksana"
                            class="menu-item submenu-item block pl-5 pr-4 py-3 rounded-lg text-gray-600 hover:text-gray-800 flex items-center space-x-3 <?php echo ($current_page == 'unit_pelaksana') ? 'active' : ''; ?>">
                            <div class="w-3 flex justify-center">
                                <i class="fas fa-users-cog text-xs w-3 text-center"></i>
                            </div>
                            <span class="text-sm">Unit Pelaksana</span>
                        </a>
                    </div>
                </div>

                <a href="?page=program_kerjasama"
                    class="menu-item submenu-item block w-full text-left px-5 py-3 rounded-xl flex items-center space-x-3 <?php echo ($current_page == 'program_kerjasama') ? 'active' : ''; ?>">
                    <div class="flex items-center justify-center background-icon w-7 h-7 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-handshake text-white text-base w-5 text-center"></i>
                    </div>
                    <span class="font-medium text-sm">Program Kerjasama</span>
                </a>

                <a href="?page=hasil_capaian"
                    class="menu-item submenu-item block w-full text-left px-5 py-3 rounded-xl flex items-center space-x-3 <?php echo ($current_page == 'hasil_capaian') ? 'active' : ''; ?>">
                    <div class="flex items-center justify-center background-icon w-7 h-7 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-chart-line text-white text-base w-5 text-center"></i>
                    </div>
                    <span class="font-medium text-sm">Hasil & Capaian</span>
                </a>

                <a href="?page=evaluasi_kinerja"
                    class="menu-item submenu-item block w-full text-left px-5 py-3 rounded-xl flex items-center space-x-3 <?php echo ($current_page == 'evaluasi_kinerja') ? 'active' : ''; ?>">
                    <div class="flex items-center justify-center background-icon w-7 h-7 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-star-half-alt text-white text-base w-5 text-center"></i>
                    </div>
                    <span class="font-medium text-sm">Evaluasi Kinerja</span>
                </a>

                <a href="?page=permasalahan_solusi"
                    class="menu-item submenu-item block w-full text-left px-5 py-3 rounded-xl flex items-center space-x-3 <?php echo ($current_page == 'permasalahan_solusi') ? 'active' : ''; ?>">
                    <div class="flex items-center justify-center background-icon w-7 h-7 bg-white rounded-xl shadow-sm">
                        <i class="fas fa-tools text-white text-base w-5 text-center"></i>
                    </div>
                    <span class="font-medium text-sm">Solusi & Masalah</span>
                </a>
            </div>
        </aside>

        <main class="lg:ml-64 flex-1 transition-all duration-300 ease-in-out">
            <div class="rounded-lg shadow p-3 sm:p-4 lg:p-6 mx-2 sm:mx-3 lg:mx-0">
                <?php
                switch ($current_page) {
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

    <script>
        // --- General Functions ---
        function logout() {
            alert('Anda akan segera logout.');
            // Implement actual logout logic here
        }

        document.addEventListener('DOMContentLoaded', function() {

            // --- RESPONSIVE CHANGE: Sidebar Toggle Logic ---
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const logoutBtn = document.getElementById("logoutBtn");

            const toggleSidebar = () => {
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('translate-x-0');
                sidebarOverlay.classList.toggle('hidden');
            };

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            // --- END RESPONSIVE CHANGE ---

            // --- Notification Dropdown Logic ---
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationDropdown = document.getElementById('notificationDropdown');

            function toggleNotifications() {
                notificationDropdown.classList.toggle('hidden');
            }

            if (notificationBtn) {
                notificationBtn.addEventListener('click', function(event) {
                    event.stopPropagation();
                    toggleNotifications();
                });
            }

            // --- Sidebar Dropdown Logic (Hanya untuk interaksi klik) ---
            const dataMasterBtn = document.getElementById('dataMasterBtn');
            const dataMasterDropdown = document.getElementById('dataMasterDropdown');
            const dataMasterIcon = document.getElementById('dataMasterIcon');

            if (dataMasterBtn) {
                dataMasterBtn.addEventListener('click', () => {
                    // Javascript tetap berfungsi untuk toggle saat diklik manual
                    dataMasterDropdown.classList.toggle('hidden');
                    dataMasterIcon.classList.toggle('rotated');
                });
            }

            // --- Modal Logic ---
            const addModal = document.getElementById('addModal');
            const fab = document.getElementById('fab');
            const addDataBtn = document.getElementById('addDataBtn');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const cancelModalBtn = document.getElementById('cancelModalBtn');

            function showAddModal() {
                if (addModal) addModal.classList.remove('hidden');
            }

            function hideAddModal() {
                if (addModal) addModal.classList.add('hidden');
            }

            // Cek elemen sebelum menambahkan event listener
            if (fab) fab.addEventListener('click', showAddModal);
            if (addDataBtn) addDataBtn.addEventListener('click', showAddModal);
            if (closeModalBtn) closeModalBtn.addEventListener('click', hideAddModal);
            if (cancelModalBtn) cancelModalBtn.addEventListener('click', hideAddModal);

            // Close modal on escape key press
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && addModal && !addModal.classList.contains('hidden')) {
                    hideAddModal();
                }
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                // Close notification dropdown
                if (notificationDropdown && !notificationDropdown.classList.contains('hidden') && !notificationBtn.contains(event.target)) {
                    notificationDropdown.classList.add('hidden');
                }
            });

            // --- Dark Mode Toggle Logic ---
            const darkModeToggle = document.getElementById('darkModeToggle');
            const themeIcon = darkModeToggle.querySelector('i');

            // Fungsi untuk menerapkan tema dan menyimpan pilihan
            const applyTheme = (theme) => {
                if (theme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                    localStorage.setItem('theme', 'light');
                }
            };

            // Event listener untuk tombol toggle
            darkModeToggle.addEventListener('click', () => {
                const currentTheme = localStorage.getItem('theme') || 'light';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                applyTheme(newTheme);
            });

            // Terapkan tema yang tersimpan saat halaman pertama kali dimuat
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);

            const swalWithTailwind = Swal.mixin({
                customClass: {
                    confirmButton: 'bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded',
                    cancelButton: 'bg-gray-300 hover:bg-gray-400 text-black px-4 py-2 rounded'
                },
                buttonsStyling: false
            });

            logoutBtn.addEventListener('click', () => {
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
                        // Arahkan ke file logout.php
                        window.location.href = 'logout.php';
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>

</html>