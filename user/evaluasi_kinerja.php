<?php
session_start();

// PERUBAHAN PENTING: Query yang diperbaiki
$sql_pending = "
    SELECT k.IdKKS, k.txtNamaKegiatanKS, k.dtMulaiPelaksanaan, k.dtSelesaiPelaksanaan
    FROM tblnamakegiatanks k
    JOIN tblmitraprogram mp ON k.IdKKS = mp.IdKKS  <!-- Perubahan disini -->
    JOIN tblmitradudika m ON mp.IdMitraDudika = m.IdMitraDudika  <!-- Perubahan disini -->
    LEFT JOIN tblevaluasikinerja e ON k.IdKKS = e.IdKKS
    WHERE m.nik = '$loggedInNik' 
    AND k.dtSelesaiPelaksanaan <= '$today'  <!-- Pakai <= bukan < -->
    AND e.IdEvKinerja IS NULL
    AND k.status = 'Selesai'  <!-- Tambahan pengecekan status -->
";
$result_pending = $koneksi->query($sql_pending);

// Fungsi baru untuk pengecekan
function canEvaluateProgram($idKKS, $koneksi) {
    $query = "SELECT COUNT(*) as count 
              FROM tblnamakegiatanks 
              WHERE IdKKS = '$idKKS'
              AND dtSelesaiPelaksanaan <= CURDATE() 
              AND status = 'Selesai'";
    $result = $koneksi->query($query);
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}
?>

<main>
    <div class="mb-10">
        <h3 class="text-base font-bold text-[var(--text-dark)] mb-4">Kegiatan Perlu Dievaluasi</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <?php if ($result_pending->num_rows > 0): ?>
                <?php while ($row = $result_pending->fetch_assoc()): ?>
                    <div class="card-modern p-5 border-l-4 border-[var(--primary)] flex items-center justify-between">
                        <div>
                            <p class="font-bold text-sm text-[var(--text-dark)]"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></p>
                            <p class="text-xs text-[var(--text-light)]">Periode: <?= date('d M Y', strtotime($row['dtMulaiPelaksanaan'])) ?> - <?= date('d M Y', strtotime($row['dtSelesaiPelaksanaan'])) ?></p>
                        </div>
                        <button onclick="showEvaluationModal('<?= $row['IdKKS'] ?>', '<?= htmlspecialchars(addslashes($row['txtNamaKegiatanKS'])) ?>')" class="btn-primary-modern text-xs px-3 py-1.5">
                            <i class="fas fa-edit text-xs mr-1"></i>
                            <span>Isi Evaluasi</span>
                        </button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-sm text-[var(--text-light)] col-span-2">Tidak ada kegiatan yang perlu dievaluasi saat ini. 👍</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 card-modern p-6">
            <h3 class="text-base font-bold text-[var(--text-dark)] mb-6">Riwayat Evaluasi</h3>
            <div class="overflow-x-auto">
                <table class="w-full min-w-max">
                    <thead class="bg-[var(--background)]">
                        <tr class="text-left text-xs font-semibold text-[var(--text-light)] uppercase tracking-wider">
                            <th class="px-4 py-3">Nama Kegiatan</th>
                            <th class="px-4 py-3 text-center">Kesesuaian</th>
                            <th class="px-4 py-3 text-center">Kualitas</th>
                            <th class="px-4 py-3 text-center">Keterlibatan</th>
                            <th class="px-4 py-3 text-center">Efisiensi</th>
                            <th class="px-4 py-3 text-center">Kepuasan</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <?php if ($result_history->num_rows > 0): ?>
                            <?php while ($row = $result_history->fetch_assoc()): ?>
                                <tr class="hover:bg-[var(--background)] transition-colors">
                                    <td class="px-4 py-4 font-medium text-sm text-[var(--text-dark)]"><?= htmlspecialchars($row['txtNamaKegiatanKS']) ?></td>
                                    <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">
                                        <span class="inline-flex items-center">
                                            <?= $row['txtSesuaiRencana'] ?>
                                            <i class="fas fa-star text-yellow-400 ml-1 text-xs"></i>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">
                                        <span class="inline-flex items-center">
                                            <?= $row['txtKualitasPelaks'] ?>
                                            <i class="fas fa-star text-yellow-400 ml-1 text-xs"></i>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">
                                        <span class="inline-flex items-center">
                                            <?= $row['txtKeterlibatanMtra'] ?>
                                            <i class="fas fa-star text-yellow-400 ml-1 text-xs"></i>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">
                                        <span class="inline-flex items-center">
                                            <?= $row['txtEfisiensiPenggSbDya'] ?>
                                            <i class="fas fa-star text-yellow-400 ml-1 text-xs"></i>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">
                                        <span class="inline-flex items-center">
                                            <?= $row['txtKepuasanPhkTerkait'] ?>
                                            <i class="fas fa-star text-yellow-400 ml-1 text-xs"></i>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <button onclick="showEvaluationModal('<?= $row['IdKKS'] ?>', '<?= htmlspecialchars(addslashes($row['txtNamaKegiatanKS'])) ?>', true)" class="p-2 rounded-md bg-[var(--accent)] text-[var(--primary)] hover:bg-[var(--primary)]/20 transition" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-sm text-[var(--text-light)]">Anda belum pernah mengisi evaluasi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="card-modern p-6">
                <h3 class="text-base text-center font-bold text-[var(--text-dark)] mb-4">Perkembangan Skor Anda</h3>
                <div class="h-80 bg-[var(--background)] rounded-lg p-2">
                    <canvas id="skorChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Evaluasi -->
