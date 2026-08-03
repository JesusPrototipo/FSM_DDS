<?php
// ============================================================
//  WEBDDS — pages/reportes/detalle.php
//  Gestión completa: ver, cambiar estatus, bitácora, reasignar
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerir();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_PATH . '/pages/reportes/lista.php');
    exit;
}

$pdo = Database::get();

$stmt = $pdo->prepare(
    "SELECT r.*,
            c.razon, c.reporto AS contacto, c.telefono, c.direccion, c.horario,
            d.departamento,
            i.marca, i.modelo, i.serie, i.status AS equipo_status,
            u.nombre AS tecnico_nombre
     FROM reportes_fallas r
     INNER JOIN clientes      c ON r.id_cliente      = c.id
     INNER JOIN departamentos d ON r.id_departamento = d.id
     INNER JOIN impresoras    i ON r.id_impresora    = i.id
     LEFT  JOIN usuarios      u ON r.tecnico_id      = u.id
     WHERE r.id = :id"
);
$stmt->execute([':id' => $id]);
$r = $stmt->fetch();

if (!$r) {
    header('Location: ' . BASE_PATH . '/pages/reportes/lista.php');
    exit;
}

$tecnicos = $pdo->query(
    "SELECT id, nombre FROM usuarios
     WHERE activo=1 AND rol IN ('admin','tecnico') ORDER BY nombre"
)->fetchAll();

$titulo_pagina = 'Reporte #' . $id;
$pagina_activa = 'reportes';
$subtitulo     = 'Detalle de Orden de Servicio';

require_once INCLUDES . '/header.php';

$badgeClass = [
    'pendiente'  => 'badge-pendiente',
    'en proceso' => 'badge-en-proceso',
    'finalizado' => 'badge-finalizado',
    'cancelado'  => 'badge-cancelado',
];

$bitacora = array_filter(
    array_map('trim', explode("\n", $r['observaciones'] ?? '')),
    fn($l) => $l !== ''
);
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <a href="<?= BASE_PATH ?>/pages/reportes/lista.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Regresar
    </a>
    <div class="d-flex gap-2">
        <a href="<?= BASE_PATH ?>/pages/reportes/pdf.php?id=<?= $id ?>"
           target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i> Ver PDF
        </a>
        <a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=<?= $r['id_cliente'] ?>"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-person me-1"></i> Ver Cliente
        </a>
    </div>
</div>

<div id="alerta-global" class="alert d-none mb-3"></div>

