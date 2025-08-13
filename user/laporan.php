<?php
$sql_hasil = "
    SELECT k.IdKKS, k.txtNamaKegiatanKS FROM tblnamakegiatanks k
    LEFT JOIN tblhasildancapaian h ON k.IdKKS = h.IdKKS
    WHERE k.dtSelesaiPelaksanaan < '$today' AND h.idHslDanCap IS NULL
";
$result_hasil = $koneksi->query($sql_hasil);

$sql_kendala = "
    SELECT k.IdKKS, k.txtNamaKegiatanKS FROM tblnamakegiatanks k
    WHERE '$today' BETWEEN k.dtMulaiPelaksanaan AND k.dtSelesaiPelaksanaan
";
$result_kendala = $koneksi->query($sql_kendala);


// === DATA UNTUK RIWAYAT ===
$history = [];

// 3. Ambil riwayat laporan HASIL
$query_history_hasil = "
    SELECT k.txtNamaKegiatanKS, h.txtManfaatBgDudika, k.dtSelesaiPelaksanaan
    FROM tblhasildancapaian h
    JOIN tblnamakegiatanks k ON h.IdKKS = k.IdKKS
";
$res_history_hasil = $koneksi->query($query_history_hasil);
while ($row = $res_history_hasil->fetch_assoc()) {
    $history[] = [
        'type' => 'hasil',
        'title' => 'Laporan Hasil: ' . $row['txtNamaKegiatanKS'],
        'description' => $row['txtManfaatBgDudika'],
        'date' => $row['dtSelesaiPelaksanaan'], // Menggunakan tanggal selesai sebagai acuan
        'status' => 'Terkirim' // Status default untuk hasil
    ];
}

// 4. Ambil riwayat laporan KENDALA
$query_history_kendala = "
    SELECT k.txtNamaKegiatanKS, p.txtKendala, p.status, k.dtSelesaiPelaksanaan
    FROM tblpermasalahandansolusi p
    JOIN tblnamakegiatanks k ON p.IdKKS = k.IdKKS
";
$res_history_kendala = $koneksi->query($query_history_kendala);
while ($row = $res_history_kendala->fetch_assoc()) {
    $history[] = [
        'type' => 'kendala',
        'title' => 'Laporan Kendala: ' . $row['txtNamaKegiatanKS'],
        'description' => $row['txtKendala'],
        'date' => $row['dtSelesaiPelaksanaan'],
        'status' => $row['status'] // Status dari database ('diproses' atau 'selesai')
    ];
}