<div id="evaluationModal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div id="evaluationModalContent" class="modal-modern w-full max-w-2xl transform transition-all duration-300 max-h-[90vh] flex flex-col text-sm sm:text-base">
        <div class="flex justify-between items-start p-4 sm:p-6 border-b border-[var(--border)]">
            <div>
                <h3 id="modalTitle" class="text-sm sm:text-base font-semibold text-[var(--text-dark)]">Evaluasi Program</h3>
                <p id="modalSubtitle" class="text-xs sm:text-sm text-[var(--text-light)]">Berikan skor (1-5) untuk setiap aspek evaluasi.</p>
            </div>
            <button onclick="closeEvaluationModal()" class="text-[var(--text-light)] hover:text-[var(--primary)] rounded-full w-8 h-8 flex items-center justify-center hover:bg-[var(--border)] transition text-base">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="evaluationForm" class="p-4 sm:p-6 space-y-5 sm:space-y-6 overflow-y-auto flex-grow bg-[var(--background)] text-sm">
            <input type="hidden" name="IdKKS" id="IdKKS">

            <div class="space-y-5 sm:space-y-6">
                <!-- Kesesuaian Rencana -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-2">Kesesuaian dengan Rencana</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="star-rating flex text-xl sm:text-2xl text-yellow-400 min-w-[120px]"></div>
                        <input type="range" name="txtSesuaiRencana" min="1" max="5" value="3" class="w-full range-slider">
                        <span class="rating-value text-sm font-medium text-[var(--text-dark)] min-w-[20px]">3</span>
                    </div>
                </div>

                <!-- Kualitas Pelaksanaan -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-2">Kualitas Pelaksanaan</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="star-rating flex text-xl sm:text-2xl text-yellow-400 min-w-[120px]"></div>
                        <input type="range" name="txtKualitasPelaks" min="1" max="5" value="3" class="w-full range-slider">
                        <span class="rating-value text-sm font-medium text-[var(--text-dark)] min-w-[20px]">3</span>
                    </div>
                </div>

                <!-- Keterlibatan Mitra -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-2">Keterlibatan Mitra</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="star-rating flex text-xl sm:text-2xl text-yellow-400 min-w-[120px]"></div>
                        <input type="range" name="txtKeterlibatanMtra" min="1" max="5" value="3" class="w-full range-slider">
                        <span class="rating-value text-sm font-medium text-[var(--text-dark)] min-w-[20px]">3</span>
                    </div>
                </div>

                <!-- Efisiensi Penggunaan Sumber Daya -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-2">Efisiensi Penggunaan Sumber Daya</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="star-rating flex text-xl sm:text-2xl text-yellow-400 min-w-[120px]"></div>
                        <input type="range" name="txtEfisiensiPenggSbDya" min="1" max="5" value="3" class="w-full range-slider">
                        <span class="rating-value text-sm font-medium text-[var(--text-dark)] min-w-[20px]">3</span>
                    </div>
                </div>

                <!-- Kepuasan Pihak Terkait -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-2">Kepuasan Pihak Terkait</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="star-rating flex text-xl sm:text-2xl text-yellow-400 min-w-[120px]"></div>
                        <input type="range" name="txtKepuasanPhkTerkait" min="1" max="5" value="3" class="w-full range-slider">
                        <span class="rating-value text-sm font-medium text-[var(--text-dark)] min-w-[20px]">3</span>
                    </div>
                </div>
            </div>

            <div id="modalFooter" class="flex flex-col sm:flex-row justify-end items-center space-y-2 sm:space-y-0 sm:space-x-3 pt-4 border-t border-[var(--border)]">
                <p id="formMessage" class="text-xs text-red-500 mr-auto"></p>
                <button type="button" onclick="closeEvaluationModal()" class="w-full sm:w-auto px-4 py-2 rounded-lg text-sm font-medium bg-[var(--border)] text-[var(--text-dark)] hover:opacity-80 transition">Tutup</button>
                <button type="submit" id="submitButton" class="w-full sm:w-auto btn-primary-modern text-sm px-4 py-2">Simpan Evaluasi</button>
            </div>
        </form>
    </div>
