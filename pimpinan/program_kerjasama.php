<?php
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
    JOIN tbljenisks j ON k.IdJenisKS = j.IdJenisKS
    JOIN tblmitradudika m ON k.IdMitraDudika = m.IdMitraDudika
    LEFT JOIN tbluser u ON k.nik = u.nik
    LEFT JOIN tbldokumentasi d ON k.idDokumentasi = d.idDokumentasi
";

$select_fields = "
    k.IdKKS, k.txtNamaKegiatanKS, m.txtNamaMitraDudika,
    u.nama_lengkap AS txtNamaUser,
    d.pathFoto,
    CASE 
        WHEN k.dtMulaiPelaksanaan IS NOT NULL AND k.dtSelesaiPelaksanaan IS NOT NULL 
        THEN CONCAT(DATE_FORMAT(k.dtMulaiPelaksanaan, '%d %b %Y'), ' - ', DATE_FORMAT(k.dtSelesaiPelaksanaan, '%d %b %Y'))
        ELSE 'Periode belum ditentukan'
    END AS txtPeriodePelaksanaan,
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
$sql_count_base = "SELECT (CASE WHEN k.dtSelesaiPelaksanaan < CURDATE() THEN 'Selesai' WHEN CURDATE() BETWEEN k.dtMulaiPelaksanaan AND k.dtSelesaiPelaksanaan AND k.dtSelesaiPelaksanaan <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Akan Berakhir' WHEN CURDATE() BETWEEN k.dtMulaiPelaksanaan AND k.dtSelesaiPelaksanaan THEN 'Aktif' WHEN k.dtMulaiPelaksanaan > CURDATE() THEN 'Belum Mulai' ELSE 'Tidak Diketahui' END) AS status $sql_base $where_sql";

