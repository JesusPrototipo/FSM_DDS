<?php
// ============================================================
//  WEBDDS — pages/usuarios/lista.php
//  Solo accesible para administradores
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerirRol([ROL_ADMIN]);

$titulo_pagina = 'Usuarios';
$pagina_activa = 'usuarios';
$subtitulo     = 'Gestión de usuarios y roles';

require_once INCLUDES . '/header.php';

$pdo      = Database::get();
$usuarios = $pdo->query(
    "SELECT id, nombre, usuario, rol, activo, creado_en
     FROM usuarios ORDER BY nombre"
)->fetchAll();

$rolLabel = [
    'admin'      => ['Admin',      'bg-danger'],
    'gerencia'   => ['Gerencia',   'bg-info'],
    'tecnico'    => ['Técnico',    'bg-primary'],
    'administrativo' => ['Administrativo', 'bg-primary'],
    'vendedor'   => ['Vendedor',   'bg-primary'],
];
$yo = Auth::usuario();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        Usuarios <span class="badge bg-secondary ms-1"><?= count($usuarios) ?></span>
    </h5>
    <button class="btn btn-primary btn-sm" onclick="abrirModalCrear()">
        <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
    </button>
</div>

<div id="alerta-global" class="alert d-none mb-3"></div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th style="width:130px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
            <?php [$rl, $rc] = $rolLabel[$u['rol']] ?? [$u['rol'], 'bg-secondary']; ?>
            <tr id="fila-<?= $u['id'] ?>" class="<?= !$u['activo'] ? 'text-secondary' : '' ?>">
                <td>
                    <?= e($u['nombre']) ?>
                    <?php if ($u['id'] == $yo['id']): ?>
                    <span class="badge bg-secondary ms-1 small">Tú</span>
                    <?php endif; ?>
                </td>
                <td><code><?= e($u['usuario']) ?></code></td>
                <td><span class="badge <?= $rc ?>"><?= $rl ?></span></td>
                <td>
                    <?php if ($u['activo']): ?>
                    <span class="badge bg-success">Activo</span>
                    <?php else: ?>
                    <span class="badge bg-warning">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td class="small text-secondary">
                    <?= date('d/m/Y', strtotime($u['creado_en'])) ?>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary"
                                title="Editar"
                                onclick='abrirModalEditar(<?= json_encode($u) ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning"
                                title="Cambiar contraseña"
                                onclick="abrirModalPassword(<?= $u['id'] ?>, '<?= e($u['nombre']) ?>')">
                            <i class="bi bi-key"></i>
                        </button>
                        <?php if ($u['id'] != $yo['id']): ?>
                        <button class="btn btn-sm <?= $u['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>"
                                onclick="toggleActivo(<?= $u['id'] ?>, this)">
                            <i class="bi <?= $u['activo'] ? 'bi-person-x' : 'bi-person-check' ?>"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Modal Crear Usuario ───────────────────────────────────── -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="error-crear" class="alert alert-danger d-none"></div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" class="form-control" id="c-nombre"
                               placeholder="Juan Pérez">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario *</label>
                        <input type="text" class="form-control" id="c-usuario"
                               placeholder="juan.perez" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rol *</label>
                        <select class="form-select" id="c-rol">
                            <option value="">Selecciona…</option>
                            <option value="gerencia">Gerencia</option>
                            <option value="tecnico">Técnico</option>
                            <option value="administrativo">Administrativo</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Contraseña * (mín. 8 caracteres)</label>
                        <input type="password" class="form-control" id="c-password"
                               autocomplete="new-password">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary"
                        id="btn-crear" onclick="crearUsuario()">
                    <i class="bi bi-check me-1"></i> Crear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Editar Usuario ──────────────────────────────────── -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-1"></i> Editar Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="error-editar" class="alert alert-danger d-none"></div>
                <input type="hidden" id="e-id">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nombre completo *</label>
                        <input type="text" class="form-control" id="e-nombre">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Usuario *</label>
                        <input type="text" class="form-control" id="e-usuario">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rol *</label>
                        <select class="form-select" id="e-rol">
                            <option value="tecnico">Técnico</option>
                            <option value="gerencia">Gerencia</option>
                            <option value="administrativo">Administrativo</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary"
                        onclick="editarUsuario()">
                    <i class="bi bi-check me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Cambiar Contraseña ──────────────────────────────── -->
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-key me-1"></i> Nueva Contraseña
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="error-password" class="alert alert-danger d-none"></div>
                <input type="hidden" id="p-id">
                <p class="small text-secondary mb-2">
                    Usuario: <strong id="p-nombre"></strong>
                </p>
                <label class="form-label">Nueva contraseña *</label>
                <input type="password" class="form-control" id="p-password"
                       placeholder="Mínimo 8 caracteres" autocomplete="new-password">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning"
                        onclick="cambiarPassword()">
                    <i class="bi bi-check me-1"></i> Cambiar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const API = '<?= BASE_PATH ?>/api/usuarios.php';

