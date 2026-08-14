<?php
// ============================================================
//  WEBDDS — registro.php
//  Registro público de usuarios. Las cuentas quedan creadas con
//  activo = 0 (pendiente de aprobación). Un administrador debe
//  activarlas desde /pages/usuarios/lista.php para que puedan
//  iniciar sesión.
// ============================================================
require_once __DIR__ . '/includes/init.php';
Auth::iniciar();

// Si ya está logueado, ir al inicio
if (!empty($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

// Roles que un usuario puede solicitar al registrarse (nunca 'admin')
$rolesPermitidos = [
    'gerencia'    => 'Gerencia',
    'tecnico'    => 'Técnico',
    'administrativo' => 'Administrativo',
    'vendedor'   => 'Vendedor',
];

$error  = '';
$exito  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']    ?? '');
    $usuario   = trim($_POST['usuario']   ?? '');
    $rol       = trim($_POST['rol']       ?? '');
    $password  = $_POST['password']       ?? '';
    $confirmar = $_POST['confirmar']      ?? '';

    if (!$nombre || !$usuario || !$rol || !$password || !$confirmar) {
        $error = 'Completa todos los campos.';
    } elseif (!array_key_exists($rol, $rolesPermitidos)) {
        $error = 'Selecciona un rol válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $pdo = Database::get();

        // Verificar que el usuario no exista ya
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u");
        $check->execute([':u' => $usuario]);

        if ($check->fetch()) {
            $error = 'Ese nombre de usuario ya está en uso.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nombre, usuario, password, rol, activo)
                 VALUES (:nombre, :usuario, :password, :rol, 0)"
            );
            $stmt->execute([
                ':nombre'   => $nombre,
                ':usuario'  => $usuario,
                ':password' => $hash,
                ':rol'      => $rol,
            ]);
            $exito = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es-MX" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        body { display: flex; align-items: center; min-height: 100vh; }
        .registro-box { width: 100%; max-width: 420px; margin: auto; }
    </style>
</head>
<body class="bg-body-tertiary">

<div class="registro-box p-4">
    <div class="text-center mb-4">
        <i class="bi bi-person-plus fs-1 text-primary"></i>
        <h4 class="mt-2 fw-bold">Crear cuenta</h4>
        <p class="text-secondary small">Digital Document Services</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <?php if ($exito): ?>

                <div class="text-center py-2">
                    <i class="bi bi-check-circle text-success fs-1 mb-2 d-block"></i>
                    <h6 class="fw-bold mb-2">¡Cuenta creada correctamente!</h6>
                    <p class="text-secondary small mb-4">
                        Tu solicitud fue enviada. Un administrador debe
                        <strong>aprobar tu cuenta</strong> antes de que puedas
                        iniciar sesión.
                    </p>
                    <a href="<?= BASE_PATH ?>/login.php" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Ir a iniciar sesión
                    </a>
                </div>

            <?php else: ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info py-2 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Tu cuenta quedará <strong>pendiente de aprobación</strong>.
                    No podrás iniciar sesión hasta que un administrador la active.
                </div>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                   placeholder="Juan Pérez" required
                                   value="<?= e($_POST['nombre'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="usuario" name="usuario"
                                   placeholder="juan" autocomplete="username" required
                                   value="<?= e($_POST['usuario'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol solicitado</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="">Selecciona…</option>
                            <?php foreach ($rolesPermitidos as $valor => $label): ?>
                            <option value="<?= e($valor) ?>"
                                <?= (($_POST['rol'] ?? '') === $valor) ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Mínimo 8 caracteres" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmar" class="form-label">Confirmar contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="confirmar" name="confirmar"
                                   placeholder="Repite la contraseña" autocomplete="new-password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus me-1"></i> Crear cuenta
                    </button>
                </form>

                <p class="text-center text-secondary small mt-3">
                    ¿Ya tienes cuenta? <a href="<?= BASE_PATH ?>/login.php">Inicia sesión</a>
                </p>

            <?php endif; ?>

        </div>
    </div>

    <p class="text-center text-secondary small mt-3">
        © <?= date('Y') ?> Digital Document Services
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>
</html>