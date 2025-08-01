<?php
// ===================================================================
// === HANDLER UNTUK PERMINTAAN AJAX (CREATE, READ, UPDATE, DELETE) ===
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Atur header untuk merespons sebagai JSON
    header('Content-Type: application/json');

    $action = $_POST['action'];
    $response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];

    switch ($action) {
        // AKSI: Mengambil satu data untuk form edit
        case 'get':
            if (isset($_POST['id_mitra'])) {
                $id = $_POST['id_mitra'];
                $stmt = $koneksi->prepare("SELECT IdMitraDudika, txtNamaMitraDudika, txtAlamatMitra, txtEmailMitra, txtNamaKepalaDudika FROM tblmitradudika WHERE IdMitraDudika = ?");
                $stmt->bind_param("s", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($data = $result->fetch_assoc()) {
                    $response = ['status' => 'success', 'data' => $data];
                } else {
                    $response['message'] = 'Data tidak ditemukan.';
                }
                $stmt->close();
            }
            break;

        // AKSI: Menambahkan data baru
        case 'add':
            $stmt = $koneksi->prepare("INSERT INTO tblmitradudika (txtNamaMitraDudika, txtAlamatMitra, txtEmailMitra, txtNamaKepalaDudika) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $_POST['txtNamaMitraDudika'], $_POST['txtAlamatMitra'], $_POST['txtEmailMitra'], $_POST['txtNamaKepalaDudika']);
            if ($stmt->execute()) {
                $response = ['status' => 'success'];
            } else {
                $response['message'] = 'Gagal menambahkan data: ' . $stmt->error;
            }
            $stmt->close();
            break;

        // AKSI: Memperbarui data yang ada
        case 'update':
            if (isset($_POST['id_mitra'])) {
                $stmt = $koneksi->prepare("UPDATE tblmitradudika SET txtNamaMitraDudika = ?, txtAlamatMitra = ?, txtEmailMitra = ?, txtNamaKepalaDudika = ? WHERE IdMitraDudika = ?");
                $stmt->bind_param("sssss", $_POST['txtNamaMitraDudika'], $_POST['txtAlamatMitra'], $_POST['txtEmailMitra'], $_POST['txtNamaKepalaDudika'], $_POST['id_mitra']);
                if ($stmt->execute()) {
                    $response = ['status' => 'success'];
                } else {
                    $response['message'] = 'Gagal memperbarui data: ' . $stmt->error;
                }
                $stmt->close();
            }
            break;

        // AKSI: Menghapus data
        case 'delete':
            if (isset($_POST['id_mitra'])) {
                $stmt = $koneksi->prepare("DELETE FROM tblmitradudika WHERE IdMitraDudika = ?");
                $stmt->bind_param("s", $_POST['id_mitra']);
                if ($stmt->execute()) {
                    $response = ['status' => 'success'];
                } else {
                    $response['message'] = 'Gagal menghapus data: ' . $stmt->error;
                }
                $stmt->close();
            }
            break;
    }

    // Mengirim respons JSON dan menghentikan eksekusi script
    echo json_encode($response);
    exit;
}

// ======================================================
// === BAGIAN TAMPILAN HALAMAN (RENDER UNTUK GET REQUEST) ===
// ======================================================

// Konfigurasi paginasi
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);

// Ambil total data untuk paginasi
$total_result = $koneksi->query("SELECT COUNT(*) AS total FROM tblmitradudika");
if (!$total_result) {
    die("Error: " . $koneksi->error);
}
$total_data = $total_result->fetch_assoc()['total'] ?? 0;
$total_pages = $total_data > 0 ? ceil($total_data / $limit) : 1;
$page = min($page, $total_pages);
$offset = ($page - 1) * $limit;

