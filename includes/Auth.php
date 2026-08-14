<?php
require_once __DIR__ . '/config.php';

// ============================================================
//  WEBDDS — Auth Helper
//  Manejo de sesión y permisos por rol
// ============================================================

// Roles disponibles en el sistema
define('ROL_ADMIN',     'admin');  
define('ROL_TECNICO',   'tecnico');    // Acceso total
define('ROL_GERENCIA',  'gerencia');   // Reportes, bitácora, estatus
define('ROL_ADMINISTRATIVO', 'administrativo'); // Crear reportes, ver clientes
define('ROL_VENDEDOR',  'vendedor');   // Agregar clientes, equipos

class Auth
{
    /**
     * Inicia sesión segura (llama esto en cada página protegida).
     */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica si hay un usuario logueado.
     * Si no, redirige al login.
     */
    public static function requerir(): void
    {
        self::iniciar();
        if (empty($_SESSION['usuario_id'])) {
            // Si es una petición AJAX/fetch, devolver JSON 401 en lugar de redirigir
            $esAjax = (
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_SERVER['HTTP_ACCEPT']) &&
                 str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            );

            if ($esAjax) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Sesión expirada. Inicia sesión de nuevo.', 'redirect' => BASE_PATH . '/login.php']);
                exit;
            }

            header('Location: ' . BASE_PATH . '/login.php');
            exit;
        }
    }

    /**
     * Verifica si el usuario tiene al menos uno de los roles indicados.
     * Uso: Auth::requerirRol([ROL_ADMIN, ROL_TECNICO]);
     */
    public static function requerirRol(array $rolesPermitidos): void
    {
        self::requerir();
        if (!in_array($_SESSION['usuario_rol'], $rolesPermitidos, true)) {
            http_response_code(403);
            die('Acceso denegado. No tienes permiso para ver esta página.');
        }
    }

    /**
     * Retorna el usuario activo como array o null.
     */
    public static function usuario(): ?array
    {
        self::iniciar();
        if (empty($_SESSION['usuario_id'])) return null;

        return [
            'id'     => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'rol'    => $_SESSION['usuario_rol'],
        ];
    }

    /**
     * Verifica si el usuario tiene un rol específico (sin redirigir).
     * Útil para mostrar/ocultar botones en la vista.
     */
    public static function tieneRol(string ...$roles): bool
    {
        self::iniciar();
        return in_array($_SESSION['usuario_rol'] ?? '', $roles, true);
    }

    /**
     * Cierra la sesión.
     */
    public static function cerrar(): void
    {
        self::iniciar();
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_PATH . '/login.php');
        exit;
    }
}
