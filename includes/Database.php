<?php
require_once __DIR__ . '/config.php';

// ============================================================
//  WEBDDS — Clase Database (PDO Singleton)
//  Un solo punto de conexión para TODO el proyecto.
//  Reemplaza: Conexion.php, APIConexion.php, mysqli en guardar_datos.php
// ============================================================

class Database
{
    private static ?PDO $instance = null;

    // Privado: nadie puede hacer "new Database()"
    private function __construct() {}

    /**
     * Retorna siempre la misma conexión PDO.
     * Uso: $pdo = Database::get();
     */
    public static function get(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,  // Seguridad extra
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // En producción esto NO mostraría detalles al usuario
                if (DEBUG_MODE) {
                    die('Error de conexión: ' . $e->getMessage());
                } else {
                    die('Error interno del servidor. Contacta al administrador.');
                }
            }
        }

        return self::$instance;
    }

    // Evitar clonación y deserialización
    private function __clone() {}
    public function __wakeup() {}
}