<div class="row g-3">

    <!-- ── Columna izquierda ────────────────────────────────── -->
    <div class="col-lg-8">

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">
                    <i class="bi bi-file-text me-1"></i>
                    Folio #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?>
                </span>
                <span class="badge fs-6 <?= $badgeClass[$r['estatus']] ?? 'bg-secondary' ?>">
                    <?= e($r['estatus']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="text-secondary small mb-1">Cliente</p>
                        <p class="fw-semibold mb-0">
                            <a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=<?= $r['id_cliente'] ?>">
                                <?= e($r['razon']) ?>
                            </a>
                        </p>
                        <p class="small text-secondary mb-0"><?= e($r['departamento']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-secondary small mb-1">Contacto</p>
                        <p class="mb-0"><?= e($r['contacto']) ?></p>
                        <p class="small text-secondary mb-0">
                            <i class="bi bi-telephone me-1"></i><?= e($r['telefono']) ?>
                            &nbsp;·&nbsp;
                            <i class="bi bi-clock me-1"></i><?= e($r['horario']) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-secondary small mb-1">Equipo</p>
                        <p class="mb-0 fw-semibold"><?= e($r['marca'] . ' ' . $r['modelo']) ?></p>
                        <p class="small text-secondary mb-0">
                            Serie: <code><?= e($r['serie'] ?: '—') ?></code>
                            &nbsp;·&nbsp;
                            <span class="badge bg-secondary"><?= e($r['equipo_status']) ?></span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-secondary small mb-1">Fecha / Técnico</p>
                        <p class="mb-0"><?= e($r['fecha']) ?></p>
                        <p class="small text-secondary mb-0">
                            <i class="bi bi-person-badge me-1"></i>
                            <?= e($r['tecnico_nombre'] ?? 'Sin asignar') ?>
                        </p>
                    </div>
                    <!-- Tipo de Servicio -->
                    <?php if (!empty($r['tservicio'])): ?>
                    <div class="col-md-6">
                        <p class="text-secondary small mb-1">Tipo de Servicio</p>
                        <span class="badge bg-primary fs-6">
                            <i class="bi bi-tools me-1"></i><?= e($r['tservicio']) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <p class="text-secondary small mb-1">Falla Reportada</p>
                        <div class="border rounded p-2 bg-body-secondary">
                            <?= nl2br(e($r['falla'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bitácora -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-journal-text me-1"></i> Bitácora
            </div>
            <div class="card-body p-0">
                <?php if (empty($bitacora)): ?>
                <p class="text-secondary text-center py-3 mb-0 small">
                    Sin notas registradas aún.
                </p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach (array_reverse($bitacora) as $linea): ?>
                    <li class="list-group-item small font-monospace">
                        <?php
                        $linea = e($linea);
                        $linea = preg_replace(
                            '/\[([^\]]+)\]/',
                            '<span class="text-primary fw-semibold">[$1]</span>',
                            $linea
                        );
                        echo $linea;
                        ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php if (Auth::tieneRol(ROL_ADMIN, ROL_TECNICO, ROL_CALLCENTER)): ?>
            <div class="card-footer">
                <label class="form-label small text-secondary mb-1">Agregar nota</label>
                <div class="input-group">
                    <textarea id="nueva-nota" class="form-control form-control-sm"
                              rows="2"
                              placeholder="Escribe una observación técnica…"></textarea>
                    <button class="btn btn-outline-secondary" onclick="agregarNota()">
                        <i class="bi bi-send"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── Columna derecha: acciones ────────────────────────── -->
    <div class="col-lg-4">

        <?php if (Auth::tieneRol(ROL_ADMIN, ROL_TECNICO)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-arrow-repeat me-1"></i> Cambiar Estatus
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php foreach ([
                        'pendiente'  => ['Marcar Pendiente',  'bi-clock',               'btn-warning'],
                        'en proceso' => ['Marcar En Proceso', 'bi-gear-wide-connected',  'btn-info'],
                        'finalizado' => ['Marcar Finalizado', 'bi-check-circle',         'btn-success'],
                        'cancelado'  => ['Cancelar Reporte',  'bi-x-circle',             'btn-outline-danger'],
                    ] as $est => [$label, $icon, $cls]):
                        $activo = $r['estatus'] === $est;
                    ?>
                    <button class="btn <?= $cls ?> <?= $activo ? 'active' : '' ?>"
                            <?= $activo ? 'disabled' : '' ?>
                            onclick="cambiarEstatus('<?= $est ?>')">
                        <i class="bi <?= $icon ?> me-1"></i><?= $label ?>
                        <?= $activo ? '<i class="bi bi-check ms-1"></i>' : '' ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <label class="form-label small text-secondary">Nota al cambiar estatus</label>
                    <textarea id="nota-estatus" class="form-control form-control-sm"
                              rows="2"
                              placeholder="Opcional: describe el motivo…"></textarea>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (Auth::tieneRol(ROL_ADMIN)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person-badge me-1"></i> Reasignar Técnico
            </div>
            <div class="card-body">
                <select id="nuevo-tecnico" class="form-select form-select-sm mb-2">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($tecnicos as $t): ?>
                    <option value="<?= $t['id'] ?>"
                        <?= (int)$r['tecnico_id'] === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= e($t['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary btn-sm w-100"
                        onclick="reasignarTecnico()">
                    <i class="bi bi-check me-1"></i> Guardar asignación
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-1"></i> Resumen
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Folio</span>
                    <strong>#<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Fecha</span>
                    <span><?= e($r['fecha']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Técnico</span>
                    <span><?= e($r['tecnico_nombre'] ?? '—') ?></span>
                </li>
                <li class="list-group-item">
                    <span class="text-secondary d-block mb-1">Dirección</span>
                    <span class="small"><?= e($r['direccion']) ?></span>
                </li>
            </ul>
        </div>

    </div>
</div>

<script>
const API_REP = '<?= BASE_PATH ?>/api/reportes.php';
const REP_ID  = <?= $id ?>;

function mostrarAlerta(tipo, msg) {
    const el = document.getElementById('alerta-global');
    el.className = `alert alert-${tipo}`;
    el.textContent = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => el.className = 'alert d-none', 5000);
}

async function post(datos) {
    const form = new FormData();
    Object.entries(datos).forEach(([k,v]) => form.append(k, v ?? ''));
    const res  = await fetch(API_REP, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Error desconocido');
    return data;
}

async function cambiarEstatus(estatus) {
    try {
        await post({
            accion:  'cambiar_estatus',
            id:      REP_ID,
            estatus,
            nota:    document.getElementById('nota-estatus')?.value.trim()
        });
        mostrarAlerta('success', `Estatus cambiado a "${estatus}".`);
        setTimeout(() => location.reload(), 1200);
    } catch(e) { mostrarAlerta('danger', e.message); }
}

async function agregarNota() {
    const nota = document.getElementById('nueva-nota').value.trim();
    if (!nota) return mostrarAlerta('warning', 'Escribe una nota antes de enviar.');
    try {
        await post({ accion: 'agregar_nota', id: REP_ID, nota });
        mostrarAlerta('success', 'Nota guardada.');
        setTimeout(() => location.reload(), 1000);
    } catch(e) { mostrarAlerta('danger', e.message); }
}

async function reasignarTecnico() {
    const tecnico_id = document.getElementById('nuevo-tecnico').value;
    try {
        await post({ accion: 'reasignar_tecnico', id: REP_ID, tecnico_id });
        mostrarAlerta('success', 'Técnico reasignado correctamente.');
        setTimeout(() => location.reload(), 1000);
    } catch(e) { mostrarAlerta('danger', e.message); }
}
</script>

<?php require_once INCLUDES . '/footer.php'; ?>
