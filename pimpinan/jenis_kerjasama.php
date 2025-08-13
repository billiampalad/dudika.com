<?php
// === LOGIKA PAGINASI & PENGAMBILAN DATA ===
$limit = 10; // Data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Ensure page is never less than 1
$offset = ($page - 1) * $limit;

// Ambil total data untuk paginasi
$total_result = $koneksi->query("SELECT COUNT(*) AS total FROM tbljenisks");
$total_data = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Query untuk mengambil data jenis KS yang akan ditampilkan
$sql = "SELECT IdJenisKS, txtNamaJenisKS FROM tbljenisks ORDER BY IdJenisKS ASC LIMIT $limit OFFSET $offset";
$result = $koneksi->query($sql);

// Buat array untuk mengecek keterpakain ID di tabel lain
$list_jenis_ks = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $list_jenis_ks[] = $row;
    }
}
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">Data Master Jenis Kerjasama</h2>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <button onclick="setupAddForm(this)"
                class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-blue-200 focus:ring-offset-2">
                <i class="fas fa-plus fa-sm"></i>
                <span class="text-sm font-medium">Tambah Jenis KS</span>
            </button>
            <button onclick="exportData()"
                class="bg-gradient-to-r from-emerald-600 to-green-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-emerald-200 focus:ring-offset-2">
                <i class="fas fa-file-excel fa-sm"></i>
                <span class="text-sm font-medium">Export Excel</span>
            </button>
        </div>
    </div>

    <div class="px-6 py-4 border-b border-gray-100">
        <form class="relative max-w-md flex items-center" onsubmit="searchJenisKS(event)">
            <!-- Icon Search -->
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>

            <!-- Input -->
            <input
                type="text"
                id="searchInput"
                name="keyword"
                placeholder="Cari jenis kerjasama..."
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">

            <!-- Tombol Cari -->
            <button
                type="submit"
                class="px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-r-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 transition">
                Cari
            </button>
        </form>
    </div>

    <div class="hidden sm:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Jenis KS</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Jenis Kerjasama</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($list_jenis_ks)): ?>
                    <?php foreach ($list_jenis_ks as $jenis):
                        // Cek apakah ID digunakan di tabel lain
                        $check_sql = $koneksi->prepare("SELECT COUNT(*) as count FROM tblnamakegiatanks WHERE IdJenisKS = ?");
                        $check_sql->bind_param("s", $jenis['IdJenisKS']);
                        $check_sql->execute();
                        $is_in_use = $check_sql->get_result()->fetch_assoc()['count'] > 0;
                        $check_sql->close();
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500"><?= htmlspecialchars($jenis['IdJenisKS']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($jenis['txtNamaJenisKS']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-center space-x-2">
                                    <button onclick="setupEditForm(this, '<?= htmlspecialchars($jenis['IdJenisKS']) ?>', '<?= htmlspecialchars(addslashes($jenis['txtNamaJenisKS'])) ?>')"
                                        class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                                        <i class="fas fa-edit fa-lg"></i>
                                    </button>
                                    <button onclick="deleteData(this, '<?= htmlspecialchars($jenis['IdJenisKS']) ?>', <?= $is_in_use ? 'true' : 'false' ?>)"
                                        class="text-red-600 hover:text-red-900 transition-colors" title="Hapus">
                                        <i class="fas fa-trash fa-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-gray-500">Tidak ada data ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="block sm:hidden space-y-3 p-4">
        <?php if (!empty($list_jenis_ks)): ?>
            <?php foreach ($list_jenis_ks as $jenis):
                $check_sql = $koneksi->prepare("SELECT COUNT(*) as count FROM tblnamakegiatanks WHERE IdJenisKS = ?");
                $check_sql->bind_param("s", $jenis['IdJenisKS']);
                $check_sql->execute();
                $is_in_use = $check_sql->get_result()->fetch_assoc()['count'] > 0;
                $check_sql->close();
            ?>
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-xs">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($jenis['txtNamaJenisKS']) ?></h3>
                            <p class="text-xs text-gray-500 font-mono mt-1"><?= htmlspecialchars($jenis['IdJenisKS']) ?></p>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="setupEditForm(this, '<?= htmlspecialchars($jenis['IdJenisKS']) ?>', '<?= htmlspecialchars(addslashes($jenis['txtNamaJenisKS'])) ?>')"
                                class="text-indigo-600 hover:text-indigo-500 transition-colors" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteData(this, '<?= htmlspecialchars($jenis['IdJenisKS']) ?>', <?= $is_in_use ? 'true' : 'false' ?>)"
                                class="text-red-600 hover:text-red-500 transition-colors" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center py-4 text-gray-500">Tidak ada data ditemukan.</p>
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
        <?php if ($total_pages > 1): ?>
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

