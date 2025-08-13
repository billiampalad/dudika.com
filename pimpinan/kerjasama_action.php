<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

ob_start();

try {
    header('Content-Type: application/json');
    require_once '../config/koneksi.php';

    // Validasi method request
    $method = $_SERVER['REQUEST_METHOD'];

    // Router sederhana untuk aksi
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get_dependencies':
            get_dependencies($koneksi);
            break;
        case 'get_single':
            get_single_kerjasama($koneksi);
            break;
        case 'add':
            if ($method !== 'POST') {
                throw new Exception('Method harus POST');
            }
            add_kerjasama($koneksi);
            break;
        case 'update':
            if ($method !== 'POST') {
                throw new Exception('Method harus POST');
            }
            update_kerjasama($koneksi);
            break;
        case 'delete':
            if ($method !== 'POST') {
                throw new Exception('Method harus POST');
            }
            delete_kerjasama($koneksi);
            break;
        case 'search':
            search_kerjasama($koneksi);
            break;
        default:
            throw new Exception('Aksi tidak valid.');
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($koneksi) && $koneksi) {
        $koneksi->close();
    }
    ob_end_flush();
}

function get_dependencies($koneksi)
{
    // Ambil data untuk dropdown
    $jenis_ks = $koneksi->query("SELECT IdJenisKS, txtNamaJenisKS FROM tbljenisks ORDER BY txtNamaJenisKS")->fetch_all(MYSQLI_ASSOC);
    $mitra = $koneksi->query("SELECT IdMitraDudika, txtNamaMitraDudika FROM tblmitradudika ORDER BY txtNamaMitraDudika")->fetch_all(MYSQLI_ASSOC);
    $unit = $koneksi->query("SELECT IdUnitPelaksana, txtNamaUnitPelaksPolimdo FROM tblunitpelaksana ORDER BY txtNamaUnitPelaksPolimdo")->fetch_all(MYSQLI_ASSOC);
    $users = $koneksi->query("SELECT nik, nama_lengkap as txtNamaUser FROM tbluser ORDER BY nama_lengkap")->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'status' => 'success',
        'jenis_ks' => $jenis_ks,
        'mitra' => $mitra,
        'unit' => $unit,
        'users' => $users
    ]);
}

function get_single_kerjasama($koneksi)
{
    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        throw new Exception('ID tidak ditemukan.');
    }

    // Ambil data utama
    // PERBAIKAN: Mengubah INNER JOIN menjadi LEFT JOIN untuk tblunitpelaksana agar lebih handal
    $stmt = $koneksi->prepare("
        SELECT k.*, j.txtNamaJenisKS, m.txtNamaMitraDudika, u.txtNamaUnitPelaksPolimdo, usr.nama_lengkap as txtNamaUser,
               CASE 
                   WHEN k.dtMulaiPelaksanaan IS NOT NULL AND k.dtSelesaiPelaksanaan IS NOT NULL 
                   THEN CONCAT(DATE_FORMAT(k.dtMulaiPelaksanaan, '%d %b %Y'), ' - ', DATE_FORMAT(k.dtSelesaiPelaksanaan, '%d %b %Y'))
                   ELSE 'Periode belum ditentukan'
               END AS txtPeriodePelaksanaan,
               d.pathFoto
        FROM tblnamakegiatanks k
        LEFT JOIN tbljenisks j ON k.IdJenisKS = j.IdJenisKS
        LEFT JOIN tblmitradudika m ON k.IdMitraDudika = m.IdMitraDudika
        LEFT JOIN tblunitpelaksana u ON k.IdUnitPelaksana = u.IdUnitPelaksana
        LEFT JOIN tbluser usr ON k.nik = usr.nik
        LEFT JOIN tbldokumentasi d ON k.idDokumentasi = d.idDokumentasi
        WHERE k.IdKKS = ?
    ");
    if (!$stmt) {
        throw new Exception('Prepare statement gagal: ' . $koneksi->error);
    }

    $stmt->bind_param('s', $id);
    if (!$stmt->execute()) {
        throw new Exception('Query gagal: ' . $stmt->error);
    }

    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$info) {
        throw new Exception('Data dengan ID ' . htmlspecialchars($id) . ' tidak ditemukan.');
    }

    // Ambil data tujuan
    $stmt_tujuan = $koneksi->prepare("SELECT * FROM tbltujuanks WHERE IdKKS = ?");
    $stmt_tujuan->bind_param('s', $id);
    $stmt_tujuan->execute();
    $tujuan = $stmt_tujuan->get_result()->fetch_assoc();
    $stmt_tujuan->close();

    // Ambil data pelaksanaan
    $stmt_pelaksanaan = $koneksi->prepare("SELECT * FROM tblpelaksanaankeg WHERE IdKKS = ?");
    $stmt_pelaksanaan->bind_param('s', $id);
    $stmt_pelaksanaan->execute();
    $pelaksanaan = $stmt_pelaksanaan->get_result()->fetch_assoc();
    $stmt_pelaksanaan->close();

    // Handle path file untuk dikonversi ke base64
    // PERBAIKAN: Menggunakan path absolut untuk memastikan file_exists dan file_get_contents berfungsi benar
    $base_path = dirname(__DIR__); // Asumsi folder 'pimpinan' sejajar dengan 'uploads'
    $full_path = !empty($info['pathFoto']) ? str_replace('../', $base_path . '/', $info['pathFoto']) : '';

    if (!empty($full_path) && file_exists($full_path)) {
        $info['pathFoto_base64'] = base64_encode(file_get_contents($full_path));
    } else {
        $info['pathFoto_base64'] = null;
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'info' => $info,
            'tujuan' => $tujuan,
            'pelaksanaan' => $pelaksanaan
        ]
    ]);
}

