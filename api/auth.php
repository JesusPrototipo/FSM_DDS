<?php
// ============================================================
//  WEBDDS — api/auth.php
//  Endpoint JSON de autenticación para la app móvil Flutter
//
//  POST → login   (usuario, password)
//  GET  → check session
//  DELETE → logout
// ============================================================
require_once dirname(__DIR__) . '/includes/init.php';
Auth::iniciar();

$method = $_SERVER['REQUEST_METHOD'];

// ── Verificar sesión activa ───────────────────────────────────
if ($method === 'GET') {
    if (!empty($_SESSION['usuario_id'])) {
        jsonResponse([
            'ok'     => true,
            'id'     => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'],
            'rol'    => $_SESSION['usuario_rol'],
        ]);
    }
    jsonResponse(['error' => 'No autenticado.'], 401);
}

// ── Login ─────────────────────────────────────────────────────
if ($method === 'POST') {
    $usuario  = trim($_POST['usuario']  ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$usuario || !$password) {
        jsonResponse(['error' => 'Usuario y contraseña requeridos.'], 422);
    }

    $pdo  = Database::get();
    $stmt = $pdo->prepare(
        "SELECT id, nombre, password, rol, activo
         FROM usuarios WHERE usuario = :u LIMIT 1"
    );
    $stmt->execute([':u' => $usuario]);
    $user = $stmt->fetch();

    if ($user && $user['activo'] && password_verify($password, $user['password'])) {
        $_SESSION['usuario_id']     = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_rol']    = $user['rol'];
        jsonResponse([
            'ok'     => true,
            'id'     => (int)$user['id'],
            'nombre' => $user['nombre'],
            'rol'    => $user['rol'],
        ]);
    }

    jsonResponse(['error' => 'Usuario o contraseña incorrectos.'], 401);
}

// ── Logout ────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $_SESSION = [];
    session_destroy();
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Método no permitido.'], 405);
