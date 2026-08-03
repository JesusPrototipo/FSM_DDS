<?php
require_once __DIR__ . '/includes/init.php';
Auth::iniciar();

// Si ya está logueado, ir al inicio
if (!empty($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = trim($_POST['usuario'] ?? '');
    $passInput    = $_POST['password'] ?? '';

    if ($usuarioInput && $passInput) {
        $pdo  = Database::get();
        $stmt = $pdo->prepare(
            "SELECT id, nombre, password, rol, activo FROM usuarios WHERE usuario = :usuario LIMIT 1"
        );
        $stmt->execute([':usuario' => $usuarioInput]);
        $user = $stmt->fetch();

        if ($user && $user['activo'] && password_verify($passInput, $user['password'])) {
            $_SESSION['usuario_id']     = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_rol']    = $user['rol'];
            header('Location: ' . BASE_PATH . '/index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $error = 'Completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es-MX" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        body { display: flex; align-items: center; min-height: 100vh; }
        .login-box { width: 100%; max-width: 380px; margin: auto; }
    </style>
</head>
<body class="bg-body-tertiary">

<div class="login-box p-4">
    <div class="text-center mb-4">
        <i class="bi bi-printer fs-1 text-primary"></i>
        <h4 class="mt-2 fw-bold">Digital Document Services</h4>
        <p class="text-secondary small">Portal de Servicio Técnico</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="usuario" name="usuario"
                               placeholder="Usuario" autocomplete="username" required
                               value="<?= e($_POST['usuario'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="••••••••" autocomplete="current-password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                </button>
            </form>
            <p class="text-center text-secondary small mt-3">
                    ¿No tienes una cuenta? <a href="error.php?code=404">Solicita una cuenta</a>
            </p>
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