function add_kerjasama($koneksi)
{
    // Validasi input yang lebih lengkap
    $required_fields = [
        'txtNamaKegiatanKS' => 'Nama Kegiatan',
        'IdJenisKS' => 'Jenis Kerjasama',
        'IdMitraDudika' => 'Mitra/DUDIKA',
        'IdUnitPelaksana' => 'Unit Pelaksana',
        'dtMulaiPelaksanaan' => 'Tanggal Mulai',
        'dtSelesaiPelaksanaan' => 'Tanggal Selesai',
        'nik' => 'Penanggung Jawab'
    ];

    $errors = [];
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = "Field $label harus diisi.";
        }
    }

    // Validasi format tanggal
    if (!empty($_POST['dtMulaiPelaksanaan']) && !empty($_POST['dtSelesaiPelaksanaan'])) {
        $start_date = DateTime::createFromFormat('Y-m-d', $_POST['dtMulaiPelaksanaan']);
        $end_date = DateTime::createFromFormat('Y-m-d', $_POST['dtSelesaiPelaksanaan']);

        if (!$start_date || !$end_date) {
            $errors[] = "Format tanggal tidak valid. Gunakan format YYYY-MM-DD.";
        } elseif ($start_date > $end_date) {
            $errors[] = "Tanggal mulai tidak boleh lebih besar dari tanggal selesai.";
        }
    }

    if (!empty($errors)) {
        throw new Exception(implode(" ", $errors));
    }

    // Mulai transaction
    $koneksi->begin_transaction();

    try {
        // 1. Generate ID untuk kerja sama (tblnamakegiatanks)
        $result_kks = $koneksi->query("SELECT MAX(CAST(SUBSTRING(IdKKS, 4) AS UNSIGNED)) as max_id FROM tblnamakegiatanks");
        $row = $result_kks->fetch_assoc();
        $next_id_kks = ($row['max_id'] ?? 0) + 1;
        $new_id_kks = 'KKS' . str_pad($next_id_kks, 3, '0', STR_PAD_LEFT);

        // 2. Generate ID untuk dokumentasi (tbldokumentasi)
        $result_doc = $koneksi->query("SELECT MAX(CAST(SUBSTRING(idDokumentasi, 4) AS UNSIGNED)) as max_id FROM tbldokumentasi");
        $row_doc = $result_doc->fetch_assoc();
        $next_id_doc = ($row_doc['max_id'] ?? 0) + 1;
        $new_doc_id = 'DOC' . str_pad($next_id_doc, 3, '0', STR_PAD_LEFT);

        // 3. Handle file upload jika ada
        $photo_path = '';
        if (!empty($_FILES['pathFoto']['name']) && $_FILES['pathFoto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/dokumentasi/';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception('Gagal membuat direktori upload.');
                }
            }

            // Validasi file upload
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($_FILES['pathFoto']['tmp_name']);

            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Jenis file tidak diizinkan. Hanya file gambar (JPEG, PNG, GIF) yang diperbolehkan.');
            }

            // Batasi ukuran file (misal 2MB)
            if ($_FILES['pathFoto']['size'] > 2097152) {
                throw new Exception('Ukuran file terlalu besar. Maksimal 2MB.');
            }

            $fileName = 'doc_' . $new_id_kks . '_' . time() . '.' . pathinfo($_FILES['pathFoto']['name'], PATHINFO_EXTENSION);
            $photo_path = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['pathFoto']['tmp_name'], $photo_path)) {
                throw new Exception('Gagal menyimpan file yang diupload.');
            }
        }

        // 4. Buat record dokumentasi
        $stmt_doc = $koneksi->prepare("INSERT INTO tbldokumentasi (idDokumentasi, pathFoto) VALUES (?, ?)");
        if (!$stmt_doc) {
            throw new Exception('Prepare statement dokumentasi gagal: ' . $koneksi->error);
        }

        $stmt_doc->bind_param('ss', $new_doc_id, $photo_path);
        if (!$stmt_doc->execute()) {
            throw new Exception('Gagal membuat record dokumentasi: ' . $stmt_doc->error);
        }
        $stmt_doc->close();

        // 5. Tambah data kerjasama
        $stmt_kks = $koneksi->prepare("
            INSERT INTO tblnamakegiatanks (
                IdKKS, txtNamaKegiatanKS, IdJenisKS, IdMitraDudika, IdUnitPelaksana,
                dtMulaiPelaksanaan, dtSelesaiPelaksanaan, txtNomorMOU, dtMOU, idDokumentasi, nik
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt_kks) {
            throw new Exception('Prepare statement kerjasama gagal: ' . $koneksi->error);
        }

        // Handle nilai null untuk field opsional
        $nomorMOU = !empty($_POST['txtNomorMOU']) ? $_POST['txtNomorMOU'] : null;
        $dtMOU = !empty($_POST['dtMOU']) ? $_POST['dtMOU'] : null;

        $stmt_kks->bind_param(
            'sssssssssss',
            $new_id_kks,
            $_POST['txtNamaKegiatanKS'],
            $_POST['IdJenisKS'],
            $_POST['IdMitraDudika'],
            $_POST['IdUnitPelaksana'],
            $_POST['dtMulaiPelaksanaan'],
            $_POST['dtSelesaiPelaksanaan'],
            $nomorMOU,
            $dtMOU,
            $new_doc_id,
            $_POST['nik']
        );

        if (!$stmt_kks->execute()) {
            throw new Exception('Gagal menambahkan data kerja sama: ' . $stmt_kks->error);
        }
        $stmt_kks->close();

        // Commit transaction
        $koneksi->commit();

        echo json_encode([
            'status' => 'success',
            'id' => $new_id_kks,
            'message' => 'Data berhasil ditambahkan'
        ]);
    } catch (Exception $e) {
        // Rollback jika ada error
        $koneksi->rollback();

        // Hapus file yang mungkin sudah terupload jika terjadi error
        if (!empty($photo_path) && file_exists($photo_path)) {
            unlink($photo_path);
        }

        throw new Exception('Gagal menambahkan data: ' . $e->getMessage());
    }
}