// Query data dengan prepared statement
$stmt = $koneksi->prepare("SELECT IdMitraDudika, txtNamaMitraDudika, txtAlamatMitra, txtEmailMitra, txtNamaKepalaDudika 
                           FROM tblmitradudika 
                           ORDER BY txtNamaMitraDudika ASC 
                           LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">Data Master Mitra Kerjasama</h2>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <button onclick="showAddModal(this)"
                    class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-blue-200 focus:ring-offset-2">
                <i class="fas fa-plus fa-sm"></i>
                <span class="text-sm font-medium">Tambah Data Mitra</span>
            </button>
            <button onclick="exportData()"
                    class="bg-gradient-to-r from-emerald-600 to-green-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-emerald-200 focus:ring-offset-2">
                <i class="fas fa-file-excel fa-sm"></i>
                <span class="text-sm font-medium">Export Excel</span>
            </button>
        </div>
    </div>

    <div class="px-6 py-4 border-b border-gray-100">
        <div class="relative max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input type="text" id="searchInput" placeholder="Fitur pencarian belum aktif..."
                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" disabled>
        </div>
    </div>

    <div class="hidden sm:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Mitra</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Alamat</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Kepala Dudika</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody id="mitraTableBody" class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['txtNamaMitraDudika']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500 hidden md:table-cell"><?= htmlspecialchars($row['txtAlamatMitra']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['txtEmailMitra']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell"><?= htmlspecialchars($row['txtNamaKepalaDudika']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-center space-x-2">
                                    <button onclick="editData(this, '<?= $row['IdMitraDudika'] ?>')" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                        <i class="fas fa-edit fa-lg"></i>
                                    </button>
                                    <button onclick="deleteData(this, '<?= $row['IdMitraDudika'] ?>')" class="text-red-600 hover:text-red-900 transition-colors" title="Hapus">
                                        <i class="fas fa-trash fa-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="mitraCardContainer" class="block sm:hidden space-y-3 p-4">
        <?php
        $result->data_seek(0);
        if ($result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-xs">
                    <div class="flex justify-between items-start">
                        <h3 class="text-sm font-semibold text-gray-800 break-all"><?= htmlspecialchars($row['txtNamaMitraDudika']) ?></h3>
                        <div class="flex space-x-3 flex-shrink-0 ml-2">
                            <button onclick="editData(this, '<?= $row['IdMitraDudika'] ?>')" class="text-indigo-600 hover:text-indigo-500 transition-colors" title="Edit"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteData(this, '<?= $row['IdMitraDudika'] ?>')" class="text-red-600 hover:text-red-500 transition-colors" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-start"><i class="fas fa-map-marker-alt text-gray-400 mr-2 w-4 mt-1"></i><span class="text-gray-600"><?= htmlspecialchars($row['txtAlamatMitra']) ?></span></div>
                        <div class="flex items-center"><i class="fas fa-envelope text-gray-400 mr-2 w-4"></i><span class="text-gray-600 break-all"><?= htmlspecialchars($row['txtEmailMitra']) ?></span></div>
                        <div class="flex items-center"><i class="fas fa-user text-gray-400 mr-2 w-4"></i><span class="text-gray-600"><?= htmlspecialchars($row['txtNamaKepalaDudika']) ?></span></div>
                    </div>
                </div>
            <?php
            endwhile;
        else:
            ?>
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

<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Tambah Data Mitra</h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="addDataForm" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="txtNamaMitraDudika" class="block text-sm font-medium text-gray-700 mb-1">Nama Mitra</label>
                <input type="text" id="txtNamaMitraDudika" name="txtNamaMitraDudika" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="txtAlamatMitra" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <input type="text" id="txtAlamatMitra" name="txtAlamatMitra" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="txtEmailMitra" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="txtEmailMitra" name="txtEmailMitra" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="txtNamaKepalaDudika" class="block text-sm font-medium text-gray-700 mb-1">Nama Kepala Dudika</label>
                <input type="text" id="txtNamaKepalaDudika" name="txtNamaKepalaDudika" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Batal</button>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Tambah</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0" id="editModalContent">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Edit Data Mitra</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <form id="editDataForm" class="p-6 space-y-4">
            <input type="hidden" id="edit_mitra_id" name="id_mitra">
            <input type="hidden" name="action" value="update">
            <div>
                <label for="edit_txtNamaMitraDudika" class="block text-sm font-medium text-gray-700 mb-1">Nama Mitra</label>
                <input type="text" id="edit_txtNamaMitraDudika" name="txtNamaMitraDudika" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="edit_txtAlamatMitra" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <input type="text" id="edit_txtAlamatMitra" name="txtAlamatMitra" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="edit_txtEmailMitra" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="edit_txtEmailMitra" name="txtEmailMitra" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="edit_txtNamaKepalaDudika" class="block text-sm font-medium text-gray-700 mb-1">Nama Kepala Dudika</label>
                <input type="text" id="edit_txtNamaKepalaDudika" name="txtNamaKepalaDudika" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Batal</button>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // === Elemen DOM & Variabel Global ===
    let activeElementBeforeModal;
    const addModal = document.getElementById('addModal');
    const addModalContent = document.getElementById('modalContent');
    const addDataForm = document.getElementById('addDataForm');
    const editModal = document.getElementById('editModal');
    const editModalContent = document.getElementById('editModalContent');
    const editDataForm = document.getElementById('editDataForm');

    // Pengaturan global untuk SweetAlert2 dengan gaya Tailwind
    const swalWithTailwind = {
        customClass: {
            popup: 'p-4 sm:p-6 w-full max-w-sm rounded-lg shadow-lg',
            title: 'text-xl font-semibold text-gray-800',
            htmlContainer: 'mt-2 text-sm text-gray-600',
            actions: 'mt-4 sm:mt-6',
            confirmButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
            cancelButton: 'ml-3 px-4 py-2 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
        },
        buttonsStyling: false
    };

    // === Fungsi Modal ===
    function showAddModal(triggerElement) {
        activeElementBeforeModal = triggerElement || document.activeElement;
        addDataForm.reset();
        addModal.classList.remove('hidden');
        setTimeout(() => {
            addModalContent.classList.remove('opacity-0', 'scale-95');
            document.getElementById('txtNamaMitraDudika').focus();
        }, 10);
    }

    function closeAddModal() {
        addModalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            addModal.classList.add('hidden');
            if (activeElementBeforeModal) activeElementBeforeModal.focus();
        }, 300);
    }

    function showEditModal(triggerElement) {
        activeElementBeforeModal = triggerElement || document.activeElement;
        editModal.classList.remove('hidden');
        setTimeout(() => {
            editModalContent.classList.remove('opacity-0', 'scale-95');
            document.getElementById('edit_txtNamaMitraDudika').focus();
        }, 10);
    }

    function closeEditModal() {
        editModalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            editModal.classList.add('hidden');
            if (activeElementBeforeModal) activeElementBeforeModal.focus();
        }, 300);
    }

    // === Fungsi Aksi Data (CRUD) ===
    async function handleFetch(formData) {
        try {
            const response = await fetch('', { // Mengirim ke URL halaman saat ini
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Network response was not ok, status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Fetch error:', error);
            // Menampilkan notifikasi error jaringan di sini agar pengguna tahu
            Swal.fire({...swalWithTailwind, icon: 'error', title: 'Error Jaringan', text: 'Tidak dapat terhubung ke server. Periksa koneksi Anda.'});
            // Melempar kembali error agar bisa ditangkap oleh fungsi pemanggil jika perlu
            throw error;
        }
    }

    // TAHAP 1 EDIT: Ambil data dan tampilkan di modal
    async function editData(triggerElement, id) {
        try {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id_mitra', id);

            const res = await handleFetch(formData);

            if (res.status === 'success') {
                const data = res.data;
                document.getElementById('edit_mitra_id').value = data.IdMitraDudika;
                document.getElementById('edit_txtNamaMitraDudika').value = data.txtNamaMitraDudika;
                document.getElementById('edit_txtAlamatMitra').value = data.txtAlamatMitra;
                document.getElementById('edit_txtEmailMitra').value = data.txtEmailMitra;
                document.getElementById('edit_txtNamaKepalaDudika').value = data.txtNamaKepalaDudika;
                showEditModal(triggerElement);
            } else {
                Swal.fire({...swalWithTailwind, icon: 'error', title: 'Gagal Memuat', text: res.message || 'Data tidak ditemukan.'});
            }
        } catch (error) {
            // Error sudah ditangani di handleFetch, tidak perlu notifikasi ganda
        }
    }
    
    // AKSI HAPUS: Konfirmasi, hapus, notifikasi, lalu reload
    function deleteData(triggerElement, id) {
        Swal.fire({
            ...swalWithTailwind,
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            customClass: {
                ...swalWithTailwind.customClass,
                confirmButton: `${swalWithTailwind.customClass.confirmButton} bg-red-600 hover:bg-red-700 focus:ring-red-500`,
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id_mitra', id);

                    const res = await handleFetch(formData);

                    if (res.status === 'success') {
                        // TUNGGU notifikasi selesai (timer 1.5 detik), BARU reload
                        await Swal.fire({
                            ...swalWithTailwind,
                            icon: 'success',
                            title: 'Terhapus!',
                            text: 'Data mitra berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({...swalWithTailwind, icon: 'error', title: 'Gagal Menghapus', text: res.message || 'Terjadi kesalahan.'});
                    }
                } catch (error) {
                    // Error sudah ditangani di handleFetch
                }
            }
        });
    }
    
    function exportData() {
        Swal.fire({...swalWithTailwind, title: 'Fungsi Dalam Pengembangan', text: 'Fitur export data ke Excel akan segera tersedia!', icon: 'info'});
    }

    // === Event Listeners ===
    
    // Handler utama untuk form TAMBAH dan UPDATE
    async function handleFormSubmit(event, form, modalCloser) {
        event.preventDefault();
        const formData = new FormData(form);
        const action = formData.get('action'); // 'add' atau 'update'
        
        try {
            const res = await handleFetch(formData);

            if (res.status === 'success') {
                modalCloser();
                
                // TUNGGU notifikasi selesai (timer 1.5 detik), BARU reload
                await Swal.fire({
                    ...swalWithTailwind,
                    icon: 'success',
                    title: 'Berhasil!',
                    text: `Data mitra berhasil di${action === 'add' ? 'tambahkan' : 'perbarui'}.`,
                    timer: 1500,
                    showConfirmButton: false
                });
                window.location.reload();
            } else {
                Swal.fire({...swalWithTailwind, icon: 'error', title: 'Gagal', text: res.message || 'Terjadi kesalahan saat menyimpan data.'});
            }
        } catch (error) {
            // Error sudah ditangani di handleFetch
        }
    }

    addDataForm.addEventListener('submit', (e) => handleFormSubmit(e, addDataForm, closeAddModal));
    editDataForm.addEventListener('submit', (e) => handleFormSubmit(e, editDataForm, closeEditModal));

    // Event listener untuk menutup modal dengan klik di luar atau tombol Esc
    addModal.addEventListener('click', (event) => { if (event.target === addModal) closeAddModal(); });
    editModal.addEventListener('click', (event) => { if (event.target === editModal) closeEditModal(); });
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (!addModal.classList.contains('hidden')) closeAddModal();
            if (!editModal.classList.contains('hidden')) closeEditModal();
        }
    });
</script>

<?php
// Menutup koneksi database yang dibuka untuk render halaman
$stmt->close();
$koneksi->close();
?>