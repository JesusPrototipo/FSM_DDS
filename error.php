<?php
require_once __DIR__ . '/includes/init.php';

// No se requiere autenticación para mostrar errores

// Obtener código y mensaje de error desde la URL
$errorCode = isset($_GET['code']) ? (int)$_GET['code'] : 503;
$customMessage = isset($_GET['message']) ? trim($_GET['message']) : '';

// Mensajes predefinidos por código de error
$defaultMessages = [
    400 => 'La solicitud no pudo ser procesada por el servidor.',
    401 => 'No has iniciado sesión o la sesión ha expirado.',
    403 => 'No tienes permiso para acceder a esta sección.',
    404 => 'La pagina esta siendo construida, contacta con el Administrador.',
    405 => 'Método de solicitud no permitido.',
    500 => 'Ocurrió un error interno en el servidor.',
    503 => 'El servidor no está disponible. Intenta más tarde.'
];

// Definir mensaje final
if (!empty($customMessage)) {
    $errorMessage = $customMessage;
} elseif (array_key_exists($errorCode, $defaultMessages)) {
    $errorMessage = $defaultMessages[$errorCode];
} else {
    $errorMessage = 'Algo salió mal. Por favor intenta de nuevo más tarde.';
}

// Establecer el código de respuesta HTTP (si es válido)
$validHttpCodes = [400, 401, 403, 404, 405, 500, 503];
if (in_array($errorCode, $validHttpCodes)) {
    http_response_code($errorCode);
} else {
    http_response_code(404);
    $errorCode = 404; // Normalizar para mostrar
}

// Título dinámico
$pageTitle = "Error $errorCode — " . APP_NAME;
?>
<!DOCTYPE html>
<html lang="es-MX" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            min-height: 100vh;
        }
        .error-box {
            width: 100%;
            max-width: 480px;
            margin: auto;
        }
        .error-icon {
            font-size: 4rem;
        }
    </style>
</head>
<body class="bg-body-tertiary">

<div class="error-box p-4">
   

    <div class="card shadow-sm">
        <div class="card-body p-4 text-center">
            <!-- Icono grande de error -->
            <div class="error-icon mb-3">
                <?php if ($errorCode === 404): ?>
                    <i class="bi bi-question-octagon text-warning"></i>
                <?php elseif ($errorCode === 403): ?>
                    <i class="bi bi-shield-lock-fill text-danger"></i>
                <?php elseif ($errorCode === 500): ?>
                    <i class="bi bi-bug-fill text-danger"></i>
                <?php else: ?>
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                <?php endif; ?>
            </div>

            <!-- Código de error y mensaje -->
            <h2 class="fw-bold mb-2">Error <?= (int)$errorCode ?></h2>
            <p class="text-secondary mb-4"><?= e($errorMessage) ?></p>

            <!-- Separador sutil -->
            <hr class="my-3">

            <!-- Botones de acción (mismo estilo que botón "Entrar" del login) -->
            <div class="d-grid gap-2">
                <a href="<?= BASE_PATH ?>/index.php" class="btn btn-primary">
                    <i class="bi bi-house-door me-1"></i> Ir al inicio
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver atrás
                </button>
            </div>

            <!-- Ayuda adicional para errores comunes -->
            <?php if ($errorCode === 401 || $errorCode === 403): ?>
                <div class="alert alert-warning mt-4 mb-0 py-2 small">
                    <i class="bi bi-info-circle"></i> ¿Necesitas ayuda?
                    <a href="#" class="alert-link">Contacta con soporte</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mismo pie de página que login.php -->
    <p class="text-center text-secondary small mt-3">
        © <?= date('Y') ?> Digital Document Services
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</body>
</html>