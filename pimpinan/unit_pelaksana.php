<?php
// === LOGIKA PAGINASI & PENGAMBILAN DATA ===
$limit = 10; // Data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Pastikan halaman tidak kurang dari 1
$page = max($page, 1); // Ini akan memastikan $page minimal 1
$offset = ($page - 1) * $limit;

// Ambil total data untuk paginasi
$total_result = $koneksi->query("SELECT COUNT(*) AS total FROM tblunitpelaksana");
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Pastikan halaman tidak melebihi total halaman yang tersedia
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Query untuk mengambil data unit pelaksana
$sql = "SELECT IdUnitPelaksana, txtNamaUnitPelaksPolimdo, txtNamaStafAdminUnit 
        FROM tblunitpelaksana 
        ORDER BY IdUnitPelaksana ASC 
        LIMIT $limit OFFSET $offset";
$result = $koneksi->query($sql);
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">Data Master Unit Pelaksana</h2>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <button onclick="showAddModal(this)" 
                class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-blue-200 focus:ring-offset-2">
                <i class="fas fa-plus fa-sm"></i>
                <span class="text-sm font-medium">Tambah Unit</span>
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
            <input type="text" id="searchInput" placeholder="Cari nama unit, staf admin..."
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
    </div>

    <div class="hidden sm:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Unit</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Unit Pelaksana</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staf Admin</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result->num_rows > 0):
                    $result->data_seek(0); // Reset pointer
                    while($row = $result->fetch_assoc()):
                        // Cek ketergantungan
                        $check_stmt = $koneksi->prepare("SELECT COUNT(*) as count FROM tblnamakegiatanks WHERE IdUnitPelaksana = ?");
                        $check_stmt->bind_param("s", $row['IdUnitPelaksana']);
                        $check_stmt->execute();
                        $is_in_use = $check_stmt->get_result()->fetch_assoc()['count'] > 0;
                        $check_stmt->close();

                        // Ekstrak nama jurusan dari nama unit
                        $jurusan_name = str_replace('Jurusan ', '', $row['txtNamaUnitPelaksPolimdo']);
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500"><?= htmlspecialchars($row['IdUnitPelaksana']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['txtNamaUnitPelaksPolimdo']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['txtNamaStafAdminUnit']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-center space-x-2">
                            <button onclick="editData(this, '<?= $row['IdUnitPelaksana'] ?>', '<?= htmlspecialchars(addslashes($row['txtNamaUnitPelaksPolimdo'])) ?>', '<?= htmlspecialchars(addslashes($row['txtNamaStafAdminUnit'])) ?>', '<?= htmlspecialchars($jurusan_name) ?>')" 
                                class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                <i class="fas fa-edit fa-lg"></i>
                            </button>
                            <button onclick="deleteData('<?= $row['IdUnitPelaksana'] ?>', <?= $is_in_use ? 'true' : 'false' ?>)" 
                                class="text-red-600 hover:text-red-900 transition-colors" title="Hapus">
                                <i class="fas fa-trash fa-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="4" class="text-center py-4 text-gray-500">Tidak ada data ditemukan.</td>
                </tr>
                <?php endif; ?>
                </tbody>
        </table>
    </div>

    <div class="block sm:hidden space-y-3 p-4">
        <?php if ($result->num_rows > 0):
            $result->data_seek(0); // Reset pointer
            while($row = $result->fetch_assoc()):
                $check_stmt = $koneksi->prepare("SELECT COUNT(*) as count FROM tblnamakegiatanks WHERE IdUnitPelaksana = ?");
                $check_stmt->bind_param("s", $row['IdUnitPelaksana']);
                $check_stmt->execute();
                $is_in_use = $check_stmt->get_result()->fetch_assoc()['count'] > 0;
                $check_stmt->close();
                $jurusan_name = str_replace('Jurusan ', '', $row['txtNamaUnitPelaksPolimdo']);
        ?>
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-xs">
            <div class="flex justify-between items-start">
                <h3 class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['txtNamaUnitPelaksPolimdo']) ?></h3>
                <div class="flex space-x-2">
                    <button onclick="editData(this, '<?= $row['IdUnitPelaksana'] ?>', '<?= htmlspecialchars(addslashes($row['txtNamaUnitPelaksPolimdo'])) ?>', '<?= htmlspecialchars(addslashes($row['txtNamaStafAdminUnit'])) ?>', '<?= htmlspecialchars($jurusan_name) ?>')" 
                        class="text-indigo-600 hover:text-indigo-500 transition-colors" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteData('<?= $row['IdUnitPelaksana'] ?>', <?= $is_in_use ? 'true' : 'false' ?>)" 
                        class="text-red-600 hover:text-red-500 transition-colors" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center">
                    <i class="fas fa-tag text-gray-400 mr-2 w-4"></i>
                    <span class="text-gray-500 font-mono"><?= htmlspecialchars($row['IdUnitPelaksana']) ?></span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-user-tie text-gray-400 mr-2 w-4"></i>
                    <span class="text-gray-600"><?= htmlspecialchars($row['txtNamaStafAdminUnit']) ?></span>
                </div>
            </div>
        </div>
        <?php endwhile; else: ?>
        <p class="text-center py-4 text-gray-500">Tidak ada data ditemukan.</p>
        <?php endif; ?>
        </div>

    <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
        <p class="text-sm text-gray-600">
            Menampilkan <span class="font-medium"><?= $total_data > 0 ? $offset + 1 : 0 ?>-<?= min($offset + $limit, $total_data) ?></span> dari <span class="font-medium"><?= $total_data ?></span> data
        </p>
        <?php if ($total_pages > 1): ?>
        <nav class="flex items-center space-x-1">
            <a href="?page=<?= max(1, $page-1) ?>" class="px-3 py-1 border <?= $page <= 1 ? 'border-gray-300 text-gray-400 bg-gray-100 pointer-events-none' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' ?> rounded-md text-sm font-medium">Previous</a>
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" class="px-3 py-1 border <?= $i == $page ? 'border-blue-500 bg-blue-500 text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' ?> rounded-md text-sm font-medium shadow-sm"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?page=<?= min($total_pages, $page+1) ?>" class="px-3 py-1 border <?= $page >= $total_pages ? 'border-gray-300 text-gray-400 bg-gray-100 pointer-events-none' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' ?> rounded-md text-sm font-medium">Next</a>
        </nav>
        <?php endif; ?>
    </div>