function update_kerjasama($koneksi)
{
    $id = $_POST['IdKKS'] ?? '';
    if (empty($id)) {
        throw new Exception('ID tidak valid.');
    }

    // Validasi input
    $required_fields = ['txtNamaKegiatanKS', 'IdJenisKS', 'IdMitraDudika', 'IdUnitPelaksana', 'dtMulaiPelaksanaan', 'dtSelesaiPelaksanaan', 'nik'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field $field harus diisi.");
        }
    }

    $koneksi->begin_transaction();

    try {
        // 1. Update data utama pada tabel tblnamakegiatanks
        $stmt_update_main = $koneksi->prepare("
            UPDATE tblnamakegiatanks SET 
                txtNamaKegiatanKS = ?, 
                IdJenisKS = ?, 
                IdMitraDudika = ?, 
                IdUnitPelaksana = ?,
                dtMulaiPelaksanaan = ?, 
                dtSelesaiPelaksanaan = ?, 
                txtNomorMOU = ?, 
                dtMOU = ?,
                nik = ?
            WHERE IdKKS = ?
        ");
        $stmt_update_main->bind_param(
            'ssssssssss',
            $_POST['txtNamaKegiatanKS'],
            $_POST['IdJenisKS'],
            $_POST['IdMitraDudika'],
            $_POST['IdUnitPelaksana'],
            $_POST['dtMulaiPelaksanaan'],
            $_POST['dtSelesaiPelaksanaan'],
            $_POST['txtNomorMOU'],
            $_POST['dtMOU'],
            $_POST['nik'],
            $id
        );
        if (!$stmt_update_main->execute()) {
            throw new Exception('Gagal update data utama: ' . $stmt_update_main->error);
        }
        $stmt_update_main->close();

        // 2. Logika penanganan upload file jika ada file baru yang diunggah
        if (isset($_FILES['pathFoto']) && $_FILES['pathFoto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/dokumentasi/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = 'doc_' . $id . '_' . time() . '.' . pathinfo($_FILES['pathFoto']['name'], PATHINFO_EXTENSION);
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['pathFoto']['tmp_name'], $filePath)) {
                throw new Exception('Gagal memindahkan file yang di-upload.');
            }

            // Dapatkan idDokumentasi yang ada saat ini dari tblnamakegiatanks
            $stmt_get_doc = $koneksi->prepare("SELECT idDokumentasi FROM tblnamakegiatanks WHERE IdKKS = ?");
            $stmt_get_doc->bind_param('s', $id);
            $stmt_get_doc->execute();
            $doc_result = $stmt_get_doc->get_result()->fetch_assoc();
            $doc_id = $doc_result['idDokumentasi'] ?? null;
            $stmt_get_doc->close();

            if ($doc_id) {
                // KASUS 1: Dokumentasi sudah ada. Update path foto dan hapus file lama.
                $stmt_get_old_path = $koneksi->prepare("SELECT pathFoto FROM tbldokumentasi WHERE idDokumentasi = ?");
                $stmt_get_old_path->bind_param('s', $doc_id);
                $stmt_get_old_path->execute();
                $old_path_result = $stmt_get_old_path->get_result()->fetch_assoc();
                $old_file_path = $old_path_result['pathFoto'] ?? '';
                $stmt_get_old_path->close();

                // Hapus file lama dari server jika ada
                if (!empty($old_file_path) && file_exists($old_file_path)) {
                    unlink($old_file_path);
                }

                // Update path foto yang baru di tabel tbldokumentasi
                $stmt_update_doc = $koneksi->prepare("UPDATE tbldokumentasi SET pathFoto = ? WHERE idDokumentasi = ?");
                $stmt_update_doc->bind_param('ss', $filePath, $doc_id);
                $stmt_update_doc->execute();
                $stmt_update_doc->close();
            } else {
                // KASUS 2: Dokumentasi belum ada. Buat record baru dan tautkan ke data kerja sama.
                // Generate ID dokumentasi baru
                $result = $koneksi->query("SELECT MAX(CAST(SUBSTRING(idDokumentasi, 4) AS UNSIGNED)) as max_id FROM tbldokumentasi");
                $next_id_num = ($result->fetch_assoc()['max_id'] ?? 0) + 1;
                $new_doc_id = 'DOC' . str_pad($next_id_num, 3, '0', STR_PAD_LEFT);

                // Masukkan record dokumentasi baru
                $stmt_insert_doc = $koneksi->prepare("INSERT INTO tbldokumentasi (idDokumentasi, pathFoto) VALUES (?, ?)");
                $stmt_insert_doc->bind_param('ss', $new_doc_id, $filePath);
                $stmt_insert_doc->execute();
                $stmt_insert_doc->close();

                // Update tabel tblnamakegiatanks untuk menautkan idDokumentasi yang baru
                $stmt_link_doc = $koneksi->prepare("UPDATE tblnamakegiatanks SET idDokumentasi = ? WHERE IdKKS = ?");
                $stmt_link_doc->bind_param('ss', $new_doc_id, $id);
                $stmt_link_doc->execute();
                $stmt_link_doc->close();
            }
        }

        $koneksi->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diupdate']);
    } catch (Exception $e) {
        $koneksi->rollback();
        // Jika terjadi error, hapus file yang mungkin sudah terlanjur di-upload
        if (isset($filePath) && file_exists($filePath)) {
            unlink($filePath);
        }
        throw new Exception('Gagal update data: ' . $e->getMessage());
    }
}

