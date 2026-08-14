<?php
// ============================================================
//  WEBDDS — api/usuarios.php
//  Solo accesible por administradores
//
//  GET  ?accion=lista              → todos los usuarios
//  GET  ?accion=detalle&id=N       → un usuario
//  POST accion=crear               → nuevo usuario
//  POST accion=actualizar          → editar usuario
//  POST accion=cambiar_password    → nueva contraseña
//  POST accion=toggle_activo       → activar / desactivar
// ============================================================

require_once dirname(__DIR__) . '/includes/init.php';
Auth::requerirRol([ROL_ADMIN]);   // Toda esta API es solo para admin

$pdo    = Database::get();
$accion = $_REQUEST['accion'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────────────────────────
if ($method === 'GET') {

    if ($accion === 'lista') {
        $stmt = $pdo->query(
            "SELECT id, nombre, usuario, rol, activo, creado_en
             FROM usuarios ORDER BY nombre"
        );
        jsonResponse($stmt->fetchAll());
    }

    if ($accion === 'detalle') {
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare(
            "SELECT id, nombre, usuario, rol, activo, creado_en
             FROM usuarios WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $u = $stmt->fetch();
        if (!$u) jsonResponse(['error' => 'Usuario no encontrado.'], 404);
        jsonResponse($u);
    }

    jsonResponse(['error' => 'Acción no reconocida.'], 400);
}

// ── POST ─────────────────────────────────────────────────────
if ($method === 'POST') {

    // ── Crear usuario ────────────────────────────────────────
    if ($accion === 'crear') {
        $nombre  = trim($_POST['nombre']  ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $pass    = trim($_POST['password'] ?? '');
        $rol     = trim($_POST['rol']     ?? '');

        $rolesValidos = ['admin' , 'gerencia', 'tecnico', 'administrativo', 'vendedor'];
        if (!$nombre || !$usuario || !$pass || !in_array($rol, $rolesValidos, true)) {
            jsonResponse(['error' => 'Todos los campos son obligatorios.'], 422);
        }
        if (strlen($pass) < 8) {
            jsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres.'], 422);
        }

        // Verificar usuario único
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u");
        $check->execute([':u' => $usuario]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'Ese nombre de usuario ya existe.'], 409);
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nombre, usuario, password, rol)
             VALUES (:nombre, :usuario, :password, :rol)"
        );
        $stmt->execute([
            ':nombre'   => $nombre,
            ':usuario'  => $usuario,
            ':password' => $hash,
            ':rol'      => $rol,
        ]);
        jsonResponse(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    // ── Actualizar usuario ───────────────────────────────────
    if ($accion === 'actualizar') {
        $id      = (int)($_POST['id']     ?? 0);
        $nombre  = trim($_POST['nombre']  ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $rol     = trim($_POST['rol']     ?? '');

        $rolesValidos = ['admin' , 'gerencia', 'tecnico', 'administrativo', 'vendedor'];
        if (!$id || !$nombre || !$usuario || !in_array($rol, $rolesValidos, true)) {
            jsonResponse(['error' => 'Datos incompletos.'], 422);
        }

        // Verificar que el usuario no esté tomado por otro
        $check = $pdo->prepare(
            "SELECT id FROM usuarios WHERE usuario = :u AND id != :id"
        );
        $check->execute([':u' => $usuario, ':id' => $id]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'Ese nombre de usuario ya lo usa otra persona.'], 409);
        }

        $stmt = $pdo->prepare(
            "UPDATE usuarios SET nombre=:nombre, usuario=:usuario, rol=:rol WHERE id=:id"
        );
        $stmt->execute([
            ':nombre'  => $nombre,
            ':usuario' => $usuario,
            ':rol'     => $rol,
            ':id'      => $id,
        ]);
        jsonResponse(['ok' => true]);
    }

    // ── Cambiar contraseña ───────────────────────────────────
    if ($accion === 'cambiar_password') {
        $id   = (int)($_POST['id']       ?? 0);
        $pass = trim($_POST['password'] ?? '');

        if (!$id || strlen($pass) < 8) {
            jsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres.'], 422);
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            "UPDATE usuarios SET password=:password WHERE id=:id"
        );
        $stmt->execute([':password' => $hash, ':id' => $id]);
        jsonResponse(['ok' => true]);
    }

    // ── Activar / Desactivar usuario ─────────────────────────
    if ($accion === 'toggle_activo') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 400);

        // No permitir desactivarse a sí mismo
        $yo = Auth::usuario();
        if ($yo['id'] == $id) {
            jsonResponse(['error' => 'No puedes desactivar tu propio usuario.'], 422);
        }

        $stmt = $pdo->prepare(
            "UPDATE usuarios SET activo = IF(activo=1, 0, 1) WHERE id=:id"
        );
        $stmt->execute([':id' => $id]);

        // Retornar el nuevo estado
        $nuevo = $pdo->prepare("SELECT activo FROM usuarios WHERE id=:id");
        $nuevo->execute([':id' => $id]);
        jsonResponse(['ok' => true, 'activo' => (int)$nuevo->fetchColumn()]);
    }

    jsonResponse(['error' => 'Acción no reconocida.'], 400);
}

jsonResponse(['error' => 'Método no permitido.'], 405);
