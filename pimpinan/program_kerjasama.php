<?php
function getStatusStyle($status_text) {
    $styles = [
        'Selesai' => ['class' => 'bg-gray-100 text-gray-800', 'icon_class' => 'text-gray-500'],
        'Aktif' => ['class' => 'bg-green-100 text-green-800', 'icon_class' => 'text-green-500'],
        'Akan Berakhir' => ['class' => 'bg-orange-100 text-orange-800', 'icon_class' => 'text-orange-500'],
        'Belum Mulai' => ['class' => 'bg-blue-100 text-blue-800', 'icon_class' => 'text-blue-500'],
        'Periode Invalid' => ['class' => 'bg-red-100 text-red-800', 'icon_class' => 'text-red-500'],
        'Tidak Diketahui' => ['class' => 'bg-gray-100 text-gray-800', 'icon_class' => 'text-gray-500'],
    ];
    return $styles[$status_text] ?? $styles['Tidak Diketahui'];
}

// Ambil data untuk dropdown filter jenis kerjasama
$jenis_ks_list = $koneksi->query("SELECT IdJenisKS, txtNamaJenisKS FROM tbljenisks ORDER BY txtNamaJenisKS");

// === PENGATURAN FILTER DAN PAGINASI ===
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search_filter = $_GET['search'] ?? '';
$jenis_filter = $_GET['jenis'] ?? '';
$status_filter_raw = $_GET['status'] ?? '';
$status_filter = $status_filter_raw ? ucfirst(str_replace('_', ' ', $status_filter_raw)) : '';

// === PEMBENTUKAN QUERY DINAMIS ===
$params = [];
$types = '';
$where_clauses = [];
$having_clauses = [];

// Query dasar dengan kalkulasi status dan periode
$sql_base = "
    FROM
        tblnamakegiatanks k
    JOIN
        tbljenisks j ON k.IdJenisKS = j.IdJenisKS
    JOIN
        tblmitradudika m ON k.IdMitraDudika = m.IdMitraDudika
";

$select_fields = "
    k.IdKKS, k.txtNamaKegiatanKS, m.txtNamaMitraDudika,
    CONCAT(DATE_FORMAT(k.dtMulaiPelaksanaan, '%d %b %Y'), ' - ', DATE_FORMAT(k.dtSelesaiPelaksanaan, '%d %b %Y')) AS txtPeriodePelaksanaan,
    (CASE
        WHEN k.dtSelesaiPelaksanaan < CURDATE() THEN 'Selesai'
        WHEN CURDATE() BETWEEN k.dtMulaiPelaksanaan AND k.dtSelesaiPelaksanaan AND k.dtSelesaiPelaksanaan <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Akan Berakhir'
        WHEN CURDATE() BETWEEN k.dtMulaiPelaksanaan AND k.dtSelesaiPelaksanaan THEN 'Aktif'
        WHEN k.dtMulaiPelaksanaan > CURDATE() THEN 'Belum Mulai'
        ELSE 'Tidak Diketahui'
    END) AS status
";

// Filter pencarian
if (!empty($search_filter)) {
    $where_clauses[] = "(k.txtNamaKegiatanKS LIKE ? OR m.txtNamaMitraDudika LIKE ? OR k.txtNomorMOU LIKE ?)";
    $search_term = '%' . $search_filter . '%';
    array_push($params, $search_term, $search_term, $search_term);
    $types .= 'sss';
}

// Filter jenis kerjasama
if (!empty($jenis_filter)) {
    $where_clauses[] = "k.IdJenisKS = ?";
    $params[] = $jenis_filter;
    $types .= 's';
}

// Filter status (menggunakan HAVING karena 'status' adalah kolom alias)
if (!empty($status_filter)) {
    $having_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Gabungkan semua klausa
$where_sql = !empty($where_clauses) ? " WHERE " . implode(' AND ', $where_clauses) : "";
$having_sql = !empty($having_clauses) ? " HAVING " . implode(' AND ', $having_clauses) : "";

// === EKSEKUSI QUERY UNTUK MENGHITUNG TOTAL DATA (COUNT) ===
$sql_count = "SELECT COUNT(*) as total FROM (SELECT $select_fields $sql_base $where_sql) AS subquery" . str_replace("status = ?", "subquery.status = ?", $having_sql);
$stmt_count = $koneksi->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_data = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);
$stmt_count->close();

