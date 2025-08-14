<?php
// Query untuk mendapatkan data program kerjasama
$query_programs = "SELECT 
    nk.IdKKS, 
    nk.txtNamaKegiatanKS, 
    jk.txtNamaJenisKS,
    nk.dtMulaiPelaksanaan,
    nk.dtSelesaiPelaksanaan,
    nk.txtNomorMOU,
    nk.dtMOU,
    tk.txtTujuanKS,
    tk.txtSasaranKS
FROM tblnamakegiatanks nk
JOIN tbljenisks jk ON nk.IdJenisKS = jk.IdJenisKS
JOIN tbltujuanks tk ON nk.IdKKS = tk.IdKKS
ORDER BY nk.dtMulaiPelaksanaan DESC";

$result_programs = mysqli_query($koneksi, $query_programs);
$programs = [];
if ($result_programs) {
    while ($row = mysqli_fetch_assoc($result_programs)) {
        $programs[] = $row;
    }
}

// Fungsi untuk menentukan status program
function getProgramStatus($start_date, $end_date)
{
    $today = new DateTime();
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    if ($today < $start) {
        return ['status' => 'Belum Dimulai', 'color' => 'bg-red-500'];
    } elseif ($today > $end) {
        return ['status' => 'Selesai', 'color' => 'bg-blue-500'];
    } else {
        $interval = $today->diff($end);
        $days_left = $interval->days;

        if ($days_left <= 30) {
            return ['status' => 'Segera Berakhir', 'color' => 'bg-orange-500'];
        } else {
            return ['status' => 'Aktif', 'color' => 'bg-green-500'];
        }
    }
}
?>

