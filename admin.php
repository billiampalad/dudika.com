<?php
include __DIR__ . '/config/koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bagian untuk menangani Aksi (Create, Update, Delete) via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Pastikan tidak ada output sebelum header JSON
    if (ob_get_length()) ob_clean();

    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Aksi tidak valid atau NIK tidak ditemukan.'];

    try {
        $action = $_POST['action'];

        switch ($action) {
            case 'get':
                if (isset($_POST['nik'])) {
                    $stmt = $koneksi->prepare("SELECT nik, nama_lengkap, role FROM tbluser WHERE nik = ?");
                    if (!$stmt) throw new Exception("Prepare failed: " . $koneksi->error);

                    $stmt->bind_param("s", $_POST['nik']);
                    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);

                    $result = $stmt->get_result();
                    if ($data = $result->fetch_assoc()) {
                        $response = ['status' => 'success', 'data' => $data];
                    } else {
                        $response['message'] = 'Data pengguna tidak ditemukan.';
                    }
                    $stmt->close();
                }
                break;

            case 'add':
                $required = ['nik', 'nama_lengkap', 'password', 'role'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) throw new Exception("Field $field harus diisi.");
                }

                $nik = $_POST['nik'];
                $nama_lengkap = $_POST['nama_lengkap'];
                $password = $_POST['password'];
                $role = $_POST['role'];
                $tanggal_masuk = date('Y-m-d');
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $koneksi->prepare("INSERT INTO tbluser (nik, nama_lengkap, password, role, tanggal_masuk) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) throw new Exception("Prepare failed: " . $koneksi->error);

                $stmt->bind_param("sssss", $nik, $nama_lengkap, $hashed_password, $role, $tanggal_masuk);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'Data berhasil ditambahkan.'];
                } else {
                    if ($koneksi->errno == 1062) { // Error untuk duplicate entry
                        $response['message'] = 'Gagal: NIK sudah terdaftar.';
                    } else {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                }
                $stmt->close();
                break;

            case 'update':
                $required = ['nik', 'nama_lengkap', 'role'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) throw new Exception("Field $field harus diisi.");
                }

                $nik = $_POST['nik'];
                $nama_lengkap = $_POST['nama_lengkap'];
                $role = $_POST['role'];
                $password = $_POST['password'] ?? null;

                // 2. Refactoring Kueri Update menjadi lebih efisien
                $query_parts = ["nama_lengkap = ?", "role = ?"];
                $params = [$nama_lengkap, $role];
                $types = "ss";

                if (!empty($password)) {
                    $query_parts[] = "password = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                    $types .= "s";
                }

                $params[] = $nik;
                $types .= "s";

                $sql = "UPDATE tbluser SET " . implode(", ", $query_parts) . " WHERE nik = ?";
                $stmt = $koneksi->prepare($sql);
                if (!$stmt) throw new Exception("Prepare failed: " . $koneksi->error);

                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'Data berhasil diperbarui.'];
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 'delete':
                if (isset($_POST['nik'])) {
                    $stmt = $koneksi->prepare("DELETE FROM tbluser WHERE nik = ?");
                    if (!$stmt) throw new Exception("Prepare failed: " . $koneksi->error);

                    $stmt->bind_param("s", $_POST['nik']);
                    if ($stmt->execute()) {
                        $response = ['status' => 'success', 'message' => 'Data berhasil dihapus.'];
                    } else {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    $stmt->close();
                }
                break;

            default:
                $response['message'] = 'Aksi tidak dikenali.';
                break;
        }
    } catch (Exception $e) {
        http_response_code(400); // Bad Request
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    $koneksi->close();
    exit;
}

// Bagian HTML untuk Menampilkan Data (hanya dijalankan untuk request GET)
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);

$total_result = $koneksi->query("SELECT COUNT(*) AS total FROM tbluser");
if (!$total_result) die("Error query total: " . $koneksi->error);

$total_data = $total_result->fetch_assoc()['total'] ?? 0;
$total_pages = $total_data > 0 ? ceil($total_data / $limit) : 1;
$page = min($page, $total_pages);
$offset = ($page - 1) * $limit;

// 3. Menghapus kolom password dari SELECT untuk keamanan
$stmt = $koneksi->prepare("SELECT nik, nama_lengkap, role, tanggal_masuk FROM tbluser ORDER BY nama_lengkap ASC LIMIT ? OFFSET ?");
if (!$stmt) die("Prepare failed: " . $koneksi->error);

