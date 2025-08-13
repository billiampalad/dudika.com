<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$query = "SELECT
    nk.IdKKS,
    nk.txtNamaKegiatanKS,
    nk.dtMulaiPelaksanaan,
    nk.dtSelesaiPelaksanaan,
    SUM(p.intJumlahPeserta) as jumlah_peserta,
    d.pathFoto as bukti_pelaksanaan,
    jk.txtNamaJenisKS
FROM tblnamakegiatanks nk
LEFT JOIN tblpelaksanaankeg p ON nk.IdKKS = p.IdKKS
LEFT JOIN tbldokumentasi d ON nk.idDokumentasi = d.idDokumentasi
LEFT JOIN tbljenisks jk ON nk.IdJenisKS = jk.IdJenisKS
GROUP BY nk.IdKKS
ORDER BY nk.dtMulaiPelaksanaan DESC;";

$result = mysqli_query($koneksi, $query);
$programs = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $programs[] = $row;
    }
}

// Function to format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Function to check if file exists
function hasBuktiPelaksanaan($blobData) {
    return !empty($blobData);
}
?>

<div>
    <!-- Search and Export Row -->
    <div class="flex flex-row justify-between items-center mb-3 sm:mb-4 gap-3 sm:gap-4">
        <!-- Search input -->
        <div class="w-[60%] sm:min-w-[200px] md:min-w-[200px]">
            <input type="text" id="searchInput" placeholder="Cari nama kegiatan..."
                class="form-modern w-full text-xs sm:text-sm px-3 sm:px-4 py-2">
        </div>

        <!-- Export button -->
        <button onclick="exportData()"
            class="btn-secondary-modern w-[40%] sm:w-auto text-xs px-3 sm:px-4 py-2">
            <i class="fas fa-file-excel text-xs sm:text-sm mr-1 sm:mr-2"></i>
            <span>Export Excel</span>
        </button>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto rounded-lg border border-[var(--border)] shadow-sm">
        <table class="w-full min-w-max">
            <thead class="bg-[var(--background)]">
                <tr class="text-left text-[10px] sm:text-xs font-semibold text-[var(--text-light)] uppercase tracking-wider">
                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3">Nama Kegiatan</th>
                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3">Periode Pelaksanaan</th>
                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-center">Jumlah Peserta</th>
                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3">Bukti Pelaksanaan</th>
                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
                <?php foreach ($programs as $program): ?>
                <tr class="hover:bg-[var(--background)] transition-colors">
                    <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-xs sm:text-sm font-medium text-[var(--text-dark)] whitespace-nowrap">
                        <?= htmlspecialchars($program['txtNamaKegiatanKS']) ?>
                    </td>
                    <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-xs sm:text-sm text-[var(--text-light)] whitespace-nowrap">
                        <?= formatDate($program['dtMulaiPelaksanaan']) ?> - <?= formatDate($program['dtSelesaiPelaksanaan']) ?>
                    </td>
                    <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-xs sm:text-sm text-[var(--text-light)] text-center">
                        <?= $program['jumlah_peserta'] ?? 0 ?>
                    </td>
                    <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-xs sm:text-sm whitespace-nowrap">
                        <?php if (hasBuktiPelaksanaan($program['bukti_pelaksanaan'])): ?>
                        <span class="flex items-center text-[var(--secondary)] font-medium">
                            <i class="fas fa-check-circle text-xs sm:text-sm mr-1 sm:mr-2"></i>
                            Dokumentasi_<?= htmlspecialchars($program['IdKKS']) ?>.pdf
                        </span>
                        <?php else: ?>
                        <span class="flex items-center text-[var(--primary)] font-semibold">
                            <i class="fas fa-exclamation-triangle text-xs sm:text-sm mr-1 sm:mr-2"></i>
                            Belum diunggah
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-center">
                        <button onclick="showLaporanModal('<?= $program['IdKKS'] ?>')" title="Detail & Lapor"
                            class="p-1 sm:p-2 rounded-md bg-[var(--accent)] text-[var(--primary)] hover:bg-[var(--primary)]/20 transition shadow-sm">
                            <i class="fas fa-file-alt text-xs sm:text-sm"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex flex-col sm:flex-row justify-between items-center mt-3 sm:mt-4 gap-2 sm:gap-0">
        <div class="text-[10px] sm:text-xs text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>Menampilkan <?= count($programs) ?> dari <?= count($programs) ?> program
        </div>
        <div class="flex space-x-1 sm:space-x-2">
            <button class="px-2 sm:px-3 py-1 border border-[var(--border)] rounded hover:bg-[var(--border)] text-[var(--text-light)] text-xs sm:text-sm">
                Previous
            </button>
            <button class="px-2 sm:px-3 py-1 bg-[var(--primary)] text-white rounded font-semibold text-xs sm:text-sm">
                1
            </button>
            <button class="px-2 sm:px-3 py-1 border border-[var(--border)] rounded hover:bg-[var(--border)] text-[var(--text-light)] text-xs sm:text-sm">
                Next
            </button>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="laporanModal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div id="modalContent" class="modal-modern w-full max-w-2xl transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] flex flex-col text-sm sm:text-base">
        <div class="flex justify-between items-center p-4 sm:p-5 border-b border-[var(--border)]">
            <h3 id="modalTitle" class="text-base sm:text-lg font-semibold text-[var(--text-dark)]">Detail & Laporan Pelaksanaan</h3>
            <button onclick="closeLaporanModal()" class="text-[var(--text-light)] hover:text-[var(--primary)] text-lg sm:text-xl transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="laporanForm" class="p-4 sm:p-6 space-y-4 sm:space-y-5 flex-grow overflow-y-auto text-sm">
            <input type="hidden" id="pelaksanaanId" name="pelaksanaanId">

            <div class="bg-[var(--background)] p-3 sm:p-4 rounded-lg border border-[var(--border)]">
                <h4 id="modalKegiatanTitle" class="font-bold text-[var(--text-dark)] text-sm sm:text-base"></h4>
                <p id="modalKegiatanPeriode" class="text-xs sm:text-sm text-[var(--text-light)]"></p>
            </div>

            <div>
                <label for="txtDeskripsiKeg" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Deskripsi Kegiatan</label>
                <textarea id="txtDeskripsiKeg" name="txtDeskripsiKeg" rows="3" placeholder="Jelaskan secara singkat kegiatan yang telah dilaksanakan..." class="form-modern w-full text-sm"></textarea>
            </div>

            <div>
                <label for="txtCakupanDanSkalaKeg" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Cakupan & Skala Kegiatan</label>
                <textarea id="txtCakupanDanSkalaKeg" name="txtCakupanDanSkalaKeg" rows="2" placeholder="Contoh: Skala lokal, melibatkan 15 mahasiswa..." class="form-modern w-full text-sm"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                <div>
                    <label for="intJumlahPeserta" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Jumlah Peserta</label>
                    <input type="number" id="intJumlahPeserta" name="intJumlahPeserta" placeholder="0" class="form-modern w-full text-sm">
                </div>
                <div>
                    <label for="txtSumberDaya" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Sumber Daya Digunakan</label>
                    <input type="text" id="txtSumberDaya" name="txtSumberDaya" placeholder="Contoh: Ruang meeting, komputer" class="form-modern w-full text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Upload Bukti Pelaksanaan</label>
                <div class="mt-1 flex justify-center px-4 sm:px-6 pt-4 sm:pt-5 pb-5 sm:pb-6 border-2 border-[var(--border)] border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <i class="fas fa-cloud-upload-alt text-3xl sm:text-4xl text-[var(--text-light)]"></i>
                        <div class="flex flex-wrap justify-center text-xs sm:text-sm text-[var(--text-light)]">
                            <label for="fileUpload" class="relative cursor-pointer bg-transparent rounded-md font-medium text-[var(--primary)] hover:text-[var(--primary-dark)]">
                                <span>Pilih file untuk diunggah</span>
                                <input id="fileUpload" name="fileUpload" type="file" class="sr-only">
                            </label>
                            <p class="pl-1">atau seret dan lepas di sini</p>
                        </div>
                        <p class="text-[10px] sm:text-xs text-[var(--text-light)]">PDF, DOCX, PNG, JPG (maks. 5MB)</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-4 border-t border-[var(--border)]">
                <button type="button" onclick="closeLaporanModal()" class="px-4 py-2 bg-[var(--border)] rounded-lg hover:opacity-80 text-[var(--text-dark)] font-semibold transition text-sm">Batal</button>
                <button type="submit" class="btn-primary-modern text-sm">
                    <i class="fas fa-save mr-2"></i>
                    <span>Simpan Laporan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Function to show modal with dynamic data
    async function showLaporanModal(programId) {
        try {
            // Fetch program details from server
            const response = await fetch(`user/get_kegiatan_detail.php?id=${programId}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const program = await response.json();
            
            // Set modal title and info
            document.getElementById('pelaksanaanId').value = programId;
            document.getElementById('modalKegiatanTitle').textContent = program.txtNamaKegiatanKS;
            document.getElementById('modalKegiatanPeriode').textContent = `Periode: ${formatJsDate(program.dtMulaiPelaksanaan)} - ${formatJsDate(program.dtSelesaiPelaksanaan)}`;
            
            // Show modal with animation
            document.getElementById('laporanModal').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('modalContent').classList.remove('opacity-0', 'scale-95');
                document.getElementById('modalContent').classList.add('opacity-100', 'scale-100');
            }, 10);
            
        } catch (error) {
            console.error('Error fetching program details:', error);
            alert('Gagal memuat detail program. Silakan coba lagi.');
        }
    }

    // Function to close modal
    function closeLaporanModal() {
        document.getElementById('modalContent').classList.remove('opacity-100', 'scale-100');
        document.getElementById('modalContent').classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            document.getElementById('laporanModal').classList.add('hidden');
        }, 300);
    }

    // Function to export data
    function exportData() {
        // This would typically make an AJAX call to generate an Excel file
        window.location.href = 'export_excel.php';
    }

    // Helper function to format date
    function formatJsDate(dateString) {
        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
    }

    // Form submission handler
    document.getElementById('laporanForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('save_laporan.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const result = await response.json();
            if (result.success) {
                alert('Laporan berhasil disimpan!');
                closeLaporanModal();
                // Optionally refresh the page or table
                window.location.reload();
            } else {
                alert(result.message || 'Gagal menyimpan laporan');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menyimpan laporan');
        }
    });
</script>