</div>

<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0" id="addModalContent">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Tambah Unit Pelaksana</h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <form id="addDataForm" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add">
            <div>
                <label for="jurusan" class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
                <select id="jurusan" name="jurusan" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
                    <option value="" disabled selected>Pilih Jurusan</option>
                    <option value="Teknik Elektro">Teknik Elektro</option>
                    <option value="Teknik Mesin">Teknik Mesin</option>
                    <option value="Teknik Sipil">Teknik Sipil</option>
                    <option value="Akuntansi">Akuntansi</option>
                    <option value="Administrasi Bisnis">Administrasi Bisnis</option>
                    <option value="Pariwisata">Pariwisata</option>
                </select>
            </div>
            <div>
                <label for="txtNamaUnitPelaksPolimdo" class="block text-sm font-medium text-gray-700 mb-1">Nama Unit Pelaksana</label>
                <input type="text" id="txtNamaUnitPelaksPolimdo" name="txtNamaUnitPelaksPolimdo" required placeholder="Contoh: Jurusan Teknik Elektro" class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="txtNamaStafAdminUnit" class="block text-sm font-medium text-gray-700 mb-1">Nama Staf Admin</label>
                <input type="text" id="txtNamaStafAdminUnit" name="txtNamaStafAdminUnit" required placeholder="Contoh: Bpk. John Doe, S.T." class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Batal</button>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Tambah Unit</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0" id="editModalContent">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Edit Unit Pelaksana</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400"><i class="fas fa-times"></i></button>
        </div>
        <form id="editDataForm" class="p-6 space-y-4">
            <input type="hidden" id="edit_IdUnit" name="IdUnitPelaksana">
            <input type="hidden" name="action" value="update">
            <div>
                <label for="edit_jurusan" class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
                <select id="edit_jurusan" name="jurusan" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
                    <option value="" disabled>Pilih Jurusan</option>
                    <option value="Teknik Elektro">Teknik Elektro</option>
                    <option value="Teknik Mesin">Teknik Mesin</option>
                    <option value="Teknik Sipil">Teknik Sipil</option>
                    <option value="Akuntansi">Akuntansi</option>
                    <option value="Administrasi Bisnis">Administrasi Bisnis</option>
                    <option value="Pariwisata">Pariwisata</option>
                </select>
            </div>
            <div>
                <label for="edit_txtNamaUnitPelaksPolimdo" class="block text-sm font-medium text-gray-700 mb-1">Nama Unit Pelaksana</label>
                <input type="text" id="edit_txtNamaUnitPelaksPolimdo" name="txtNamaUnitPelaksPolimdo" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label for="edit_txtNamaStafAdminUnit" class="block text-sm font-medium text-gray-700 mb-1">Nama Staf Admin</label>
                <input type="text" id="edit_txtNamaStafAdminUnit" name="txtNamaStafAdminUnit" required class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
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
    // === All modal-related JS functions and variables are the same as your template ===
    let activeElementBeforeModal;
    const addModal = document.getElementById('addModal');
    const addModalContent = document.getElementById('addModalContent');
    const addDataForm = document.getElementById('addDataForm');
    const editModal = document.getElementById('editModal');
    const editModalContent = document.getElementById('editModalContent');
    const editDataForm = document.getElementById('editDataForm');
    const swalWithTailwind = {
        customClass: { popup: 'p-4 sm:p-6 w-full max-w-sm rounded-lg shadow-lg', title: 'text-xl font-semibold text-gray-800', htmlContainer: 'mt-2 text-sm text-gray-600', actions: 'mt-4 sm:mt-6', confirmButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500', cancelButton: 'ml-3 px-4 py-2 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500' },
        buttonsStyling: false
    };

    function showAddModal(triggerElement) { activeElementBeforeModal = triggerElement || document.activeElement; addDataForm.reset(); addModal.classList.remove('hidden'); setTimeout(() => { addModalContent.classList.remove('opacity-0', 'scale-95'); document.getElementById('jurusan').focus(); }, 10); }
    function closeAddModal() { addModalContent.classList.add('opacity-0', 'scale-95'); setTimeout(() => { addModal.classList.add('hidden'); if (activeElementBeforeModal) activeElementBeforeModal.focus(); }, 300); }
    function showEditModal(triggerElement) { activeElementBeforeModal = triggerElement || document.activeElement; editModal.classList.remove('hidden'); setTimeout(() => { editModalContent.classList.remove('opacity-0', 'scale-95'); document.getElementById('edit_jurusan').focus(); }, 10); }
    function closeEditModal() { editModalContent.classList.add('opacity-0', 'scale-95'); setTimeout(() => { editModal.classList.add('hidden'); if (activeElementBeforeModal) activeElementBeforeModal.focus(); }, 300); }

    // === JS MODIFICATION: Functions now connect to backend ===

    function editData(triggerElement, id, unitName, adminName, jurusan) {
        document.getElementById('edit_IdUnit').value = id;
        document.getElementById('edit_jurusan').value = jurusan;
        document.getElementById('edit_txtNamaUnitPelaksPolimdo').value = unitName;
        document.getElementById('edit_txtNamaStafAdminUnit').value = adminName;
        showEditModal(triggerElement);
    }

    function deleteData(id, isInUse) {
        if (isInUse) {
            Swal.fire({...swalWithTailwind, icon: 'error', title: 'Gagal Menghapus!', html: `Unit Pelaksana ID <b>${id}</b> sedang digunakan pada data kegiatan dan tidak bisa dihapus.`});
            return;
        }
        Swal.fire({
            ...swalWithTailwind, title: 'Apakah Anda yakin?', text: "Data yang dihapus tidak dapat dikembalikan!", icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal',
            customClass: {...swalWithTailwind.customClass, confirmButton: `${swalWithTailwind.customClass.confirmButton} bg-red-600 hover:bg-red-700 focus:ring-red-500`}
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('IdUnitPelaksana', id);
                try {
                    const response = await fetch('unit_pelaksana_action.php', { method: 'POST', body: formData });
                    const res = await response.json();
                    if (res.status === 'success') {
                        await Swal.fire({...swalWithTailwind, title: 'Terhapus!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false});
                        window.location.reload();
                    } else {
                        Swal.fire({...swalWithTailwind, title: 'Gagal!', text: res.message, icon: 'error'});
                    }
                } catch(error) {
                    Swal.fire({...swalWithTailwind, title: 'Error!', text: 'Tidak dapat terhubung ke server.', icon: 'error'});
                }
            }
        });
    }

    function exportData() { Swal.fire({...swalWithTailwind, title: 'Fungsi Dalam Pengembangan', text: 'Fitur export data ke Excel akan segera tersedia!', icon: 'info'}); }
    
    // === Event Listeners (Modified for Backend) ===
    document.getElementById('jurusan').addEventListener('change', function() {
        document.getElementById('txtNamaUnitPelaksPolimdo').value = 'Jurusan ' + this.value;
    });
    document.getElementById('edit_jurusan').addEventListener('change', function() {
        document.getElementById('edit_txtNamaUnitPelaksPolimdo').value = 'Jurusan ' + this.value;
    });

    async function handleFormSubmit(formElement, url) {
        const formData = new FormData(formElement);
        const action = formData.get('action');
        const isEditing = action === 'update';
        
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const res = await response.json();

            if (res.status === 'success') {
                isEditing ? closeEditModal() : closeAddModal();
                await Swal.fire({...swalWithTailwind, title: 'Berhasil!', text: `Data berhasil ${isEditing ? 'diperbarui' : 'ditambahkan'}.`, icon: 'success', timer: 1500, showConfirmButton: false});
                window.location.reload();
            } else {
                Swal.fire({...swalWithTailwind, title: 'Gagal!', text: res.message, icon: 'error'});
            }
        } catch(error) {
            Swal.fire({...swalWithTailwind, title: 'Error!', text: 'Tidak dapat terhubung ke server.', icon: 'error'});
        }
    }

    addDataForm.addEventListener('submit', (e) => { e.preventDefault(); handleFormSubmit(addDataForm, 'unit_pelaksana_action.php'); });
    editDataForm.addEventListener('submit', (e) => { e.preventDefault(); handleFormSubmit(editDataForm, 'unit_pelaksana_action.php'); });

    addModal.addEventListener('click', (event) => { if (event.target === addModal) closeAddModal(); });
    editModal.addEventListener('click', (event) => { if (event.target === editModal) closeEditModal(); });
    window.addEventListener('keydown', (event) => { if (event.key === 'Escape') { if (!addModal.classList.contains('hidden')) closeAddModal(); if (!editModal.classList.contains('hidden')) closeEditModal(); }});
</script>

<?php $koneksi->close(); ?>