</div>

<script>
    const evaluationModal = document.getElementById('evaluationModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const evaluationForm = document.getElementById('evaluationForm');
    const formMessage = document.getElementById('formMessage');
    const idKKSInput = document.getElementById('IdKKS');
    const submitButton = document.getElementById('submitButton');

    function showEvaluationModal(idKKS, programName, isReadOnly = false) {
        console.log('showEvaluationModal called with:', {
            idKKS,
            programName,
            isReadOnly
        });

        if (!evaluationModal || !idKKS) {
            console.error('Modal element atau idKKS tidak valid');
            return;
        }

        // Reset form dan pesan error
        formMessage.textContent = '';
        evaluationForm.reset();

        // Set ID kegiatan
        idKKSInput.value = idKKS;
        console.log('IdKKS input value set to:', idKKSInput.value);

        // Atur judul modal
        modalTitle.textContent = isReadOnly ? 'Detail Evaluasi' : 'Evaluasi Program';
        modalSubtitle.textContent = programName;

        // Reset semua slider ke nilai default dan update tampilan
        const sliders = evaluationForm.querySelectorAll('input[type="range"]');
        sliders.forEach(slider => {
            slider.value = 3;
            updateStarsAndValue(slider);
        });

        // Atur mode readonly
        const formElements = evaluationForm.querySelectorAll('input, textarea');
        const submitBtn = evaluationForm.querySelector('button[type="submit"]');

        if (isReadOnly) {
            submitBtn.classList.add('hidden');
            formElements.forEach(el => el.setAttribute('disabled', 'true'));

            // Fetch data detail evaluasi
            fetch(`user/get_evaluasi.php?id=${idKKS}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Data received from get_evaluasi.php:', data);
                    if (data.status === 'success') {
                        // Isi form dengan data yang diterima
                        Object.keys(data.data).forEach(key => {
                            const input = evaluationForm.elements[key];
                            if (input && data.data[key] !== null) {
                                input.value = data.data[key];
                                if (input.type === 'range') {
                                    updateStarsAndValue(input);
                                }
                            }
                        });
                    } else {
                        formMessage.textContent = data.message || 'Gagal memuat detail evaluasi.';
                    }
                })
                .catch(error => {
                    console.error('Error fetching evaluation data:', error);
                    formMessage.textContent = 'Gagal memuat detail evaluasi.';
                });
        } else {
            submitBtn.classList.remove('hidden');
            formElements.forEach(el => el.removeAttribute('disabled'));
        }

        // Tampilkan modal
        evaluationModal.classList.remove('hidden');
    }

    function closeEvaluationModal() {
        if (evaluationModal) {
            evaluationModal.classList.add('hidden');
        }
    }

    function updateStarsAndValue(slider) {
        const rating = parseInt(slider.value);
        const container = slider.parentElement;

        // Update bintang
        const starContainer = container.querySelector('.star-rating');
        if (starContainer) {
            starContainer.innerHTML = Array.from({
                    length: 5
                }, (_, i) =>
                `<i class="fa-star ${i < rating ? 'fas' : 'far'}"></i>`
            ).join('');
        }

        // Update nilai angka
        const valueSpan = container.querySelector('.rating-value');
        if (valueSpan) {
            valueSpan.textContent = rating;
        }
    }

    // Event listener untuk slider
    if (evaluationForm) {
        evaluationForm.addEventListener('input', (e) => {
            if (e.target.matches('input[type="range"]')) {
                updateStarsAndValue(e.target);
            }
        });

        // Event listener untuk submit form
        evaluationForm.addEventListener('submit', (e) => {
            e.preventDefault();

            // Validasi IdKKS
            if (!idKKSInput.value) {
                formMessage.textContent = 'ID Kegiatan tidak valid.';
                return;
            }

            console.log('Form submission started. IdKKS:', idKKSInput.value);

            submitButton.disabled = true;
            submitButton.textContent = 'Menyimpan...';
            formMessage.textContent = '';

            const formData = new FormData(evaluationForm);

            // Debug: Log semua data yang akan dikirim
            console.log('Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }

            fetch('simpan_evaluasi.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response from server:', data);
                    if (data.status === 'success') {
                        alert('Evaluasi berhasil disimpan!');
                        closeEvaluationModal();
                        window.location.reload();
                    } else {
                        formMessage.textContent = data.message || 'Terjadi kesalahan saat menyimpan.';
                    }
                })
                .catch(error => {
                    console.error('Error submitting form:', error);
                    formMessage.textContent = 'Gagal terhubung ke server: ' + error.message;
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Simpan Evaluasi';
                });
        });
    }

    // Event listener untuk menutup modal saat klik overlay
    if (evaluationModal) {
        evaluationModal.addEventListener('click', (e) => {
            if (e.target === evaluationModal) {
                closeEvaluationModal();
            }
        });
    }

    // Initialize stars pada load
    document.addEventListener('DOMContentLoaded', () => {
        const sliders = document.querySelectorAll('input[type="range"]');
        sliders.forEach(slider => {
            updateStarsAndValue(slider);
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Ambil elemen canvas
        const ctx = document.getElementById('skorChart');

        // Ambil data dari backend menggunakan fetch API
        fetch('user/get_skor_data.php')
            .then(response => response.json())
            .then(data => {
                // Cek jika tidak ada data atau ada error
                if (!data.labels || data.labels.length === 0) {
                    ctx.parentElement.innerHTML = '<div class="flex items-center justify-center h-full text-sm text-[var(--text-light)]">Belum ada data skor untuk ditampilkan.</div>';
                    return;
                }

                // Buat grafik baru menggunakan Chart.js
                new Chart(ctx, {
                    type: 'line', // Jenis grafik: garis
                    data: {
                        labels: data.labels, // Label sumbu X dari PHP
                        datasets: [{
                            label: 'Skor Rata-rata',
                            data: data.scores, // Data skor sumbu Y dari PHP
                            fill: true,
                            backgroundColor: 'rgba(75, 122, 254, 0.2)', // Warna area di bawah garis
                            borderColor: 'rgba(75, 122, 254, 1)', // Warna garis
                            tension: 0.2 // Membuat garis sedikit melengkung
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5 // Skor maksimal adalah 5
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
                ctx.parentElement.innerHTML = '<div class="flex items-center justify-center h-full text-sm text-red-500">Gagal memuat data grafik.</div>';
            });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>