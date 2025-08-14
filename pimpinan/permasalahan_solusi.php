<?php
// Ambil semua kegiatan untuk dropdown di modal
$kegiatan_list = $koneksi->query("SELECT IdKKS, txtNamaKegiatanKS FROM tblnamakegiatanks ORDER BY txtNamaKegiatanKS ASC");

// === Logika Filter & Paginasi ===
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$filter_urgensi = $_GET['urgensi'] ?? '';

// Bangun query dinamis
$where_clauses = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "(k.txtNamaKegiatanKS LIKE ? OR p.txtKendala LIKE ?)";
    $search_term = '%' . $search . '%';
    $params = array_merge($params, [$search_term, $search_term]);
    $types .= 'ss';
}

if (!empty($filter_urgensi)) {
    $where_clauses[] = "p.urgensi = ?";
    $params[] = $filter_urgensi;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? " WHERE " . implode(' AND ', $where_clauses) : "";

// Query untuk menghitung total data
$sql_count = "SELECT COUNT(*) as total FROM tblpermasalahandansolusi p JOIN tblnamakegiatanks k ON p.IdKKS = k.IdKKS" . $where_sql;
$stmt_count = $koneksi->prepare($sql_count);
if (!empty($params)) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_data = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);
$stmt_count->close();

// Query utama untuk mengambil data
$sql_data = "SELECT p.*, k.txtNamaKegiatanKS FROM tblpermasalahandansolusi p JOIN tblnamakegiatanks k ON p.IdKKS = k.IdKKS" . $where_sql . " ORDER BY FIELD(p.status, 'diproses', 'selesai'), FIELD(p.urgensi, 'tinggi', 'sedang', 'rendah') LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt_data = $koneksi->prepare($sql_data);
if (!empty($params)) $stmt_data->bind_param($types, ...$params);
$stmt_data->execute();
$result = $stmt_data->get_result();
$problems_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt_data->close();