// Urutkan riwayat berdasarkan tanggal (terbaru dulu)
usort($history, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

?>

<main class="pb-5">
    <div class="card-modern mb-8 overflow-hidden text-sm sm:text-base">
        <div class="border-b border-[var(--border)]">
            <div class="overflow-x-auto whitespace-nowrap px-4 sm:px-6 -mb-px">
                <nav class="inline-flex" aria-label="Tabs">
                    <button data-tab="hasilPanel" class="tab-btn active whitespace-nowrap py-3 sm:py-4 px-2 sm:px-4 border-b-2 font-medium text-xs sm:text-sm">
                        <i class="fas fa-trophy mr-2"></i> Lapor Hasil & Capaian
                    </button>
                    <button data-tab="kendalaPanel" class="tab-btn whitespace-nowrap py-3 sm:py-4 px-2 sm:px-4 border-b-2 font-medium text-xs sm:text-sm">
                        <i class="fas fa-flag mr-2"></i> Lapor Kendala & Solusi
                    </button>
                </nav>
            </div>
        </div>

        <div class="p-4 sm:p-6">
            <div id="hasilPanel" class="tab-pane active">
                <form id="formHasil" class="space-y-4 sm:space-y-5">
                    <div>
                        <label for="kegiatanHasil" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Pilih Program Kerjasama (Selesai)</label>
                        <select name="IdKKS" id="kegiatanHasil" class="form-modern w-full text-xs sm:text-sm" required>
                            <option value="">-- Pilih Kegiatan --</option>
                            <?php while ($row = $result_hasil->fetch_assoc()): ?>
                                <option value="<?= $row['IdKKS'] ?>"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="txtManfaatBgDudika" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Manfaat yang Dirasakan Mitra</label>
                        <textarea name="txtManfaatBgDudika" id="txtManfaatBgDudika" rows="4" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Mendapatkan talenta baru yang kompeten, inovasi produk..."></textarea>
                    </div>
                    <div>
                        <label for="txtDampakJangkaMenengah" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Potensi Dampak Jangka Menengah</label>
                        <textarea name="txtDampakJangkaMenengah" id="txtDampakJangkaMenengah" rows="3" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Peningkatan efisiensi operasional..."></textarea>
                    </div>
                    <p id="hasilMessage" class="text-xs text-red-500"></p>
                    <div class="flex justify-end pt-3">
                        <button type="submit" class="btn-secondary-modern text-xs sm:text-sm px-3 py-1.5">
                            <i class="fas fa-save mr-1"></i>Simpan Hasil
                        </button>
                    </div>
                </form>
            </div>

            <div id="kendalaPanel" class="tab-pane hidden">
                <form id="formKendala" class="space-y-4 sm:space-y-5">
                    <div>
                        <label for="kegiatanKendala" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Pilih Program Kerjasama (Berjalan)</label>
                        <select name="IdKKS" id="kegiatanKendala" class="form-modern w-full text-xs sm:text-sm" required>
                            <option value="">-- Pilih Kegiatan --</option>
                            <?php while ($row = $result_kendala->fetch_assoc()): ?>
                                <option value="<?= $row['IdKKS'] ?>"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="txtKendala" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Deskripsikan Kendala</label>
                        <textarea name="txtKendala" id="txtKendala" rows="4" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Jadwal pendampingan dari pihak mitra sering tertunda..."></textarea>
                    </div>
                    <div>
                        <label for="txtUpayaUtkAtasiMslh" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Solusi yang Diusulkan atau Telah Dilakukan</label>
                        <textarea name="txtUpayaUtkAtasiMslh" id="txtUpayaUtkAtasiMslh" rows="3" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Mengusulkan penjadwalan ulang..."></textarea>
                    </div>
                    <p id="kendalaMessage" class="text-xs text-red-500"></p>
                    <div class="flex justify-end pt-3">
                        <button type="submit" class="btn-primary-modern text-xs sm:text-sm px-3 py-1.5">
                            <i class="fas fa-paper-plane mr-1"></i> Kirim Laporan Kendala
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm sm:text-base font-bold text-[var(--text-dark)] mb-4">Riwayat Umpan Balik Anda</h3>
        <div class="space-y-4">
            <?php if (empty($history)): ?>
                <p class="text-sm text-gray-500 text-center">Belum ada riwayat laporan.</p>
            <?php else: ?>
                <?php foreach ($history as $item): ?>
                    <?php if ($item['type'] == 'hasil'): ?>
                        <div class="card-modern p-4 flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0 text-xs sm:text-sm">
                            <div class="flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center bg-blue-500/10 text-blue-500 text-base"><i class="fas fa-trophy"></i></div>
                            <div class="flex-grow">
                                <p class="font-semibold text-xs sm:text-sm text-[var(--text-dark)]"><?= htmlspecialchars($item['title']) ?></p>
                                <p class="text-[10px] sm:text-xs text-[var(--text-light)] truncate">"<?= htmlspecialchars($item['description']) ?>"</p>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
                                <span class="text-[10px] sm:text-xs text-[var(--text-light)] whitespace-nowrap"><?= date('d M Y', strtotime($item['date'])) ?></span>
                                <span class="badge-success whitespace-nowrap text-[10px] sm:text-xs">Terkirim</span>
                            </div>
                        </div>
                    <?php else: // type == 'kendala' 
                    ?>
                        <div class="card-modern p-4 flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0 text-xs sm:text-sm">
                            <div class="flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center bg-red-500/10 text-red-500 text-base"><i class="fas fa-flag"></i></div>
                            <div class="flex-grow">
                                <p class="font-semibold text-xs sm:text-sm text-[var(--text-dark)]"><?= htmlspecialchars($item['title']) ?></p>
                                <p class="text-[10px] sm:text-xs text-[var(--text-light)] truncate">"<?= htmlspecialchars($item['description']) ?>"</p>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4">
                                <span class="text-[10px] sm:text-xs text-[var(--text-light)] whitespace-nowrap"><?= date('d M Y', strtotime($item['date'])) ?></span>
                                <?php if ($item['status'] == 'selesai'): ?>
                                    <span class="badge-success whitespace-nowrap text-[10px] sm:text-xs">Selesai</span>
                                <?php else: ?>
                                    <span class="px-2 sm:px-3 py-1 text-[10px] sm:text-xs font-semibold rounded-full text-white whitespace-nowrap" style="background: linear-gradient(135deg, #f97316, #ea580c);">Diproses</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => {
                    pane.classList.add('hidden');
                    pane.classList.remove('active');
                });
                button.classList.add('active');
                const targetPane = document.getElementById(button.getAttribute('data-tab'));
                if (targetPane) {
                    targetPane.classList.remove('hidden');
                    targetPane.classList.add('active');
                }
            });
        });

        // Form submission for Hasil
        const formHasil = document.getElementById('formHasil');
        const hasilMessage = document.getElementById('hasilMessage');
        formHasil.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formHasil);
            const submitButton = formHasil.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            fetch('simpan_hasil.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Laporan hasil berhasil disimpan!');
                        window.location.reload();
                    } else {
                        hasilMessage.textContent = data.message || 'Gagal menyimpan data.';
                        submitButton.disabled = false;
                    }
                })
                .catch(() => {
                    hasilMessage.textContent = 'Terjadi kesalahan jaringan.';
                    submitButton.disabled = false;
                });
        });

        // Form submission for Kendala
        const formKendala = document.getElementById('formKendala');
        const kendalaMessage = document.getElementById('kendalaMessage');
        formKendala.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formKendala);
            const submitButton = formKendala.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            fetch('simpan_kendala.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Laporan kendala berhasil dikirim!');
                        window.location.reload();
                    } else {
                        kendalaMessage.textContent = data.message || 'Gagal mengirim laporan.';
                        submitButton.disabled = false;
                    }
                })
                .catch(() => {
                    kendalaMessage.textContent = 'Terjadi kesalahan jaringan.';
                    submitButton.disabled = false;
                });
        });
    });
</script>