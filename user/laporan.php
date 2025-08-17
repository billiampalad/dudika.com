<?php
// Definisikan tanggal hari ini
$today = date('Y-m-d');

// --- Ambil data untuk form dan masukkan ke array ---
$kegiatan_selesai = [];
$sql_hasil = "
    SELECT k.IdKKS, k.txtNamaKegiatanKS FROM tblnamakegiatanks k
    LEFT JOIN tblhasildancapaian h ON k.IdKKS = h.IdKKS
    WHERE k.dtSelesaiPelaksanaan < '$today' AND h.idHslDanCap IS NULL
";
$result_hasil = $koneksi->query($sql_hasil);
if ($result_hasil) {
    while ($row = $result_hasil->fetch_assoc()) {
        $kegiatan_selesai[] = $row;
    }
}


$kegiatan_berjalan = [];
$sql_kendala = "
    SELECT k.IdKKS, k.txtNamaKegiatanKS FROM tblnamakegiatanks k
    WHERE '$today' BETWEEN k.dtMulaiPelaksanaan AND k.dtSelesaiPelaksanaan
";
$result_kendala = $koneksi->query($sql_kendala);
if ($result_kendala) {
    while ($row = $result_kendala->fetch_assoc()) {
        $kegiatan_berjalan[] = $row;
    }
}


// --- Ambil data untuk riwayat ---
$history = [];

// 1. Ambil riwayat laporan HASIL
$query_history_hasil = "
    SELECT k.txtNamaKegiatanKS, h.txtManfaatBgDudika, k.dtSelesaiPelaksanaan
    FROM tblhasildancapaian h
    JOIN tblnamakegiatanks k ON h.IdKKS = k.IdKKS
    ORDER BY k.dtSelesaiPelaksanaan DESC
";
$res_history_hasil = $koneksi->query($query_history_hasil);
if ($res_history_hasil) {
    while ($row = $res_history_hasil->fetch_assoc()) {
        $history[] = [
            'type' => 'hasil',
            'title' => 'Laporan Hasil: ' . $row['txtNamaKegiatanKS'],
            'description' => $row['txtManfaatBgDudika'],
            'date' => $row['dtSelesaiPelaksanaan'],
            'status' => 'Terkirim'
        ];
    }
}

// 2. Ambil riwayat laporan KENDALA
$query_history_kendala = "
    SELECT k.txtNamaKegiatanKS, p.txtKendala, p.status, p.urgensi, k.dtSelesaiPelaksanaan
    FROM tblpermasalahandansolusi p
    JOIN tblnamakegiatanks k ON p.IdKKS = k.IdKKS
    ORDER BY k.dtSelesaiPelaksanaan DESC
";
$res_history_kendala = $koneksi->query($query_history_kendala);
if ($res_history_kendala) {
    while ($row = $res_history_kendala->fetch_assoc()) {
        $history[] = [
            'type' => 'kendala',
            'title' => 'Laporan Kendala: ' . $row['txtNamaKegiatanKS'],
            'description' => $row['txtKendala'],
            'date' => $row['dtSelesaiPelaksanaan'],
            'status' => $row['status'],
            'urgensi' => $row['urgensi']
        ];
    }
}

