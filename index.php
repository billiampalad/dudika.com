<?php
session_start();
include __DIR__ . '/config/koneksi.php';

$error_message = '';
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = $_POST['nik'] ?? '';
    $password = $_POST['password'] ?? '';
    $provider = strtolower($_POST['provider'] ?? '');

    // Validasi input dasar
    if (empty($nik) || empty($password) || empty($provider)) {
        $_SESSION['login_error'] = 'NIK, Password, dan Provider harus diisi!';
        header("Location: index.php");
        exit();
    }

    // Validasi provider
    if (!in_array($provider, ['polimdo', 'mitra'])) {
        $_SESSION['login_error'] = 'Provider yang dipilih tidak valid!';
        header("Location: index.php");
        exit();
    }

    $sql = "SELECT nik, password, role FROM tbluser WHERE nik=? AND role=? LIMIT 1";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ss", $nik, $provider);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Pengguna dengan NIK dan Role yang sesuai ditemukan. Sekarang verifikasi passwordnya.

        // ===================================================================
        // PERBAIKAN 1 (KRITIS): Gunakan password_verify() untuk mencocokkan hash
        // ===================================================================
        if (password_verify($password, $row['password'])) {
            // Login sukses, password cocok.

            // ===================================================================
            // PERBAIKAN 2 (BEST PRACTICE): Regenerasi ID sesi untuk keamanan
            // ===================================================================
            session_regenerate_id(true);

            $_SESSION['nik'] = $row['nik'];
            $_SESSION['role'] = $row['role'];

            // Hapus pesan error jika ada dari percobaan sebelumnya
            unset($_SESSION['login_error']);

            // Arahkan sesuai role
            if ($row['role'] == 'polimdo') {
                header("Location: pimpinan.php");
            } else {
                header("Location: user.php");
            }
            exit();
        }
    }

    // Jika kode sampai di sini, berarti:
    // 1. User dengan NIK & Role tersebut tidak ada, ATAU
    // 2. Password salah.
    // Pesan errornya harus sama untuk keduanya agar tidak membocorkan informasi.
    $_SESSION['login_error'] = 'Kombinasi NIK, Password, atau Provider salah!';
    header("Location: index.php");
    exit();

    // Baris ini tidak akan pernah dijangkau, tapi baik untuk ada
    $stmt->close();
    $koneksi->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login Mitra Dudika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="asset/style.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-blue': '#6366f1', // Indigo
                        'primary-green': '#10b981', // Emerald
                        'primary-red': '#ef4444', // Rose
                        'secondary-blue': '#3b82f6', // Blue untuk aksen
                        'secondary-green': '#34d399', // Teal untuk aksen
                    },
                    animation: {
                        'float': 'float 3s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'slide-in': 'slideIn 0.8s ease-out',
                        'fade-in': 'fadeIn 1s ease-out'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': {
                                transform: 'translateY(0px)'
                            },
                            '50%': {
                                transform: 'translateY(-10px)'
                            }
                        },
                        slideIn: {
                            '0%': {
                                transform: 'translateX(50px)',
                                opacity: '0'
                            },
                            '100%': {
                                transform: 'translateX(0)',
                                opacity: '1'
                            }
                        },
                        fadeIn: {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(20px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            }
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen gradient-bg relative">
    <?php
    echo file_get_contents(__DIR__ . '/asset/loading/loading.html');
    ?>
    <div class="floating-shapes">
        <div class="shape w-32 h-32 rounded-full"></div>
        <div class="shape w-24 h-24 rounded-lg rotate-45"></div>
        <div class="shape w-40 h-40 rounded-full"></div>
        <div class="shape w-28 h-28 rounded-lg rotate-12"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 relative z-10">
        <div class="glass-effect rounded-lg shadow-2xl w-full max-w-6xl flex flex-col lg:flex-row items-center p-6 sm:p-8 lg:p-12 space-y-6 sm:space-y-8 lg:space-y-0 lg:space-x-16 animate-fade-in bg-white">
            <!-- Logo & Welcome Text -->
            <div class="flex flex-col items-center justify-center w-full lg:w-1/2 space-y-4 sm:space-y-6">
                <div class="relative flex items-center justify-center">
                    <div class="absolute inset-0 bg-gradient-to-r from-secondary-blue to-secondary-green rounded-full blur-xl opacity-40 animate-pulse-slow"></div>
                    <div class="relative bg-white p-4 rounded-full shadow-lg animate-float w-28 sm:w-44 md:w-48 h-28 sm:h-44 md:h-48 flex items-center justify-center">
                        <img src="asset/logo.png" alt="logo POLIMDO"
                            class="object-contain w-20 h-20 sm:w-32 sm:h-32 md:w-36 md:h-36">
                    </div>
                </div>
                <div class="text-center space-y-1 sm:space-y-2">
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold bg-gradient-to-r from-primary-blue via-primary-green to-primary-red bg-clip-text text-transparent">
                        POLIMDO & DUDIKA
                    </h1>
                    <p class="text-gray-600 text-sm sm:text-base">Sistem Komunikasi & Layanan Kerjasama</p>
                </div>
            </div>

            <!-- Login Form -->
            <div class="w-full lg:w-1/2 max-w-md animate-slide-in">
                <div>
                    <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-center mb-6 sm:mb-8 bg-gradient-to-r from-secondary-blue to-secondary-green bg-clip-text text-transparent">Masuk</h2>

                    <?php if (!empty($error_message)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                        </div>
                    <?php endif; ?>

                    <form class="space-y-5 sm:space-y-6" action="#" method="POST" autocomplete="off" novalidate>
                        <div class="relative group">
                            <div class="relative flex items-center border-2 border-gray-200 rounded-xl bg-white/90 focus-within:border-secondary-blue focus-within:ring-4 focus-within:ring-secondary-blue/20 transition-all duration-300 input-glow group-hover:border-secondary-blue/50">
                                <div class="flex items-center pl-3">
                                    <i class="fas fa-user text-secondary-blue"></i>
                                    <div class="h-5 w-px bg-gray-300 mx-3"></div>
                                </div>
                                <input id="nik" type="text" name="nik"
                                    class="flex-1 py-3 text-sm sm:text-base text-gray-900 placeholder-gray-400 bg-transparent focus:outline-none"
                                    placeholder="NIK" required />
                                <div class="flex items-center pr-3">
                                    <div class="h-5 w-px bg-gray-300 mx-3"></div>
                                    <i class="fas fa-id-badge text-secondary-blue"></i>
                                </div>
                            </div>
                        </div>

                        <div class="relative group">
                            <div class="relative flex items-center border-2 border-gray-200 rounded-xl bg-white/90 focus-within:border-secondary-green focus-within:ring-4 focus-within:ring-secondary-green/20 transition-all duration-300 input-glow group-hover:border-secondary-green/50">
                                <div class="flex items-center pl-3">
                                    <i class="fas fa-lock text-secondary-green"></i>
                                    <div class="h-5 w-px bg-gray-300 mx-3"></div>
                                </div>
                                <input id="password" type="password" name="password"
                                    class="flex-1 py-3 text-sm sm:text-base text-gray-900 placeholder-gray-400 bg-transparent focus:outline-none"
                                    placeholder="Password" required />

                                <!-- Toggle password visibility -->
                                <div class="flex items-center pr-3">
                                    <div class="h-5 w-px bg-gray-300 mx-2"></div>
                                    <button type="button" onclick="togglePassword()" tabindex="-1">
                                        <i id="toggleIcon" class="fas fa-eye text-secondary-green cursor-pointer"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Provider -->
                        <div class="relative group" x-data="{ open: false, selected: 'MITRA' }" @click.outside="open = false">
                            <label for="provider" class="text-sm sm:text-base font-medium text-gray-700 mb-1 sm:mb-2 block">
                                <i class="fas fa-network-wired text-primary-red mr-2"></i>User
                            </label>

                            <!-- Trigger Button -->
                            <button type="button"
                                class="block w-full rounded-xl border-2 border-gray-200 bg-white/90 py-3 px-4 text-sm sm:text-base text-gray-900 transition-all duration-300 focus:border-primary-red focus:ring-4 focus:ring-primary-red/20 input-glow group-hover:border-primary-red/50 cursor-pointer text-left relative"
                                @click="open = !open">
                                <span x-text="selected"></span>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-primary-red pointer-events-none"></i>
                            </button>

                            <!-- Dropdown Options -->
                            <div x-show="open" x-transition
                                class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                                <button type="button"
                                    class="w-full text-left px-4 py-2 hover:bg-red-100 text-base"
                                    @click="selected = 'POLIMDO'; open = false">POLIMDO</button>
                                <button type="button"
                                    class="w-full text-left px-4 py-2 hover:bg-red-100 text-base"
                                    @click="selected = 'MITRA'; open = false">MITRA</button>
                            </div>

                            <!-- Hidden input to submit value -->
                            <input type="hidden" name="provider" :value="selected">
                        </div>


                        <!-- Submit Button -->
                        <button type="submit" class="w-full bg-gradient-to-r from-primary-red to-primary-red/90 hover:from-primary-red/90 hover:to-primary-red text-white font-bold py-3 sm:py-4 rounded-xl text-sm sm:text-base transition-all duration-300 transform button-hover focus:outline-none focus:ring-4 focus:ring-primary-red/30 shadow-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Masuk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<script src="https://unpkg.com/alpinejs" defer></script>
<script src="asset/pimpinan/js/index.js"></script>