<?php
session_start();
include __DIR__ . '/config/koneksi.php';

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

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #FB4141;
            --primary-dark: #e03737;
            --secondary: #78C841;
            --secondary-dark: #6ab537;
            --accent: #FEF3F3;
            --accent-secondary: #F0F9E8;
            --background: #FAFBFC;
            --surface: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --gradient-primary: linear-gradient(135deg, #FB4141 0%, #FF6B6B 100%);
            --gradient-secondary: linear-gradient(135deg, #78C841 0%, #95D862 100%);
            --gradient-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow-primary: 0 10px 25px -5px rgba(251, 65, 65, 0.25);
            --shadow-secondary: 0 10px 25px -5px rgba(120, 200, 65, 0.25);
            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-elevated: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        html[data-theme='dark'] {
            --primary: #FF5757;
            --primary-dark: #FB4141;
            --secondary: #8ED653;
            --secondary-dark: #78C841;
            --accent: #2D1B1B;
            --accent-secondary: #1F2E1A;
            --background: #0F172A;
            --surface: #1E293B;
            --text-dark: #F1F5F9;
            --text-light: #CBD5E1;
            --border: #334155;
            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
            --shadow-elevated: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text-dark);
            min-height: 100vh;
        }

        html[data-theme='dark'] body {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
        }

        /* Header Styles */
        .header-gradient {
            background: linear-gradient(135deg, var(--surface) 0%, rgba(251, 65, 65, 0.05) 100%);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        html[data-theme='dark'] .header-gradient {
            background: linear-gradient(135deg, var(--surface) 0%, rgba(255, 87, 87, 0.1) 100%);
        }

        /* Sidebar Styles */
        .sidebar-modern {
            background: var(--surface);
            border-right: 1px solid var(--border);
            box-shadow: var(--shadow-card);
        }

        html[data-theme='dark'] .sidebar-modern {
            background: linear-gradient(180deg, var(--surface) 0%, #0F172A 100%);
        }

        /* Logo Container */
        .logo-modern {
            background: var(--gradient-primary);
            border-radius: 16px;
            padding: 12px;
            box-shadow: var(--shadow-primary);
            transition: all 0.3s ease;
        }

        .logo-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(251, 65, 65, 0.4);
        }

        /* Menu Items */
        .menu-item-modern {
            position: relative;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 4px;
        }

        .menu-item-modern:hover {
            background: linear-gradient(135deg, rgba(251, 65, 65, 0.1) 0%, rgba(120, 200, 65, 0.1) 100%);
            transform: translateX(4px);
        }

        .menu-item-modern.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: var(--shadow-primary);
        }

        .menu-item-modern.active .menu-icon {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }

        .menu-icon {
            background: var(--accent);
            color: var(--primary);
            border-radius: 10px;
            padding: 8px;
            transition: all 0.3s ease;
        }

        html[data-theme='dark'] .menu-icon {
            background: var(--accent);
            color: var(--primary);
        }

        /* Content Area */
        .content-modern {
            background: var(--surface);
            border-radius: 20px;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border);
        }

        /* Button Styles */
        .btn-primary-modern {
            background: var(--gradient-primary);
            color: white;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            box-shadow: var(--shadow-primary);
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(251, 65, 65, 0.4);
        }

        .btn-secondary-modern {
            background: var(--gradient-secondary);
            color: white;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            box-shadow: var(--shadow-secondary);
        }

        .btn-secondary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(120, 200, 65, 0.4);
        }

        /* User Profile */
        .user-avatar-modern {
            background: var(--gradient-secondary);
            border-radius: 50%;
            padding: 2px;
            position: relative;
        }

        .user-avatar-modern::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: var(--gradient-primary);
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .user-avatar-modern:hover::before {
            opacity: 1;
        }

        /* Card Styles */
        .card-modern {
            background: var(--surface);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
            transition: all 0.3s ease;
        }

        .card-modern:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-elevated);
        }

        /* Status Badges */
        .badge-success {
            background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            color: white;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-danger {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Form Styles */
        .form-modern {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            background: var(--surface);
        }

        .form-modern:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(251, 65, 65, 0.1);
        }

        /* Modal Styles */
        .modal-modern {
            background: var(--surface);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-elevated);
        }

        /* Floating Action Button */
        .fab-modern {
            background: var(--gradient-primary);
            border-radius: 20px;
            box-shadow: var(--shadow-primary);
            transition: all 0.3s ease;
        }

        .fab-modern:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 25px 50px -10px rgba(251, 65, 65, 0.4);
        }

        /* Dark mode form adjustments */
        html[data-theme='dark'] .form-modern {
            background: var(--background);
            color: var(--text-dark);
        }

        html[data-theme='dark'] input,
        html[data-theme='dark'] select,
        html[data-theme='dark'] textarea {
            background: var(--background);
            color: var(--text-dark);
            border-color: var(--border);
        }

        html[data-theme='dark'] .bg-white {
            background-color: var(--surface) !important;
        }

        html[data-theme='dark'] .bg-gray-50 {
            background-color: var(--background) !important;
        }

        html[data-theme='dark'] .bg-gray-100 {
            background-color: var(--border) !important;
        }

        html[data-theme='dark'] .text-gray-800,
        html[data-theme='dark'] .text-gray-900 {
            color: var(--text-dark) !important;
        }

        html[data-theme='dark'] .text-gray-600,
        html[data-theme='dark'] .text-gray-700 {
            color: var(--text-light) !important;
        }

        html[data-theme='dark'] .border-gray-200,
        html[data-theme='dark'] .border-gray-300 {
            border-color: var(--border) !important;
        }

        html[data-theme='dark'] .divide-gray-200> :not([hidden])~ :not([hidden]) {
            border-color: var(--border) !important;
        }

        html[data-theme='dark'] .hover\:bg-gray-50:hover,
        html[data-theme='dark'] .hover\:bg-gray-100:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar-modern {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                position: fixed;
                z-index: 40;
                height: 100vh;
            }

            .sidebar-modern.open {
                transform: translateX(0);
            }

            .content-area {
                margin-left: 0 !important;
            }
        }

        /* Animation utilities */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Ganti atau tambahkan ini di CSS Anda */
        #sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            position: fixed;
            z-index: 40;
            height: 100vh;
        }

        #sidebar.translate-x-0 {
            transform: translateX(0);
        }

        @media (min-width: 1024px) {
            #sidebar {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body class="overflow-hidden">
    <nav class="fixed w-full top-0 z-50 shadow-lg bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-20">
                <!-- Logo & Title - Always visible on all screens -->
                <div class="flex items-center space-x-2">
                    <!-- Mobile menu button (hidden on desktop) -->
                    <button id="sidebarToggle" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fa-solid fa-bars-staggered"></i>
                    </button>

                    <!-- Logo - Smaller on mobile -->
                    <div class="logo-modern !p-1 sm:!p-2">
                        <img src="asset/logo.png" alt="Logo" class="w-6 h-6 sm:w-8 sm:h-8">
                    </div>

                    <!-- Title - Always visible but smaller on mobile -->
                    <div class="flex flex-col">
                        <h1 class="text-xs sm:text-lg md:text-xl font-bold bg-gradient-to-r from-red-500 to-green-500 bg-clip-text text-transparent leading-tight">
                            POLIMDO & DUDIKA
                        </h1>
                        <p class="text-[8px] sm:text-xs text-gray-500 font-medium leading-tight">Sistem Informasi Kerjasama</p>
                    </div>
                </div>

                <!-- Header Actions - Adjusted spacing for mobile -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- Dark Mode Toggle - Smaller on mobile -->
                    <button id="darkModeToggle" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full hover:bg-gray-100 transition-colors">
                        <i class="fas fa-moon text-gray-600 text-sm sm:text-base"></i>
                    </button>

                    <!-- User Profile - Always visible -->
                    <div class="flex items-center space-x-3 sm:space-x-5">
                        <!-- User info - Always visible but compact on mobile -->
                        <div class="text-right">
                            <span class="text-[11px] sm:text-xs md:text-sm font-bold text-gray-800 leading-tight">Mitra Partner</span>
                            <p class="text-[9px] sm:text-[10px] md:text-xs text-gray-500 leading-tight">Clynten Palad</p>
                        </div>

                        <!-- Logout Button - Smaller on mobile -->
                        <button onclick="logout()" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-red-500 hover:bg-red-50">
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
                            <span class="text-xs text-gray-600">Aktif</span>
                            <span class="badge-success">12</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-600">Tertunda</span>
                            <span class="badge-danger">3</span>
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
        // --- General Functions ---
        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = 'logout.php';
            }
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
</body>

</html>