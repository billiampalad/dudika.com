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
function get_urgensi_style($urgensi) {
    $styles = ['tinggi' => 'bg-red-500 text-red-600', 'sedang' => 'bg-yellow-500 text-yellow-600', 'rendah' => 'bg-green-500 text-green-600'];
    return $styles[$urgensi] ?? 'bg-gray-500 text-gray-600';
}
function get_status_style($status) {
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
            <form id="filterForm" method="GET" action="">
                <div class="px-6 py-5 border-b flex flex-col sm:flex-row justify-between items-start gap-4">
                    <h3 class="font-bold text-gray-800 mt-2">Daftar Kendala dan Solusi</h3>
                    <div class="w-full sm:w-auto grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400 text-sm"></i></div><input type="text" name="search" id="searchInput" placeholder="Cari kegiatan atau kendala..." value="<?= htmlspecialchars($search) ?>" class="pl-9 pr-4 py-2 border rounded-lg w-full text-sm"></div>
                        <select name="urgensi" id="filterUrgensi" class="text-sm px-3 py-2 border rounded-lg bg-white text-gray-700">
                            <option value="">Semua Urgensi</option>
                            <option value="tinggi" <?= $filter_urgensi == 'tinggi' ? 'selected' : '' ?>>Tinggi</option>
                            <option value="sedang" <?= $filter_urgensi == 'sedang' ? 'selected' : '' ?>>Sedang</option>
                            <option value="rendah" <?= $filter_urgensi == 'rendah' ? 'selected' : '' ?>>Rendah</option>
                        </select>
                    </div>
                </div>
            </form>

            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Kegiatan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendala</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Solusi</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($problems_data)): foreach ($problems_data as $row): 
                            $urgensi_style = get_urgensi_style($row['urgensi']);
                            $status_style = get_status_style($row['status']);
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></div>
                                <div class="flex items-center mt-1.5"><span class="w-2 h-2 rounded-full mr-2 <?= $urgensi_style ?>"></span><span class="text-xs font-medium <?= $urgensi_style ?>"><?= 'Urgensi ' . ucfirst($row['urgensi']) ?></span></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs"><?= htmlspecialchars($row['txtKendala']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs"><?= htmlspecialchars($row['txtUpayaUtkAtasiMslh']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center"><span class="status-badge inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $status_style ?>"><i class="fas <?= $row['status'] == 'diproses' ? 'fa-hourglass-half' : 'fa-check-circle' ?> mr-1.5"></i> <?= ucfirst($row['status']) ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <?php if ($row['status'] == 'diproses'): ?><button onclick="markAsDone(this, '<?= $row['IdMslhDanSolusi'] ?>')" class="text-gray-500 hover:text-green-600" title="Tandai Selesai"><i class="fas fa-check-circle fa-lg"></i></button><?php endif; ?>
                                    <button onclick="showEditModal(this, '<?= $row['IdMslhDanSolusi'] ?>')" class="text-gray-500 hover:text-indigo-600" title="Detail / Edit"><i class="fas fa-edit fa-lg"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center py-8 text-gray-500">Tidak ada data kendala yang cocok.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="block sm:hidden space-y-4 p-4">
                <?php if (!empty($problems_data)): foreach ($problems_data as $row): 
                    $urgensi_style = get_urgensi_style($row['urgensi']);
                    $status_style = get_status_style($row['status']);
                ?>
                <div class="bg-white border rounded-lg p-5 shadow-xs">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></div>
                            <div class="flex items-center mt-1"><span class="w-2 h-2 rounded-full mr-2 <?= $urgensi_style ?>"></span><span class="text-xs font-medium <?= $urgensi_style ?>">Urgensi <?= ucfirst($row['urgensi']) ?></span></div>
                        </div>
                        <span class="status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_style ?>"><i class="fas <?= $row['status'] == 'diproses' ? 'fa-hourglass-half' : 'fa-check-circle' ?> mr-1.5"></i> <?= ucfirst($row['status']) ?></span>
                    </div>
                    <div class="space-y-3 text-sm border-t pt-3 mt-3">
                        <div><span class="block text-xs font-medium text-gray-500 mb-1">Kendala:</span><p class="text-gray-700"><?= htmlspecialchars($row['txtKendala']) ?></p></div>
                        <div><span class="block text-xs font-medium text-gray-500 mb-1">Solusi:</span><p class="text-gray-700"><?= htmlspecialchars($row['txtUpayaUtkAtasiMslh']) ?></p></div>
                    </div>
                    <div class="flex justify-end space-x-3 border-t pt-3 mt-4">
                        <?php if ($row['status'] == 'diproses'): ?><button onclick="markAsDone(this, '<?= $row['IdMslhDanSolusi'] ?>')" class="px-3 py-1.5 text-xs rounded-md text-green-700 bg-green-50"><i class="fas fa-check-circle fa-sm mr-1"></i> Selesai</button><?php endif; ?>
                        <button onclick="showEditModal(this, '<?= $row['IdMslhDanSolusi'] ?>')" class="px-3 py-1.5 text-xs rounded-md text-indigo-700 bg-indigo-50"><i class="fas fa-edit fa-sm mr-1"></i> Edit</button>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <p class="text-center py-8 text-gray-500">Tidak ada data kendala yang cocok.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div id="editModalContent" class="bg-white w-full max-w-2xl rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center px-6 py-4 border-b"><h3 class="text-lg font-semibold" id="modalTitle"></h3><button onclick="closeEditModal()" class="text-gray-400">&times;</button></div>
        <form id="kendalaForm" class="p-6 space-y-5 overflow-y-auto flex-grow">
            <input type="hidden" id="IdMslhDanSolusi" name="IdMslhDanSolusi">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="IdKKS" class="block text-sm font-medium text-gray-700 mb-1">Nama Kegiatan</label>
                    <select id="IdKKS" name="IdKKS" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md" required>
                        <option value="">-- Pilih Kegiatan --</option>
                        <?php mysqli_data_seek($kegiatan_list, 0); while($kegiatan = $kegiatan_list->fetch_assoc()): ?>
                        <option value="<?= $kegiatan['IdKKS'] ?>"><?= htmlspecialchars($kegiatan['txtNamaKegiatanKS']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label for="urgensi" class="block text-sm font-medium text-gray-700 mb-1">Tingkat Urgensi</label>
                    <select id="urgensi" name="urgensi" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md" required>
                        <option value="rendah">Rendah</option><option value="sedang">Sedang</option><option value="tinggi">Tinggi</option>
                    </select>
                </div>
            </div>
            <div><label for="txtKendala" class="block text-sm font-medium text-gray-700 mb-1">Kendala yang Dihadapi</label><textarea id="txtKendala" name="txtKendala" rows="3" class="mt-1 block w-full border-gray-300 rounded-md p-3" required></textarea></div>
            <div><label for="txtUpayaUtkAtasiMslh" class="block text-sm font-medium text-gray-700 mb-1">Upaya Penyelesaian (Solusi)</label><textarea id="txtUpayaUtkAtasiMslh" name="txtUpayaUtkAtasiMslh" rows="3" class="mt-1 block w-full border-gray-300 rounded-md p-3" required></textarea></div>
            <div><label for="txtRekomUtkPerbaikan" class="block text-sm font-medium text-gray-700 mb-1">Rekomendasi Perbaikan ke Depan</label><textarea id="txtRekomUtkPerbaikan" name="txtRekomUtkPerbaikan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md p-3"></textarea></div>
            <div class="flex justify-end space-x-3 pt-5 border-t"><button type="button" onclick="closeEditModal()" class="px-4 py-2 border rounded-md text-sm">Batal</button><button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-blue-600"><i class="fas fa-save fa-sm mr-2"></i><span id="submitButtonText"></span></button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // === Konfigurasi & Elemen Global ===
    let activeElementBeforeModal;
    const editModal = document.getElementById('editModal');
    const editModalContent = document.getElementById('editModalContent');
    const modalTitle = document.getElementById('modalTitle');
    const kendalaForm = document.getElementById('kendalaForm');
    const submitButtonText = document.getElementById('submitButtonText');
    const swalConfig = { customClass: { popup: 'p-4 sm:p-6 rounded-lg', confirmButton: 'px-4 py-2 rounded-md text-white', cancelButton: 'ml-3 px-4 py-2 rounded-md bg-white border' }, buttonsStyling: false };

    // === Fungsi Backend Communication ===
    const apiCall = async (action, options = {}) => {
        const url = `permasalahan_solusi_action.php?action=${action}`;
        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            Swal.fire({ ...swalConfig, icon: 'error', title: 'Koneksi Gagal', customClass: {...swalConfig.customClass, confirmButton: 'bg-red-600'} });
            return null;
        }
    };

    // === Fungsi Modal ===
    window.showEditModal = async (triggerEl, id = null) => {
        activeElementBeforeModal = triggerEl;
        kendalaForm.reset();
        document.getElementById('IdMslhDanSolusi').value = '';
        modalTitle.textContent = id ? 'Edit Kendala & Solusi' : 'Laporkan Kendala Baru';
        submitButtonText.textContent = id ? 'Simpan Perubahan' : 'Laporkan';

        if (id) {
            const result = await apiCall(`get_single_problem&id=${id}`);
            if (result && result.status === 'success') {
                Object.keys(result.data).forEach(key => {
                    const el = document.getElementById(key);
                    if (el) el.value = result.data[key];
                });
            }
        }
        
        editModal.classList.remove('hidden');
        setTimeout(() => editModalContent.classList.remove('opacity-0', 'scale-95'), 10);
    };

    window.closeEditModal = () => {
        editModalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => editModal.classList.add('hidden'), 300);
    };

    // === Fungsi Aksi & Notifikasi ===
    window.markAsDone = (button, id) => {
        Swal.fire({ ...swalConfig, title: 'Tandai Selesai?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Selesaikan', cancelButtonText: 'Batal', customClass: { ...swalConfig.customClass, confirmButton: 'bg-green-600' }
        }).then(async (result) => {
            if (result.isConfirmed) {
                const res = await apiCall('mark_as_done', { method: 'POST', body: new URLSearchParams({id: id}) });
                if (res && res.status === 'success') {
                    await Swal.fire({ ...swalConfig, title: 'Berhasil!', icon: 'success', timer: 1500, showConfirmButton: false });
                    window.location.reload();
                }
            }
        });
    };

    // === Event Listeners ===
    kendalaForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('IdMslhDanSolusi').value;
        const action = id ? 'update_problem' : 'add_problem';
        const result = await apiCall(action, { method: 'POST', body: new FormData(kendalaForm) });

        if (result && result.status === 'success') {
            closeEditModal();
            await Swal.fire({ ...swalConfig, icon: 'success', title: 'Berhasil!', text: `Data berhasil ${id ? 'diperbarui' : 'dilaporkan'}.`, timer: 1500, showConfirmButton: false });
            window.location.reload();
        }
    });

    const filterForm = document.getElementById('filterForm');
    ['searchInput', 'filterUrgensi'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => filterForm.submit());
    });

    editModal.addEventListener('click', e => { if (e.target === editModal) closeEditModal(); });
    window.addEventListener('keydown', e => { if (e.key === 'Escape' && !editModal.classList.contains('hidden')) closeEditModal(); });
});
</script>

<?php
$koneksi->close();
?>