$stmt->bind_param("ii", $limit, $offset);
if (!$stmt->execute()) die("Execute failed: " . $stmt->error);

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Hak Akses</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        .password-cell {
            max-width: 150px;
            /* Atur lebar maksimal sel password */
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 tracking-tight">Data Hak Akses</h2>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <button onclick="showAddModal()" class="bg-gradient-to-r from-blue-600 to-red-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-blue-200 focus:ring-offset-2">
                        <i class="fas fa-plus fa-sm"></i>
                        <span class="text-sm font-medium">Tambah Akun</span>
                    </button>
                </div>
            </div>

            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIK</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Nama Lengkap</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Tanggal Buat Akun</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody" class="bg-white divide-y divide-gray-200">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['nik']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 hidden md:table-cell"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ********
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell"><?= htmlspecialchars($row['role']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell"><?= htmlspecialchars(date('d M Y', strtotime($row['tanggal_masuk']))) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-center space-x-2">
                                            <button onclick="editData('<?= $row['nik'] ?>')" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit"><i class="fas fa-edit fa-lg"></i></button>
                                            <button onclick="deleteData('<?= $row['nik'] ?>')" class="text-red-600 hover:text-red-900 transition-colors" title="Hapus"><i class="fas fa-trash fa-lg"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="userCardContainer" class="block sm:hidden space-y-3 p-4">
                <?php
                $result->data_seek(0);
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-xs">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800 break-all"><?= htmlspecialchars($row['nama_lengkap']) ?></h3>
                                    <p class="text-xs text-gray-500">NIK: <?= htmlspecialchars($row['nik']) ?></p>
                                </div>
                                <div class="flex space-x-3 flex-shrink-0 ml-2">
                                    <button onclick="editData('<?= $row['nik'] ?>')" class="text-indigo-600 hover:text-indigo-500 transition-colors" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button onclick="deleteData('<?= $row['nik'] ?>')" class="text-red-600 hover:text-red-500 transition-colors" title="Hapus"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="mt-3 space-y-2 text-sm">
                                <div class="flex items-center"><i class="fas fa-user-tag text-gray-400 mr-2 w-4"></i><span class="text-gray-600">Role: <span class="font-semibold"><?= htmlspecialchars($row['role']) ?></span></span></div>
                                <div class="flex items-center"><i class="fas fa-calendar-alt text-gray-400 mr-2 w-4"></i><span class="text-gray-600">Bergabung: <?= htmlspecialchars(date('d M Y', strtotime($row['tanggal_masuk']))) ?></span></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data ditemukan.</p>
                <?php endif; ?>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                <?php
                $start_data = $total_data > 0 ? $offset + 1 : 0;
                $end_data = min($offset + $limit, $total_data);
                ?>
                <p class="text-sm text-gray-600">
                    Menampilkan <span class="font-medium"><?= $start_data ?>-<?= $end_data ?></span> dari <span class="font-medium"><?= $total_data ?></span> data
                </p>
                <?php if ($total_pages > 1) : ?>
                    <nav class="flex items-center space-x-1">
                        <a href="?page=<?= max(1, $page - 1) ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                            Previous
                        </a>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="px-3 py-1 border <?= $i == $page ? 'border-blue-500 bg-blue-500 text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' ?> rounded-md text-sm font-medium shadow">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <a href="?page=<?= min($total_pages, $page + 1) ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 <?= $page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?>">
                            Next
                        </a>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-lg shadow-xl transform transition-all" id="modalContent">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Tambah Akun</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addDataForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add">
                <div>
                    <label for="nik" class="block text-sm font-medium text-gray-700 mb-1">NIK</label>
                    <input type="text" id="nik" name="nik" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select id="role" name="role" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Pilih Role</option>
                        <option value="polimdo">Polimdo</option>
                        <option value="mitra">Mitra</option>
                    </select>
                </div>
                <div class="pt-4 border-t border-gray-200">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-lg shadow-xl transform transition-all" id="editModalContent">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Edit Akun</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editDataForm" class="p-6 space-y-4">
                <input type="hidden" id="edit_nik" name="nik">
                <input type="hidden" name="action" value="update">
                <div>
                    <label for="edit_nik_display" class="block text-sm font-medium text-gray-700 mb-1">NIK</label>
                    <input type="text" id="edit_nik_display" disabled class="mt-1 block w-full shadow-sm sm:text-sm bg-gray-100 border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label for="edit_nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="edit_nama_lengkap" name="nama_lengkap" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label for="edit_password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru (Opsional)</label>
                    <input type="password" id="edit_password" name="password" placeholder="Isi jika ingin mengubah" class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
                </div>
                <div>
                    <label for="edit_role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select id="edit_role" name="role" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
                        <option value="polimdo">Polimdo</option>
                        <option value="mitra">Mitra</option>
                    </select>
                </div>
                <div class="pt-4 border-t border-gray-200">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Konfigurasi SweetAlert dengan Tailwind
        const swalWithTailwind = Swal.mixin({
            customClass: {
                popup: 'p-4 sm:p-6 w-full max-w-sm rounded-lg shadow-lg',
                title: 'text-xl font-semibold text-gray-800',
                htmlContainer: 'mt-2 text-sm text-gray-600',
                actions: 'mt-4 sm:mt-6',
                confirmButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
                cancelButton: 'ml-3 px-4 py-2 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
            },
            buttonsStyling: false
        });

        // Fungsi Modal
        function showAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('nik').focus();
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addDataForm').reset();
        }

        function showEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('edit_nama_lengkap').focus();
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editDataForm').reset();
        }

        // Tutup modal jika klik di luar
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddModal();
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });

        // Fungsi Fetch dengan error handling
        async function handleFetch(formData) {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                // Debug raw response
                const text = await response.text();
                // console.log("Raw response:", text);

                try {
                    const data = JSON.parse(text);
                    if (!data.status) {
                        throw new Error("Invalid response format");
                    }
                    return data;
                } catch (e) {
                    console.error("JSON parse error:", e);
                    throw new Error("Invalid JSON response: " + text.substring(0, 100));
                }
            } catch (error) {
                console.error('Fetch error:', error);
                await swalWithTailwind.fire({
                    icon: 'error',
                    title: 'Error Jaringan',
                    text: 'Tidak dapat terhubung ke server. Periksa koneksi Anda.'
                });
                throw error;
            }
        }

        // Fungsi Tambah Data (Event Listener)
        document.getElementById('addDataForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await handleFetch(formData);
                if (res.status === 'success') {
                    closeAddModal();
                    await swalWithTailwind.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } else {
                    await swalWithTailwind.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.message || 'Terjadi kesalahan saat menambahkan data.'
                    });
                }
            } catch (error) {
                // Error sudah ditangani di handleFetch
            }
        });

        // Fungsi Update Data (Event Listener)
        document.getElementById('editDataForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const res = await handleFetch(formData);
                if (res.status === 'success') {
                    closeEditModal();
                    await swalWithTailwind.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    window.location.reload();
                } else {
                    await swalWithTailwind.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.message || 'Terjadi kesalahan saat memperbarui data.'
                    });
                }
            } catch (error) {
                // Error sudah ditangani di handleFetch
            }
        });

        // Fungsi Edit Data
        async function editData(nik) {
            try {
                const formData = new FormData();
                formData.append('action', 'get');
                formData.append('nik', nik);

                const res = await handleFetch(formData);

                if (res.status === 'success') {
                    document.getElementById('edit_nik').value = res.data.nik;
                    document.getElementById('edit_nik_display').value = res.data.nik;
                    document.getElementById('edit_nama_lengkap').value = res.data.nama_lengkap;
                    document.getElementById('edit_role').value = res.data.role;
                    document.getElementById('edit_password').value = '';
                    showEditModal();
                } else {
                    await swalWithTailwind.fire({
                        icon: 'error',
                        title: 'Gagal Memuat',
                        text: res.message || 'Data tidak ditemukan.'
                    });
                }
            } catch (error) {
                // Error sudah ditangani di handleFetch
            }
        }

        // Fungsi Delete Data
        async function deleteData(nik) {
            const result = await swalWithTailwind.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('nik', nik);

                    const res = await handleFetch(formData);

                    if (res.status === 'success') {
                        await swalWithTailwind.fire({
                            title: 'Dihapus!',
                            text: 'Data telah berhasil dihapus.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        await swalWithTailwind.fire({
                            title: 'Gagal',
                            text: res.message || 'Data gagal dihapus.',
                            icon: 'error'
                        });
                    }
                } catch (error) {
                    // Error sudah ditangani di handleFetch
                }
            }
        }
    </script>
</body>

</html>