<main class="pb-5">
    <div class="card-modern mb-8 overflow-hidden text-sm sm:text-base">
        <div class="border-b border-[var(--border)]">
            <div class="overflow-x-auto whitespace-nowrap px-4 sm:px-6 -mb-px">
                <nav class="inline-flex" aria-label="Tabs">
                    <button data-tab="hasilPanel" class="tab-btn active whitespace-nowrap py-3 sm:py-4 px-2 sm:px-4 border-b-2 font-medium text-xs sm:text-sm">
                        <i class="fas fa-trophy mr-2"></i>
                        Lapor Hasil & Capaian
                    </button>
                    <button data-tab="kendalaPanel" class="tab-btn whitespace-nowrap py-3 sm:py-4 px-2 sm:px-4 border-b-2 font-medium text-xs sm:text-sm">
                        <i class="fas fa-flag mr-2"></i>
                        Lapor Kendala & Solusi
                    </button>
                </nav>
            </div>
        </div>

        <div class="p-4 sm:p-6">
            <div id="hasilPanel" class="tab-pane active">
                <form class="space-y-4 sm:space-y-5">
                    <div>
                        <label for="kegiatanHasil" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Pilih Program Kerjasama</label>
                        <select id="kegiatanHasil" class="form-modern w-full text-xs sm:text-sm">
                            <option>Magang Mahasiswa Batch 4 (Selesai)</option>
                            <option>Penelitian Sistem Cerdas (Selesai)</option>
                        </select>
                    </div>
                    <div>
                        <label for="txtManfaatBgDudika" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Manfaat yang Dirasakan Mitra</label>
                        <textarea id="txtManfaatBgDudika" rows="4" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Mendapatkan talenta baru yang kompeten, inovasi produk..."></textarea>
                    </div>
                    <div>
                        <label for="txtDampakJangkaMenengah" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Potensi Dampak Jangka Menengah</label>
                        <textarea id="txtDampakJangkaMenengah" rows="3" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Peningkatan efisiensi operasional, pengembangan lini produk baru..."></textarea>
                    </div>
                    <div class="flex justify-end pt-3">
                        <button type="submit" class="btn-secondary-modern text-xs sm:text-sm px-3 py-1.5">
                            <i class="fas fa-save mr-1"></i>Simpan Hasil
                        </button>
                    </div>
                </form>
            </div>

            <div id="kendalaPanel" class="tab-pane hidden">
                <form class="space-y-4 sm:space-y-5">
                    <div>
                        <label for="kegiatanKendala" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Pilih Program Kerjasama</label>
                        <select id="kegiatanKendala" class="form-modern w-full text-xs sm:text-sm">
                            <option>Magang Mahasiswa Batch 5 (Berjalan)</option>
                            <option>Penelitian Sistem Informasi Cerdas (Berjalan)</option>
                        </select>
                    </div>
                    <div>
                        <label for="txtKendala" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Deskripsikan Kendala</label>
                        <textarea id="txtKendala" rows="4" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Jadwal pendampingan dari pihak mitra sering tertunda..."></textarea>
                    </div>
                    <div>
                        <label for="txtUpayaUtkAtasiMslh" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Solusi yang Diusulkan atau Telah Dilakukan</label>
                        <textarea id="txtUpayaUtkAtasiMslh" rows="3" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Mengusulkan penjadwalan ulang atau menunjuk PIC alternatif..."></textarea>
                    </div>
                    <div class="flex justify-end pt-3">
                        <button type="submit" class="btn-primary-modern text-xs sm:text-sm px-3 py-1.5">
                            <i class="fas fa-paper-plane mr-1"></i>
                            Kirim Laporan Kendala
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm sm:text-base font-bold text-[var(--text-dark)] mb-4">Riwayat Umpan Balik Anda</h3>
        <div class="space-y-4">
            <div class="card-modern p-4 flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0 text-xs sm:text-sm">
                <div class="flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center bg-[var(--accent-secondary)] text-[var(--secondary)] text-base">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="flex-grow">
                    <p class="font-semibold text-xs sm:text-sm text-[var(--text-dark)]">Laporan Kendala: Penelitian Sistem Cerdas</p>
                    <p class="text-[10px] sm:text-xs text-[var(--text-light)] truncate">"Keterbatasan akses ke beberapa jurnal internasional berbayar..."</p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
                    <span class="text-[10px] sm:text-xs text-[var(--text-light)] whitespace-nowrap">10 Juli 2025</span>
                    <span class="badge-success whitespace-nowrap text-[10px] sm:text-xs">Sudah Ditinjau</span>
                </div>
            </div>

            <div class="card-modern p-4 flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0 text-xs sm:text-sm">
                <div class="flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center bg-blue-500/10 text-blue-500 text-base">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="flex-grow">
                    <p class="font-semibold text-xs sm:text-sm text-[var(--text-dark)]">Laporan Hasil: Magang Mahasiswa Batch 4</p>
                    <p class="text-[10px] sm:text-xs text-[var(--text-light)] truncate">"Mendapatkan talenta baru yang sangat membantu proyek internal..."</p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
                    <span class="text-[10px] sm:text-xs text-[var(--text-light)] whitespace-nowrap">15 Juli 2025</span>
                    <span class="px-2 sm:px-3 py-1 text-[10px] sm:text-xs font-semibold rounded-full text-white whitespace-nowrap" style="background: linear-gradient(135deg, #f97316, #ea580c);">Menunggu Tinjauan</span>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    /* Style untuk tab agar sesuai dengan tema utama */
    .tab-btn {
        color: var(--text-light);
        border-color: transparent;
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        color: var(--primary);
        border-color: var(--primary);
    }

    .tab-btn:not(.active):hover {
        color: var(--primary);
        border-color: var(--primary-dark);
    }

    .tab-pane.active {
        display: block;
        /* Memastikan tab yang aktif terlihat */
    }
</style>

<script>
    // Script ini sebaiknya berada di file JS utama, tapi diletakkan di sini untuk kelengkapan
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Nonaktifkan semua tombol dan sembunyikan semua panel
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => {
                    pane.classList.add('hidden');
                    pane.classList.remove('active');
                });

                // Aktifkan tombol dan panel yang diklik
                button.classList.add('active');
                const targetPaneId = button.getAttribute('data-tab');
                const targetPane = document.getElementById(targetPaneId);
                if (targetPane) {
                    targetPane.classList.remove('hidden');
                    targetPane.classList.add('active');
                }
            });
        });
    });
</script>