<?php
    session_start();
    error_reporting(0);
    ini_set('display_errors', '0');

    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/controllers/BarangController.php';

    try {
        $controller = new BarangController(Database::connect());
        $controller->handle();
    } catch (Throwable $e) {
        http_response_code(500);
        echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:sans-serif;padding:2rem">'
        . '<h3>Aplikasi tidak dapat terhubung ke database</h3>'
        . '<p>Pastikan MySQL berjalan, database <code>db_inventaris</code> sudah diimpor '
        . '(<code>database.sql</code>), dan konfigurasi di <code>config/database.php</code> benar.</p></body>';
    }
?>