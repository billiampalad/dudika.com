
document.addEventListener('DOMContentLoaded', function () {
    // === ELEMEN DOM ===
    // Mengumpulkan semua elemen yang dibutuhkan di satu tempat untuk efisiensi.
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const dataMasterBtn = document.getElementById('dataMasterBtn');
    const dataMasterDropdown = document.getElementById('dataMasterDropdown');
    const dataMasterIcon = document.getElementById('dataMasterIcon');
    const darkModeToggle = document.getElementById('darkModeToggle');

    // Asumsi elemen modal ada di halaman tertentu (seperti di skrip asli)
    const addModal = document.getElementById('addModal');
    const fab = document.getElementById('fab');
    const addDataBtn = document.getElementById('addDataBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');

    // Variabel untuk menyimpan elemen yang aktif sebelum modal dibuka (untuk aksesibilitas)
    let activeElementBeforeModal;

    // === KONFIGURASI GLOBAL ===
    // Pengaturan global untuk SweetAlert2 dengan gaya Tailwind
    const swalWithTailwind = {
        customClass: {
            popup: 'p-4 sm:p-6 w-full max-w-sm rounded-lg shadow-lg',
            title: 'text-xl font-semibold text-gray-800',
            htmlContainer: 'mt-2 text-sm text-gray-600',
            actions: 'mt-4 sm:mt-6',
            confirmButton: 'px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
            cancelButton: 'ml-3 px-4 py-2 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
        },
        buttonsStyling: false
    };


    // === FUNGSI UMUM (UI & MODAL) ===
    window.togglePassword = function () {
        const passwordInput = document.getElementById("password");
        const toggleIcon = document.getElementById("toggleIcon");

        if (passwordInput && toggleIcon) {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            toggleIcon.classList.toggle("fa-eye");
            toggleIcon.classList.toggle("fa-eye-slash");
        }
    };

    // Fungsi modal generik untuk mengurangi duplikasi
    function showModal(modalEl, modalContentEl, focusEl, triggerElement) {
        if (!modalEl) return;
        activeElementBeforeModal = triggerElement || document.activeElement;
        modalEl.classList.remove('hidden');
        setTimeout(() => {
            modalContentEl.classList.remove('opacity-0', 'scale-95');
            if (focusEl) focusEl.focus();
        }, 10);
    }

    function closeModal(modalEl, modalContentEl) {
        if (!modalEl) return;
        modalContentEl.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            modalEl.classList.add('hidden');
            if (activeElementBeforeModal) activeElementBeforeModal.focus();
        }, 300);
    }

    // Fungsi UI dari skrip kedua
    const toggleSidebar = () => {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }
    };

    const applyTheme = (theme) => {
        const themeIcon = darkModeToggle ? darkModeToggle.querySelector('i') : null;
        if (!themeIcon) return;

        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
            localStorage.setItem('theme', 'light');
        }
    };


    // === FUNGSI AKSI DATA (CRUD) ===

    async function handleFetch(formData) {
        try {
            const response = await fetch('', { // Mengirim ke URL halaman saat ini
                method: 'POST',
                body: formData
            });
            if (!response.ok) {
                throw new Error(`Network response was not ok, status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Fetch error:', error);
            Swal.fire({
                ...swalWithTailwind,
                icon: 'error',
                title: 'Error Jaringan',
                text: 'Tidak dapat terhubung ke server. Periksa koneksi Anda.'
            });
            throw error;
        }
    }

    async function handleFormSubmit(event, form, modalCloser) {
        event.preventDefault();
        const formData = new FormData(form);
        const action = formData.get('action'); // 'add' atau 'update'

        try {
            const res = await handleFetch(formData);
            if (res.status === 'success') {
                modalCloser();
                await Swal.fire({
                    ...swalWithTailwind,
                    icon: 'success',
                    title: 'Berhasil!',
                    text: `Data berhasil di${action === 'add' ? 'tambahkan' : 'perbarui'}.`,
                    timer: 1500,
                    showConfirmButton: false
                });
                window.location.reload();
            } else {
                Swal.fire({
                    ...swalWithTailwind,
                    icon: 'error',
                    title: 'Gagal',
                    text: res.message || 'Terjadi kesalahan saat menyimpan data.'
                });
            }
        } catch (error) { /* Error sudah ditangani di handleFetch */ }
    }

    // Fungsi-fungsi ini sekarang bisa dipanggil dari HTML (misal: onclick="editData(this, 1)")
    window.editData = async function (triggerElement, id) {
        try {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id_mitra', id);
            const res = await handleFetch(formData);

            if (res.status === 'success') {
                const data = res.data;
                document.getElementById('edit_mitra_id').value = data.IdMitraDudika;
                document.getElementById('edit_txtNamaMitraDudika').value = data.txtNamaMitraDudika;
                document.getElementById('edit_txtAlamatMitra').value = data.txtAlamatMitra;
                document.getElementById('edit_txtEmailMitra').value = data.txtEmailMitra;
                document.getElementById('edit_txtNamaKepalaDudika').value = data.txtNamaKepalaDudika;
                showModal(editModal, editModalContent, document.getElementById('edit_txtNamaMitraDudika'), triggerElement);
            } else {
                Swal.fire({
                    ...swalWithTailwind,
                    icon: 'error',
                    title: 'Gagal Memuat',
                    text: res.message || 'Data tidak ditemukan.'
                });
            }
        } catch (error) { /* Error sudah ditangani di handleFetch */ }
    };

    window.deleteData = function (triggerElement, id) {
        const swalDeleteConfig = JSON.parse(JSON.stringify(swalWithTailwind)); // Deep copy
        swalDeleteConfig.customClass.confirmButton = `${swalWithTailwind.customClass.confirmButton} bg-red-600 hover:bg-red-700 focus:ring-red-500`;

        Swal.fire({
            ...swalDeleteConfig,
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id_mitra', id);
                    const res = await handleFetch(formData);

                    if (res.status === 'success') {
                        await Swal.fire({
                            ...swalWithTailwind,
                            icon: 'success',
                            title: 'Terhapus!',
                            text: 'Data berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        window.location.reload();
                    } else {
                        Swal.fire({
                            ...swalWithTailwind,
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: res.message || 'Terjadi kesalahan.'
                        });
                    }
                } catch (error) { /* Error sudah ditangani di handleFetch */ }
            }
        });
    };

    window.exportData = function () {
        Swal.fire({
            ...swalWithTailwind,
            title: 'Fungsi Dalam Pengembangan',
            text: 'Fitur export data ke Excel akan segera tersedia!',
            icon: 'info'
        });
    }

    // === EVENT LISTENERS UTAMA ===

    // --- Sidebar & UI Layout ---
    if (sidebarToggle) sidebarToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleSidebar();
    });
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleSidebar();
    });

    if (dataMasterBtn) {
        dataMasterBtn.addEventListener('click', () => {
            dataMasterDropdown.classList.toggle('hidden');
            dataMasterIcon.classList.toggle('rotated');
        });
    }

    // --- Notifikasi ---
    if (notificationBtn) {
        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (notificationDropdown) notificationDropdown.classList.toggle('hidden');
        });
    }

    // --- Modal Listeners ---
    if (fab) fab.addEventListener('click', (e) => {
        if (addDataForm) addDataForm.reset();
        showModal(addModal, addModalContent, document.getElementById('txtNamaMitraDudika'), e.currentTarget);
    });

    // Menutup modal dengan klik di background
    if (addModal) addModal.addEventListener('click', (e) => {
        if (e.target === addModal) closeModal(addModal, addModalContent);
    });
    if (editModal) editModal.addEventListener('click', (e) => {
        if (e.target === editModal) closeModal(editModal, editModalContent);
    });

    // --- Form Submit Listeners ---
    if (addDataForm) addDataForm.addEventListener('submit', (e) => handleFormSubmit(e, addDataForm, () => closeModal(addModal, addModalContent)));
    if (editDataForm) editDataForm.addEventListener('submit', (e) => handleFormSubmit(e, editDataForm, () => closeModal(editModal, editModalContent)));

    // --- Dark Mode ---
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', () => {
            const currentTheme = localStorage.getItem('theme') || 'light';
            applyTheme(currentTheme === 'light' ? 'dark' : 'light');
        });
        // Terapkan tema saat load
        applyTheme(localStorage.getItem('theme') || 'light');
    }

    // --- Global Listeners (Esc, Click Outside) ---
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (addModal && !addModal.classList.contains('hidden')) closeModal(addModal, addModalContent);
            if (editModal && !editModal.classList.contains('hidden')) closeModal(editModal, editModalContent);
        }
    });

    document.addEventListener('click', (event) => {
        // Menutup dropdown notifikasi
        if (notificationDropdown && !notificationDropdown.classList.contains('hidden') && !notificationBtn.contains(event.target)) {
            notificationDropdown.classList.add('hidden');
        }
    });

    // Membuat fungsi global agar bisa diakses dari `onclick` di HTML
    window.showModal = showModal;

    // --- Logika untuk Loading Overlay ---
    const loader = document.getElementById('loading-overlay');

    // Untuk form login (gunakan selector yang lebih spesifik jika perlu, misal: #loginForm)
    const loginForm = document.querySelector('form');
    if (loginForm && loader) {
        loginForm.addEventListener('submit', function () {
            loader.style.display = 'flex';
        });
    }

    // Untuk semua link navigasi
    document.querySelectorAll('a').forEach(link => {
        const href = link.getAttribute('href');
        const target = link.getAttribute('target');

        // Cek kondisi agar loader hanya muncul pada navigasi normal
        if (href && href !== '#' && !href.startsWith('javascript:') && target !== '_blank') {
            link.addEventListener('click', function () {
                loader.style.display = 'flex';
            });
        }
    });
});