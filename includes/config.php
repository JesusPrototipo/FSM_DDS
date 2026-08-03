<?php
// ============================================================
//  WEBDDS — Configuración Central
//  Edita SOLO este archivo para cambiar entorno (local/prod)
// ============================================================

// --- Base de datos ---
define('DB_HOST',   'localhost');
define('DB_PORT',   '3306');
define('DB_NAME',   'digitaldocument');
define('DB_USER',   'root');
define('DB_PASS',   '');
define('DB_CHARSET','utf8mb4');

// --- Aplicación ---
define('APP_NAME',  'Portal DDS');
define('APP_URL',   'http://localhost/DDS2');   // Cambia esto en producción
define('APP_ROOT',  __DIR__ . '/..');             // Raíz del proyecto

// --- Rutas relativas (para href en HTML) ---
define('BASE_PATH', '/DDS2');                   // Prefijo de URLs en el navegador

// --- Zona horaria ---
date_default_timezone_set('America/Monterrey');

// --- Errores: true en desarrollo, false en producción ---
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
