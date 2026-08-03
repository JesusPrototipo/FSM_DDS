<?php
// ============================================================
//  WEBDDS — Bootstrap del proyecto
//  Incluir en CADA página: require_once '/ruta/absoluta/a/init.php';
//  O mejor: define la ruta en cada archivo así:
//    require_once dirname(__DIR__, N) . '/includes/init.php';
//  Donde N es cuántos niveles subir hasta la raíz.
// ============================================================

// Ruta absoluta a /includes (para usar en cualquier archivo)
define('INCLUDES', __DIR__);
define('ROOT',     dirname(__DIR__));
define('PAGES',    ROOT . '/pages');
define('API_DIR',  ROOT . '/api');

// Cargar configuración
require_once INCLUDES . '/config.php';

// Cargar clases base
require_once INCLUDES . '/Database.php';
require_once INCLUDES . '/Auth.php';

// Función helper global para sanitizar salidas HTML
// Uso: e($variable) en lugar de htmlspecialchars($variable, ...)
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Función helper para respuestas JSON en las APIs
function jsonResponse(mixed $data, int $httpCode = 200): never {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
