<?php
// Pastikan variabel $koneksi sudah ada dari file config Anda
// require_once '../config/koneksi.php';

// === 1. AMBIL DATA RATA-RATA UNTUK KARTU SKOR ===
$sql_avg = "SELECT
                AVG(txtSesuaiRencana) as avg_rencana,
                AVG(txtKualitasPelaks) as avg_kualitas,
                AVG(txtKeterlibatanMtra) as avg_mitra,
                AVG(txtEfisiensiPenggSbDya) as avg_efisiensi,
                AVG(txtKepuasanPhkTerkait) as avg_kepuasan
            FROM tblevaluasikinerja";
$avg_result = $koneksi->query($sql_avg);
$average_scores = $avg_result->fetch_assoc();

// Fungsi helper untuk generate bintang dari skor
function generate_stars($score)
{
    $html = '';
    $full_stars = floor($score);
    $half_star = ($score - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    for ($i = 0; $i < $full_stars; $i++) $html .= '<i class="fas fa-star"></i>';
    if ($half_star) $html .= '<i class="fas fa-star-half-alt"></i>';
    for ($i = 0; $i < $empty_stars; $i++) $html .= '<i class="far fa-star"></i>';

    return $html;
}

// === 2. AMBIL DATA UNTUK TABEL DETAIL DENGAN PAGINASI ===
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$count_query = $koneksi->query("SELECT COUNT(*) as total FROM tblevaluasikinerja");
$total_data = $count_query->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

$sql_table = "SELECT
                    e.IdEvKinerja,
                    k.txtNamaKegiatanKS,
                    e.txtSesuaiRencana,
                    e.txtKualitasPelaks,
                    e.txtKeterlibatanMtra
                FROM tblevaluasikinerja e
                JOIN tblnamakegiatanks k ON e.IdKKS = k.IdKKS
                ORDER BY k.dtMOU DESC
                LIMIT ? OFFSET ?";
$stmt = $koneksi->prepare($sql_table);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result_table = $stmt->get_result();
$evaluasi_data = $result_table->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<main>
    <div class="space-y-8">
        <div class="flex flex-wrap justify-between items-center gap-4 bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div>
                <h2 class="text-xl font-bold text-gray-800 tracking-tight">Evaluasi Kinerja</h2>
                <p class="text-sm text-gray-500 mt-1">Analisis kinerja dan skor setiap program kerjasama.</p>
            </div>
            <button onclick="exportData()" class="bg-gradient-to-r from-emerald-600 to-green-500 text-white px-5 py-2.5 rounded-lg hover:opacity-90"><i class="fas fa-file-excel fa-sm mr-2"></i><span class="text-sm font-medium">Export Excel</span></button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5">
            <?php
            $indicators = [
                'rencana' => 'Kesesuaian Rencana',
                'kualitas' => 'Kualitas Pelaksanaan',
                'mitra' => 'Keterlibatan Mitra',
                'efisiensi' => 'Efisiensi',
                'kepuasan' => 'Kepuasan'
            ];
            foreach ($indicators as $key => $label):
                $score = $average_scores['avg_' . $key] ?? 0;
            ?>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center transition-all hover:shadow-md hover:border-gray-200">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= $label ?></h4>
                    <p class="text-3xl font-bold text-blue-600 my-2"><?= number_format($score, 1) ?></p>
                    <div class="flex justify-center space-x-0.5 text-yellow-400 text-base"><?= generate_stars($score) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- PERUBAHAN DI SINI: dari lg:grid-cols-3 menjadi lg:grid-cols-2 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- PERUBAHAN DI SINI: dari lg:col-span-2 menjadi lg:col-span-1 -->
            <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800 tracking-tight">Daftar Evaluasi Terperinci</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kegiatan</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kesesuaian</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kualitas</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Keterlibatan</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($evaluasi_data)): foreach ($evaluasi_data as $row): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?= $row['txtSesuaiRencana'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?= $row['txtKualitasPelaks'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?= $row['txtKeterlibatanMtra'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <button onclick="showDetailModal(this, '<?= $row['IdEvKinerja'] ?>')" class="text-indigo-600 hover:text-indigo-900" title="Detail / Edit"><i class="fas fa-edit fa-lg"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-6 text-gray-500">Belum ada data evaluasi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PERUBAHAN DI SINI: lg:col-span-1 sudah benar, tidak perlu diubah -->
            <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800 tracking-tight">Perbandingan Kinerja Program</h3>
                </div>
                <div class="p-4 h-96">
                    <canvas id="performanceRadarChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-lg shadow-xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col" id="detailModalContent">
        <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50 rounded-t-lg">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Evaluasi Kinerja: [Nama Kegiatan]</h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
        </div>
        <form id="evaluasiForm" class="flex-grow overflow-y-auto p-6 space-y-6">
            <input type="hidden" id="IdEvKinerja" name="IdEvKinerja">

            <div id="rating-container" class="space-y-4">
                <!-- Rating controls will be generated by JS here -->
            </div>

            <div>
                <label for="txtRekomUtkPerbaikan" class="block text-sm font-medium text-gray-700 mb-1">Rekomendasi Untuk Perbaikan</label>
                <textarea id="txtRekomUtkPerbaikan" name="txtRekomUtkPerbaikan" rows="4" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>
        </form>
         <div class="flex justify-end space-x-3 p-4 bg-gray-50 border-t rounded-b-lg">
            <button type="button" onclick="closeDetailModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Batal</button>
            <button type="submit" form="evaluasiForm" class="px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">Simpan Evaluasi</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // === Konfigurasi dan Elemen Global ===
        let activeElementBeforeModal, performanceRadarChart;
        const detailModal = document.getElementById('detailModal');
        const detailModalContent = document.getElementById('detailModalContent');
        const modalTitle = document.getElementById('modalTitle');
        const ratingContainer = document.getElementById('rating-container');
        const evaluasiForm = document.getElementById('evaluasiForm');
        const swalWithTailwind = {
            customClass: {
                popup: 'p-4 sm:p-6 w-full max-w-sm rounded-lg shadow-lg bg-white',
                title: 'text-xl font-semibold text-gray-800 mb-2',
                htmlContainer: 'text-gray-600',
                confirmButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
                cancelButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
            },
            buttonsStyling: false
        };
        const ratingLabels = {
            txtSesuaiRencana: 'Kesesuaian Rencana',
            txtKualitasPelaks: 'Kualitas Pelaksanaan',
            txtKeterlibatanMtra: 'Keterlibatan Mitra',
            txtEfisiensiPenggSbDya: 'Efisiensi Sumber Daya',
            txtKepuasanPhkTerkait: 'Kepuasan Pihak Terkait'
        };

        // === Fungsi Backend Communication ===
        const apiCall = async (action, options = {}) => {
            const url = `pimpinan/evaluasi_action.php?action=${action}`; // Sesuaikan path jika perlu
            try {
                const response = await fetch(url, options);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error("API Call Error:", error);
                Swal.fire({
                    ...swalWithTailwind,
                    icon: 'error',
                    title: 'Koneksi Gagal',
                    text: 'Tidak dapat terhubung ke server. Periksa konsol untuk detail.'
                });
                return null;
            }
        };

        // === Fungsi Interaktif & Modal ===
        const createRatingControl = (key, label, value) => {
            let starsHTML = '';
            for (let i = 1; i <= 5; i++) {
                starsHTML += `<i class="star-icon cursor-pointer text-2xl ${i <= value ? 'fas fa-star text-yellow-400' : 'far fa-star text-gray-300'}" data-value="${i}"></i>`;
            }
            return `
            <div class="rating-group" data-key="${key}">
                <label class="block text-sm font-medium text-gray-700">${label}</label>
                <div class="flex items-center justify-between gap-4 mt-1">
                    <div class="star-display flex space-x-1">${starsHTML}</div>
                    <input type="hidden" name="${key}" value="${value}">
                    <span class="font-bold text-lg text-blue-600 w-8 text-center">${value}</span>
                </div>
            </div>`;
        };

        window.showDetailModal = async (triggerElement, id) => {
            activeElementBeforeModal = triggerElement || document.activeElement;
            const result = await apiCall(`get_single_evaluation&id=${id}`);
            if (!result || result.status !== 'success') {
                 Swal.fire({...swalWithTailwind, icon: 'error', title: 'Gagal Memuat', text: result.message || 'Data tidak ditemukan.'});
                 return;
            }

            const { evaluasi, rekomendasi, nama_kegiatan } = result.data;
            modalTitle.textContent = `Evaluasi: ${nama_kegiatan}`;
            document.getElementById('IdEvKinerja').value = evaluasi.IdEvKinerja;

            ratingContainer.innerHTML = '';
            for (const key in ratingLabels) {
                if (evaluasi.hasOwnProperty(key)) {
                    ratingContainer.innerHTML += createRatingControl(key, ratingLabels[key], evaluasi[key]);
                }
            }
            document.getElementById('txtRekomUtkPerbaikan').value = rekomendasi;

            detailModal.classList.remove('hidden');
            setTimeout(() => detailModalContent.classList.remove('opacity-0', 'scale-95'), 10);
        };

        window.closeDetailModal = () => {
            detailModalContent.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                detailModal.classList.add('hidden');
                if(activeElementBeforeModal) activeElementBeforeModal.focus();
            }, 300);
        };

        // === Fungsi Chart & Notifikasi ===
        const loadRadarChart = async () => {
            const result = await apiCall('get_radar_chart_data');
            if (!result || result.status !== 'success') {
                console.error('Failed to load radar chart data:', result ? result.message : 'No response');
                const canvas = document.getElementById('performanceRadarChart');
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.textAlign = 'center';
                ctx.fillStyle = '#9ca3af';
                ctx.fillText('Data perbandingan tidak tersedia.', canvas.width / 2, canvas.height / 2);
                return;
            }

            const ctx = document.getElementById('performanceRadarChart').getContext('2d');
            if (window.performanceRadarChart instanceof Chart) {
                window.performanceRadarChart.destroy();
            }

            window.performanceRadarChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: result.data.labels || Object.values(ratingLabels),
                    datasets: result.data.programs.map((prog, index) => ({
                        label: prog.name,
                        data: prog.scores,
                        fill: true,
                        backgroundColor: `rgba(${result.data.colors[index]}, 0.2)`,
                        borderColor: `rgb(${result.data.colors[index]})`,
                        pointBackgroundColor: `rgb(${result.data.colors[index]})`,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: `rgb(${result.data.colors[index]})`,
                        borderWidth: 2
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            angleLines: { display: true },
                            suggestedMin: 0,
                            suggestedMax: 5,
                            ticks: { stepSize: 1, backdropColor: 'transparent' },
                            pointLabels: { font: { size: 10 } }
                        }
                    },
                    plugins: {
                        legend: { position: 'top', labels: { font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.dataset.label}: ${context.raw.toFixed(1)}`
                            }
                        }
                    }
                }
            });
        };

        window.exportData = () => {
            // Pastikan path ini benar sesuai struktur folder Anda
            window.open('pimpinan/export_excel.php?action=export_evaluasi_kinerja', '_blank');
        };

        // === Event Listeners ===
        loadRadarChart();

        ratingContainer.addEventListener('click', e => {
            if (e.target.classList.contains('star-icon')) {
                const group = e.target.closest('.rating-group');
                const key = group.dataset.key;
                const value = e.target.dataset.value;
                
                group.querySelector('input[name="' + key + '"]').value = value;
                group.querySelector('span').textContent = value;
                
                const stars = group.querySelectorAll('.star-icon');
                stars.forEach(star => {
                    if(star.dataset.value <= value) {
                        star.classList.remove('far', 'fa-star', 'text-gray-300');
                        star.classList.add('fas', 'fa-star', 'text-yellow-400');
                    } else {
                        star.classList.remove('fas', 'fa-star', 'text-yellow-400');
                        star.classList.add('far', 'fa-star', 'text-gray-300');
                    }
                });
            }
        });

        evaluasiForm.addEventListener('submit', async e => {
            e.preventDefault();
            const formData = new FormData(evaluasiForm);
            const result = await apiCall('update_evaluation', {
                method: 'POST',
                body: formData
            });

            if (result && result.status === 'success') {
                closeDetailModal();
                await Swal.fire({
                    ...swalWithTailwind,
                    icon: 'success',
                    title: 'Evaluasi Disimpan!',
                    timer: 1800,
                    showConfirmButton: false,
                    timerProgressBar: true
                });
                window.location.reload();
            } else {
                 Swal.fire({...swalWithTailwind, icon: 'error', title: 'Gagal Menyimpan', text: result.message || 'Terjadi kesalahan.'});
            }
        });

        detailModal.addEventListener('click', e => {
            if (e.target === detailModal) closeDetailModal();
        });
        window.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !detailModal.classList.contains('hidden')) closeDetailModal();
        });
    });
</script>

<?php
$koneksi->close();
?>