function delete_kerjasama($koneksi)
{
    $id = $_POST['IdKKS'] ?? '';
    if (empty($id)) {
        throw new Exception('ID tidak valid.');
    }

    // Cek apakah ID digunakan di tabel lain
    $check_tables = ['tblhasildancapaian', 'tblevaluasikinerja', 'tblpelaksanaankeg', 'tblpermasalahandansolusi', 'tbltujuanks'];
    foreach ($check_tables as $table) {
        $check_sql = $koneksi->prepare("SELECT COUNT(*) as count FROM $table WHERE IdKKS = ?");
        if ($check_sql) {
            $check_sql->bind_param("s", $id);
            $check_sql->execute();
            $result = $check_sql->get_result();
            $is_in_use = $result->fetch_assoc()['count'] > 0;
            $check_sql->close();

            if ($is_in_use) {
                throw new Exception('Data kerjasama sedang digunakan dan tidak dapat dihapus.');
            }
        }
    }

    // Mulai transaction
    $koneksi->begin_transaction();

    try {
        // Ambil idDokumentasi untuk dihapus juga
        $stmt = $koneksi->prepare("SELECT idDokumentasi FROM tblnamakegiatanks WHERE IdKKS = ?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $doc_id = $data['idDokumentasi'] ?? null;
        $stmt->close();

        // 1. Hapus data kerjasama terlebih dahulu
        $stmt = $koneksi->prepare("DELETE FROM tblnamakegiatanks WHERE IdKKS = ?");
        $stmt->bind_param('s', $id);
        if (!$stmt->execute()) {
            throw new Exception('Gagal menghapus kerjasama: ' . $stmt->error);
        }
        $stmt->close();

        // 2. Hapus dokumentasi jika ada
        if ($doc_id) {
            // Get file path first to delete the file
            $file_stmt = $koneksi->prepare("SELECT pathFoto FROM tbldokumentasi WHERE idDokumentasi = ?");
            $file_stmt->bind_param('s', $doc_id);
            $file_stmt->execute();
            $file_result = $file_stmt->get_result();
            $file_data = $file_result->fetch_assoc();
            $file_path = $file_data['pathFoto'] ?? '';
            $file_stmt->close();

            // Delete file if exists
            if (!empty($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }

            // Delete record
            $stmt = $koneksi->prepare("DELETE FROM tbldokumentasi WHERE idDokumentasi = ?");
            $stmt->bind_param('s', $doc_id);
            if (!$stmt->execute()) {
                throw new Exception('Gagal menghapus dokumentasi: ' . $stmt->error);
            }
            $stmt->close();
        }

        // Commit transaction
        $koneksi->commit();

        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
    } catch (Exception $e) {
        // Rollback jika ada error
        $koneksi->rollback();
        throw new Exception('Gagal menghapus data: ' . $e->getMessage());
    }
}

function search_kerjasama($koneksi)
{
    // Ambil parameter dari GET request
    $keyword = $_GET['search'] ?? '';
    $jenis = $_GET['jenis'] ?? '';
    $status_filter = $_GET['status'] ?? '';

    // Query dasar
    $sql = "
        SELECT 
            k.IdKKS, 
            k.txtNamaKegiatanKS, 
            m.txtNamaMitraDudika, 
            j.txtNamaJenisKS,
            u.nama_lengkap as txtNamaUser,
            CASE 
                WHEN k.dtMulaiPelaksanaan IS NOT NULL AND k.dtSelesaiPelaksanaan IS NOT NULL 
                THEN CONCAT(DATE_FORMAT(k.dtMulaiPelaksanaan, '%d %b %Y'), ' - ', DATE_FORMAT(k.dtSelesaiPelaksanaan, '%d %b %Y'))
                ELSE 'Periode belum ditentukan'
            END AS txtPeriodePelaksanaan,
            d.pathFoto,
            (CASE
                WHEN k.dtSelesaiPelaksanaan < CURDATE() THEN 'Selesai'
                WHEN CURDATE() BETWEEN k.dtMulaiPelaksanaan AND k.dtSelesaiPelaksanaan AND k.dtSelesaiPelaksanaan <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Akan Berakhir'
                WHEN CURDATE() BETWEEN k.dtMulaiPelaksanaan AND k.dtSelesaiPelaksanaan THEN 'Aktif'
                WHEN k.dtMulaiPelaksanaan > CURDATE() THEN 'Belum Mulai'
                ELSE 'Tidak Diketahui'
            END) AS status
        FROM tblnamakegiatanks k
        JOIN tblmitradudika m ON k.IdMitraDudika = m.IdMitraDudika
        JOIN tbljenisks j ON k.IdJenisKS = j.IdJenisKS
        LEFT JOIN tbluser u ON k.nik = u.nik
        LEFT JOIN tbldokumentasi d ON k.idDokumentasi = d.idDokumentasi
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    // Filter pencarian keyword
    if (!empty($keyword)) {
        $sql .= " AND (k.txtNamaKegiatanKS LIKE ? OR m.txtNamaMitraDudika LIKE ? OR k.txtNomorMOU LIKE ?)";
        $searchTerm = "%$keyword%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
        $types .= 'sss';
    }

    // Filter jenis kerjasama
    if (!empty($jenis)) {
        $sql .= " AND k.IdJenisKS = ?";
        $params[] = $jenis;
        $types .= 's';
    }

    // Filter status (gunakan HAVING karena status adalah hasil perhitungan)
    if (!empty($status_filter)) {
        $status_mapping = [
            'aktif' => 'Aktif',
            'selesai' => 'Selesai',
            'akan_berakhir' => 'Akan Berakhir',
            'belum_mulai' => 'Belum Mulai'
        ];

        if (array_key_exists($status_filter, $status_mapping)) {
            $sql .= " HAVING status = ?";
            $params[] = $status_mapping[$status_filter];
            $types .= 's';
        }
    }

    // Tambahkan pengurutan default
    $sql .= " ORDER BY k.dtMOU DESC";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare statement gagal: ' . $koneksi->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception('Query gagal: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $style = getStatusStyle($row['status']);

        $row['status_class'] = $style['class'];
        $row['status_icon_class'] = $style['icon_class'];

        // Handle file path untuk base64
        if (!empty($row['pathFoto']) && file_exists($row['pathFoto'])) {
            $row['pathFoto_base64'] = base64_encode(file_get_contents($row['pathFoto']));
        } else {
            $row['pathFoto_base64'] = null;
        }

        $data[] = $row;
    }

    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'total' => count($data)
    ]);
}

// Fungsi helper untuk style status
function getStatusStyle($status_text)
{
    $styles = [
        'Selesai' => ['class' => 'bg-gray-100 text-gray-800', 'icon_class' => 'text-gray-500'],
        'Aktif' => ['class' => 'bg-green-100 text-green-800', 'icon_class' => 'text-green-500'],
        'Akan Berakhir' => ['class' => 'bg-orange-100 text-orange-800', 'icon_class' => 'text-orange-500'],
        'Belum Mulai' => ['class' => 'bg-blue-100 text-blue-800', 'icon_class' => 'text-blue-500'],
        'Tidak Diketahui' => ['class' => 'bg-gray-100 text-gray-800', 'icon_class' => 'text-gray-500']
    ];

    return $styles[$status_text] ?? $styles['Tidak Diketahui'];
}
