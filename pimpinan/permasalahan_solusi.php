<?php
// Ambil daftar kegiatan HANYA untuk dropdown di modal.
// Data utama akan diambil via AJAX.
$kegiatan_list = $koneksi->query("SELECT IdKKS, txtNamaKegiatanKS FROM tblnamakegiatanks ORDER BY txtNamaKegiatanKS ASC");

// Ambil nilai filter dari URL untuk ditampilkan di form
$search = $_GET['search'] ?? '';
$filter_urgensi = $_GET['urgensi'] ?? '';
?>

<main>
    <div class="space-y-6">
        <div class="flex flex-wrap justify-between items-center gap-4 bg-white rounded-xl p-6 shadow-sm border">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Permasalahan dan Solusi</h1>
                <p class="text-sm text-gray-500 mt-1">Identifikasi dan kelola setiap kendala dalam kerjasama.</p>
            </div>
            <button onclick="showEditModal(null)" class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 flex items-center space-x-2 shadow-md">
                <i class="fas fa-plus fa-sm"></i><span class="text-sm font-medium">Laporkan Kendala Baru</span>
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <form id="filterForm" method="GET" action="" class="px-6 py-5 border-b">
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                    <h3 class="font-bold text-gray-800 mt-2">Daftar Kendala dan Solusi</h3>
                    <div class="w-full sm:w-auto grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400 text-sm"></i>
                            </div>
                            <input type="text" name="search" id="searchInput" placeholder="Cari kegiatan atau kendala..." value="<?= htmlspecialchars($search) ?>" class="pl-9 pr-4 py-2 border rounded-lg w-full text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        </div>
                        <select name="urgensi" id="filterUrgensi" class="text-sm px-3 py-2 border rounded-lg bg-white text-gray-700 focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                            <option value="">Semua Urgensi</option>
                            <option value="tinggi" <?= $filter_urgensi == 'tinggi' ? 'selected' : '' ?>>Tinggi</option>
                            <option value="sedang" <?= $filter_urgensi == 'sedang' ? 'selected' : '' ?>>Sedang</option>
                            <option value="rendah" <?= $filter_urgensi == 'rendah' ? 'selected' : '' ?>>Rendah</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="page" id="pageInput" value="1">
            </form>

            <div class="hidden sm:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-blue-50 to-indigo-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama Kegiatan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kendala</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Solusi</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="problems-table-body" class="bg-white divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="problems-mobile-container" class="block sm:hidden space-y-4 p-4">
            </div>

            <div id="pagination-container" class="px-6 py-4 border-t">
            </div>
        </div>
    </div>
</main>