// Fungsi helper untuk styling
function get_urgensi_style($urgensi)
{
    $styles = ['tinggi' => 'bg-red-500 text-red-600', 'sedang' => 'bg-yellow-500 text-yellow-600', 'rendah' => 'bg-green-500 text-green-600'];
    return $styles[$urgensi] ?? 'bg-gray-500 text-gray-600';
}
function get_status_style($status)
{
    return $status == 'diproses' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
}
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
                            <input type="text" name="search" id="searchInput"
                                placeholder="Cari kegiatan atau kendala..."
                                value="<?= htmlspecialchars($search) ?>"
                                class="pl-9 pr-4 py-2 border rounded-lg w-full text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                        </div>
                        <select name="urgensi" id="filterUrgensi"
                            class="text-sm px-3 py-2 border rounded-lg bg-white text-gray-700 focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                            <option value="">Semua Urgensi</option>
                            <option value="tinggi" <?= $filter_urgensi == 'tinggi' ? 'selected' : '' ?>>Tinggi</option>
                            <option value="sedang" <?= $filter_urgensi == 'sedang' ? 'selected' : '' ?>>Sedang</option>
                            <option value="rendah" <?= $filter_urgensi == 'rendah' ? 'selected' : '' ?>>Rendah</option>
                        </select>
                    </div>
                </div>
                <!-- Tambahkan input hidden untuk parameter pagination -->
                <input type="hidden" name="page" value="1">
            </form>

            <div class="hidden sm:block rounded-xl border border-gray-100 shadow-sm overflow-hidden">
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
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($problems_data)): foreach ($problems_data as $row):
                                    $urgensi_style = get_urgensi_style($row['urgensi']);
                                    $status_style = get_status_style($row['status']);
                            ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></span>
                                                <div class="flex items-center mt-1">
                                                    <span class="w-2.5 h-2.5 rounded-full mr-2 <?= $urgensi_style ?>"></span>
                                                    <span class="text-xs font-medium text-gray-500"><?= 'Urgensi ' . ucfirst($row['urgensi']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                            <div class="line-clamp-2"><?= htmlspecialchars($row['txtKendala']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                            <div class="line-clamp-2"><?= htmlspecialchars($row['txtUpayaUtkAtasiMslh']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium leading-4 <?= $status_style ?>">
                                                <i class="fas <?= $row['status'] == 'diproses' ? 'fa-hourglass-half mr-1.5' : 'fa-check-circle mr-1.5' ?>"></i>
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex justify-center space-x-3">
                                                <?php if ($row['status'] == 'diproses'): ?>
                                                    <button onclick="markAsDone(this, '<?= $row['IdMslhDanSolusi'] ?>')"
                                                        class="p-1.5 rounded-full text-gray-500 hover:text-green-600 hover:bg-green-50 transition-colors"
                                                        title="Tandai Selesai">
                                                        <i class="fas fa-check-circle fa-lg"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="showEditModal(this, '<?= $row['IdMslhDanSolusi'] ?>')"
                                                    class="p-1.5 rounded-full text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                                                    title="Detail / Edit">
                                                    <i class="fas fa-edit fa-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <i class="fas fa-inbox text-4xl mb-3"></i>
                                            <span class="text-sm font-medium">Tidak ada data kendala yang cocok</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="block sm:hidden space-y-4 p-4">
                <?php if (!empty($problems_data)): foreach ($problems_data as $row):
                        $urgensi_style = get_urgensi_style($row['urgensi']);
                        $status_style = get_status_style($row['status']);
                ?>
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <!-- Header Row -->
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></h3>
                            </div>

                            <!-- Status and Urgency Row -->
                            <div class="flex justify-between items-center mb-3">
                                <div class="flex items-center">
                                    <span class="w-2.5 h-2.5 rounded-full mr-2 <?= $urgensi_style ?>"></span>
                                    <span class="text-xs font-medium text-gray-600">Urgensi <?= ucfirst($row['urgensi']) ?></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $status_style ?>">
                                        <i class="fas <?= $row['status'] == 'diproses' ? 'fa-hourglass-half mr-1' : 'fa-check-circle mr-1' ?> text-xs"></i>
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                    <div class="flex space-x-1">
                                        <?php if ($row['status'] == 'diproses'): ?>
                                            <button onclick="markAsDone(this, '<?= $row['IdMslhDanSolusi'] ?>')"
                                                class="p-1.5 rounded-full text-green-600 hover:bg-green-50">
                                                <i class="fas fa-check-circle text-sm"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="showEditModal(this, '<?= $row['IdMslhDanSolusi'] ?>')"
                                            class="p-1.5 rounded-full text-indigo-600 hover:bg-indigo-50">
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="space-y-3 text-sm border-t border-gray-100 pt-3">
                                <div>
                                    <span class="block text-xs font-medium text-gray-500 mb-1">Kendala:</span>
                                    <p class="text-gray-700 text-sm"><?= htmlspecialchars($row['txtKendala']) ?></p>
                                </div>
                                <div>
                                    <span class="block text-xs font-medium text-gray-500 mb-1">Solusi:</span>
                                    <p class="text-gray-700 text-sm"><?= htmlspecialchars($row['txtUpayaUtkAtasiMslh']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <i class="fas fa-inbox text-3xl text-gray-300 mb-3"></i>
                        <p class="text-sm font-medium text-gray-500">Tidak ada data kendala yang cocok</p>
                    </div>
                <?php endif; ?>
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
        const editModal = document.getElementById('editModal');
        const editModalContent = document.getElementById('editModalContent');
        const modalTitle = document.getElementById('modalTitle');
        const kendalaForm = document.getElementById('kendalaForm');
        const submitButtonText = document.getElementById('submitButtonText');
        const filterForm = document.getElementById('filterForm');
        const searchInput = document.getElementById('searchInput');
        const filterUrgensi = document.getElementById('filterUrgensi');
        const problemsContainer = document.querySelector('.overflow-x-auto');
        const mobileProblemsContainer = document.querySelector('.block.sm\\:hidden');

        // Konfigurasi SweetAlert
        const swalConfig = {
            customClass: {
                popup: 'p-4 sm:p-6 rounded-lg',
                confirmButton: 'px-4 py-2 rounded-md text-white',
                cancelButton: 'ml-3 px-4 py-2 rounded-md bg-white border'
            },
            buttonsStyling: false
        };

        // === Fungsi Utilitas ===

        // Debounce untuk optimasi performa
        const debounce = (func, delay = 500) => {
            let timeoutId;
            return (...args) => {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => func.apply(this, args), delay);
            };
        };

        // Fungsi untuk menampilkan loading
        const showLoading = () => {
            return Swal.fire({
                title: 'Memproses...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        };

        // === Fungsi API ===

        const fetchData = async (url, options = {}) => {
            try {
                const response = await fetch(url, options);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error('Fetch error:', error);
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Koneksi Gagal',
                    text: 'Terjadi kesalahan saat menghubungi server',
                    customClass: {
                        ...swalConfig.customClass,
                        confirmButton: 'bg-red-600'
                    }
                });
                return null;
            }
        };

        const apiCall = async (action, data = {}) => {
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }

            return await fetchData(`pimpinan/permasalahan_solusi_action.php?action=${action}`, {
                method: 'POST',
                body: formData
            });
        };

        // === Fungsi Modal ===

        window.showEditModal = async (triggerEl, id = null) => {
            // Set judul modal dan tombol submit
            modalTitle.textContent = id ? 'Edit Kendala & Solusi' : 'Laporkan Kendala Baru';
            submitButtonText.textContent = id ? 'Simpan Perubahan' : 'Laporkan';

            // Reset form dan set ID jika edit
            kendalaForm.reset();
            document.getElementById('IdMslhDanSolusi').value = id || '';

            // Jika mode edit, ambil data dari server
            if (id) {
                const loading = showLoading();

                try {
                    const result = await fetchData(`pimpinan/permasalahan_solusi_action.php?action=get_single_problem&id=${id}`);
                    if (result?.status === 'success') {
                        // Isi form dengan data yang diterima
                        Object.entries(result.data).forEach(([key, value]) => {
                            const el = document.getElementById(key);
                            if (el) el.value = value;
                        });
                    }
                } finally {
                    loading.close();
                }
            }

            // Tampilkan modal dengan animasi
            editModal.classList.remove('hidden');
            setTimeout(() => {
                editModalContent.classList.remove('opacity-0', 'scale-95');
                // Fokus ke elemen pertama yang bisa diisi
                const firstInput = kendalaForm.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus();
            }, 10);
        };

        window.closeEditModal = () => {
            // Sembunyikan modal dengan animasi
            editModalContent.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                editModal.classList.add('hidden');
            }, 300);
        };

        // === Fungsi Aksi ===

        window.markAsDone = async (button, id) => {
            const result = await Swal.fire({
                ...swalConfig,
                title: 'Tandai Selesai?',
                text: 'Anda yakin ingin menandai kendala ini sebagai selesai?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesaikan',
                cancelButtonText: 'Batal',
                customClass: {
                    ...swalConfig.customClass,
                    confirmButton: 'bg-green-600'
                }
            });

            if (result.isConfirmed) {
                const loading = showLoading();

                try {
                    const res = await apiCall('mark_as_done', {
                        id
                    });
                    if (res?.status === 'success') {
                        await Swal.fire({
                            ...swalConfig,
                            title: 'Berhasil!',
                            text: 'Status telah diperbarui',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        // Refresh data tanpa reload halaman
                        loadProblemsData();
                    }
                } finally {
                    loading.close();
                }
            }
        };

        // === Fungsi untuk Memuat Data Permasalahan ===

        const loadProblemsData = async () => {
            const loading = showLoading();

            try {
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);

                const response = await fetch(`permasalahan_solusi.php?${params.toString()}`);
                const html = await response.text();

                // Parse response dan update konten
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Update tabel desktop
                const newTable = doc.querySelector('.overflow-x-auto');
                if (newTable && problemsContainer) {
                    problemsContainer.innerHTML = newTable.innerHTML;
                }

                // Update tampilan mobile
                const newMobileView = doc.querySelector('.block.sm\\:hidden');
                if (newMobileView && mobileProblemsContainer) {
                    mobileProblemsContainer.innerHTML = newMobileView.innerHTML;
                }
            } catch (error) {
                console.error('Load data error:', error);
                Swal.fire({
                    ...swalConfig,
                    icon: 'error',
                    title: 'Gagal Memuat Data',
                    text: 'Terjadi kesalahan saat memuat data permasalahan'
                });
            } finally {
                loading.close();
            }
        };

        // === Event Listeners ===

        // Handle submit form kendala
        kendalaForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(kendalaForm);
            const id = formData.get('IdMslhDanSolusi');
            const action = id ? 'update_problem' : 'add_problem';

            const loading = showLoading();

            try {
                const result = await apiCall(action, Object.fromEntries(formData));

                if (result?.status === 'success') {
                    closeEditModal();
                    await Swal.fire({
                        ...swalConfig,
                        icon: 'success',
                        title: 'Berhasil!',
                        text: `Data berhasil ${id ? 'diperbarui' : 'dilaporkan'}.`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Refresh data setelah submit
                    await loadProblemsData();
                }
            } finally {
                loading.close();
            }
        });

        // Handle filter form
        const handleFilterChange = debounce(() => {
            // Reset ke halaman 1 saat filter berubah
            filterForm.querySelector('input[name="page"]').value = 1;
            loadProblemsData();
        });

        searchInput.addEventListener('input', handleFilterChange);
        filterUrgensi.addEventListener('change', handleFilterChange);

        // Handle pagination
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.pagination a')) {
                e.preventDefault();
                const page = e.target.closest('a').getAttribute('href').split('page=')[1];
                filterForm.querySelector('input[name="page"]').value = page;
                await loadProblemsData();
            }
        });

        // Handle modal close
        editModal.addEventListener('click', (e) => e.target === editModal && closeEditModal());
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !editModal.classList.contains('hidden')) {
                closeEditModal();
            }
        });

        // Inisialisasi pertama kali
        loadProblemsData();
    });
</script>

<?php
$koneksi->close();
?>