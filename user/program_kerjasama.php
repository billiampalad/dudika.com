<div class="pb-5">
    <div class="flex flex-col sm:flex-row gap-4 mb-8">
        <!-- Search Input - Takes full width on mobile, adjusts on larger screens -->
        <div class="w-full sm:flex-1 min-w-[250px]">
            <input type="text" id="searchInput" placeholder="Cari program kerjasama..." class="form-modern w-full text-sm">
        </div>

        <!-- Filter Dropdowns - Stack vertically on mobile, horizontal on tablet/desktop -->
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <select id="filterJenis" class="form-modern w-full sm:w-auto sm:min-w-[140px] text-sm">
                <option value="">Semua Program</option>
                <option value="magang">Magang</option>
                <option value="penelitian">Penelitian</option>
                <option value="workshop">Workshop</option>
            </select>
            <select id="filterStatus" class="form-modern w-full sm:w-auto sm:min-w-[120px] text-sm">
                <option value="">Semua</option>
                <option value="aktif" selected>Aktif</option>
                <option value="selesai">Selesai</option>
                <option value="segera berakhir">Segera Berakhir</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 md:hidden">
        <div class="bg-white rounded-lg border border-[var(--border)] p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 space-y-4">
            <div class="flex flex-col gap-2">
                <div class="flex-1">
                    <h3 class="font-semibold text-[var(--text-dark)] text-sm mb-1">Program Pengembangan Skill Digital</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="fas fa-user-graduate mr-1.5"></i>Magang
                    </span>
                </div>
                <span class="px-3 py-1 text-xs font-semibold rounded-full text-white bg-orange-500 w-fit badge-success">
                    Aktif
                </span>
            </div>
            <div class="flex items-center text-xs text-[var(--text-light)]">
                <i class="fas fa-calendar-alt mr-2"></i>
                01 Feb 2025 - 31 Jul 2025
            </div>
            <button onclick="showDetailModal(true)" class="w-full py-2 px-4 bg-[var(--accent)] text-[var(--primary)] rounded-lg hover:bg-[var(--primary)]/10 transition-colors font-medium text-sm">
                <i class="fas fa-eye mr-2"></i>Lihat Detail
            </button>
        </div>

        <div class="bg-white rounded-lg border border-[var(--border)] p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 space-y-4">
            <div class="flex flex-col gap-2">
                <div class="flex-1">
                    <h3 class="font-semibold text-[var(--text-dark)] text-sm mb-1">Program Pengembangan Skill Digital</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-chalkboard-teacher mr-1.5"></i>Workshop
                    </span>
                </div>
                <span class="px-3 py-1 text-xs font-semibold rounded-full text-white bg-orange-500 w-fit">
                    Segera Berakhir
                </span>
            </div>
            <div class="flex items-center text-xs text-[var(--text-light)]">
                <i class="fas fa-calendar-alt mr-2"></i>
                15 Sep 2024 - 15 Des 2025
            </div>
            <button onclick="showDetailModal(true)" class="w-full py-2 px-4 bg-[var(--accent)] text-[var(--primary)] rounded-lg hover:bg-[var(--primary)]/10 transition-colors font-medium text-sm">
                <i class="fas fa-eye mr-2"></i>Lihat Detail
            </button>
        </div>
    </div>

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
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-sm text-[var(--text-dark)]">Magang Mahasiswa Batch 5</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-user-graduate mr-1.5"></i>Magang
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600">01 Feb 2025 - 31 Jul 2025</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><span class="badge-success">Aktif</span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <button onclick="showDetailModal(false)" class="inline-flex items-center px-3 py-2 text-xs font-medium text-[var(--primary)] bg-[var(--accent)] rounded-lg hover:bg-[var(--primary)]/10 transition-colors">
                                <i class="fas fa-eye mr-2"></i>Lihat Detail
                            </button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-sm text-[var(--text-dark)]">Program Pengembangan Skill Digital</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-chalkboard-teacher mr-1.5"></i>Workshop
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600">15 Sep 2024 - 15 Des 2025</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full text-white bg-orange-600">Segera Berakhir</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <button onclick="showDetailModal(true)" class="inline-flex items-center px-3 py-2 text-xs font-medium text-[var(--primary)] bg-[var(--accent)] rounded-lg hover:bg-[var(--primary)]/10 transition-colors">
                                <i class="fas fa-eye mr-2"></i>Lihat Detail
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
        <div class="text-xs text-gray-500">
            <i class="fas fa-info-circle mr-1"></i>Menampilkan 2 dari 10 program
        </div>
        <div class="flex space-x-2 text-sm">
            <button class="px-3 py-1 border border-[var(--border)] rounded hover:bg-[var(--border)] text-[var(--text-light)]">Previous</button>
            <button class="px-3 py-1 bg-[var(--primary)] text-white rounded font-semibold">1</button>
            <button class="px-3 py-1 border border-[var(--border)] rounded hover:bg-[var(--border)] text-[var(--text-light)]">Next</button>
        </div>
    </div>

    <div id="detailModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 transition-opacity duration-300">
        <div id="modalContent" class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col transform transition-all duration-300 scale-95 opacity-0">
            <div class="flex justify-between items-center p-3 sm:p-5 border-b border-gray-200">
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <div class="w-8 h-8 sm:w-12 sm:h-12 bg-gradient-to-br from-[var(--primary)] to-blue-600 text-white rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-handshake text-sm sm:text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-lg font-bold text-gray-800">Detail Program Kerjasama</h3>
                        <p class="text-[10px] sm:text-xs text-gray-500">Informasi lengkap mengenai program kerjasama.</p>
                    </div>
                </div>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-700 transition-colors rounded-full w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center hover:bg-gray-100">
                    <i class="fas fa-times text-sm sm:text-xl"></i>
                </button>
            </div>

            <div class="border-b border-gray-200 px-2 sm:px-6">
                <div class="overflow-x-auto">
                    <nav class="flex -mb-px space-x-2 sm:space-x-6 w-max min-w-full">
                        <button data-tab="info" class="tab-btn text-xs sm:text-sm font-medium flex items-center py-3 sm:py-4 px-1 border-b-2 whitespace-nowrap">
                            <i class="fas fa-file-contract text-xs sm:text-sm mr-1 sm:mr-2"></i>Informasi MOU
                        </button>
                        <button data-tab="tujuan" class="tab-btn text-xs sm:text-sm font-medium flex items-center py-3 sm:py-4 px-1 border-b-2 whitespace-nowrap">
                            <i class="fas fa-bullseye text-xs sm:text-sm mr-1 sm:mr-2"></i>Tujuan Program
                        </button>
                        <button data-tab="dokumentasi" class="tab-btn text-xs sm:text-sm font-medium flex items-center py-3 sm:py-4 px-1 border-b-2 whitespace-nowrap">
                            <i class="fas fa-images text-xs sm:text-sm mr-1 sm:mr-2"></i>Galeri
                        </button>
                    </nav>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto p-3 sm:p-6 space-y-4 sm:space-y-6">
                <!-- Info Tab -->
                <div id="info" class="tab-pane space-y-4 sm:space-y-6">
                    <div class="bg-blue-50/50 rounded-lg p-4 sm:p-6 border border-blue-200">
                        <h4 class="text-sm sm:text-base font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                            <i class="fas fa-info-circle text-xs sm:text-sm mr-1 sm:mr-2 text-blue-600"></i>Informasi Dokumen MOU
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 sm:gap-x-6 gap-y-3 sm:gap-y-4">
                            <div>
                                <dt class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Nomor MOU</dt>
                                <dd class="mt-1 text-xs sm:text-sm text-gray-700 font-mono bg-white px-2 sm:px-3 py-1 sm:py-2 rounded-md border">123/MOU/POLIMDO-MB/I/2025</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Tanggal MOU</dt>
                                <dd class="mt-1 text-xs sm:text-sm text-gray-700 bg-white px-2 sm:px-3 py-1 sm:py-2 rounded-md border">20 Januari 2025</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Periode Kerjasama</dt>
                                <dd class="mt-1 text-xs sm:text-sm text-gray-700 bg-white px-2 sm:px-3 py-1 sm:py-2 rounded-md border">01 Feb 2025 - 31 Jul 2025</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Status</dt>
                                <dd class="mt-1 sm:mt-2"><span class="badge-success text-xs sm:text-sm">Aktif</span></dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tujuan Tab -->
                <div id="tujuan" class="tab-pane hidden space-y-4 sm:space-y-6">
                    <div class="bg-green-50/50 rounded-lg p-4 sm:p-6 border border-green-200">
                        <h4 class="text-sm sm:text-base font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                            <i class="fas fa-bullseye text-xs sm:text-sm mr-1 sm:mr-2 text-green-600"></i>Tujuan Program
                        </h4>
                        <ul class="space-y-3 sm:space-y-4">
                            <li class="flex items-start space-x-2 sm:space-x-3">
                                <i class="fas fa-check-circle text-green-500 mt-0.5 sm:mt-1 text-xs sm:text-sm"></i>
                                <div>
                                    <h5 class="font-medium text-xs sm:text-sm text-gray-800">Pengalaman Kerja Nyata</h5>
                                    <p class="text-[10px] sm:text-xs text-gray-600 mt-0.5 sm:mt-1">Memberikan pengalaman kerja nyata kepada mahasiswa di lingkungan industri sesungguhnya.</p>
                                </div>
                            </li>
                            <li class="flex items-start space-x-2 sm:space-x-3">
                                <i class="fas fa-check-circle text-green-500 mt-0.5 sm:mt-1 text-xs sm:text-sm"></i>
                                <div>
                                    <h5 class="font-medium text-xs sm:text-sm text-gray-800">Peningkatan Kompetensi</h5>
                                    <p class="text-[10px] sm:text-xs text-gray-600 mt-0.5 sm:mt-1">Meningkatkan kompetensi mahasiswa sesuai dengan kebutuhan dan standar industri terkini.</p>
                                </div>
                            </li>
                            <li class="flex items-start space-x-2 sm:space-x-3">
                                <i class="fas fa-check-circle text-green-500 mt-0.5 sm:mt-1 text-xs sm:text-sm"></i>
                                <div>
                                    <h5 class="font-medium text-xs sm:text-sm text-gray-800">Kemitraan Strategis</h5>
                                    <p class="text-[10px] sm:text-xs text-gray-600 mt-0.5 sm:mt-1">Menjalin hubungan baik dan berkelanjutan antara POLIMDO dengan DUDIKA.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Dokumentasi Tab -->
                <div id="dokumentasi" class="tab-pane hidden space-y-4 sm:space-y-6">
                    <div>
                        <h4 class="text-sm sm:text-base font-semibold text-gray-800 mb-1 sm:mb-2 flex items-center">
                            <i class="fas fa-images text-xs sm:text-sm mr-1 sm:mr-2 text-indigo-600"></i>Galeri Dokumentasi
                        </h4>
                        <p class="text-[10px] sm:text-xs text-gray-500 mb-3 sm:mb-4">Kumpulan foto selama program kerjasama berlangsung.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-4">
                            <div class="group relative">
                                <img src="https://via.placeholder.com/300x200/818cf8/ffffff?text=MOU" alt="Dokumentasi 1" class="w-full h-24 sm:h-32 object-cover rounded-lg shadow-sm border border-gray-200 group-hover:shadow-xl transition-all duration-300">
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg">
                                    <i class="fas fa-search-plus text-white text-lg sm:text-2xl"></i>
                                </div>
                            </div>
                            <div class="group relative">
                                <img src="https://via.placeholder.com/300x200/a5b4fc/ffffff?text=Kegiatan" alt="Dokumentasi 2" class="w-full h-24 sm:h-32 object-cover rounded-lg shadow-sm border border-gray-200 group-hover:shadow-xl transition-all duration-300">
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg">
                                    <i class="fas fa-search-plus text-white text-lg sm:text-2xl"></i>
                                </div>
                            </div>
                            <div class="group relative">
                                <img src="https://via.placeholder.com/300x200/f87171/ffffff?text=Penutupan" alt="Dokumentasi 3" class="w-full h-24 sm:h-32 object-cover rounded-lg shadow-sm border border-gray-200 group-hover:shadow-xl transition-all duration-300">
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg">
                                    <i class="fas fa-search-plus text-white text-lg sm:text-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="modalFooter" class="p-3 sm:p-5 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-2 sm:gap-4 rounded-b-xl">
                <div class="text-[10px] sm:text-xs text-gray-500 flex items-center text-center sm:text-left">
                    <i class="fas fa-info-circle text-xs sm:text-sm mr-1 sm:mr-2"></i>
                    <span>Butuh bantuan? Hubungi admin untuk informasi lebih lanjut.</span>
                </div>
                <div class="flex gap-2 sm:gap-3">
                    <button onclick="closeDetailModal()" class="px-3 py-1 sm:px-4 sm:py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors text-xs sm:text-sm font-medium">
                        Tutup
                    </button>
                    <button id="btnPerpanjangan" class="hidden items-center px-3 py-1 sm:px-4 sm:py-2 text-white font-medium rounded-lg hover:opacity-90 transition-opacity text-xs sm:text-sm" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                        <i class="fas fa-sync-alt text-xs sm:text-sm mr-1 sm:mr-2"></i>
                        Ajukan Perpanjangan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const detailModal = document.getElementById('detailModal');
    const modalContent = document.getElementById('modalContent');
    const btnPerpanjangan = document.getElementById('btnPerpanjangan');

    // Fungsi untuk membuka modal dengan animasi fade-in & scale-up
    function showDetailModal(isExpiring = false) {
        if (isExpiring) {
            btnPerpanjangan.classList.remove('hidden');
            btnPerpanjangan.classList.add('inline-flex'); // 'flex' jadi 'inline-flex' agar rapi
        } else {
            btnPerpanjangan.classList.add('hidden');
            btnPerpanjangan.classList.remove('inline-flex');
        }

        detailModal.classList.remove('hidden');
        // Memicu reflow browser agar transisi berjalan
        setTimeout(() => {
            detailModal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95', 'opacity-0');
        }, 10);
    }

    // Fungsi untuk menutup modal dengan animasi fade-out & scale-down
    function closeDetailModal() {
        detailModal.classList.add('opacity-0');
        modalContent.classList.add('scale-95', 'opacity-0');

        // Tunggu animasi selesai sebelum menyembunyikan modal
        setTimeout(() => {
            detailModal.classList.add('hidden');
        }, 300); // Durasi harus sama dengan di kelas transition
    }

    // Event listener untuk tombol tab di dalam modal
    document.addEventListener('DOMContentLoaded', function() {
        const tabContainer = document.getElementById('detailModal');
        if (!tabContainer) return;

        const tabButtons = tabContainer.querySelectorAll('.tab-btn');
        const tabPanes = tabContainer.querySelectorAll('.tab-pane');

        // Atur tab pertama sebagai aktif secara default
        function initializeTabs() {
            const firstButton = tabButtons[0];
            const firstPane = document.getElementById(firstButton.dataset.tab);

            // Reset semua tombol
            tabButtons.forEach(btn => {
                btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                btn.classList.remove('text-[var(--primary)]', 'border-[var(--primary)]');
            });

            // Sembunyikan semua panel
            tabPanes.forEach(pane => {
                pane.classList.add('hidden');
                pane.classList.remove('active');
            });

            // Aktifkan yang pertama
            firstButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            firstButton.classList.add('text-[var(--primary)]', 'border-[var(--primary)]');

            if (firstPane) {
                firstPane.classList.remove('hidden');
                firstPane.classList.add('active');
            }
        }

        initializeTabs(); // Panggil saat DOM loaded

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Reset semua tombol ke state tidak aktif
                tabButtons.forEach(btn => {
                    btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    btn.classList.remove('text-[var(--primary)]', 'border-[var(--primary)]');
                });

                // Sembunyikan semua panel
                tabPanes.forEach(pane => {
                    pane.classList.add('hidden');
                    pane.classList.remove('active');
                });

                // Aktifkan tombol yang diklik
                button.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                button.classList.add('text-[var(--primary)]', 'border-[var(--primary)]');

                // Tampilkan panel yang sesuai
                const targetPaneId = button.dataset.tab;
                const targetPane = document.getElementById(targetPaneId);
                if (targetPane) {
                    targetPane.classList.remove('hidden');
                    targetPane.classList.add('active');
                }
            });
        });

        // Menambahkan fungsionalitas untuk menutup modal jika mengklik area luar (overlay)
        detailModal.addEventListener('click', (event) => {
            if (event.target === detailModal) {
                closeDetailModal();
            }
        });
    });
</script>