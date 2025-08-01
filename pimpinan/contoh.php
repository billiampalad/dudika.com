<div class="bg-white rounded-2xl p-4 md:p-6 shadow-lg space-y-4 md:space-y-6">

        <div class="flex flex-wrap justify-between items-center gap-4">

                <h2 class="text-xl font-bold text-gray-800">Data Master Jenis Kerjasama</h2>

                <button onclick="showAddModal()"

                        class="w-full sm:w-auto bg-gradient-to-r from-blue-500 to-cyan-400 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-400">

                        <i class="fas fa-plus fa-sm"></i>

                        <span class="text-sm font-medium">Tambah Jenis KS</span>

                    </button>

            </div>



        <div class="flex flex-col sm:flex-row sm:justify-between items-center gap-3">

                <div class="relative w-full sm:max-w-xs">

                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">

                                 <i class="fas fa-search text-gray-400"></i>

                            </div>

                        <input type="text" id="searchInput" placeholder="Cari jenis kerjasama..."

                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-transparent w-full text-sm">

                    </div>

                <button onclick="exportData()"

                        class="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-green-500 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center space-x-2 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">

                        <i class="fas fa-file-excel fa-sm"></i>

                        <span class="text-sm font-medium">Export Excel</span>

                    </button>

            </div>



        <div class="hidden sm:block overflow-x-auto border border-gray-200 rounded-lg">

                <table class="w-full min-w-max">

                        <thead class="bg-slate-50">

                                <tr class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">

                                        <th class="px-6 py-3 w-32">ID Jenis KS</th>

                                        <th class="px-6 py-3">Nama Jenis Kerjasama</th>

                                        <th class="px-6 py-3 text-center w-32">Aksi</th>

                                    </tr>

                            </thead>

                        <tbody id="tableBody" class="divide-y divide-gray-100">

                                <tr class="hover:bg-slate-50 transition-colors">

                                        <td class="px-6 py-4 text-sm font-mono text-gray-600">KS-001</td>

                                        <td class="px-6 py-4 text-sm font-medium text-gray-800">Magang Mahasiswa</td>

                                        <td class="px-6 py-4 whitespace-nowrap text-center">

                                                <div class="flex justify-center space-x-2">

                                                         <button onclick="editData('KS-001', 'Magang Mahasiswa')" title="Edit" aria-label="Edit data Magang Mahasiswa"

                                                                class="w-9 h-9 rounded-md text-gray-500 hover:bg-indigo-100 hover:text-indigo-600 transition-all">

                                                                <i class="fas fa-edit"></i>

                                                            </button>

                                                        <button onclick="deleteData('KS-001', false)" title="Hapus" aria-label="Hapus data Magang Mahasiswa"

                                                                class="w-9 h-9 rounded-md text-gray-500 hover:bg-red-100 hover:text-red-600 transition-all">

                                                                <i class="fas fa-trash"></i>

                                                            </button>

                                                    </div>

                                            </td>

                                    </tr>

                                </tbody>

                    </table>

            </div>



        <div class="block sm:hidden space-y-3" id="mobileCardContainer">

                <div class="bg-white border border-gray-200 rounded-lg p-4 flex justify-between items-center gap-3">

                        <div>

                                <h3 class="font-semibold text-gray-800 text-sm">Magang Mahasiswa</h3>

                                <p class="text-xs text-gray-500 font-mono mt-1">KS-001</p>

                            </div>

                        <div class="flex space-x-1 flex-shrink-0">

                                 <button onclick="editData('KS-001', 'Magang Mahasiswa')" title="Edit" aria-label="Edit data Magang Mahasiswa"

                                        class="w-9 h-9 rounded-md text-gray-500 hover:bg-indigo-100 hover:text-indigo-600 transition-all">

                                        <i class="fas fa-edit text-sm"></i>

                                    </button>

                                <button onclick="deleteData('KS-001', false)" title="Hapus" aria-label="Hapus data Magang Mahasiswa"

                                        class="w-9 h-9 rounded-md text-gray-500 hover:bg-red-100 hover:text-red-600 transition-all">

                                        <i class="fas fa-trash text-sm"></i>

                                    </button>

                            </div>

                    </div>

                </div>





        <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-2">

                <p class="text-sm text-gray-600 order-2 md:order-1">Menampilkan <strong>1-10</strong> dari <strong>50</strong> data</p>

                <div class="flex items-center space-x-1 order-1 md:order-2">

                         <button class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>

                        <button class="px-3 py-1.5 text-sm bg-gradient-to-r from-blue-500 to-cyan-400 text-white rounded-md shadow-sm">1</button>

                        <button class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-100">2</button>

                        <button class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-100">Next</button>

                    </div>

            </div>

