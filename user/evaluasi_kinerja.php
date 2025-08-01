<main>
    <div class="mb-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="card-modern p-5 border-l-4 border-[var(--primary)] flex items-center justify-between">
                <div>
                    <p class="font-bold text-sm text-[var(--text-dark)]">Magang Mahasiswa Batch 4</p>
                    <p class="text-xs text-[var(--text-light)]">Periode: 01 Agu 2024 - 31 Jan 2025</p>
                </div>
                <button onclick="showEvaluationModal('Magang Mahasiswa Batch 4')" class="btn-primary-modern text-xs px-3 py-1.5">
                    <i class="fas fa-edit text-xs mr-1"></i>
                    <span>Isi Evaluasi</span>
                </button>

            </div>
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
                            <th class="px-4 py-3 text-center">Kepuasan</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <tr class="hover:bg-[var(--background)] transition-colors">
                            <td class="px-4 py-4 font-medium text-sm text-[var(--text-dark)]">Workshop Pengembangan SDM</td>
                            <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">5</td>
                            <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">4</td>
                            <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">5</td>
                            <td class="px-4 py-4 text-center">
                                <button onclick="showEvaluationModal('Workshop Pengembangan SDM', true)" class="p-2 rounded-md bg-[var(--accent)] text-[var(--primary)] hover:bg-[var(--primary)]/20 transition" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="hover:bg-[var(--background)] transition-colors">
                            <td class="px-4 py-4 font-medium text-sm text-[var(--text-dark)]">Magang Mahasiswa Batch 3</td>
                            <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">4</td>
                            <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">4</td>
                            <td class="px-4 py-4 text-center text-sm text-[var(--text-light)]">4</td>
                            <td class="px-4 py-4 text-center">
                                <button onclick="showEvaluationModal('Magang Mahasiswa Batch 3', true)" class="p-2 rounded-md bg-[var(--accent)] text-[var(--primary)] hover:bg-[var(--primary)]/20 transition" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="card-modern p-6">
                <h3 class="text-base text-center font-bold text-[var(--text-dark)] mb-4">Perkembangan Skor Anda</h3>
                <div class="h-80 flex items-center justify-center bg-[var(--background)] rounded-lg">
                    <i class="fas fa-chart-area fa-3x text-[var(--border)]"></i>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="evaluationModal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div id="evaluationModalContent" class="modal-modern w-full max-w-2xl transform transition-all duration-300 max-h-[90vh] flex flex-col text-sm sm:text-base">
        <div class="flex justify-between items-start p-4 sm:p-6 border-b border-[var(--border)]">
            <div>
                <h3 id="modalTitle" class="text-sm sm:text-base font-semibold text-[var(--text-dark)]">Evaluasi Program</h3>
                <p class="text-xs sm:text-sm text-[var(--text-light)]">Berikan skor (1-5) dan rekomendasi untuk perbaikan.</p>
            </div>
            <button onclick="closeEvaluationModal()" class="text-[var(--text-light)] hover:text-[var(--primary)] rounded-full w-8 h-8 flex items-center justify-center hover:bg-[var(--border)] transition text-base">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="evaluationForm" class="p-4 sm:p-6 space-y-5 sm:space-y-6 overflow-y-auto flex-grow bg-[var(--background)] text-sm">
            <div class="space-y-5 sm:space-y-6">
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Kesesuaian Rencana</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="star-rating flex text-xl sm:text-2xl text-yellow-400"></div>
                        <input type="range" name="txtSesuaiRencana" min="1" max="5" value="3" class="w-full range-slider">
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Kualitas Pelaksanaan</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="star-rating flex text-xl sm:text-2xl text-yellow-400"></div>
                        <input type="range" name="txtKualitasPelaks" min="1" max="5" value="3" class="w-full range-slider">
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-1">Kepuasan Pihak Terkait</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <div class="star-rating flex text-xl sm:text-2xl text-yellow-400"></div>
                        <input type="range" name="txtKepuasanPhkTerkait" min="1" max="5" value="3" class="w-full range-slider">
                    </div>
                </div>
            </div>

            <div>
                <label for="txtRekomUtkPerbaikan" class="block text-xs sm:text-sm font-medium text-[var(--text-light)] mb-2">Rekomendasi untuk Perbaikan</label>
                <textarea id="txtRekomUtkPerbaikan" name="txtRekomUtkPerbaikan" rows="4" placeholder="Tuliskan saran atau masukan Anda di sini..." class="form-modern w-full text-sm"></textarea>
            </div>

            <div id="modalFooter" class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-4 border-t border-[var(--border)]">
                <button type="button" onclick="closeEvaluationModal()" class="px-4 py-2 rounded-lg text-sm font-medium bg-[var(--border)] text-[var(--text-dark)] hover:opacity-80 transition">Tutup</button>
                <button type="submit" class="btn-primary-modern text-sm">Simpan Evaluasi</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Custom styling untuk range slider agar sesuai tema */
    .range-slider {
        -webkit-appearance: none;
        appearance: none;
        height: 8px;
        background: var(--border);
        border-radius: 5px;
        cursor: pointer;
        outline: none;
        width: 100%;
    }

    .range-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        background: var(--primary);
        border-radius: 50%;
        cursor: pointer;
        border: 3px solid var(--surface);
        transition: background 0.3s ease-in-out;
    }

    .range-slider::-moz-range-thumb {
        width: 14px;
        height: 14px;
        background: var(--primary);
        border-radius: 50%;
        cursor: pointer;
        border: 3px solid var(--surface);
    }

    .range-slider:hover::-webkit-slider-thumb {
        background: var(--primary-dark);
    }
</style>

<script>
    const evaluationModal = document.getElementById('evaluationModal');
    const modalContent = document.getElementById('evaluationModalContent');
    const modalTitle = document.getElementById('modalTitle');
    const evaluationForm = document.getElementById('evaluationForm');

    function showEvaluationModal(programName, isReadOnly = false) {
        if (!evaluationModal) return;
        modalTitle.textContent = isReadOnly ? `Detail Evaluasi: ${programName}` : `Evaluasi Program: ${programName}`;
        evaluationForm.reset();

        evaluationForm.querySelectorAll('input[type="range"]').forEach(updateStarsFromSlider);

        const formElements = evaluationForm.querySelectorAll('input, textarea, button[type="submit"]');
        formElements.forEach(el => {
            isReadOnly ? el.setAttribute('disabled', 'true') : el.removeAttribute('disabled');
        });

        evaluationModal.classList.remove('hidden');
    }

    function closeEvaluationModal() {
        if (evaluationModal) evaluationModal.classList.add('hidden');
    }

    function updateStarsFromSlider(slider) {
        const rating = slider.value;
        const starContainer = slider.previousElementSibling;
        starContainer.innerHTML = Array.from({
                length: 5
            }, (_, i) =>
            `<i class="fa-star ${i < rating ? 'fas' : 'far'}"></i>`
        ).join('');
    }

    if (evaluationForm) {
        evaluationForm.addEventListener('input', (e) => {
            if (e.target.matches('input[type="range"]')) {
                updateStarsFromSlider(e.target);
            }
        });
        evaluationForm.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Evaluasi berhasil disimpan!');
            closeEvaluationModal();
        });
    }
</script>