// === EKSEKUSI QUERY UNTUK MENGAMBIL DATA UTAMA (PAGINATED) ===
$paginated_data = [];
if ($total_data > 0) {
    $sql_data = "SELECT $select_fields $sql_base $where_sql $having_sql ORDER BY k.dtMOU DESC LIMIT ? OFFSET ?";
    
    // Tambahkan parameter untuk LIMIT dan OFFSET
    $limit_params = $params;
    $limit_types = $types;
    $limit_params[] = $limit;
    $limit_params[] = $offset;
    $limit_types .= 'ii';
    
    $stmt_data = $koneksi->prepare($sql_data);
    if (!empty($limit_params)) {
        $stmt_data->bind_param($limit_types, ...$limit_params);
    }
    $stmt_data->execute();
    $result = $stmt_data->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $style = getStatusStyle($row['status']);
        $row['status_class'] = $style['class'];
        $row['status_icon_class'] = $style['icon_class'];
        $paginated_data[] = $row;
    }
    $stmt_data->close();
}

?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div><h2 class="text-xl font-bold text-gray-800 tracking-tight">Program Kerjasama</h2></div>
        <button onclick="showAddEditModal(this)" class="w-full sm:w-auto bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-blue-200 focus:ring-offset-2">
            <i class="fas fa-plus fa-sm"></i><span class="text-sm font-medium">Tambah Kerjasama</span>
        </button>
    </div>

    <form id="filterForm" method="GET" action="">
        <div class="px-6 py-4 border-b border-gray-100 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-search text-gray-400"></i></div>
                <input type="text" id="searchInput" name="search" placeholder="Cari nama kegiatan, mitra, atau nomor MOU..." value="<?= htmlspecialchars($search_filter) ?>" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <select id="filterJenis" name="jenis" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
                    <option value="">Semua Jenis</option>
                    <?php mysqli_data_seek($jenis_ks_list, 0); // Reset pointer
                    while($jenis = $jenis_ks_list->fetch_assoc()): ?>
                    <option value="<?= $jenis['IdJenisKS'] ?>" <?= $jenis_filter == $jenis['IdJenisKS'] ? 'selected' : '' ?>><?= htmlspecialchars($jenis['txtNamaJenisKS']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <select id="filterStatus" name="status" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= $status_filter_raw == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="selesai" <?= $status_filter_raw == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="akan_berakhir" <?= $status_filter_raw == 'akan_berakhir' ? 'selected' : '' ?>>Akan Berakhir</option>
                    <option value="belum_mulai" <?= $status_filter_raw == 'belum_mulai' ? 'selected' : '' ?>>Belum Mulai</option>
                </select>
            </div>
        </div>
    </form>

    <div class="hidden sm:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kegiatan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mitra</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($paginated_data)): foreach ($paginated_data as $row): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></div></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['txtNamaMitraDudika']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['txtPeriodePelaksanaan']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $row['status_class'] ?>">
                            <svg class="-ml-0.5 mr-1.5 h-2 w-2 <?= $row['status_icon_class'] ?>" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-center space-x-2">
                            <button onclick="showDetailModal(this, '<?= $row['IdKKS'] ?>')" class="text-blue-600 hover:text-blue-900" title="Detail"><i class="fas fa-eye fa-lg"></i></button>
                            <button onclick="showAddEditModal(this, '<?= $row['IdKKS'] ?>')" class="text-indigo-600 hover:text-indigo-900" title="Edit"><i class="fas fa-edit fa-lg"></i></button>
                            <button onclick="deleteData('<?= $row['IdKKS'] ?>')" class="text-red-600 hover:text-red-900" title="Hapus"><i class="fas fa-trash fa-lg"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center py-4 text-gray-500">Tidak ada data kerja sama yang cocok dengan filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="block sm:hidden space-y-3 p-4">
        <?php if (!empty($paginated_data)): foreach ($paginated_data as $row): ?>
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-xs">
            <div class="flex justify-between items-start">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $row['status_class'] ?>">
                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 <?= $row['status_icon_class'] ?>" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                    <?= $row['status'] ?>
                </span>
                <div class="flex space-x-2">
                    <button onclick="showDetailModal(this, '<?= $row['IdKKS'] ?>')" class="text-blue-600 hover:text-blue-500" title="Detail"><i class="fas fa-eye"></i></button>
                    <button onclick="showAddEditModal(this, '<?= $row['IdKKS'] ?>')" class="text-indigo-600 hover:text-indigo-500" title="Edit"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteData('<?= $row['IdKKS'] ?>')" class="text-red-600 hover:text-red-500" title="Hapus"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <h3 class="mt-2 text-sm font-semibold text-gray-800"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></h3>
            <div class="mt-3 space-y-2 text-sm border-t border-gray-200 pt-3">
                <div class="flex items-start"><i class="fas fa-handshake text-gray-400 mt-0.5 mr-2"></i><span class="text-gray-600"><?= htmlspecialchars($row['txtNamaMitraDudika']) ?></span></div>
                <div class="flex items-start"><i class="fas fa-calendar-alt text-gray-400 mt-0.5 mr-2"></i><span class="text-gray-600"><?= htmlspecialchars($row['txtPeriodePelaksanaan']) ?></span></div>
            </div>
        </div>
        <?php endforeach; else: ?>
        <p class="text-center py-4 text-gray-500">Tidak ada data kerja sama.</p>
        <?php endif; ?>
    </div>

    <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
        <p class="text-sm text-gray-600">Menampilkan <span class="font-medium"><?= $total_data > 0 ? $offset + 1 : 0 ?>-<?= min($offset + $limit, $total_data) ?></span> dari <span class="font-medium"><?= $total_data ?></span> data</p>
        <?php if ($total_pages > 1): ?>
        <nav class="flex items-center space-x-1">
            <?php
            // Membangun query string dari parameter GET yang ada
            $queryParams = $_GET;
            $prevPage = max(1, $page - 1);
            $queryParams['page'] = $prevPage;
            $prevLink = http_build_query($queryParams);

            $nextPage = min($total_pages, $page + 1);
            $queryParams['page'] = $nextPage;
            $nextLink = http_build_query($queryParams);
            ?>
            <a href="?<?= $prevLink ?>" class="px-3 py-1 border <?= $page <= 1 ? '... opacity-50' : '... hover:bg-gray-50' ?> rounded-md text-sm font-medium">Previous</a>
            <?php for ($i = 1; $i <= $total_pages; $i++):
                $queryParams['page'] = $i;
                $pageLink = http_build_query($queryParams);
            ?>
            <a href="?<?= $pageLink ?>" class="px-3 py-1 border <?= $i == $page ? 'border-blue-500 bg-blue-500 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?> rounded-md text-sm font-medium"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?<?= $nextLink ?>" class="px-3 py-1 border <?= $page >= $total_pages ? '... opacity-50' : '... hover:bg-gray-50' ?> rounded-md text-sm font-medium">Next</a>
        </nav>
        <?php endif; ?>
    </div>
</div>

<div id="addEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col" id="addEditModalContent">
        <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50"><h3 class="text-lg font-semibold text-gray-800" id="modalTitle"></h3><button onclick="closeAddEditModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button></div>
        <form id="addEditForm" class="flex-grow overflow-y-auto p-6 space-y-5">
            <input type="hidden" id="kerjasamaId" name="IdKKS">
            <input type="hidden" id="formAction" name="action">
            <div><label for="txtNamaKegiatanKS" class="block text-sm font-medium text-gray-700 mb-1">Nama Kegiatan</label><input type="text" id="txtNamaKegiatanKS" name="txtNamaKegiatanKS" class="mt-1 block w-full shadow-sm sm:text-sm rounded-md border-gray-300" required></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label for="IdJenisKS" class="block text-sm font-medium text-gray-700 mb-1">Jenis Kerjasama</label><select id="IdJenisKS" name="IdJenisKS" class="mt-1 block w-full rounded-md border-gray-300" required></select></div>
                <div><label for="IdMitraDudika" class="block text-sm font-medium text-gray-700 mb-1">Mitra</label><select id="IdMitraDudika" name="IdMitraDudika" class="mt-1 block w-full rounded-md border-gray-300" required></select></div>
            </div>
            <div><label for="IdUnitPelaksana" class="block text-sm font-medium text-gray-700 mb-1">Unit Pelaksana</label><select id="IdUnitPelaksana" name="IdUnitPelaksana" class="mt-1 block w-full rounded-md border-gray-300" required></select></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label for="txtNomorMOU" class="block text-sm font-medium text-gray-700 mb-1">Nomor MOU</label><input type="text" id="txtNomorMOU" name="txtNomorMOU" class="mt-1 block w-full shadow-sm sm:text-sm rounded-md border-gray-300"></div>
                <div><label for="dtMOU" class="block text-sm font-medium text-gray-700 mb-1">Tanggal MOU</label><input type="date" id="dtMOU" name="dtMOU" class="mt-1 block w-full shadow-sm sm:text-sm rounded-md border-gray-300"></div>
            </div>
             <div><label for="txtPeriodePelaksanaan" class="block text-sm font-medium text-gray-700 mb-1">Periode Pelaksanaan</label><input type="text" id="txtPeriodePelaksanaan" name="txtPeriodePelaksanaan" class="mt-1 block w-full shadow-sm sm:text-sm rounded-md border-gray-300" placeholder="Gunakan format: YYYY-MM-DD - YYYY-MM-DD"></div>
            <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200 mt-6">
                <button type="button" onclick="closeAddEditModal()" class="px-4 py-2 border rounded-md text-sm font-medium">Batal</button>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"><i class="fas fa-save fa-sm mr-2"></i><span id="submitButtonText">Simpan</span></button>
            </div>
        </form>
    </div>
</div>

<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col" id="detailModalContent">
        <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-file-alt text-lg"></i></div>
                <div><h3 class="text-lg font-semibold text-gray-800" id="detailModalTitle">Detail Kerjasama</h3><p class="text-sm text-gray-500" id="detailModalSubtitle"></p></div>
            </div>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="border-b border-gray-200 px-6"><nav class="flex -mb-px space-x-8"><button data-tab="info" class="tab-btn active"><i class="fas fa-info-circle mr-2"></i>Info Utama</button><button data-tab="tujuan" class="tab-btn"><i class="fas fa-bullseye mr-2"></i>Tujuan</button><button data-tab="pelaksanaan" class="tab-btn"><i class="fas fa-tasks mr-2"></i>Pelaksanaan</button></nav></div>
        <div class="flex-grow overflow-y-auto p-6">
            <div id="info" class="tab-pane active space-y-6"></div>
            <div id="tujuan" class="tab-pane hidden space-y-4"></div>
            <div id="pelaksanaan" class="tab-pane hidden space-y-4"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// SCRIPT ASLI (TIDAK DIUBAH)
document.addEventListener('DOMContentLoaded', () => {
    const swalWithTailwind = {
        customClass: { popup: 'p-4 sm:p-6 w-full max-w-sm rounded-lg shadow-lg', title: 'text-xl font-semibold text-gray-800', htmlContainer: 'mt-2 text-sm text-gray-600', actions: 'mt-4 sm:mt-6', confirmButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700', cancelButton: 'ml-3 px-4 py-2 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 border' },
        buttonsStyling: false
    };

    const detailModal = document.getElementById('detailModal'), addEditModal = document.getElementById('addEditModal');
    const detailModalContent = document.getElementById('detailModalContent'), addEditModalContent = document.getElementById('addEditModalContent');
    const modalTitle = document.getElementById('modalTitle'), addEditForm = document.getElementById('addEditForm');
    const submitButtonText = document.getElementById('submitButtonText');
    const filterForm = document.getElementById('filterForm');

    let activeElementBeforeModal;
    const openModal = (modalEl, contentEl, triggerEl) => { activeElementBeforeModal = triggerEl || document.activeElement; modalEl.classList.remove('hidden'); setTimeout(() => contentEl.classList.remove('opacity-0', 'scale-95'), 10); };
    const closeModal = (modalEl, contentEl) => { contentEl.classList.add('opacity-0', 'scale-95'); setTimeout(() => { modalEl.classList.add('hidden'); if (activeElementBeforeModal) activeElementBeforeModal.focus(); }, 300); };
    
    window.closeDetailModal = () => closeModal(detailModal, detailModalContent);
    window.closeAddEditModal = () => closeModal(addEditModal, addEditModalContent);

    const populateSelect = (selectId, data, placeholder, valueField, textField) => {
        const select = document.getElementById(selectId);
        select.innerHTML = `<option value="">${placeholder}</option>`;
        data.forEach(item => {
            select.innerHTML += `<option value="${item[valueField]}">${item[textField]}</option>`;
        });
    };
    
    fetch('kerjasama_action.php?action=get_dependencies').then(res => res.json()).then(data => {
        if (data.status === 'success') {
            populateSelect('IdJenisKS', data.jenis_ks, 'Pilih jenis...', 'IdJenisKS', 'txtNamaJenisKS');
            populateSelect('IdMitraDudika', data.mitra, 'Pilih mitra...', 'IdMitraDudika', 'txtNamaMitraDudika');
            populateSelect('IdUnitPelaksana', data.unit, 'Pilih unit...', 'IdUnitPelaksana', 'txtNamaUnitPelaksPolimdo');
        }
    });

    const showLoading = (el) => { el.innerHTML = `<div class="text-center p-8"><i class="fas fa-spinner fa-spin text-blue-500 text-3xl"></i></div>`; };
    const createInfoItem = (label, value) => `<div><dt class="text-xs font-semibold text-gray-500 uppercase">${label}</dt><dd class="mt-1 text-sm text-gray-900">${value || '-'}</dd></div>`;
    
    window.showDetailModal = (triggerEl, id) => {
        openModal(detailModal, detailModalContent, triggerEl);
        const panes = ['info', 'tujuan', 'pelaksanaan'];
        panes.forEach(pane => showLoading(document.getElementById(pane)));

        fetch(`kerjasama_action.php?action=get_single&id=${id}`).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                const d = data.data;
                document.getElementById('detailModalTitle').textContent = d.info.txtNamaKegiatanKS;
                document.getElementById('detailModalSubtitle').textContent = `ID: ${d.info.IdKKS}`;

                document.getElementById('info').innerHTML = `<dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    ${createInfoItem('Jenis Kerjasama', d.info.txtNamaJenisKS)}
                    ${createInfoItem('Mitra', d.info.txtNamaMitraDudika)}
                    ${createInfoItem('Unit Pelaksana', d.info.txtNamaUnitPelaksPolimdo)}
                    ${createInfoItem('Periode', d.info.txtPeriodePelaksanaan)}
                    ${createInfoItem('Nomor MOU', d.info.txtNomorMOU)}
                    ${createInfoItem('Tanggal MOU', new Date(d.info.dtMOU).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}))}
                </dl>`;
                
                document.getElementById('tujuan').innerHTML = `<dl class="space-y-4">${createInfoItem('Tujuan Kerjasama', d.tujuan?.txtTujuanKS?.replace(/\n/g, '<br>'))}${createInfoItem('Sasaran', d.tujuan?.txtSasaranKS?.replace(/\n/g, '<br>'))}</dl>`;
                document.getElementById('pelaksanaan').innerHTML = `<dl class="space-y-4">${createInfoItem('Deskripsi', d.pelaksanaan?.txtDeskripsiKeg?.replace(/\n/g, '<br>'))}${createInfoItem('Cakupan/Skala', d.pelaksanaan?.txtCakupanDanSkalaKeg)}${createInfoItem('Jumlah Peserta', d.pelaksanaan?.intJumlahPeserta)}</dl>`;
            }
        });
    };

    window.showAddEditModal = (triggerEl, id = null) => {
        addEditForm.reset();
        document.getElementById('formAction').value = id ? 'update' : 'add';
        modalTitle.textContent = id ? 'Edit Program Kerjasama' : 'Tambah Program Kerjasama';
        submitButtonText.textContent = id ? 'Simpan Perubahan' : 'Tambah';
        document.getElementById('kerjasamaId').value = id || '';

        if (id) {
            fetch(`kerjasama_action.php?action=get_single&id=${id}`).then(res => res.json()).then(data => {
                if(data.status === 'success') {
                    const d = data.data.info;
                    // Mengisi periode dengan format YYYY-MM-DD - YYYY-MM-DD
                    if (d.dtMulaiPelaksanaan && d.dtSelesaiPelaksanaan) {
                         d.txtPeriodePelaksanaan = `${d.dtMulaiPelaksanaan} - ${d.dtSelesaiPelaksanaan}`;
                    }
                    Object.keys(d).forEach(key => {
                        const el = document.getElementById(key);
                        if (el) el.value = d[key];
                    });
                }
            });
        }
        openModal(addEditModal, addEditModalContent, triggerEl);
    };

    window.deleteData = (id) => {
        Swal.fire({
            ...swalWithTailwind, title: 'Apakah Anda yakin?', text: `Data ID: ${id} akan dihapus permanen.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal',
            customClass: { ...swalWithTailwind.customClass, confirmButton: `${swalWithTailwind.customClass.confirmButton} bg-red-600 hover:bg-red-700` }
        }).then(async (result) => {
            if(result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('IdKKS', id);
                const response = await fetch('kerjasama_action.php', { method: 'POST', body: formData });
                const res = await response.json();
                if (res.status === 'success') {
                    await Swal.fire({ ...swalWithTailwind, title: 'Terhapus!', icon: 'success', timer: 1500, showConfirmButton: false });
                    window.location.reload();
                } else {
                    Swal.fire({ ...swalWithTailwind, title: 'Gagal!', text: res.message, icon: 'error' });
                }
            }
        });
    };
    
    addEditForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const response = await fetch('kerjasama_action.php', { method: 'POST', body: new FormData(addEditForm) });
        const res = await response.json();
        if (res.status === 'success') {
            closeAddEditModal();
            await Swal.fire({ ...swalWithTailwind, icon: 'success', title: 'Berhasil!', timer: 1500, showConfirmButton: false });
            window.location.reload();
        } else {
            Swal.fire({ ...swalWithTailwind, icon: 'error', title: 'Gagal!', text: res.message });
        }
    });

    ['searchInput', 'filterJenis', 'filterStatus'].forEach(id => {
        const element = document.getElementById(id);
        // Submit on change for selects, but on keyup for search input for better UX
        if (id === 'searchInput') {
            let timer;
            element.addEventListener('keyup', () => {
                clearTimeout(timer);
                timer = setTimeout(() => { filterForm.submit(); }, 500); // Debounce
            });
        } else {
            element.addEventListener('change', () => filterForm.submit());
        }
    });
    
    document.querySelectorAll('#detailModal .tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            const targetPaneId = button.dataset.tab;
            document.querySelectorAll('#detailModal .tab-btn').forEach(btn => btn.classList.remove('active', 'border-blue-500', 'text-blue-600'));
            button.classList.add('active', 'border-blue-500', 'text-blue-600');
            document.querySelectorAll('#detailModal .tab-pane').forEach(pane => pane.classList.add('hidden'));
            document.getElementById(targetPaneId).classList.remove('hidden');
        });
    });
});
</script>

<?php $koneksi->close(); ?>