$sql_count = "SELECT COUNT(*) as total FROM ($sql_count_base) AS subquery" . str_replace("status = ?", "subquery.status = ?", $having_sql);

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
}
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">Program Kerjasama</h2>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
            <button onclick="showAddEditModal(this)"
                class="w-full sm:w-auto bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-blue-200 focus:ring-offset-2">
                <i class="fas fa-plus fa-sm"></i>
                <span class="text-sm font-medium">Tambah Kerjasama</span>
            </button>
            <button onclick="exportData()"
                class="w-full sm:w-auto bg-gradient-to-r from-emerald-600 to-green-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-emerald-200 focus:ring-offset-2">
                <i class="fas fa-file-excel fa-sm"></i>
                <span class="text-sm font-medium">Export Excel</span>
            </button>
        </div>
    </div>

    <form id="filterForm" onsubmit="return false;">
        <div class="px-6 py-4 border-b border-gray-100 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="searchInput" name="search" placeholder="Cari nama kegiatan, mitra, atau nomor MOU..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <select id="filterJenis" name="jenis" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
                    <option value="">Semua Jenis</option>
                    <?php mysqli_data_seek($jenis_ks_list, 0);
                    while ($jenis = $jenis_ks_list->fetch_assoc()): ?>
                        <option value="<?= $jenis['IdJenisKS'] ?>"><?= htmlspecialchars($jenis['txtNamaJenisKS']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <select id="filterStatus" name="status" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="selesai">Selesai</option>
                    <option value="akan_berakhir">Akan Berakhir</option>
                    <option value="belum_mulai">Belum Mulai</option>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penanggung Jawab</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Dokumentasi</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($paginated_data)): foreach ($paginated_data as $row): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['txtNamaMitraDudika']) ?></td>

                            <!-- Tambah kolom penanggung jawab -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($row['txtNamaUser'] ?? 'Belum ditentukan') ?>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['txtPeriodePelaksanaan']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $row['status_class'] ?>">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 <?= $row['status_icon_class'] ?>" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    <?= $row['status'] ?>
                                </span>
                            </td>

                            <!-- Tambah kolom dokumentasi -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if (!empty($row['pathFoto'])): ?>
                                    <button onclick="showImageModal('<?= base64_encode($row['pathFoto']) ?>')"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-md hover:bg-green-200">
                                        <i class="fas fa-image mr-1"></i>
                                        Lihat Foto
                                    </button>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded-md">
                                        <i class="fas fa-image-slash mr-1"></i>
                                        Tidak Ada
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-center space-x-2">
                                    <button onclick="showDetailModal(this, '<?= $row['IdKKS'] ?>')" class="text-blue-600 hover:text-blue-900" title="Detail"><i class="fas fa-eye fa-lg"></i></button>
                                    <button onclick="showAddEditModal(this, '<?= $row['IdKKS'] ?>')" class="text-indigo-600 hover:text-indigo-900" title="Edit"><i class="fas fa-edit fa-lg"></i></button>
                                    <button onclick="deleteData('<?= $row['IdKKS'] ?>')" class="text-red-600 hover:text-red-900" title="Hapus"><i class="fas fa-trash fa-lg"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-gray-500">Tidak ada data kerja sama yang cocok dengan filter.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="block sm:hidden space-y-3 p-4">
        <?php if (!empty($paginated_data)): foreach ($paginated_data as $row): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-xs">
                    <div class="flex justify-between items-start">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $row['status_class'] ?>">
                            <svg class="-ml-0.5 mr-1.5 h-2 w-2 <?= $row['status_icon_class'] ?>" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="3" />
                            </svg>
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
                        <div class="flex items-start">
                            <i class="fas fa-handshake text-gray-400 mt-0.5 mr-2"></i>
                            <span class="text-gray-600"><?= htmlspecialchars($row['txtNamaMitraDudika']) ?></span>
                        </div>

                        <!-- Tambah info penanggung jawab -->
                        <div class="flex items-start">
                            <i class="fas fa-user text-gray-400 mt-0.5 mr-2"></i>
                            <span class="text-gray-600"><?= htmlspecialchars($row['txtNamaUser'] ?? 'Belum ditentukan') ?></span>
                        </div>

                        <div class="flex items-start">
                            <i class="fas fa-calendar-alt text-gray-400 mt-0.5 mr-2"></i>
                            <span class="text-gray-600"><?= htmlspecialchars($row['txtPeriodePelaksanaan']) ?></span>
                        </div>

                        <!-- Tambah info dokumentasi -->
                        <div class="flex items-start">
                            <i class="fas fa-camera text-gray-400 mt-0.5 mr-2"></i>
                            <?php if (!empty($row['pathFoto'])): ?>
                                <button onclick="showImageModal('<?= base64_encode($row['pathFoto']) ?>')"
                                    class="text-blue-600 hover:text-blue-500 text-left">
                                    Lihat Dokumentasi
                                </button>
                            <?php else: ?>
                                <span class="text-gray-500">Tidak ada dokumentasi</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach;
        else: ?>
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
                unset($queryParams['page']); // Hapus page sebelumnya

                // Previous link
                $prevPage = max(1, $page - 1);
                $queryParams['page'] = $prevPage;
                $prevLink = http_build_query($queryParams);

                // Next link
                $nextPage = min($total_pages, $page + 1);
                $queryParams['page'] = $nextPage;
                $nextLink = http_build_query($queryParams);

                // Page links
                for ($i = 1; $i <= $total_pages; $i++):
                    $queryParams['page'] = $i;
                    $pageLink = http_build_query($queryParams);
                ?>
                    <a href="?<?= $pageLink ?>"
                        class="px-3 py-1 border <?= $i == $page ? 'border-blue-500 bg-blue-500 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?> rounded-md text-sm font-medium">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div id="addEditModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col" id="addEditModalContent">
        <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800" id="modalTitle"></h3>
            <button onclick="closeAddEditModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="addEditForm" class="flex-grow overflow-y-auto p-6 space-y-5" enctype="multipart/form-data">
            <input type="hidden" id="kerjasamaId" name="IdKKS">
            <input type="hidden" id="formAction" name="action">

            <div>
                <label for="txtNamaKegiatanKS" class="block text-sm font-medium text-gray-700 mb-1">Nama Kegiatan</label>
                <input type="text" id="txtNamaKegiatanKS" name="txtNamaKegiatanKS"
                    class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2"
                    placeholder="Contoh: Program Penguatan Kurikulum" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="IdJenisKS" class="block text-sm font-medium text-gray-700 mb-1">Jenis Kerjasama</label>
                    <select id="IdJenisKS" name="IdJenisKS"
                        class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2" required>
                        <option value="">-- Pilih Jenis Kerjasama --</option>
                    </select>
                </div>
                <div>
                    <label for="IdMitraDudika" class="block text-sm font-medium text-gray-700 mb-1">Mitra</label>
                    <select id="IdMitraDudika" name="IdMitraDudika"
                        class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2" required>
                        <option value="">-- Pilih Mitra --</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="IdUnitPelaksana" class="block text-sm font-medium text-gray-700 mb-1">Unit Pelaksana</label>
                <select id="IdUnitPelaksana" name="IdUnitPelaksana"
                    class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2" required>
                    <option value="">-- Pilih Unit Pelaksana --</option>
                </select>
            </div>

            <div>
                <label for="nik" class="block text-sm font-medium text-gray-700 mb-1">Penanggungjawab</label>
                <select id="nik" name="nik"
                    class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2" required>
                    <option value="">-- Pilih Penanggungjawab --</option>
                </select>
            </div>

            <div>
                <label for="pathFoto" class="block text-sm font-medium text-gray-700 mb-1">Upload Foto Dokumentasi</label>
                <div class="mt-1 flex items-center space-x-4">
                    <input type="file" id="pathFoto" name="pathFoto" accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <div id="currentPhoto" class="hidden">
                        <img id="photoPreview" src="" alt="Preview" class="h-20 w-20 object-cover rounded-md border">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="txtNomorMOU" class="block text-sm font-medium text-gray-700 mb-1">Nomor MOU</label>
                    <input type="text" id="txtNomorMOU" name="txtNomorMOU"
                        class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2"
                        placeholder="Contoh: MOU-001">
                </div>
                <div>
                    <label for="dtMOU" class="block text-sm font-medium text-gray-700 mb-1">Tanggal MOU</label>
                    <input type="date" id="dtMOU" name="dtMOU"
                        class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="dtMulaiPelaksanaan" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai Pelaksanaan</label>
                    <input type="date" id="dtMulaiPelaksanaan" name="dtMulaiPelaksanaan"
                        class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label for="dtSelesaiPelaksanaan" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai Pelaksanaan</label>
                    <input type="date" id="dtSelesaiPelaksanaan" name="dtSelesaiPelaksanaan"
                        class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md px-3 py-2" required>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200">
                <button type="button" onclick="closeAddEditModal()"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Batal
                </button>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-save fa-sm mr-2"></i>
                    <span id="submitButtonText">Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col" id="detailModalContent">
        <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0"><i class="fas fa-file-alt text-lg"></i></div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800" id="detailModalTitle">Detail Kerjasama</h3>
                    <p class="text-sm text-gray-500" id="detailModalSubtitle"></p>
                </div>
            </div>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="border-b border-gray-200 px-6">
            <nav class="flex -mb-px space-x-8">
                <button data-tab="info" class="tab-btn active border-b-2 border-blue-500 text-blue-600 py-2 px-1 text-sm font-medium">
                    <i class="fas fa-info-circle mr-2"></i>Info Utama
                </button>
                <button data-tab="tujuan" class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 text-sm font-medium">
                    <i class="fas fa-bullseye mr-2"></i>Tujuan
                </button>
                <button data-tab="pelaksanaan" class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 text-sm font-medium">
                    <i class="fas fa-tasks mr-2"></i>Pelaksanaan
                </button>
                <button data-tab="dokumentasi" class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-2 px-1 text-sm font-medium">
                    <i class="fas fa-camera mr-2"></i>Dokumentasi
                </button>
            </nav>
        </div>
        <div class="flex-grow overflow-y-auto p-6">
            <div id="info" class="tab-pane active space-y-6"></div>
            <div id="tujuan" class="tab-pane hidden space-y-4"></div>
            <div id="pelaksanaan" class="tab-pane hidden space-y-4"></div>
            <div id="dokumentasi" class="tab-pane hidden space-y-4"></div>
        </div>
    </div>