// Urutkan gabungan riwayat berdasarkan tanggal (terbaru dulu)
usort($history, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

?>

<main class="pb-5">
    <!-- Notifikasi Kustom -->
    <div id="notification" class="fixed top-5 right-5 bg-green-500 text-white py-2 px-4 rounded-lg shadow-lg transform translate-x-[120%] transition-transform duration-300 ease-in-out">
        <p id="notification-message"></p>
    </div>

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
            <!-- Panel Laporan Hasil -->
            <div id="hasilPanel" class="tab-pane active">
                <form id="formHasil" class="space-y-4 sm:space-y-5">
                    <div>
                        <label for="kegiatanHasil" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Pilih Program Kerjasama (Selesai)</label>
                        <select name="IdKKS" id="kegiatanHasil" class="form-modern w-full text-xs sm:text-sm" required>
                            <option value="">-- Pilih Kegiatan --</option>
                            <?php if (empty($kegiatan_selesai)): ?>
                                <option value="" disabled>Tidak ada program yang selesai untuk dilaporkan.</option>
                            <?php else: ?>
                                <?php foreach ($kegiatan_selesai as $kegiatan): ?>
                                    <option value="<?= htmlspecialchars($kegiatan['IdKKS']) ?>"><?= htmlspecialchars($kegiatan['txtNamaKegiatanKS']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="txtManfaatBgDudika" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Manfaat yang Dirasakan Mitra</label>
                        <textarea name="txtManfaatBgDudika" id="txtManfaatBgDudika" rows="4" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Mendapatkan talenta baru yang kompeten, inovasi produk..." required></textarea>
                    </div>
                    <div>
                        <label for="txtDampakJangkaMenengah" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Potensi Dampak Jangka Menengah</label>
                        <textarea name="txtDampakJangkaMenengah" id="txtDampakJangkaMenengah" rows="3" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Peningkatan efisiensi operasional..."></textarea>
                    </div>
                    <p id="hasilMessage" class="text-xs text-red-500 min-h-[16px]"></p>
                    <div class="flex justify-end pt-3">
                        <button type="submit" class="btn-secondary-modern text-xs sm:text-sm px-3 py-1.5 w-32 text-center">
                            <span class="btn-text"><i class="fas fa-save mr-1"></i>Simpan Hasil</span>
                            <span class="btn-spinner hidden"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Panel Laporan Kendala -->
            <div id="kendalaPanel" class="tab-pane hidden">
                <form id="formKendala" class="space-y-4 sm:space-y-5">
                    <div>
                        <label for="kegiatanKendala" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Pilih Program Kerjasama (Berjalan)</label>
                        <select name="IdKKS" id="kegiatanKendala" class="form-modern w-full text-xs sm:text-sm" required>
                            <option value="">-- Pilih Kegiatan --</option>
                            <?php if (empty($kegiatan_berjalan)): ?>
                                <option value="" disabled>Tidak ada program berjalan untuk dilaporkan.</option>
                            <?php else: ?>
                                <?php foreach ($kegiatan_berjalan as $kegiatan): ?>
                                    <option value="<?= htmlspecialchars($kegiatan['IdKKS']) ?>"><?= htmlspecialchars($kegiatan['txtNamaKegiatanKS']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="txtKendala" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Deskripsikan Kendala</label>
                        <textarea name="txtKendala" id="txtKendala" rows="4" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Jadwal pendampingan dari pihak mitra sering tertunda..." required></textarea>
                    </div>
                    <div>
                        <label for="txtUpayaUtkAtasiMslh" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Solusi yang Diusulkan atau Telah Dilakukan</label>
                        <textarea name="txtUpayaUtkAtasiMslh" id="txtUpayaUtkAtasiMslh" rows="3" class="form-modern w-full text-xs sm:text-sm" placeholder="Contoh: Mengusulkan penjadwalan ulang..."></textarea>
                    </div>
                    <div>
                        <label for="urgensi" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Tingkat Urgensi</label>
                        <select name="urgensi" id="urgensi" class="form-modern w-full text-xs sm:text-sm">
                            <option value="rendah">Rendah</option>
                            <option value="sedang" selected>Sedang</option>
                            <option value="tinggi">Tinggi</option>
                        </select>
                    </div>
                    <p id="kendalaMessage" class="text-xs text-red-500 min-h-[16px]"></p>
                    <div class="flex justify-end pt-3">
                        <button type="submit" class="btn-primary-modern text-xs sm:text-sm px-3 py-1.5 w-48 text-center">
                             <span class="btn-text"><i class="fas fa-paper-plane mr-1"></i> Kirim Laporan Kendala</span>
                             <span class="btn-spinner hidden"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat Umpan Balik -->
    <div>
        <h3 class="text-sm sm:text-base font-bold text-[var(--text-dark)] mb-4">Riwayat Umpan Balik Anda</h3>
        <div class="space-y-4">
            <?php if (empty($history)): ?>
                <div class="card-modern p-4 text-center">
                    <p class="text-sm text-gray-500">Belum ada riwayat laporan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($history as $item): ?>
                    <div class="card-modern p-4 flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0 text-xs sm:text-sm">
                        <!-- Icon -->
                        <div class="flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center <?= $item['type'] == 'hasil' ? 'bg-blue-500/10 text-blue-500' : 'bg-red-500/10 text-red-500' ?> text-base">
                            <i class="fas <?= $item['type'] == 'hasil' ? 'fa-trophy' : 'fa-flag' ?>"></i>
                        </div>
                        
                        <!-- Judul dan Deskripsi -->
                        <div class="flex-grow">
                            <p class="font-semibold text-xs sm:text-sm text-[var(--text-dark)]"><?= htmlspecialchars($item['title']) ?></p>
                            <p class="text-[10px] sm:text-xs text-[var(--text-light)] truncate">"<?= htmlspecialchars($item['description']) ?>"</p>
                            <?php if ($item['type'] == 'kendala'): ?>
                                <?php
                                $urgensi_color = 'bg-gray-100 text-gray-800'; // Default
                                if (isset($item['urgensi'])) {
                                    if ($item['urgensi'] == 'tinggi') $urgensi_color = 'bg-red-100 text-red-800';
                                    elseif ($item['urgensi'] == 'sedang') $urgensi_color = 'bg-yellow-100 text-yellow-800';
                                    else $urgensi_color = 'bg-green-100 text-green-800';
                                }
                                ?>
                                <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-[10px] <?= $urgensi_color ?>">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    Urgensi: <?= ucfirst(htmlspecialchars($item['urgensi'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Tanggal dan Status -->
                        <div class="flex flex-col items-start sm:items-end sm:flex-row sm:items-center sm:space-x-4">
                            <span class="text-[10px] sm:text-xs text-[var(--text-light)] whitespace-nowrap"><?= date('d M Y', strtotime($item['date'])) ?></span>
                            <?php
                            $status_badge = '';
                            if ($item['status'] == 'Terkirim' || $item['status'] == 'selesai') {
                                $status_badge = '<span class="badge-success whitespace-nowrap text-[10px] sm:text-xs">Selesai</span>';
                            } else { // 'diproses'
                                $status_badge = '<span class="px-2 sm:px-3 py-1 text-[10px] sm:text-xs font-semibold rounded-full text-white whitespace-nowrap" style="background: linear-gradient(135deg, #f97316, #ea580c);">Diproses</span>';
                            }
                            echo $status_badge;
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Fungsi Notifikasi Kustom ---
    const notification = document.getElementById('notification');
    const notificationMessage = document.getElementById('notification-message');
    
    function showNotification(message, isSuccess = true) {
        notificationMessage.textContent = message;
        notification.className = 'fixed top-5 right-5 text-white py-2 px-4 rounded-lg shadow-lg transform transition-transform duration-300 ease-in-out'; // Reset
        if (isSuccess) {
            notification.classList.add('bg-green-500', 'translate-x-0');
        } else {
            notification.classList.add('bg-red-500', 'translate-x-0');
        }

        setTimeout(() => {
            notification.classList.remove('translate-x-0');
            notification.classList.add('translate-x-[120%]');
        }, 3000);
    }

    // --- Fungsi Tab ---
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

    // --- Fungsi untuk handle loading state tombol ---
    function setButtonLoading(button, isLoading) {
        const btnText = button.querySelector('.btn-text');
        const btnSpinner = button.querySelector('.btn-spinner');
        if (isLoading) {
            button.disabled = true;
            btnText.classList.add('hidden');
            btnSpinner.classList.remove('hidden');
        } else {
            button.disabled = false;
            btnText.classList.remove('hidden');
            btnSpinner.classList.add('hidden');
        }
    }

    // --- Penanganan Form ---
    function handleFormSubmit(form, url, messageElement) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            
            setButtonLoading(submitButton, true);
            messageElement.textContent = ''; // Kosongkan pesan error sebelumnya

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message || 'Laporan berhasil disimpan!');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    messageElement.textContent = data.message || 'Gagal menyimpan data.';
                    showNotification(data.message || 'Gagal menyimpan data.', false);
                    setButtonLoading(submitButton, false);
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                messageElement.textContent = 'Terjadi kesalahan jaringan.';
                showNotification('Terjadi kesalahan jaringan.', false);
                setButtonLoading(submitButton, false);
            });
        });
    }

    // Inisialisasi penanganan untuk kedua form dengan URL absolut
    const baseUrl = window.location.origin + '/wd4/user/';
    handleFormSubmit(document.getElementById('formHasil'), baseUrl + 'simpan_hasil.php', document.getElementById('hasilMessage'));
    handleFormSubmit(document.getElementById('formKendala'), baseUrl + 'simpan_kendala.php', document.getElementById('kendalaMessage'));
});
</script>