<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
    <div id="editModalContent" class="bg-white w-full max-w-2xl rounded-xl shadow-2xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">
        <!-- Modal Header -->
        <div class="flex justify-between items-center px-4 py-3 sm:px-6 sm:py-4 border-b background-icon">
            <h3 class="text-lg sm:text-xl font-semibold text-white" id="modalTitle"></h3>
            <button onclick="closeEditModal()" class="text-white hover:text-gray-200 transition-colors text-2xl font-light p-1">&times;</button>
        </div>

        <!-- Modal Body -->
        <form id="kendalaForm" class="p-4 sm:p-6 space-y-4 sm:space-y-6 overflow-y-auto flex-grow">
            <input type="hidden" id="IdMslhDanSolusi" name="IdMslhDanSolusi">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <div class="space-y-1">
                    <label for="IdKKS" class="block text-sm font-medium text-gray-600">Nama Kegiatan</label>
                    <select id="IdKKS" name="IdKKS" class="w-full px-3 py-2 sm:px-4 sm:py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all outline-none text-sm sm:text-base">
                        <option value="">-- Pilih Kegiatan --</option>
                        <?php mysqli_data_seek($kegiatan_list, 0);
                        while ($kegiatan = $kegiatan_list->fetch_assoc()): ?>
                            <option value="<?= $kegiatan['IdKKS'] ?>"><?= htmlspecialchars($kegiatan['txtNamaKegiatanKS']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="space-y-1">
                    <label for="urgensi" class="block text-sm font-medium text-gray-600">Tingkat Urgensi</label>
                    <select id="urgensi" name="urgensi" class="w-full px-3 py-2 sm:px-4 sm:py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all outline-none text-sm sm:text-base">
                        <option value="rendah">Rendah</option>
                        <option value="sedang">Sedang</option>
                        <option value="tinggi">Tinggi</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1">
                <label for="txtKendala" class="block text-sm font-medium text-gray-600">Kendala yang Dihadapi</label>
                <textarea id="txtKendala" name="txtKendala" rows="3" class="w-full px-3 py-2 sm:px-4 sm:py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all outline-none resize-none text-sm sm:text-base"></textarea>
            </div>

            <div class="space-y-1">
                <label for="txtUpayaUtkAtasiMslh" class="block text-sm font-medium text-gray-600">Upaya Penyelesaian (Solusi)</label>
                <textarea id="txtUpayaUtkAtasiMslh" name="txtUpayaUtkAtasiMslh" rows="3" class="w-full px-3 py-2 sm:px-4 sm:py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all outline-none resize-none text-sm sm:text-base"></textarea>
            </div>

            <div class="space-y-1">
                <label for="txtRekomUtkPerbaikan" class="block text-sm font-medium text-gray-600">Rekomendasi Perbaikan ke Depan</label>
                <textarea id="txtRekomUtkPerbaikan" name="txtRekomUtkPerbaikan" rows="3" class="w-full px-3 py-2 sm:px-4 sm:py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all outline-none resize-none text-sm sm:text-base"></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-2 sm:space-x-3 pt-4 sm:pt-6 border-t">
                <button type="button" onclick="closeEditModal()" class="px-3 py-2 sm:px-5 sm:py-2.5 border border-gray-300 rounded-lg text-xs sm:text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" class="inline-flex items-center px-3 py-2 sm:px-5 sm:py-2.5 text-xs sm:text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors shadow-sm">
                    <i class="fas fa-save text-xs sm:text-sm mr-1 sm:mr-2"></i>
                    <span id="submitButtonText"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // === Konfigurasi & Elemen Global ===
        const filterForm = document.getElementById('filterForm');
        const searchInput = document.getElementById('searchInput');
        const filterUrgensi = document.getElementById('filterUrgensi');
        const pageInput = document.getElementById('pageInput');
        const tableBody = document.getElementById('problems-table-body');
        const mobileContainer = document.getElementById('problems-mobile-container');
        const paginationContainer = document.getElementById('pagination-container');
        const editModal = document.getElementById('editModal');
        const editModalContent = document.getElementById('editModalContent');
        const modalTitle = document.getElementById('modalTitle');
        const kendalaForm = document.getElementById('kendalaForm');
        const submitButtonText = document.getElementById('submitButtonText');

        const swalConfig = {
            customClass: {
                confirmButton: 'px-4 py-2 rounded-lg text-white',
                cancelButton: 'px-4 py-2 rounded-lg border'
            },
            buttonsStyling: false
        };

        // === Fungsi Render Tampilan ===
        const getUrgensiStyle = (urgensi) => ({
            'tinggi': 'bg-red-500 text-red-600',
            'sedang': 'bg-yellow-500 text-yellow-600',
            'rendah': 'bg-green-500 text-green-600'
        } [urgensi] || 'bg-gray-500 text-gray-600');

        const getStatusStyle = (status) => (status === 'diproses' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');

        const renderEmptyState = () => {
            const emptyHtml = `
                <td colspan="5" class="text-center py-12">
                    <div class="flex flex-col items-center justify-center text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <span class="text-sm font-medium">Tidak ada data untuk ditampilkan</span>
                        <p class="text-xs mt-1">Coba ubah filter pencarian Anda atau tambahkan data baru.</p>
                    </div>
                </td>`;
            tableBody.innerHTML = emptyHtml;
            mobileContainer.innerHTML = emptyHtml.replace('<td colspan="5"', '<div').replace('</td>', '</div>');
        };

        const renderLoadingState = () => {
            const loadingHtml = `
                <td colspan="5" class="text-center py-12">
                    <div class="flex items-center justify-center text-gray-500">
                        <i class="fas fa-spinner fa-spin text-2xl mr-3"></i>
                        <span class="font-medium">Memuat data...</span>
                    </div>
                </td>`;
            tableBody.innerHTML = loadingHtml;
            mobileContainer.innerHTML = loadingHtml.replace('<td colspan="5"', '<div').replace('</td>', '</div>');
        }

        const renderTableRows = (data) => {
            tableBody.innerHTML = data.map(row => {
                const urgensiStyle = getUrgensiStyle(row.urgensi);
                const statusStyle = getStatusStyle(row.status);
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">${row.txtNamaKegiatanKS}</span>
                                <div class="flex items-center mt-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full mr-2 ${urgensiStyle.split(' ')[0]}"></span>
                                    <span class="text-xs font-medium text-gray-500">Urgensi ${row.urgensi}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs"><div class="line-clamp-2">${row.txtKendala}</div></td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs"><div class="line-clamp-2">${row.txtUpayaUtkAtasiMslh}</div></td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${statusStyle}">
                                <i class="fas ${row.status == 'diproses' ? 'fa-hourglass-half' : 'fa-check-circle'} mr-1.5"></i>
                                ${row.status.charAt(0).toUpperCase() + row.status.slice(1)}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center space-x-2">
                                ${row.status == 'diproses' ? `<button onclick="markAsDone('${row.IdMslhDanSolusi}')" class="p-2 rounded-full text-gray-500 hover:text-green-600 hover:bg-green-50" title="Tandai Selesai"><i class="fas fa-check-circle"></i></button>` : ''}
                                <button onclick="showEditModal('${row.IdMslhDanSolusi}')" class="p-2 rounded-full text-gray-500 hover:text-indigo-600 hover:bg-indigo-50" title="Detail / Edit"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        };

        const renderMobileCards = (data) => {
            mobileContainer.innerHTML = data.map(row => {
                const urgensiStyle = getUrgensiStyle(row.urgensi);
                const statusStyle = getStatusStyle(row.status);
                return `
                    <div class="bg-white border rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-sm font-semibold text-gray-800 pr-4">${row.txtNamaKegiatanKS}</h3>
                            <div class="flex items-center space-x-1 flex-shrink-0">
                                ${row.status == 'diproses' ? `<button onclick="markAsDone('${row.IdMslhDanSolusi}')" class="p-1.5 rounded-full text-green-600 hover:bg-green-50"><i class="fas fa-check-circle text-base"></i></button>` : ''}
                                <button onclick="showEditModal('${row.IdMslhDanSolusi}')" class="p-1.5 rounded-full text-indigo-600 hover:bg-indigo-50"><i class="fas fa-edit text-base"></i></button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <div class="flex items-center">
                                <span class="w-2.5 h-2.5 rounded-full mr-2 ${urgensiStyle.split(' ')[0]}"></span>
                                <span class="text-xs font-medium text-gray-600">Urgensi ${row.urgensi}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${statusStyle}">
                                <i class="fas ${row.status == 'diproses' ? 'fa-hourglass-half' : 'fa-check-circle'} mr-1"></i>
                                ${row.status.charAt(0).toUpperCase() + row.status.slice(1)}
                            </span>
                        </div>
                        <div class="space-y-3 text-sm border-t pt-3">
                            <div>
                                <span class="block text-xs font-medium text-gray-500 mb-1">Kendala:</span>
                                <p class="text-gray-700">${row.txtKendala}</p>
                            </div>
                            <div>
                                <span class="block text-xs font-medium text-gray-500 mb-1">Solusi:</span>
                                <p class="text-gray-700">${row.txtUpayaUtkAtasiMslh || '-'}</p>
                            </div>
                        </div>
                    </div>`;
            }).join('');
        };

        const renderPagination = ({
            total_pages,
            current_page
        }) => {
            if (total_pages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            let links = '';
            for (let i = 1; i <= total_pages; i++) {
                const isActive = i == current_page;
                links += `<a href="#" data-page="${i}" class="pagination-link px-3 py-2 text-sm font-medium rounded-md ${isActive ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'}">${i}</a>`;
            }
            paginationContainer.innerHTML = `<nav class="flex justify-center space-x-1">${links}</nav>`;
        };

        // === Fungsi API & Data ===
        const debounce = (func, delay = 500) => {
            let timeoutId;
            return (...args) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        };

        const loadProblemsData = async () => {
            renderLoadingState();
            const params = new URLSearchParams(new FormData(filterForm)).toString();
            try {
                const response = await fetch(`pimpinan/permasalahan_solusi_action.php?action=get_problems_list&${params}`);
                const result = await response.json();

                if (result.status === 'success') {
                    if (result.data.length > 0) {
                        renderTableRows(result.data);
                        renderMobileCards(result.data);
                    } else {
                        renderEmptyState();
                    }
                    renderPagination(result.pagination);
                } else {
                    throw new Error(result.message || 'Gagal memuat data.');
                }
            } catch (error) {
                console.error('Fetch error:', error);
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Oops...',
                    text: error.message
                });
                renderEmptyState();
            }
        };

        // === Fungsi Modal ===
        window.showEditModal = async (id = null) => {
            kendalaForm.reset();
            document.getElementById('IdMslhDanSolusi').value = id || '';
            modalTitle.textContent = id ? 'Edit Kendala & Solusi' : 'Laporkan Kendala Baru';
            submitButtonText.textContent = id ? 'Simpan Perubahan' : 'Laporkan';

            if (id) {
                const loadingSwal = Swal.fire({
                    title: 'Memuat data...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                try {
                    const response = await fetch(`pimpinan/permasalahan_solusi_action.php?action=get_single_problem&id=${id}`);
                    const result = await response.json();
                    loadingSwal.close();
                    if (result.status === 'success') {
                        Object.entries(result.data).forEach(([key, value]) => {
                            const el = document.getElementById(key);
                            if (el) el.value = value;
                        });
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    loadingSwal.close();
                    Swal.fire({
                        ...swalConfig,
                        icon: 'error',
                        title: 'Gagal',
                        text: error.message
                    });
                    return;
                }
            }

            editModal.classList.remove('hidden');
            setTimeout(() => editModalContent.classList.remove('opacity-0', 'scale-95'), 10);
        };

        window.closeEditModal = () => {
            editModalContent.classList.add('opacity-0', 'scale-95');
            setTimeout(() => editModal.classList.add('hidden'), 300);
        };

        // === Fungsi Aksi ===
        window.markAsDone = async (id) => {
            const confirmation = await Swal.fire({
                ...swalConfig,
                title: 'Anda Yakin?',
                text: "Menandai kendala ini sebagai 'selesai' tidak dapat dibatalkan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan!',
                cancelButtonText: 'Batal',
                customClass: {
                    ...swalConfig.customClass,
                    confirmButton: 'bg-green-600'
                }
            });

            if (confirmation.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                const loadingSwal = Swal.fire({
                    title: 'Memproses...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const response = await fetch('pimpinan/permasalahan_solusi_action.php?action=mark_as_done', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    loadingSwal.close();
                    if (result.status === 'success') {
                        Swal.fire({
                            ...swalConfig,
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Status telah diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadProblemsData();
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    loadingSwal.close();
                    Swal.fire({
                        ...swalConfig,
                        icon: 'error',
                        title: 'Gagal',
                        text: error.message
                    });
                }
            }
        };

        // === Event Listeners ===
        kendalaForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(kendalaForm);
            const id = formData.get('IdMslhDanSolusi');
            const action = id ? 'update_problem' : 'add_problem';
            const loadingSwal = Swal.fire({
                title: 'Menyimpan...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(`pimpinan/permasalahan_solusi_action.php?action=${action}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                loadingSwal.close();

                if (result.status === 'success') {
                    closeEditModal();
                    Swal.fire({
                        ...swalConfig,
                        icon: 'success',
                        title: 'Berhasil!',
                        text: `Data berhasil ${id ? 'diperbarui' : 'dilaporkan'}.`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadProblemsData();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                loadingSwal.close();
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Gagal Menyimpan',
                    text: error.message,
                    customClass: {
                        ...swalConfig.customClass,
                        confirmButton: 'bg-red-600'
                    }
                });
            }
        });

        const debouncedLoadData = debounce(loadProblemsData, 500);

        searchInput.addEventListener('input', () => {
            pageInput.value = 1;
            debouncedLoadData();
        });

        filterUrgensi.addEventListener('change', () => {
            pageInput.value = 1;
            loadProblemsData();
        });

        paginationContainer.addEventListener('click', (e) => {
            if (e.target.matches('.pagination-link')) {
                e.preventDefault();
                pageInput.value = e.target.dataset.page;
                loadProblemsData();
            }
        });

        editModal.addEventListener('click', (e) => e.target === editModal && closeEditModal());
        window.addEventListener('keydown', (e) => (e.key === 'Escape' && !editModal.classList.contains('hidden')) && closeEditModal());

        // Inisialisasi: Muat data saat halaman pertama kali dibuka
        loadProblemsData();
    });
</script>

<?php
// Tutup koneksi PHP setelah selesai
$koneksi->close();
?>