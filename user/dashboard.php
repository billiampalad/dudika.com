<div>
    <div class="mb-8 content-modern p-7">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-[var(--text-dark)] mb-1">Dashboard Mitra</h2>
                <p class="text-xs text-[var(--text-light)]">Ringkasan aktivitas dan statistik kerjasama Anda saat ini.</p>
            </div>
            <div class="mt-5 md:mt-0">
                <button class="text-sm px-3 py-2 bg-green-500 hover:bg-green-600 text-white border-green-600 focus:ring-green-300 rounded">
                    <i class="fas fa-print mr-2 text-xs"></i>
                    Cetak Laporan
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Active Projects Card -->
        <div class="bg-gradient-to-r from-red-600 to-red-400 text-white p-6 rounded-lg shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-white/20 rounded-lg">
                    <i class="fas fa-project-diagram text-xl"></i>
                </div>
                <div>
                    <p class="text-sm opacity-90">Proyek Aktif</p>
                    <p class="text-2xl font-bold mt-1">14</p>
                    <p class="text-xs opacity-80 mt-1">+2 dari bulan lalu</p>
                </div>
            </div>
        </div>

        <!-- Average Rating Card -->
        <div class="bg-gradient-to-r from-green-600 to-green-400 text-white p-6 rounded-lg shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-white/20 rounded-lg">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div>
                    <p class="text-sm opacity-90">Nilai Rata-rata</p>
                    <p class="text-2xl font-bold mt-1">4.2<span class="text-lg opacity-80">/5.0</span></p>
                    <p class="text-xs opacity-80 mt-1">8 evaluasi terselesaikan</p>
                </div>
            </div>
        </div>

        <!-- Total Participants Card -->
        <div class="bg-gradient-to-r from-cyan-600 to-cyan-400 text-white p-6 rounded-lg shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-white/20 rounded-lg">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div>
                    <p class="text-sm opacity-90">Total Partisipan</p>
                    <p class="text-2xl font-bold mt-1">327</p>
                    <p class="text-xs opacity-80 mt-1">120 aktif bulan ini</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-5">
        <div class="lg:col-span-2 space-y-8">
            <div class="card-modern p-6 card-modern p-6 border-l-4 border-green-500 rounded-lg">
                <h5 class="font-bold text-[var(--text-dark)] mb-5">Status Pelaksanaan Program</h5>
                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="font-semibold text-[var(--text-dark)] text-xs">Magang Industri 2025</span>
                            <span class="text-xs font-medium text-[var(--text-dark)]">75%</span>
                        </div>
                        <div class="w-full bg-[var(--border)] rounded-full h-2.5">
                            <div class="bg-red-600 h-2.5 rounded-full" style="width: 75%"></div>
                        </div>
                        <p class="text-xs text-[var(--text-light)] mt-1.5" style="font-size: 0.65rem;">Deadline: Desember 2025</p>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="font-semibold text-[var(--text-dark)] text-xs">Pelatihan Digital Marketing</span>
                            <span class="text-xs font-medium text-[var(--text-dark)]">30%</span>
                        </div>
                        <div class="w-full bg-[var(--border)] rounded-full h-2.5">
                            <div class="bg-green-500 h-2.5 rounded-full" style="width: 30%"></div>
                        </div>
                        <p class="text-xs text-[var(--text-light)] mt-1.5" style="font-size: 0.65rem;">Deadline: November 2025</p>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="font-semibold text-[var(--text-dark)] text-xs">Penelitian Bersama AI</span>
                            <span class="text-xs font-medium text-[var(--text-dark)]">90%</span>
                        </div>
                        <div class="w-full bg-[var(--border)] rounded-full h-2.5">
                            <div class="bg-cyan-600 h-2.5 rounded-full" style="width: 90%"></div>
                        </div>
                        <p class="text-xs text-[var(--text-light)] mt-1.5" style="font-size: 0.65rem;">Deadline: Agustus 2025</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="card-modern p-6 card-modern p-6 border-l-4 border-cyan-500 rounded-lg">
                    <h4 class="text-center font-bold text-[var(--text-dark)] mb-4">Distribusi Kerjasama</h4>
                    <div class="h-48 flex flex-col items-center justify-center space-y-4">
                        <div class="relative w-32 h-32">
                            <div class="w-full h-full rounded-full" style="background: conic-gradient(var(--primary) 45%, var(--secondary) 0 75%, #42a5f5 0 100%);"></div>
                            <div class="absolute inset-2 bg-[var(--surface)] rounded-full"></div>
                        </div>
                        <div class="flex space-x-4 text-xs text-[var(--text-light)]" style="font-size: 0.65rem;">
                            <div class="flex items-center"><span class="w-2.5 h-2.5 bg-[var(--primary)] rounded-full mr-1.5"></span>Magang</div>
                            <div class="flex items-center"><span class="w-2.5 h-2.5 bg-[var(--secondary)] rounded-full mr-1.5"></span>Pelatihan</div>
                            <div class="flex items-center"><span class="w-2.5 h-2.5 bg-[#42a5f5] rounded-full mr-1.5"></span>Penelitian</div>
                        </div>
                    </div>
                </div>

                <div class="card-modern p-6 border-l-4 border-[var(--primary)] rounded-lg">
                    <h4 class="text-center font-bold text-[var(--text-dark)] mb-4">Evaluasi Menunggu</h4>
                    <div class="h-48 flex flex-col justify-center space-y-3">
                        <div class="flex items-center p-3 bg-[var(--accent)] rounded-lg">
                            <i class="fas fa-exclamation-circle text-[var(--primary)] mr-3"></i>
                            <div>
                                <p class="font-medium text-xs text-[var(--text-dark)]">Program Magang Industri</p>
                                <p class="text-xs text-[var(--text-light)]" style="font-size: 0.65rem;">Batas: 15 Agustus 2025</p>
                            </div>
                        </div>
                        <div class="flex items-center p-3 bg-[var(--accent-secondary)] rounded-lg">
                            <i class="fas fa-clock text-[var(--secondary)] mr-3"></i>
                            <div>
                                <p class="font-medium text-xs text-[var(--text-dark)]">Pelatihan Soft Skills</p>
                                <p class="text-xs text-[var(--text-light)]" style="font-size: 0.65rem;">Batas: 30 September 2025</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-8">
            <div class="card-modern p-6 card-modern p-6 border-l-4 border-cyan-500 rounded-lg">
                <h4 class="text-center font-bold text-[var(--text-dark)] mb-6">Aktivitas Terkini</h4>
                <div class="space-y-5">
                    <div class="flex space-x-3">
                        <div class="w-1.5 h-auto bg-[var(--border)] rounded-full"></div>
                        <div class="space-y-5 relative">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-red-500/10 rounded-full flex items-center justify-center text-red-500 ring-4 ring-[var(--surface)]">
                                    <i class="fas fa-calendar-check text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-[var(--text-dark)] text-xs">Monitoring Program Magang</p>
                                    <p class="text-xs text-[var(--text-light)]" style="font-size: 0.65rem;">Hari ini, 10:00 WITA</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-green-500/10 rounded-full flex items-center justify-center text-green-500 ring-4 ring-[var(--surface)]">
                                    <i class="fas fa-file-upload text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-[var(--text-dark)] text-xs">Laporan triwulan diterima</p>
                                    <p class="text-xs text-[var(--text-light)]" style="font-size: 0.65rem;">2 hari yang lalu</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-cyan-500/10 rounded-full flex items-center justify-center text-cyan-500 ring-4 ring-[var(--surface)]">
                                    <i class="fas fa-user-graduate text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-[var(--text-dark)] text-xs">5 peserta baru bergabung</p>
                                    <p class="text-xs text-[var(--text-light)]" style="font-size: 0.65rem;">5 Juli 2025</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-modern p-6 border-l-4 border-green-500 rounded-lg">
                <h4 class="text-center font-bold text-[var(--text-dark)] mb-5 text-sl">MOU Akan Berakhir</h4>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-file-contract text-red-500 mt-1 text-lg"></i>
                        <div>
                            <p class="font-bold text-[var(--text-dark)] text-xs" style="font-size: 0.65rem;">No. 012/MOU/POLIMDO/2023</p>
                            <p class="text-xs text-[var(--text-light)]" style="font-size: 0.65rem;">Berakhir pada: <span class="font-semibold text-red-500">25 Oktober 2025</span></p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-[var(--border)]">
                        <button class="w-full py-2 bg-red-500/10 text-red-600 rounded-lg text-xs font-medium hover:bg-red-500/20 transition">
                            <i class="fas fa-sync-alt mr-2"></i> Ajukan Perpanjangan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>