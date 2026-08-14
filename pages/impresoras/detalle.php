<?php
// ============================================================
//  WEBDDS — pages/impresoras/detalle.php
//  Bitácora completa del equipo + edición de datos
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerir();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_PATH . '/pages/impresoras/lista.php');
    exit;
}

$pdo  = Database::get();
$stmt = $pdo->prepare(
    "SELECT i.*,
            c.id AS cliente_id, c.razon, c.telefono, c.horario,
            d.departamento
     FROM impresoras i
     INNER JOIN clientes      c ON i.cliente_id      = c.id
     INNER JOIN departamentos d ON i.departamento_id = d.id
     WHERE i.id = :id"
);
$stmt->execute([':id' => $id]);
$eq = $stmt->fetch();

if (!$eq) {
    header('Location: ' . BASE_PATH . '/pages/impresoras/lista.php');
    exit;
}

// Historial completo de reportes de este equipo
$hist = $pdo->prepare(
    "SELECT r.id, r.fecha, r.falla, r.estatus, r.observaciones,
            u.nombre AS tecnico_nombre
     FROM reportes_fallas r
     LEFT JOIN usuarios u ON r.tecnico_id = u.id
     WHERE r.id_impresora = :id
     ORDER BY r.id DESC"
);
$hist->execute([':id' => $id]);
$historial = $hist->fetchAll();

$titulo_pagina = e($eq['marca'] . ' ' . $eq['modelo']);
$pagina_activa = 'impresoras';
$subtitulo     = 'Bitácora del equipo';

require_once INCLUDES . '/header.php';

