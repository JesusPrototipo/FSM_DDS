<?php
// ============================================================
//  WEBDDS — api/perfil.php
//  Operaciones del usuario sobre su propia cuenta
//  POST accion=cambiar_password_propia
// ============================================================
require_once dirname(__DIR__) . '/includes/init.php';
Auth::requerir();

$pdo    = Database::get();
$accion = $_POST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($accion === 'cambiar_password_propia') {
        $usuario   = Auth::usuario();
        $actual    = $_POST['password_actual'] ?? '';
        $nueva     = $_POST['password_nueva']  ?? '';

        if (!$actual || !$nueva) {
            jsonResponse(['error' => 'Datos incompletos.'], 422);
        }
        if (strlen($nueva) < 8) {
            jsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres.'], 422);
        }

        // Verificar contraseña actual
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $usuario['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($actual, $row['password'])) {
            jsonResponse(['error' => 'La contraseña actual es incorrecta.'], 403);
        }

        $hash = password_hash($nueva, PASSWORD_BCRYPT);
        $upd  = $pdo->prepare("UPDATE usuarios SET password = :p WHERE id = :id");
        $upd->execute([':p' => $hash, ':id' => $usuario['id']]);

        jsonResponse(['ok' => true]);
    }

    jsonResponse(['error' => 'Acción no reconocida.'], 400);
}

jsonResponse(['error' => 'Método no permitido.'], 405);