<div id="dataModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800" id="modalTitle"></h3>
            <button onclick="closeDataModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="dataForm" class="p-6 space-y-4" novalidate>
            <input type="hidden" id="IdJenisKS" name="IdJenisKS">
            <input type="hidden" id="formAction" name="action" value="">
            <div>
                <label for="txtNamaJenisKS" class="block text-sm font-medium text-gray-700 mb-1">Nama Jenis Kerjasama</label>
                <input type="text" id="txtNamaJenisKS" name="txtNamaJenisKS" required placeholder="Contoh: Penelitian Bersama"
                    class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeDataModal()"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Batal
                </button>
                <button type="submit" id="submitButton"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // JS: All variables and modal functions remain the same as your template.
    let activeElementBeforeModal;
    const dataModal = document.getElementById('dataModal');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('modalTitle');
    const dataForm = document.getElementById('dataForm');
    const submitButton = document.getElementById('submitButton');
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

    // JS MODIFICATION: Setting the correct action for the form
    function setupAddForm(triggerElement) {
        dataForm.reset();
        modalTitle.textContent = 'Tambah Jenis Kerjasama';
        submitButton.textContent = 'Tambah';
        document.getElementById('formAction').value = 'add'; // Set action for backend
        openDataModal(triggerElement);
    }

    function setupEditForm(triggerElement, id, currentName) {
        dataForm.reset();
        modalTitle.textContent = 'Edit Jenis Kerjasama';
        submitButton.textContent = 'Simpan';
        document.getElementById('IdJenisKS').value = id;
        document.getElementById('txtNamaJenisKS').value = currentName;
        document.getElementById('formAction').value = 'update'; // Set action for backend
        openDataModal(triggerElement);
    }

    function openDataModal(triggerElement) {
        activeElementBeforeModal = triggerElement || document.activeElement;
        dataModal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('opacity-0', 'scale-95');
            document.getElementById('txtNamaJenisKS').focus();
        }, 10);
    }

    function closeDataModal() {
        modalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            dataModal.classList.add('hidden');
            if (activeElementBeforeModal) activeElementBeforeModal.focus();
        }, 300);
    }

    // JS MODIFICATION: `deleteData` now sends request to backend
    function deleteData(triggerElement, id, isInUse) {
        if (isInUse) {
            Swal.fire({
                ...swalWithTailwind,
                icon: 'error',
                title: 'Gagal Menghapus!',
                html: `Jenis kerjasama ID <b>${id}</b> sedang digunakan pada data lain dan tidak bisa dihapus.`
            });
            return;
        }

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
                confirmButton: `${swalWithTailwind.customClass.confirmButton} bg-red-600 hover:bg-red-700 focus:ring-red-500`
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('IdJenisKS', id);

                try {
                    const response = await fetch('pimpinan/jenis_ks_action.php', {
                        method: 'POST',
                        body: formData
                    });
                    const res = await response.json();
                    if (res.status === 'success') {
                        await Swal.fire({
                            ...swalWithTailwind,
                            title: 'Terhapus!',
                            text: res.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            ...swalWithTailwind,
                            title: 'Gagal!',
                            text: res.message,
                            icon: 'error'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        ...swalWithTailwind,
                        title: 'Error!',
                        text: 'Tidak dapat terhubung ke server.',
                        icon: 'error'
                    });
                }
            }
        });
    }

    function exportData() {
        window.open('pimpinan/export_excel.php?action=export_jenis_ks', '_blank');
    }

    // JS MODIFICATION: `dataForm` event listener now sends data to backend
    dataForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(dataForm);
        const isEditing = formData.get('action') === 'update';

        try {
            const response = await fetch('pimpinan/jenis_ks_action.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();

            if (res.status === 'success') {
                closeDataModal();
                await Swal.fire({
                    ...swalWithTailwind,
                    title: 'Berhasil!',
                    text: `Data jenis kerjasama berhasil ${isEditing ? 'diperbarui' : 'ditambahkan'}.`,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                window.location.reload();
            } else {
                Swal.fire({
                    ...swalWithTailwind,
                    title: 'Gagal!',
                    text: res.message,
                    icon: 'error'
                });
            }
        } catch (error) {
            Swal.fire({
                ...swalWithTailwind,
                title: 'Error!',
                text: 'Tidak dapat terhubung ke server.',
                icon: 'error'
            });
        }
    });

    // Fungsi untuk melakukan pencarian
    async function searchJenisKS(event) {
        event.preventDefault();
        const keyword = document.getElementById('searchInput').value.trim();

        try {
            // Tampilkan loading indicator
            const loadingSwal = Swal.fire({
                ...swalWithTailwind,
                title: 'Mencari...',
                html: 'Sedang memproses pencarian',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Kirim permintaan pencarian ke server
            const response = await fetch(`pimpinan/jenis_ks_action.php?action=search&keyword=${encodeURIComponent(keyword)}`);
            const data = await response.json();

            // Tutup loading indicator
            await Swal.close(loadingSwal);

            if (data.status === 'success') {
                // Update tabel dengan hasil pencarian
                updateJenisKSTable(data.data);
            } else {
                Swal.fire({
                    ...swalWithTailwind,
                    icon: 'error',
                    title: 'Pencarian Gagal',
                    text: data.message || 'Terjadi kesalahan saat melakukan pencarian'
                });
            }
        } catch (error) {
            Swal.fire({
                ...swalWithTailwind,
                icon: 'error',
                title: 'Error',
                text: 'Terjadi kesalahan saat melakukan pencarian'
            });
        }
    }

    // Fungsi untuk memperbarui tabel dengan hasil pencarian
    function updateJenisKSTable(data) {
        const tableBody = document.querySelector('tbody');
        const cardContainer = document.querySelector('.block.sm\\:hidden.space-y-3');

        // Kosongkan konten sebelumnya
        tableBody.innerHTML = '';
        cardContainer.innerHTML = '';

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-gray-500">Tidak ada data ditemukan.</td></tr>';
            cardContainer.innerHTML = '<p class="text-center py-4 text-gray-500">Tidak ada data ditemukan.</p>';
            return;
        }

        // Update tabel (untuk desktop)
        data.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors';
            row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">${item.IdJenisKS}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.txtNamaJenisKS}</td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-center space-x-2">
                    <button onclick="setupEditForm(this, '${item.IdJenisKS}', '${item.txtNamaJenisKS.replace(/'/g, "\\'")}')"
                        class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Edit">
                        <i class="fas fa-edit fa-lg"></i>
                    </button>
                    <button onclick="deleteData(this, '${item.IdJenisKS}', false)"
                        class="text-red-600 hover:text-red-900 transition-colors" title="Hapus">
                        <i class="fas fa-trash fa-lg"></i>
                    </button>
                </div>
            </td>
        `;
            tableBody.appendChild(row);
        });

        // Update card (untuk mobile)
        data.forEach(item => {
            const card = document.createElement('div');
            card.className = 'bg-white border border-gray-200 rounded-lg p-4 shadow-xs';
            card.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">${item.txtNamaJenisKS}</h3>
                    <p class="text-xs text-gray-500 font-mono mt-1">${item.IdJenisKS}</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="setupEditForm(this, '${item.IdJenisKS}', '${item.txtNamaJenisKS.replace(/'/g, "\\'")}')"
                        class="text-indigo-600 hover:text-indigo-500 transition-colors" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteData(this, '${item.IdJenisKS}', false)"
                        class="text-red-600 hover:text-red-500 transition-colors" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
            cardContainer.appendChild(card);
        });
    }

    dataModal.addEventListener('click', (event) => {
        if (event.target === dataModal) closeDataModal();
    });
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dataModal.classList.contains('hidden')) closeDataModal();
    });
</script>

</body>

</html>
<?php $koneksi->close(); ?>