$badgeClass = [
    'pendiente'  => 'badge-pendiente',
    'en proceso' => 'badge-en-proceso',
    'finalizado' => 'badge-finalizado',
    'cancelado'  => 'badge-cancelado',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <a href="<?= BASE_PATH ?>/pages/impresoras/lista.php"
       class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Regresar
    </a>
    <?php if (Auth::tieneRol(ROL_ADMIN, ROL_GERENCIA, ROL_ADMINISTRATIVO, ROL_TECNICO)): ?>
    <a href="<?= BASE_PATH ?>/pages/reportes/nuevo.php?equipo_id=<?= $id ?>&cliente_id=<?= $eq['cliente_id'] ?>"
       class="btn btn-sm btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Reporte
    </a>
    <?php endif; ?>
</div>

<div id="alerta-global" class="alert d-none mb-3"></div>

<div class="row g-3">

    <!-- ── Columna izquierda: datos del equipo ─────────────── -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-printer me-1"></i> Datos del Equipo</span>
                <?php if (Auth::tieneRol(ROL_ADMIN, ROL_VENDEDOR, ROL_GERENCIA, ROL_ADMINISTRATIVO)): ?>
                <button class="btn btn-sm btn-outline-warning" onclick="abrirEdicion()">
                    <i class="bi bi-pencil"></i>
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">

                <!-- Vista lectura -->
                <div id="vista-lectura">
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2">
                            <span class="text-secondary">Marca</span>
                            <div class="fw-semibold"><?= e($eq['marca']) ?></div>
                        </li>
                        <li class="mb-2">
                            <span class="text-secondary">Modelo</span>
                            <div class="fw-semibold"><?= e($eq['modelo']) ?></div>
                        </li>
                        <li class="mb-2">
                            <span class="text-secondary">No. de Serie</span>
                            <div><code><?= e($eq['serie'] ?: '—') ?></code></div>
                        </li>
                        <li class="mb-2">
                            <span class="text-secondary">Status</span>
                            <div>
                                <span class="badge bg-secondary"><?= e($eq['status']) ?></span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Vista edición -->
                <div id="vista-edicion" class="d-none">
                    <div id="error-edicion" class="alert alert-danger d-none mb-2"></div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label form-label-sm">Marca</label>
                            <select class="form-select form-select-sm" id="edit-marca">
                                <?php foreach (['Xerox','Sharp','Samsung','HP','Kyocera','Canon'] as $m): ?>
                                <option value="<?= $m ?>"
                                    <?= $eq['marca'] === $m ? 'selected' : '' ?>>
                                    <?= $m ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm">Modelo</label>
                            <input type="text" class="form-control form-control-sm"
                                   id="edit-modelo" value="<?= e($eq['modelo']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm">No. de Serie</label>
                            <input type="text" class="form-control form-control-sm"
                                   id="edit-serie" value="<?= e($eq['serie']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm">Status</label>
                            <select class="form-select form-select-sm" id="edit-status">
                                <?php foreach (['Renta','Propio','Poliza','Garantia'] as $s): ?>
                                <option value="<?= $s ?>"
                                    <?= $eq['status'] === $s ? 'selected' : '' ?>>
                                    <?= $s ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-success btn-sm" onclick="guardarEdicion()">
                            <i class="bi bi-check me-1"></i> Guardar
                        </button>
                        <button class="btn btn-outline-secondary btn-sm"
                                onclick="cancelarEdicion()">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info del cliente -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person me-1"></i> Cliente
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item">
                    <a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=<?= $eq['cliente_id'] ?>">
                        <strong><?= e($eq['razon']) ?></strong>
                    </a>
                </li>
                <li class="list-group-item text-secondary">
                    <i class="bi bi-building me-1"></i><?= e($eq['departamento']) ?>
                </li>
                <li class="list-group-item text-secondary">
                    <i class="bi bi-telephone me-1"></i><?= e($eq['telefono']) ?>
                </li>
                <li class="list-group-item text-secondary">
                    <i class="bi bi-clock me-1"></i><?= e($eq['horario']) ?>
                </li>
            </ul>
        </div>
    </div>

    <!-- ── Columna derecha: historial completo ─────────────── -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-journal-text me-1"></i>
                    Historial de Reportes
                </span>
                <span class="badge bg-secondary"><?= count($historial) ?> reportes</span>
            </div>

            <?php if (empty($historial)): ?>
            <div class="card-body text-center text-secondary py-4">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                Este equipo no tiene reportes registrados aún.
            </div>
            <?php else: ?>

            <!-- Estadísticas rápidas -->
            <?php
            $cts = array_count_values(array_column($historial, 'estatus'));
            ?>
            <div class="card-body border-bottom py-2">
                <div class="d-flex gap-3 flex-wrap small">
                    <?php foreach ([
                        'pendiente'  => 'badge-pendiente',
                        'en proceso' => 'badge-en-proceso',
                        'finalizado' => 'badge-finalizado',
                        'cancelado'  => 'badge-cancelado',
                    ] as $est => $cls): ?>
                    <?php if (!empty($cts[$est])): ?>
                    <span>
                        <span class="badge <?= $cls ?>"><?= $cts[$est] ?></span>
                        <?= $est ?>
                    </span>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Lista de reportes -->
            <div class="accordion accordion-flush" id="accordionHistorial">
                <?php foreach ($historial as $i => $rep): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#rep-<?= $rep['id'] ?>">
                            <div class="d-flex justify-content-between w-100 me-3 align-items-center">
                                <div>
                                    <span class="badge <?= $badgeClass[$rep['estatus']] ?? 'bg-secondary' ?> me-2">
                                        <?= e($rep['estatus']) ?>
                                    </span>
                                    <span class="small fw-semibold">
                                        #<?= str_pad($rep['id'], 5, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <span class="text-secondary small ms-2">
                                        <?= e($rep['falla']) ?>
                                    </span>
                                </div>
                                <span class="text-secondary small text-nowrap">
                                    <?= e($rep['fecha']) ?>
                                </span>
                            </div>
                        </button>
                    </h2>
                    <div id="rep-<?= $rep['id'] ?>"
                         class="accordion-collapse collapse">
                        <div class="accordion-body py-2 small">
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <span class="text-secondary">Técnico:</span>
                                    <?= e($rep['tecnico_nombre'] ?? '—') ?>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <a href="<?= BASE_PATH ?>/pages/reportes/detalle.php?id=<?= $rep['id'] ?>"
                                       class="btn btn-xs btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i> Ver reporte completo
                                    </a>
                                </div>
                            </div>
                            <div class="mb-1">
                                <span class="text-secondary">Falla:</span>
                                <div class="border rounded p-1 bg-body-secondary">
                                    <?= nl2br(e($rep['falla'])) ?>
                                </div>
                            </div>
                            <?php if ($rep['observaciones']): ?>
                            <div class="mt-2">
                                <span class="text-secondary">Bitácora:</span>
                                <div class="font-monospace small border rounded p-1 bg-body-secondary">
                                    <?php
                                    $lineas = array_filter(
                                        array_map('trim', explode("\n", $rep['observaciones'])),
                                        fn($l) => $l !== ''
                                    );
                                    foreach (array_reverse($lineas) as $l) {
                                        $l = e($l);
                                        $l = preg_replace(
                                            '/\[([^\]]+)\]/',
                                            '<span class="text-primary">[$1]</span>',
                                            $l
                                        );
                                        echo $l . '<br>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
const API_IMP = '<?= BASE_PATH ?>/api/impresoras.php';
const EQ_ID   = <?= $id ?>;

function mostrarAlerta(tipo, msg) {
    const el = document.getElementById('alerta-global');
    el.className = `alert alert-${tipo}`;
    el.textContent = msg;
    setTimeout(() => el.className = 'alert d-none', 5000);
}
function abrirEdicion() {
    document.getElementById('vista-lectura').classList.add('d-none');
    document.getElementById('vista-edicion').classList.remove('d-none');
}
function cancelarEdicion() {
    document.getElementById('vista-edicion').classList.add('d-none');
    document.getElementById('vista-lectura').classList.remove('d-none');
    document.getElementById('error-edicion').classList.add('d-none');
}
async function guardarEdicion() {
    const form = new FormData();
    form.append('accion',  'actualizar');
    form.append('id',      EQ_ID);
    form.append('marca',   document.getElementById('edit-marca').value);
    form.append('modelo',  document.getElementById('edit-modelo').value.trim());
    form.append('serie',   document.getElementById('edit-serie').value.trim());
    form.append('status',  document.getElementById('edit-status').value);

    try {
        const res  = await fetch(API_IMP, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) {
            document.getElementById('error-edicion').textContent = data.error;
            document.getElementById('error-edicion').classList.remove('d-none');
            return;
        }
        mostrarAlerta('success', 'Equipo actualizado correctamente.');
        setTimeout(() => location.reload(), 1000);
    } catch(e) {
        document.getElementById('error-edicion').textContent = 'Error de conexión.';
        document.getElementById('error-edicion').classList.remove('d-none');
    }
}
</script>

<?php require_once INCLUDES . '/footer.php'; ?>
