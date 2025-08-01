<?php
include __DIR__ . '/config/koneksi.php';

// Hapus semua data session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: index.php");
exit();
?>