<div class="pb-5">
    <div class="flex flex-col sm:flex-row gap-4 mb-8">
        <!-- Search Input -->
        <div class="w-full sm:flex-1 min-w-[250px]">
            <input type="text" id="searchInput" placeholder="Cari program kerjasama..." class="form-modern w-full text-sm">
        </div>

        <!-- Filter Dropdowns -->
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <select id="filterJenis" class="form-modern w-full sm:w-auto sm:min-w-[140px] text-sm">
                <option value="">Semua Program</option>
                <?php
                $jenis_query = "SELECT DISTINCT txtNamaJenisKS FROM tbljenisks";
                $jenis_result = mysqli_query($koneksi, $jenis_query);
                while ($jenis = mysqli_fetch_assoc($jenis_result)) {
                    echo '<option value="' . htmlspecialchars($jenis['txtNamaJenisKS']) . '">' . htmlspecialchars($jenis['txtNamaJenisKS']) . '</option>';
                }
                ?>
            </select>
            <select id="filterStatus" class="form-modern w-full sm:w-auto sm:min-w-[120px] text-sm">
                <option value="">Semua</option>
                <option value="aktif">Aktif</option>
                <option value="selesai">Selesai</option>
                <option value="segera berakhir">Segera Berakhir</option>
                <option value="belum dimulai">Belum Dimulai</option>
            </select>
        </div>
    </div>

    <!-- Mobile View -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 md:hidden">
        <?php foreach ($programs as $program):
            $status = getProgramStatus($program['dtMulaiPelaksanaan'], $program['dtSelesaiPelaksanaan']);
        ?>
            <div class="bg-white rounded-lg border border-[var(--border)] p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 space-y-4">
                <div class="flex flex-col gap-2">
                    <div class="flex-1">
                        <h3 class="font-semibold text-[var(--text-dark)] text-sm mb-1"><?= htmlspecialchars($program['txtNamaKegiatanKS']) ?></h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-user-graduate mr-1.5"></i><?= htmlspecialchars($program['txtNamaJenisKS']) ?>
                        </span>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full text-white <?= $status['color'] ?> w-fit">
                        <?= $status['status'] ?>
                    </span>
                </div>
                <div class="flex items-center text-xs text-[var(--text-light)]">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    <?= date('d M Y', strtotime($program['dtMulaiPelaksanaan'])) ?> - <?= date('d M Y', strtotime($program['dtSelesaiPelaksanaan'])) ?>
                </div>
                <button onclick="showDetailModal('<?= $program['IdKKS'] ?>')" class="w-full py-2 px-4 bg-[var(--accent)] text-[var(--primary)] rounded-lg hover:bg-[var(--primary)]/10 transition-colors font-medium text-sm">
                    <i class="fas fa-eye mr-2"></i>Lihat Detail
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Desktop View -->
    <div class="hidden md:block bg-white rounded-lg border border-[var(--border)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border)]">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th scope="col" class="px-6 py-4">Program Kerjasama</th>
                        <th scope="col" class="px-6 py-4">Jenis</th>
                        <th scope="col" class="px-6 py-4">Periode</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-[var(--border)]">
                    <?php foreach ($programs as $program):
                        $status = getProgramStatus($program['dtMulaiPelaksanaan'], $program['dtSelesaiPelaksanaan']);
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-sm text-[var(--text-dark)]"><?= htmlspecialchars($program['txtNamaKegiatanKS']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-user-graduate mr-1.5"></i><?= htmlspecialchars($program['txtNamaJenisKS']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600"><?= date('d M Y', strtotime($program['dtMulaiPelaksanaan'])) ?> - <?= date('d M Y', strtotime($program['dtSelesaiPelaksanaan'])) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full text-white <?= $status['color'] ?>"><?= $status['status'] ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button onclick="showDetailModal('<?= $program['IdKKS'] ?>')" class="inline-flex items-center px-3 py-2 text-xs font-medium text-[var(--primary)] bg-[var(--accent)] rounded-lg hover:bg-[var(--primary)]/10 transition-colors">
                                    <i class="fas fa-eye mr-2"></i>Lihat Detail
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
        <div class="text-xs text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>Menampilkan <?= count($programs) ?> dari <?= count($programs) ?> program
        </div>
        <div class="flex space-x-2 text-sm">
            <button class="px-3 py-1 border border-[var(--border)] rounded hover:bg-[var(--border)] text-[var(--text-light)]">Previous</button>
            <button class="px-3 py-1 bg-[var(--primary)] text-white rounded font-semibold">1</button>
            <button class="px-3 py-1 border border-[var(--border)] rounded hover:bg-[var(--border)] text-[var(--text-light)]">Next</button>
        </div>
    </div>

    <!-- Modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 transition-opacity duration-300">
        <div id="modalContent" class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col transform transition-all duration-300 scale-95 opacity-0">
            <!-- Konten akan diisi secara dinamis -->
        </div>
    </div>
</div>

<script>
    // Fungsi untuk menampilkan modal detail
    async function showDetailModal(programId) {
        try {
            // Tampilkan loading spinner
            document.getElementById('modalContent').innerHTML = `
            <div class="flex items-center justify-center p-8">
                <i class="fas fa-circle-notch fa-spin text-2xl text-[var(--primary)]"></i>
            </div>
        `;

            // Tampilkan modal
            const modal = document.getElementById('detailModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                document.getElementById('modalContent').classList.remove('scale-95', 'opacity-0');
            }, 10);

            // Fetch data dari server
            const response = await fetch(`user/get_program_detail.php?id=${programId}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data || data.error) {
                throw new Error(data?.error || 'Data tidak valid');
            }

            // Format tanggal
            const formatDate = (dateString) => {
                const options = {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                };
                return new Date(dateString).toLocaleDateString('id-ID', options);
            };

            // Generate konten modal
            const modalContent = `
            <!-- Header Modal -->
            <div class="flex justify-between items-center p-5 border-b border-gray-200">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-[var(--primary)] to-blue-600 text-white rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-handshake text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">${data.txtNamaKegiatanKS}</h3>
                        <p class="text-xs text-gray-500">${data.txtNamaJenisKS}</p>
                    </div>
                </div>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-700 transition-colors rounded-full w-10 h-10 flex items-center justify-center hover:bg-gray-100">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Konten Modal -->
            <div class="flex-grow overflow-y-auto p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-blue-50/50 rounded-lg p-6 border border-blue-200">
                        <h4 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-sm mr-2 text-blue-600"></i>Informasi MOU
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Nomor MOU</p>
                                <p class="mt-1 text-sm text-gray-700 font-mono bg-white px-3 py-2 rounded-md border">${data.txtNomorMOU}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase">Tanggal MOU</p>
                                <p class="mt-1 text-sm text-gray-700 bg-white px-3 py-2 rounded-md border">${formatDate(data.dtMOU)}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50/50 rounded-lg p-6 border border-green-200">
                        <h4 class="text-base font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-bullseye text-sm mr-2 text-green-600"></i>Tujuan Program
                        </h4>
                        <div class="space-y-3">
                            <div class="flex items-start space-x-3">
                                <i class="fas fa-check-circle text-green-500 mt-1 text-sm"></i>
                                <div>
                                    <p class="text-sm text-gray-800">${data.txtTujuanKS}</p>
                                    <p class="text-xs text-gray-600 mt-1">${data.txtSasaranKS}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end rounded-b-xl">
                <button onclick="closeDetailModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors text-sm font-medium">
                    Tutup
                </button>
            </div>
        `;

            // Isi konten modal
            document.getElementById('modalContent').innerHTML = modalContent;

        } catch (error) {
            console.error('Error:', error);
            document.getElementById('modalContent').innerHTML = `
            <div class="p-6 text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-3"></i>
                <h3 class="font-bold text-lg">Gagal Memuat Data</h3>
                <p class="text-sm text-gray-600 mt-2">${error.message}</p>
                <button onclick="closeDetailModal()" class="mt-4 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                    Tutup
                </button>
            </div>
        `;
        }
    }

    // Fungsi untuk menutup modal
    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('opacity-0');
        document.getElementById('modalContent').classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Event listener untuk menutup modal saat klik di luar
    document.getElementById('detailModal').addEventListener('click', (event) => {
        if (event.target === document.getElementById('detailModal')) {
            closeDetailModal();
        }
    });
</script>