// ── Helpers ──────────────────────────────────────────────────
function mostrarAlerta(tipo, msg) {
    const el = document.getElementById('alerta-global');
    el.className = `alert alert-${tipo}`;
    el.textContent = msg;
    setTimeout(() => el.className = 'alert d-none', 5000);
}
function ocultarError(id) {
    document.getElementById(id).classList.add('d-none');
}
function mostrarError(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.classList.remove('d-none');
}
async function post(datos) {
    const form = new FormData();
    Object.entries(datos).forEach(([k,v]) => form.append(k, v ?? ''));
    const res  = await fetch(API, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Error desconocido');
    return data;
}
function cerrarModal(id) {
    bootstrap.Modal.getInstance(document.getElementById(id))?.hide();
}

// ── Crear ─────────────────────────────────────────────────────
function abrirModalCrear() {
    ['c-nombre','c-usuario','c-password'].forEach(id =>
        document.getElementById(id).value = '');
    document.getElementById('c-rol').value = '';
    ocultarError('error-crear');
    new bootstrap.Modal(document.getElementById('modalCrear')).show();
}
async function crearUsuario() {
    ocultarError('error-crear');
    try {
        await post({
            accion:   'crear',
            nombre:   document.getElementById('c-nombre').value.trim(),
            usuario:  document.getElementById('c-usuario').value.trim(),
            password: document.getElementById('c-password').value,
            rol:      document.getElementById('c-rol').value,
        });
        cerrarModal('modalCrear');
        mostrarAlerta('success', 'Usuario creado correctamente.');
        setTimeout(() => location.reload(), 1000);
    } catch(e) { mostrarError('error-crear', e.message); }
}

// ── Editar ────────────────────────────────────────────────────
function abrirModalEditar(u) {
    document.getElementById('e-id').value      = u.id;
    document.getElementById('e-nombre').value  = u.nombre;
    document.getElementById('e-usuario').value = u.usuario;
    document.getElementById('e-rol').value     = u.rol;
    ocultarError('error-editar');
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
async function editarUsuario() {
    ocultarError('error-editar');
    try {
        await post({
            accion:  'actualizar',
            id:      document.getElementById('e-id').value,
            nombre:  document.getElementById('e-nombre').value.trim(),
            usuario: document.getElementById('e-usuario').value.trim(),
            rol:     document.getElementById('e-rol').value,
        });
        cerrarModal('modalEditar');
        mostrarAlerta('success', 'Usuario actualizado.');
        setTimeout(() => location.reload(), 1000);
    } catch(e) { mostrarError('error-editar', e.message); }
}

// ── Contraseña ────────────────────────────────────────────────
function abrirModalPassword(id, nombre) {
    document.getElementById('p-id').value     = id;
    document.getElementById('p-nombre').textContent = nombre;
    document.getElementById('p-password').value = '';
    ocultarError('error-password');
    new bootstrap.Modal(document.getElementById('modalPassword')).show();
}
async function cambiarPassword() {
    ocultarError('error-password');
    try {
        await post({
            accion:   'cambiar_password',
            id:       document.getElementById('p-id').value,
            password: document.getElementById('p-password').value,
        });
        cerrarModal('modalPassword');
        mostrarAlerta('success', 'Contraseña actualizada correctamente.');
    } catch(e) { mostrarError('error-password', e.message); }
}

// ── Toggle Activo ─────────────────────────────────────────────
async function toggleActivo(id, btn) {
    try {
        const data = await post({ accion: 'toggle_activo', id });
        const fila  = document.getElementById(`fila-${id}`);
        const activo = data.activo === 1;
        fila.classList.toggle('text-secondary', !activo);
        // Actualizar badge
        const badgeEstado = fila.querySelector('td:nth-child(4) .badge');
        badgeEstado.className = `badge ${activo ? 'bg-success' : 'bg-secondary'}`;
        badgeEstado.textContent = activo ? 'Activo' : 'Inactivo';
        // Actualizar botón
        btn.className = `btn btn-sm ${activo ? 'btn-outline-danger' : 'btn-outline-success'}`;
        btn.title     = activo ? 'Desactivar' : 'Activar';
        btn.querySelector('i').className = `bi ${activo ? 'bi-person-x' : 'bi-person-check'}`;
        mostrarAlerta('success', `Usuario ${activo ? 'activado' : 'desactivado'}.`);
    } catch(e) { mostrarAlerta('danger', e.message); }
}
</script>

<?php require_once INCLUDES . '/footer.php'; ?>
