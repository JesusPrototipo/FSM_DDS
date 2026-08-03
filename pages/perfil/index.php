<?php
// ============================================================
//  WEBDDS — pages/perfil/index.php
//  Cada usuario puede cambiar su propia contraseña
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerir();

$titulo_pagina = 'Mi Perfil';
$pagina_activa = '';
$subtitulo     = 'Configuración de cuenta';

require_once INCLUDES . '/header.php';

$usuario = Auth::usuario();
$pdo     = Database::get();

// Traer datos frescos del usuario actual
$stmt = $pdo->prepare(
    "SELECT id, nombre, usuario, rol, creado_en FROM usuarios WHERE id = :id"
);
$stmt->execute([':id' => $usuario['id']]);
$datos = $stmt->fetch();

$rolLabel = [
    'admin'      => ['Administrador', 'bg-danger'],
    'tecnico'    => ['Técnico',       'bg-primary'],
    'callcenter' => ['Call Center',   'bg-info text-dark'],
    'vendedor'   => ['Vendedor',      'bg-warning text-dark'],
];
[$rl, $rc] = $rolLabel[$datos['rol']] ?? [$datos['rol'], 'bg-secondary'];
?>

<div id="alerta-global" class="alert d-none mb-3"></div>

<div class="row g-3 justify-content-center">

    <!-- Info del usuario -->
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="bg-secondary rounded-circle d-inline-flex align-items-center
                            justify-content-center mb-3"
                     style="width:72px;height:72px;font-size:2rem">
                    <i class="bi bi-person-circle text-white"></i>
                </div>
                <h5 class="mb-1"><?= e($datos['nombre']) ?></h5>
                <p class="text-secondary small mb-2">
                    <code><?= e($datos['usuario']) ?></code>
                </p>
                <span class="badge <?= $rc ?>"><?= $rl ?></span>
                <p class="text-secondary small mt-3 mb-0">
                    Miembro desde <?= date('d/m/Y', strtotime($datos['creado_en'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Cambiar contraseña -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-key me-1"></i> Cambiar Contraseña
            </div>
            <div class="card-body">
                <div id="error-pass" class="alert alert-danger d-none"></div>
                <div id="ok-pass"    class="alert alert-success d-none"></div>

                <div class="mb-3">
                    <label class="form-label">Contraseña actual *</label>
                    <input type="password" class="form-control" id="pass-actual"
                           autocomplete="current-password"
                           placeholder="Tu contraseña actual">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña *</label>
                    <input type="password" class="form-control" id="pass-nueva"
                           autocomplete="new-password"
                           placeholder="Mínimo 8 caracteres">
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirmar nueva contraseña *</label>
                    <input type="password" class="form-control" id="pass-confirmar"
                           autocomplete="new-password"
                           placeholder="Repite la nueva contraseña">
                </div>
                <button class="btn btn-primary w-100" onclick="cambiarPassword()">
                    <i class="bi bi-check-circle me-1"></i> Actualizar Contraseña
                </button>
            </div>
        </div>
    </div>

</div>

<script>
async function cambiarPassword() {
    const actual     = document.getElementById('pass-actual').value;
    const nueva      = document.getElementById('pass-nueva').value;
    const confirmar  = document.getElementById('pass-confirmar').value;
    const errorEl    = document.getElementById('error-pass');
    const okEl       = document.getElementById('ok-pass');

    errorEl.classList.add('d-none');
    okEl.classList.add('d-none');

    if (!actual || !nueva || !confirmar) {
        errorEl.textContent = 'Completa todos los campos.';
        errorEl.classList.remove('d-none');
        return;
    }
    if (nueva.length < 8) {
        errorEl.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
        errorEl.classList.remove('d-none');
        return;
    }
    if (nueva !== confirmar) {
        errorEl.textContent = 'Las contraseñas nuevas no coinciden.';
        errorEl.classList.remove('d-none');
        return;
    }

    const form = new FormData();
    form.append('accion',          'cambiar_password_propia');
    form.append('password_actual', actual);
    form.append('password_nueva',  nueva);

    try {
        const res  = await fetch('<?= BASE_PATH ?>/api/perfil.php',
                                 { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) {
            errorEl.textContent = data.error || 'Error al actualizar.';
            errorEl.classList.remove('d-none');
            return;
        }
        okEl.textContent = '¡Contraseña actualizada correctamente!';
        okEl.classList.remove('d-none');
        ['pass-actual','pass-nueva','pass-confirmar']
            .forEach(id => document.getElementById(id).value = '');
    } catch(e) {
        errorEl.textContent = 'Error de conexión.';
        errorEl.classList.remove('d-none');
    }
}
</script>

<?php require_once INCLUDES . '/footer.php'; ?>