</div>

<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="relative max-w-4xl max-h-full">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <img id="modalImage" src="" alt="Dokumentasi" class="max-w-full max-h-full object-contain rounded-lg">
    </div>
    <div id="imageLoading" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-lg hidden">
        <i class="fas fa-spinner fa-spin text-white text-2xl"></i>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const swalWithTailwind = {
            customClass: {
                popup: 'p-4 sm:p-6 w-full max-w-sm rounded-lg shadow-lg',
                title: 'text-xl font-semibold text-gray-800',
                htmlContainer: 'mt-2 text-sm text-gray-600',
                actions: 'mt-4 sm:mt-6',
                confirmButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700',
                cancelButton: 'ml-3 px-4 py-2 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 border'
            },
            buttonsStyling: false
        };

        const detailModal = document.getElementById('detailModal'),
            addEditModal = document.getElementById('addEditModal');
        const detailModalContent = document.getElementById('detailModalContent'),
            addEditModalContent = document.getElementById('addEditModalContent');
        const modalTitle = document.getElementById('modalTitle'),
            addEditForm = document.getElementById('addEditForm');
        const submitButtonText = document.getElementById('submitButtonText');
        const filterForm = document.getElementById('filterForm');
        const searchInput = document.getElementById('searchInput');
        const filterJenis = document.getElementById('filterJenis');
        const filterStatus = document.getElementById('filterStatus');

        // Fungsi untuk escape HTML (mencegah XSS)
        const escapeHtml = (unsafe) => {
            if (unsafe === null || typeof unsafe === 'undefined') {
                return '';
            }
            return unsafe.toString().replace(/[&<"'>]/g, function(m) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [m];
            });
        };

        // Fungsi untuk menampilkan error
        const showError = (message) => {
            console.error('Error:', message);
            Swal.fire({
                ...swalWithTailwind,
                icon: 'error',
                title: 'Error!',
                text: message
            });
        };

        let activeElementBeforeModal;
        const openModal = (modalEl, contentEl, triggerEl) => {
            activeElementBeforeModal = triggerEl || document.activeElement;
            modalEl.classList.remove('hidden');
            setTimeout(() => contentEl.classList.remove('opacity-0', 'scale-95'), 10);
        };
        const closeModal = (modalEl, contentEl) => {
            contentEl.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modalEl.classList.add('hidden');
                if (activeElementBeforeModal) activeElementBeforeModal.focus();
            }, 300);
        };

        window.closeDetailModal = () => closeModal(detailModal, detailModalContent);
        window.closeAddEditModal = () => closeModal(addEditModal, addEditModalContent);

        const populateSelect = (selectId, data, placeholder, valueField, textField) => {
            const select = document.getElementById(selectId);
            select.innerHTML = `<option value="">${placeholder}</option>`;
            data.forEach(item => {
                select.innerHTML += `<option value="${item[valueField]}">${escapeHtml(item[textField])}</option>`;
            });
        };

        // Load dependencies untuk dropdown
        fetch('pimpinan/kerjasama_action.php?action=get_dependencies')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    populateSelect('IdJenisKS', data.jenis_ks, 'Pilih jenis...', 'IdJenisKS', 'txtNamaJenisKS');
                    populateSelect('IdMitraDudika', data.mitra, 'Pilih mitra...', 'IdMitraDudika', 'txtNamaMitraDudika');
                    populateSelect('IdUnitPelaksana', data.unit, 'Pilih unit...', 'IdUnitPelaksana', 'txtNamaUnitPelaksPolimdo');
                    populateSelect('nik', data.users, 'Pilih penanggungjawab...', 'nik', 'txtNamaUser');
                }
            })
            .catch(error => {
                console.error('Error loading dependencies:', error);
            });

        const showLoading = (el) => {
            el.innerHTML = `<div class="text-center p-8"><i class="fas fa-spinner fa-spin text-blue-500 text-3xl"></i></div>`;
        };
        const createInfoItem = (label, value) => `<div><dt class="text-xs font-semibold text-gray-500 uppercase">${label}</dt><dd class="mt-1 text-sm text-gray-900">${value || '-'}</dd></div>`;

        // Fungsi untuk menampilkan hasil pencarian
        const renderSearchResults = (data) => {
            const tbody = document.querySelector('tbody');
            const mobileContainer = document.querySelector('.block.sm\\:hidden .space-y-3');

            if (!data || data.length === 0) {
                const noDataHtml = '<tr><td colspan="7" class="text-center py-4 text-gray-500">Tidak ada data yang ditemukan</td></tr>';
                const noDataMobileHtml = '<p class="text-center py-4 text-gray-500">Tidak ada data kerja sama.</p>';
                if (tbody) tbody.innerHTML = noDataHtml;
                if (mobileContainer) mobileContainer.innerHTML = noDataMobileHtml;
                return;
            }

            // Render untuk desktop table
            if (tbody) {
                let html = '';
                data.forEach(row => {
                    html += `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">${escapeHtml(row.txtNamaKegiatanKS)}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(row.txtNamaMitraDudika)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${escapeHtml(row.txtNamaUser || 'Belum ditentukan')}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(row.txtPeriodePelaksanaan)}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${row.status_class}">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 ${row.status_icon_class}" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    ${row.status}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                ${row.pathFoto_base64 ? 
                                    `<button onclick="showImageModal('${row.pathFoto_base64}')" class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-md hover:bg-green-200">
                                        <i class="fas fa-image mr-1"></i> Lihat Foto
                                    </button>` : 
                                    `<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded-md">
                                        <i class="fas fa-image-slash mr-1"></i> Tidak Ada
                                    </span>`
                                }
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-center space-x-2">
                                    <button onclick="showDetailModal(this, '${row.IdKKS}')" class="text-blue-600 hover:text-blue-900" title="Detail"><i class="fas fa-eye fa-lg"></i></button>
                                    <button onclick="showAddEditModal(this, '${row.IdKKS}')" class="text-indigo-600 hover:text-indigo-900" title="Edit"><i class="fas fa-edit fa-lg"></i></button>
                                    <button onclick="deleteData('${row.IdKKS}')" class="text-red-600 hover:text-red-900" title="Hapus"><i class="fas fa-trash fa-lg"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            }

            // Render untuk mobile cards
            if (mobileContainer) {
                let mobileHtml = '';
                data.forEach(row => {
                    mobileHtml += `
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-xs">
                            <div class="flex justify-between items-start">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${row.status_class}">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 ${row.status_icon_class}" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3" />
                                    </svg>
                                    ${row.status}
                                </span>
                                <div class="flex space-x-2">
                                    <button onclick="showDetailModal(this, '${row.IdKKS}')" class="text-blue-600 hover:text-blue-500" title="Detail"><i class="fas fa-eye"></i></button>
                                    <button onclick="showAddEditModal(this, '${row.IdKKS}')" class="text-indigo-600 hover:text-indigo-500" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button onclick="deleteData('${row.IdKKS}')" class="text-red-600 hover:text-red-500" title="Hapus"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <h3 class="mt-2 text-sm font-semibold text-gray-800">${escapeHtml(row.txtNamaKegiatanKS)}</h3>
                            <div class="mt-3 space-y-2 text-sm border-t border-gray-200 pt-3">
                                <div class="flex items-start">
                                    <i class="fas fa-handshake text-gray-400 mt-0.5 mr-2"></i>
                                    <span class="text-gray-600">${escapeHtml(row.txtNamaMitraDudika)}</span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-user text-gray-400 mt-0.5 mr-2"></i>
                                    <span class="text-gray-600">${escapeHtml(row.txtNamaUser || 'Belum ditentukan')}</span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-calendar-alt text-gray-400 mt-0.5 mr-2"></i>
                                    <span class="text-gray-600">${escapeHtml(row.txtPeriodePelaksanaan)}</span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-camera text-gray-400 mt-0.5 mr-2"></i>
                                    ${row.pathFoto_base64 ? 
                                        `<button onclick="showImageModal('${row.pathFoto_base64}')" class="text-blue-600 hover:text-blue-500 text-left">
                                            Lihat Dokumentasi
                                        </button>` : 
                                        `<span class="text-gray-500">Tidak ada dokumentasi</span>`
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                });
                mobileContainer.innerHTML = mobileHtml;
            }
        };

        // Fungsi untuk update info pagination
        const updatePaginationInfo = (total) => {
            const paginationInfo = document.querySelector('p.text-sm.text-gray-600');
            if (paginationInfo) {
                paginationInfo.innerHTML = `Menampilkan <span class="font-medium">1-${Math.min(10, total)}</span> dari <span class="font-medium">${total}</span> data`;
            }
        };

        // Fungsi utama untuk melakukan pencarian
        const performSearch = async () => {
            const searchValue = searchInput?.value || '';
            const jenisValue = filterJenis?.value || '';
            const statusValue = filterStatus?.value || '';
            const tbody = document.querySelector('tbody');
            const mobileContainer = document.querySelector('.block.sm\\:hidden .space-y-3');

            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>';
            if (mobileContainer) mobileContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';

            try {
                const params = new URLSearchParams({
                    action: 'search',
                    search: searchValue,
                    jenis: jenisValue,
                    status: statusValue
                });
                const response = await fetch(`pimpinan/kerjasama_action.php?${params.toString()}`);

                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();

                if (data.status === 'success') {
                    renderSearchResults(data.data);
                    updatePaginationInfo(data.total);
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan saat memproses data');
                }
            } catch (error) {
                console.error('Error in performSearch:', error);
                showError(error.message);
                const errorHtml = '<tr><td colspan="7" class="text-center py-4 text-red-500">Gagal memuat data. Silakan coba lagi.</td></tr>';
                const errorMobileHtml = '<p class="text-center py-4 text-red-500">Gagal memuat data. Silakan coba lagi.</p>';
                if (tbody) tbody.innerHTML = errorHtml;
                if (mobileContainer) mobileContainer.innerHTML = errorMobileHtml;
            }
        };

        // Modal functions
        window.showDetailModal = (triggerEl, id) => {
            openModal(detailModal, detailModalContent, triggerEl);
            const panes = ['info', 'tujuan', 'pelaksanaan', 'dokumentasi'];
            panes.forEach(pane => showLoading(document.getElementById(pane)));

            fetch(`pimpinan/kerjasama_action.php?action=get_single&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const d = data.data;
                        document.getElementById('detailModalTitle').textContent = escapeHtml(d.info.txtNamaKegiatanKS);
                        document.getElementById('detailModalSubtitle').textContent = `ID: ${escapeHtml(d.info.IdKKS)}`;

                        document.getElementById('info').innerHTML = `<dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            ${createInfoItem('Jenis Kerjasama', escapeHtml(d.info.txtNamaJenisKS))}
                            ${createInfoItem('Mitra', escapeHtml(d.info.txtNamaMitraDudika))}
                            ${createInfoItem('Unit Pelaksana', escapeHtml(d.info.txtNamaUnitPelaksPolimdo))}
                            ${createInfoItem('Periode', escapeHtml(d.info.txtPeriodePelaksanaan))}
                            ${createInfoItem('Nomor MOU', escapeHtml(d.info.txtNomorMOU))}
                            ${createInfoItem('Tanggal MOU', d.info.dtMOU ? new Date(d.info.dtMOU).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : '-')}
                        </dl>`;

                        document.getElementById('tujuan').innerHTML = `<dl class="space-y-4">${createInfoItem('Tujuan Kerjasama', d.tujuan?.txtTujuanKS?.replace(/\n/g, '<br>') || '-')}${createInfoItem('Sasaran', d.tujuan?.txtSasaranKS?.replace(/\n/g, '<br>') || '-')}</dl>`;
                        document.getElementById('pelaksanaan').innerHTML = `<dl class="space-y-4">${createInfoItem('Deskripsi', d.pelaksanaan?.txtDeskripsiKeg?.replace(/\n/g, '<br>') || '-')}${createInfoItem('Cakupan/Skala', escapeHtml(d.pelaksanaan?.txtCakupanDanSkalaKeg) || '-')}${createInfoItem('Jumlah Peserta', d.pelaksanaan?.intJumlahPeserta || '-')}</dl>`;

                        const fotoHtml = d.info.pathFoto_base64 ?
                            `<img src="data:image/jpeg;base64,${d.info.pathFoto_base64}" alt="Dokumentasi" class="h-48 w-full object-cover rounded-md border cursor-pointer" onclick="showImageModal('${d.info.pathFoto_base64}')">` :
                            '<span class="text-sm text-gray-500">Tidak ada foto</span>';

                        document.getElementById('dokumentasi').innerHTML = `
                        <dl class="space-y-4">
                            ${createInfoItem('Penanggungjawab', escapeHtml(d.info.txtNamaUser) || 'Belum ditentukan')}
                            <div>
                                <dt class="text-xs font-semibold text-gray-500 uppercase">Foto Dokumentasi</dt>
                                <dd class="mt-2">${fotoHtml}</dd>
                            </div>
                        </dl>`;
                    } else {
                        throw new Error(data.message || 'Data tidak ditemukan');
                    }
                })
                .catch(error => {
                    console.error('Error loading detail:', error);
                    showError('Gagal memuat detail data: ' + error.message);
                });
        };

        window.showAddEditModal = (triggerEl, id = null) => {
            addEditForm.reset();
            document.getElementById('formAction').value = id ? 'update' : 'add';
            modalTitle.textContent = id ? 'Edit Program Kerjasama' : 'Tambah Program Kerjasama';
            submitButtonText.textContent = id ? 'Simpan Perubahan' : 'Tambah';
            document.getElementById('kerjasamaId').value = id || '';

            document.getElementById('currentPhoto').classList.add('hidden');
            document.getElementById('photoPreview').src = '';
            document.getElementById('pathFoto').value = ''; // Reset file input

            if (id) {
                fetch(`pimpinan/kerjasama_action.php?action=get_single&id=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const d = data.data.info;
                            const fieldsToPopulate = [
                                'txtNamaKegiatanKS', 'IdJenisKS', 'IdMitraDudika',
                                'IdUnitPelaksana', 'nik', 'txtNomorMOU', 'dtMOU',
                                'dtMulaiPelaksanaan', 'dtSelesaiPelaksanaan'
                            ];
                            fieldsToPopulate.forEach(fieldName => {
                                const element = document.getElementById(fieldName);
                                if (element && d[fieldName] !== undefined) {
                                    element.value = d[fieldName] || '';
                                }
                            });

                            if (d.pathFoto_base64) {
                                document.getElementById('currentPhoto').classList.remove('hidden');
                                document.getElementById('photoPreview').src = `data:image/jpeg;base64,${d.pathFoto_base64}`;
                            }
                        } else {
                            throw new Error(data.message || 'Gagal memuat data');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading data for edit:', error);
                        showError('Gagal memuat data untuk edit: ' + error.message);
                        closeAddEditModal();
                    });
            }
            openModal(addEditModal, addEditModalContent, triggerEl);
        };

        window.deleteData = (id) => {
            Swal.fire({
                ...swalWithTailwind,
                title: 'Apakah Anda yakin?',
                text: `Data ID: ${id} akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    ...swalWithTailwind.customClass,
                    confirmButton: `${swalWithTailwind.customClass.confirmButton} bg-red-600 hover:bg-red-700`
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('IdKKS', id);

                        const response = await fetch('pimpinan/kerjasama_action.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        const responseText = await response.text();
                        let res;

                        try {
                            res = JSON.parse(responseText);
                        } catch (parseError) {
                            console.error('Response is not valid JSON:', responseText);
                            throw new Error('Server mengembalikan response yang tidak valid');
                        }

                        if (res.status === 'success') {
                            await Swal.fire({
                                ...swalWithTailwind,
                                title: 'Terhapus!',
                                text: res.message || 'Data berhasil dihapus',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            performSearch(); // Refresh data setelah delete
                        } else {
                            throw new Error(res.message || 'Gagal menghapus data dari server.');
                        }
                    } catch (error) {
                        console.error('Error deleting data:', error);
                        showError(error.message || 'Terjadi kesalahan saat menghapus data.');
                    }
                }
            });
        };

        // Fungsi untuk menampilkan modal gambar
        window.showImageModal = (base64Image) => {
            Swal.fire({
                imageUrl: `data:image/jpeg;base64,${base64Image}`,
                imageAlt: 'Dokumentasi Kerjasama',
                showCloseButton: true,
                showConfirmButton: false,
                background: '#fff',
                customClass: {
                    popup: 'p-0 rounded-lg',
                    image: 'w-full h-auto max-h-[80vh] object-contain rounded-lg'
                }
            });
        };

        // Event listener untuk submit form tambah/edit
        addEditForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = addEditForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButtonText.textContent;
            submitButton.disabled = true;
            submitButtonText.textContent = 'Menyimpan...';

            try {
                const formData = new FormData(addEditForm);
                const response = await fetch('pimpinan/kerjasama_action.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error(`Server error: ${response.statusText}`);
                const res = await response.json();

                if (res.status === 'success') {
                    closeAddEditModal();
                    await Swal.fire({
                        ...swalWithTailwind,
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    performSearch();
                } else {
                    throw new Error(res.message || 'Terjadi kesalahan yang tidak diketahui.');
                }
            } catch (error) {
                showError(error.message);
            } finally {
                submitButton.disabled = false;
                submitButtonText.textContent = originalButtonText;
            }
        });

        // Event listener untuk pratinjau foto
        const photoInput = document.getElementById('pathFoto');
        if (photoInput) {
            photoInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        document.getElementById('photoPreview').src = event.target.result;
                        document.getElementById('currentPhoto').classList.remove('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            });
        }

        // Event listeners untuk filter dan pencarian
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                performSearch();
            });
        }
        if (filterJenis) filterJenis.addEventListener('change', performSearch);
        if (filterStatus) filterStatus.addEventListener('change', performSearch);

        // Event listener untuk menutup modal
        [detailModal, addEditModal].forEach(modal => {
            modal.addEventListener('click', e => {
                if (e.target === modal) {
                    if (modal === detailModal) closeDetailModal();
                    if (modal === addEditModal) closeAddEditModal();
                }
            });
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                if (!detailModal.classList.contains('hidden')) closeDetailModal();
                if (!addEditModal.classList.contains('hidden')) closeAddEditModal();
            }
        });

        // Memuat data awal saat halaman dimuat
        performSearch();
    });

    function exportData() {
        // Ambil parameter filter yang aktif
        const search = document.getElementById('searchInput').value;
        const jenis = document.getElementById('filterJenis').value;
        const status = document.getElementById('filterStatus').value;

        // Bangun URL dengan parameter
        const params = new URLSearchParams({
            action: 'export_kerjasama',
            search: search,
            jenis: jenis,
            status: status
        });

        // Buka tab baru untuk export
        window.open(`pimpinan/export_excel.php?${params.toString()}`, '_blank');
    }

    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    // Tambahkan event listener untuk setiap tombol
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Ambil nama tab target dari atribut data-tab
            const targetTab = button.getAttribute('data-tab');

            // 1. Non-aktifkan semua tombol terlebih dahulu
            tabButtons.forEach(btn => {
                btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            // 2. Aktifkan tombol yang baru saja diklik
            button.classList.add('active', 'border-blue-500', 'text-blue-600');
            button.classList.remove('border-transparent', 'text-gray-500');

            // 3. Sembunyikan semua panel konten
            tabPanes.forEach(pane => {
                pane.classList.add('hidden');
            });

            // 4. Tampilkan panel konten yang sesuai dengan tombol yang diklik
            const activePane = document.getElementById(targetTab);
            if (activePane) {
                activePane.classList.remove('hidden');
            }
        });
    });
</script>

<?php $koneksi->close(); ?>