<?php
$limit = 5; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query untuk menghitung total data
$count_query = $koneksi->query("SELECT COUNT(*) as total FROM tblhasildancapaian");
$total_data = $count_query->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Query untuk mengambil data capaian dengan join ke nama kegiatan
$sql = "SELECT
            hc.idHslDanCap,
            k.txtNamaKegiatanKS,
            hc.txtHasilLangsung,
            hc.txtManfaatBgMhsw
        FROM
            tblhasildancapaian hc
        JOIN
            tblnamakegiatanks k ON hc.IdKKS = k.IdKKS
        ORDER BY
            k.dtMOU DESC
        LIMIT ? OFFSET ?";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$capaian_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<main>
    <div class="space-y-8">
        <div class="flex flex-wrap justify-between items-center gap-4 bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div>
                <h2 class="text-xl font-bold text-gray-800 tracking-tight">Hasil dan Capaian Kerjasama</h2>
                <p class="text-sm text-gray-500 mt-1">Evaluasi dan rekam dampak dari setiap program kerjasama.</p>
            </div>
            <button onclick="exportData()" class="bg-gradient-to-r from-emerald-600 to-green-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90 transition-opacity flex items-center space-x-2 shadow-md hover:shadow-lg focus:ring-2 focus:ring-emerald-200 focus:ring-offset-2">
                <i class="fas fa-file-excel fa-sm"></i>
                <span class="text-sm font-medium">Export Excel</span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800 tracking-tight">Daftar Capaian Kerjasama</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kegiatan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hasil Langsung</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manfaat Bagi Mahasiswa</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                             <?php if (!empty($capaian_data)): ?>
                                <?php foreach ($capaian_data as $row): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($row['txtHasilLangsung']) ?>"><?= htmlspecialchars($row['txtHasilLangsung']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($row['txtManfaatBgMhsw']) ?>"><?= htmlspecialchars($row['txtManfaatBgMhsw']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <button onclick="showEditModal(this, '<?= $row['idHslDanCap'] ?>')" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="Detail / Edit">
                                                <i class="fas fa-edit fa-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-6 text-gray-500">Belum ada data capaian yang diinput.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                    <p class="text-sm text-gray-600">Menampilkan <?= count($capaian_data) ?> dari <?= $total_data ?> data</p>
                    <?php if ($total_pages > 1): ?>
                    <nav class="flex items-center space-x-1">
                        <a href="?page=<?= max(1, $page-1) ?>" class="px-3 py-1 border <?= $page <= 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'hover:bg-gray-50' ?> rounded-md text-sm">Previous</a>
                        <a href="?page=<?= min($total_pages, $page+1) ?>" class="px-3 py-1 border <?= $page >= $total_pages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'hover:bg-gray-50' ?> rounded-md text-sm">Next</a>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800 tracking-tight">Visualisasi Manfaat</h3>
                    </div>
                    <div class="p-4">
                        <canvas id="benefitChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800 tracking-tight">Catatan Pimpinan</h3>
                    </div>
                    <div class="p-4">
                        <label for="catatanPimpinan" class="sr-only">Tulis catatan Anda</label>
                        <textarea id="catatanPimpinan" rows="4" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Beri masukan atau arahan terkait hasil kerjasama..."></textarea>
                        <button onclick="saveNote()" class="mt-3 w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Simpan Catatan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col overflow-hidden border border-gray-200" id="editModalContent">
        <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">Detail & Edit Capaian</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500 rounded-full w-8 h-8 flex items-center justify-center hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editCapaianForm" class="flex-grow overflow-y-auto p-6 space-y-6">
            <input type="hidden" id="idHslDanCap" name="idHslDanCap">
            <div class="space-y-5">
                <div class="flex items-center border-b pb-2"><i class="fas fa-chart-pie text-blue-500 mr-3"></i><h4 class="text-base font-semibold text-gray-700">Hasil & Dampak</h4></div>
                <div><label for="txtHasilLangsung" class="block text-sm font-medium text-gray-700 mb-1">Hasil Langsung (Output)</label><textarea id="txtHasilLangsung" name="txtHasilLangsung" rows="3" class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md p-3"></textarea></div>
                <div><label for="txtDampakJangkaMenengah" class="block text-sm font-medium text-gray-700 mb-1">Dampak Jangka Menengah (Outcome)</label><textarea id="txtDampakJangkaMenengah" name="txtDampakJangkaMenengah" rows="3" class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md p-3"></textarea></div>
            </div>
            <div class="space-y-5">
                <div class="flex items-center border-b pb-2"><i class="fas fa-users text-blue-500 mr-3"></i><h4 class="text-base font-semibold text-gray-700">Rincian Manfaat</h4></div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div><label for="txtManfaatBgMhsw" class="block text-sm font-medium text-gray-700 mb-1">Bagi Mahasiswa</label><textarea id="txtManfaatBgMhsw" name="txtManfaatBgMhsw" rows="4" class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md p-3"></textarea></div>
                    <div><label for="txtManfaatBgPolimdo" class="block text-sm font-medium text-gray-700 mb-1">Bagi Polimdo</label><textarea id="txtManfaatBgPolimdo" name="txtManfaatBgPolimdo" rows="4" class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md p-3"></textarea></div>
                    <div><label for="txtManfaatBgDudika" class="block text-sm font-medium text-gray-700 mb-1">Bagi DUDIKA</label><textarea id="txtManfaatBgDudika" name="txtManfaatBgDudika" rows="4" class="mt-1 block w-full shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-md p-3"></textarea></div>
                </div>
            </div>
            <div class="flex justify-end space-x-3 pt-5 border-t border-gray-200">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Batal</button>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"><i class="fas fa-save fa-sm mr-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // === Konfigurasi dan Elemen Global ===
    let activeElementBeforeModal;
    const editModal = document.getElementById('editModal');
    const editModalContent = document.getElementById('editModalContent');
    const editCapaianForm = document.getElementById('editCapaianForm');
    const catatanPimpinanTextarea = document.getElementById('catatanPimpinan');
    let benefitChart = null; // Variabel untuk menyimpan instance chart

    const swalWithTailwind = {
        customClass: { popup: 'p-4 sm:p-6 w-full max-w-sm rounded-lg shadow-lg', title: 'text-xl font-semibold text-gray-800', htmlContainer: 'mt-2 text-sm text-gray-600', actions: 'mt-4 sm:mt-6', confirmButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700' },
        buttonsStyling: false
    };

    // === Fungsi Backend Communication ===
    const apiCall = async (action, options = {}) => {
        const url = `capaian_action.php?action=${action}`;
        try {
            const response = await fetch(url, options);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error("API call failed:", error);
            Swal.fire({ ...swalWithTailwind, icon: 'error', title: 'Koneksi Gagal', text: 'Tidak dapat terhubung ke server.' });
            return { status: 'error', message: 'Network error' };
        }
    };

    // === Fungsi Modal ===
    async function showEditModal(triggerElement, id) {
        activeElementBeforeModal = triggerElement || document.activeElement;
        const result = await apiCall(`get_single_capaian&id=${id}`);
        
        if (result.status === 'success') {
            const data = result.data;
            Object.keys(data).forEach(key => {
                const el = document.getElementById(key);
                if (el) el.value = data[key];
            });
            editModal.classList.remove('hidden');
            setTimeout(() => editModalContent.classList.remove('opacity-0', 'scale-95'), 10);
        }
    }

    function closeEditModal() {
        editModalContent.classList.add('opacity-0', 'scale-95');
        setTimeout(() => editModal.classList.add('hidden'), 300);
    }
    
    // === Fungsi Halaman ===
    function exportData() {
        Swal.fire({ ...swalWithTailwind, icon: 'info', title: 'Fungsi Dalam Pengembangan', text: 'Fitur export data ke Excel akan segera tersedia!' });
    }

    async function saveNote() {
        const note = catatanPimpinanTextarea.value;
        const result = await apiCall('save_note', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `note=${encodeURIComponent(note)}`
        });
        if (result.status === 'success') {
            Swal.fire({ ...swalWithTailwind, icon: 'success', title: 'Berhasil!', text: 'Catatan pimpinan telah disimpan.', timer: 1500, showConfirmButton: false });
        }
    }
    
    async function loadChartData() {
        const result = await apiCall('get_chart_data');
        if (result.status === 'success') {
            const ctx = document.getElementById('benefitChart').getContext('2d');
            if(benefitChart) benefitChart.destroy(); // Hapus chart lama jika ada
            benefitChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Mahasiswa', 'Polimdo', 'DUDIKA'],
                    datasets: [{
                        label: 'Rata-rata Manfaat (berdasarkan panjang teks)',
                        data: [result.data.mahasiswa, result.data.polimdo, result.data.dudika],
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'],
                        borderColor: ['#2563EB', '#059669', '#D97706'],
                        borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true } }, responsive: true, maintainAspectRatio: false }
            });
        }
    }
    
    // === Event Listeners ===
    document.addEventListener('DOMContentLoaded', async () => {
        // Muat data chart saat halaman dibuka
        loadChartData();
        // Muat catatan pimpinan
        const noteResult = await apiCall('get_note');
        if(noteResult.status === 'success') {
            catatanPimpinanTextarea.value = noteResult.data;
        }
    });

    editCapaianForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(editCapaianForm);
        formData.append('action', 'update_capaian');
        
        const result = await apiCall('update_capaian', { method: 'POST', body: formData });
        
        if (result.status === 'success') {
            closeEditModal();
            await Swal.fire({ ...swalWithTailwind, icon: 'success', title: 'Berhasil Disimpan!', timer: 1500, showConfirmButton: false });
            window.location.reload();
        }
    });
    
    // Event listener untuk menutup modal
    editModal.addEventListener('click', (e) => { if (e.target === editModal) closeEditModal(); });
    window.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !editModal.classList.contains('hidden')) closeEditModal(); });
</script>

<?php
$koneksi->close();
?>