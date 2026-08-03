<?php
// ============================================================
//  WEBDDS — pages/clientes/detalle.php
//  Reemplaza: InfoClientes.php + EditarC.php
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerir();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_PATH . '/pages/clientes/lista.php');
    exit;
}

// Cargar datos vía la misma lógica de la API (consulta directa, sin HTTP)
$pdo = Database::get();

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->execute([':id' => $id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: ' . BASE_PATH . '/pages/clientes/lista.php');
    exit;
}

$titulo_pagina = e($cliente['razon']);
$pagina_activa = 'clientes';
$subtitulo     = 'Detalle del cliente';

require_once INCLUDES . '/header.php';

// Departamentos + equipos
$stmt = $pdo->prepare(
    "SELECT d.id AS depto_id, d.departamento,
            i.id AS equipo_id, i.marca, i.modelo, i.serie, i.status
     FROM departamentos d
     LEFT JOIN impresoras i ON i.departamento_id = d.id
     WHERE d.cliente_id = :id
     ORDER BY d.departamento, i.modelo"
);
$stmt->execute([':id' => $id]);
$filas = $stmt->fetchAll();

$deptos = [];
foreach ($filas as $f) {
    $did = $f['depto_id'];
    if (!isset($deptos[$did])) {
        $deptos[$did] = ['id' => $did, 'nombre' => $f['departamento'], 'equipos' => []];
    }
    if ($f['equipo_id']) {
        $deptos[$did]['equipos'][] = $f;
    }
}

// Historial de reportes
$stmt = $pdo->prepare(
    "SELECT r.id, r.fecha, r.falla, r.estatus,
            d.departamento, i.modelo, i.marca,
            u.nombre AS tecnico_nombre
     FROM reportes_fallas r
     LEFT JOIN departamentos d ON r.id_departamento = d.id
     LEFT JOIN impresoras   i ON r.id_impresora    = i.id
     LEFT JOIN usuarios     u ON r.tecnico_id      = u.id
     WHERE r.id_cliente = :id
     ORDER BY r.id DESC
     LIMIT 20"
);
$stmt->execute([':id' => $id]);
$reportes = $stmt->fetchAll();

$badgeClass = [
    'pendiente'  => 'badge-pendiente',
    'en proceso' => 'badge-en-proceso',
    'finalizado' => 'badge-finalizado',
    'cancelado'  => 'badge-cancelado',
];
?>

<!-- Botones de acción superiores -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_PATH ?>/pages/clientes/lista.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Regresar
    </a>
    <div class="d-flex gap-2">
        <?php if (Auth::tieneRol(ROL_ADMIN, ROL_CALLCENTER, ROL_TECNICO)): ?>
        <a href="<?= BASE_PATH ?>/pages/reportes/nuevo.php?cliente_id=<?= $id ?>"
           class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Reporte
        </a>
        <?php endif; ?>
        <?php if (Auth::tieneRol(ROL_ADMIN, ROL_VENDEDOR, ROL_CALLCENTER)): ?>
        <button class="btn btn-outline-warning btn-sm" onclick="abrirEdicion()">
            <i class="bi bi-pencil me-1"></i> Editar
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ── Datos del cliente ─────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person me-1"></i> <strong><?= e($cliente['razon']) ?></strong></span>
        <span class="text-secondary small"># <?= $cliente['id'] ?></span>
    </div>
    <div class="card-body">
        <!-- Vista lectura -->
        <div id="vista-lectura">
            <div class="row g-2">
                <div class="col-md-6">
                    <span class="text-secondary small">Contacto</span>
                    <div><?= e($cliente['reporto']) ?: '—' ?></div>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary small">Teléfono</span>
                    <div><?= e($cliente['telefono']) ?: '—' ?></div>
                </div>
                <div class="col-12">
                    <span class="text-secondary small">Dirección</span>
                    <div><?= e($cliente['direccion']) ?: '—' ?></div>
                </div>
                <div class="col-md-6">
                    <span class="text-secondary small">Horario</span>
                    <div><?= e($cliente['horario']) ?: '—' ?></div>
                </div>
            </div>
        </div>

        <!-- Vista edición (oculta por defecto) -->
        <div id="vista-edicion" class="d-none">
            <div id="error-edicion" class="alert alert-danger d-none"></div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label form-label-sm">Razón Social</label>
                    <input type="text" class="form-control form-control-sm" id="edit-razon"
                           value="<?= e($cliente['razon']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label form-label-sm">Contacto</label>
                    <input type="text" class="form-control form-control-sm" id="edit-reporto"
                           value="<?= e($cliente['reporto']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label form-label-sm">Dirección</label>
                    <input type="text" class="form-control form-control-sm" id="edit-direccion"
                           value="<?= e($cliente['direccion']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Teléfono</label>
                    <input type="tel" class="form-control form-control-sm" id="edit-telefono"
                           value="<?= e($cliente['telefono']) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label form-label-sm">Horario</label>
                    <input type="text" class="form-control form-control-sm" id="edit-horario"
                           value="<?= e($cliente['horario']) ?>">
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-success btn-sm" onclick="guardarEdicion()">
                    <i class="bi bi-check me-1"></i> Guardar
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="cancelarEdicion()">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Departamentos y equipos ───────────────────────────── -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-building me-1"></i> Departamentos y Equipos</span>
        <?php if (Auth::tieneRol(ROL_ADMIN, ROL_VENDEDOR, ROL_CALLCENTER)): ?>
        <a href="<?= BASE_PATH ?>/pages/clientes/agregar_depto.php?cliente_id=<?= $id ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus"></i> Agregar
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($deptos)): ?>
        <p class="text-secondary text-center py-3 mb-0">
            Sin departamentos registrados.
        </p>
        <?php else: ?>
        <div class="accordion accordion-flush" id="accordionDeptos">
            <?php foreach ($deptos as $d): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#depto-<?= $d['id'] ?>">
                        <i class="bi bi-building me-2 text-secondary"></i>
                        <span id="nombre-depto-<?= $d['id'] ?>"><?= e($d['nombre']) ?></span>
                        <span class="badge bg-secondary ms-2">
                            <?= count($d['equipos']) ?> equipo<?= count($d['equipos']) !== 1 ? 's' : '' ?>
                        </span>
                    </button>
                    <?php if (Auth::tieneRol(ROL_ADMIN, ROL_CALLCENTER, ROL_VENDEDOR)): ?>
                    <button class="btn btn-sm btn-outline-warning ms-2 me-3"
                            style="z-index:10; position:relative;"
                            title="Editar nombre del departamento"
                            onclick="abrirEditarDepto(<?= $d['id'] ?>, '<?= e($d['nombre']) ?>'); event.stopPropagation();">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <?php endif; ?>
                </h2>
                <div id="depto-<?= $d['id'] ?>" class="accordion-collapse collapse">
                    <div class="accordion-body p-0">
                        <?php if (empty($d['equipos'])): ?>
                        <p class="text-secondary small text-center py-2 mb-0">Sin equipos.</p>
                        <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Serie</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($d['equipos'] as $eq): ?>
                            <tr>
                                <td><?= e($eq['marca']) ?></td>
                                <td><?= e($eq['modelo']) ?></td>
                                <td><code><?= e($eq['serie'] ?: '—') ?></code></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= e($eq['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_PATH ?>/pages/reportes/nuevo.php?equipo_id=<?= $eq['equipo_id'] ?>&cliente_id=<?= $id ?>"
                                       class="btn btn-xs btn-outline-primary"
                                       title="Nuevo reporte para este equipo">
                                        <i class="bi bi-file-plus"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Historial de reportes ─────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history me-1"></i> Historial de Reportes
    </div>
    <?php if (empty($reportes)): ?>
    <div class="card-body text-secondary text-center py-3">
        Sin reportes registrados para este cliente.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Depto</th>
                    <th>Equipo</th>
                    <th>Falla</th>
                    <th>Técnico</th>
                    <th>Estatus</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reportes as $r): ?>
            <tr>
                <td class="text-secondary small"><?= $r['id'] ?></td>
                <td class="small"><?= e($r['fecha']) ?></td>
                <td><?= e($r['departamento']) ?></td>
                <td><?= e($r['marca'] . ' ' . $r['modelo']) ?></td>
                <td class="small text-truncate" style="max-width:180px" title="<?= e($r['falla']) ?>">
                    <?= e($r['falla']) ?>
                </td>
                <td><?= e($r['tecnico_nombre'] ?? '—') ?></td>
                <td>
                    <span class="badge <?= $badgeClass[$r['estatus']] ?? 'bg-secondary' ?>">
                        <?= e($r['estatus']) ?>
                    </span>
                </td>
                <td>
                    <a href="<?= BASE_PATH ?>/pages/reportes/detalle.php?id=<?= $r['id'] ?>"
                       class="btn btn-xs btn-outline-secondary">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal editar departamento -->
<div class="modal fade" id="modalEditarDepto" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-1"></i> Editar Departamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="error-depto-modal" class="alert alert-danger d-none"></div>
                <input type="hidden" id="modal-depto-id">
                <label class="form-label">Nombre del departamento *</label>
                <input type="text" class="form-control" id="modal-depto-nombre"
                       placeholder="Ej: Oficinas, Recepción…">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning"
                        id="btn-guardar-depto" onclick="guardarDepto()">
                    <i class="bi bi-check me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const API     = '<?= BASE_PATH ?>/api/clientes.php';
const CLI_ID  = <?= $id ?>;

function abrirEdicion() {
    document.getElementById('vista-lectura').classList.add('d-none');
    document.getElementById('vista-edicion').classList.remove('d-none');
}
function cancelarEdicion() {
    document.getElementById('vista-edicion').classList.add('d-none');
    document.getElementById('vista-lectura').classList.remove('d-none');
    document.getElementById('error-edicion').classList.add('d-none');
}
// ── Editar departamento ──────────────────────────────────────
function abrirEditarDepto(id, nombre) {
    document.getElementById('modal-depto-id').value     = id;
    document.getElementById('modal-depto-nombre').value = nombre;
    document.getElementById('error-depto-modal').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalEditarDepto')).show();
}

async function guardarDepto() {
    const id     = document.getElementById('modal-depto-id').value;
    const nombre = document.getElementById('modal-depto-nombre').value.trim();
    const errEl  = document.getElementById('error-depto-modal');
    errEl.classList.add('d-none');

    if (!nombre) {
        errEl.textContent = 'El nombre no puede estar vacío.';
        errEl.classList.remove('d-none');
        return;
    }

    const btn = document.getElementById('btn-guardar-depto');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    const form = new FormData();
    form.append('accion',        'actualizar_depto');
    form.append('id',            id);
    form.append('departamento',  nombre);

    try {
        const res  = await fetch(API, {
            method: 'POST',
            body:   form,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (!res.ok) {
            errEl.textContent = data.error || 'Error al guardar.';
            errEl.classList.remove('d-none');
            return;
        }

        // Actualizar el nombre en el acordeón sin recargar la página
        const span = document.getElementById(`nombre-depto-${id}`);
        if (span) span.textContent = nombre;

        bootstrap.Modal.getInstance(
            document.getElementById('modalEditarDepto')
        ).hide();

    } catch(e) {
        errEl.textContent = 'Error de conexión.';
        errEl.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check me-1"></i> Guardar';
    }
}

async function guardarEdicion() {
    const form = new FormData();
    form.append('accion',    'actualizar_cliente');
    form.append('id',        CLI_ID);
    form.append('razon',     document.getElementById('edit-razon').value.trim());
    form.append('reporto',   document.getElementById('edit-reporto').value.trim());
    form.append('direccion', document.getElementById('edit-direccion').value.trim());
    form.append('telefono',  document.getElementById('edit-telefono').value.trim());
    form.append('horario',   document.getElementById('edit-horario').value.trim());

    try {
        const res  = await fetch(API, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('error-edicion').textContent = data.error || 'Error al guardar.';
            document.getElementById('error-edicion').classList.remove('d-none');
            return;
        }
        // Recargar para mostrar datos actualizados
        location.reload();
    } catch(e) {
        document.getElementById('error-edicion').textContent = 'Error de conexión.';
        document.getElementById('error-edicion').classList.remove('d-none');
    }
}
</script>

<?php require_once INCLUDES . '/footer.php'; ?>