</div>



<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center p-4"

        role="dialog" aria-modal="true" aria-labelledby="modalTitle">

        <div class="bg-white w-full max-w-md rounded-xl p-6 space-y-4 shadow-xl transform transition-all duration-300 ease-out scale-95 opacity-0 -translate-y-4" id="modalContent">

                <div class="flex justify-between items-center">

                        <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Tambah Jenis Kerjasama</h3>

                        <button onclick="closeAddModal()" class="text-gray-500 hover:text-red-500 text-3xl w-10 h-10 rounded-full hover:bg-gray-100 transition-colors flex items-center justify-center -mr-2 -mt-2">

                                &times;

                            </button>

                    </div>

                <form id="addDataForm" class="space-y-4">

                        <input type="hidden" id="IdJenisKS" name="IdJenisKS">

                        <div>

                                <label for="txtNamaJenisKS" class="block text-sm font-medium text-gray-700 mb-1.5">Nama Jenis Kerjasama</label>

                                <input type="text" id="txtNamaJenisKS" name="txtNamaJenisKS" required placeholder="Contoh: Penelitian Bersama"

                                        class="w-full text-sm px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-transparent">

                            </div>

                        <div class="flex justify-end space-x-3 pt-4">

                                <button type="button" onclick="closeAddModal()"

                                        class="w-full sm:w-auto px-5 py-2.5 bg-gray-100 rounded-lg hover:bg-gray-200 text-gray-800 text-sm font-medium transition-colors order-2 sm:order-1">Batal</button>

                                <button type="submit"

                                        class="w-full sm:w-auto px-5 py-2.5 bg-gradient-to-r from-blue-500 to-cyan-400 text-white rounded-lg text-sm font-medium shadow-md hover:opacity-90 transition-opacity order-1 sm:order-2">Simpan</button>

                            </div>

                    </form>

            </div>

</div>



<script>
    const addModal = document.getElementById('addModal');

    const modalContent = document.getElementById('modalContent');

    const modalTitle = document.getElementById('modalTitle');

    const dataForm = document.getElementById('addDataForm');



    function showAddModal() {

        dataForm.reset();

        modalTitle.textContent = 'Tambah Jenis Kerjasama';

        document.getElementById('IdJenisKS').value = '';

        addModal.classList.remove('hidden');

        setTimeout(() => {

            modalContent.classList.remove('opacity-0', 'scale-95');

        }, 10);

    }



    function closeAddModal() {

        modalContent.classList.add('opacity-0', 'scale-95');

        setTimeout(() => {

            addModal.classList.add('hidden');

        }, 300);

    }



    function exportData() {

        alert('Fungsi Export Data ke Excel akan diimplementasikan!');

    }



    function editData(id, currentName) {

        modalTitle.textContent = 'Edit Jenis Kerjasama';

        document.getElementById('IdJenisKS').value = id;

        document.getElementById('txtNamaJenisKS').value = currentName;

        showAddModal();

    }



    /**

     * Fungsi untuk menghapus data dengan pengecekan.

     * @param {string} id - ID dari jenis kerjasama yang akan dihapus.

     * @param {boolean} isInUse - Status apakah data ini digunakan di tabel lain.

     */

    function deleteData(id, isInUse) {

        if (isInUse) {

            alert(`Gagal menghapus! Jenis kerjasama dengan ID: ${id} sedang digunakan pada data kegiatan lainnya.`);

            return;

        }



        if (confirm(`Apakah Anda yakin ingin menghapus data dengan ID: ${id}?`)) {

            alert(`Data dengan ID: ${id} telah dihapus.`);

            // Lakukan permintaan AJAX untuk menghapus data dari server.

        }

    }



    // Event listener untuk menutup modal

    addModal.addEventListener('click', (event) => {

        if (event.target === addModal) {

            closeAddModal();

        }

    });



    window.addEventListener('keydown', (event) => {

        if (event.key === 'Escape' && !addModal.classList.contains('hidden')) {

            closeAddModal();

        }

    });
</script>