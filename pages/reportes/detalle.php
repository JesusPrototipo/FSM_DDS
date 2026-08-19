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
            <?php if (Auth::tieneRol(ROL_ADMIN, ROL_TECNICO, ROL_ADMINISTRATIVO, ROL_GERENCIA)): ?>
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
                        $activo  = $r['estatus'] === $est;
                        $onclick = $est === 'finalizado' ? 'abrirModalFinalizar()' : "cambiarEstatus('{$est}')";
                    ?>
                    <button class="btn <?= $cls ?> <?= $activo ? 'active' : '' ?>"
                            <?= $activo ? 'disabled' : '' ?>
                            onclick="<?= $onclick ?>">
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
                    <div class="form-text">
                        Esta nota no aplica al marcar "Finalizado" — ese estatus usa el cuestionario emergente.
                    </div>
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

<!-- ══════════════════════════════════════════════════════════
     Modal: Cuestionario de Finalización
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalFinalizar" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-clipboard-check me-1"></i> Detalle de Servicio Realizado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="error-finalizar" class="alert alert-danger d-none"></div>

        <!-- Aspirado -->
        <div class="mb-3 pb-2 border-bottom">
          <label class="form-label fw-semibold">¿Se aspiró la impresora?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="aspirado" value="si" id="asp-si">
              <label class="form-check-label" for="asp-si">Sí</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="aspirado" value="no" id="asp-no">
              <label class="form-check-label" for="asp-no">No</label>
            </div>
          </div>
          <input type="text" class="form-control form-control-sm mt-2 d-none" id="aspirado-nota" placeholder="¿Por qué? (opcional)">
        </div>

        <!-- Sensores -->
        <div class="mb-3 pb-2 border-bottom">
          <label class="form-label fw-semibold">¿Se limpiaron sensores?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="sensores" value="si" id="sen-si">
              <label class="form-check-label" for="sen-si">Sí</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="sensores" value="no" id="sen-no">
              <label class="form-check-label" for="sen-no">No</label>
            </div>
          </div>
          <input type="text" class="form-control form-control-sm mt-2 d-none" id="sensores-nota" placeholder="¿Por qué? (opcional)">
        </div>

        <!-- Rodillos / Alimentadores -->
        <div class="mb-3 pb-2 border-bottom">
          <label class="form-label fw-semibold">¿Se limpiaron rodillos y/o alimentadores?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="rodillos" value="si" id="rod-si">
              <label class="form-check-label" for="rod-si">Sí</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="rodillos" value="no" id="rod-no">
              <label class="form-check-label" for="rod-no">No</label>
            </div>
          </div>
          <input type="text" class="form-control form-control-sm mt-2 d-none" id="rodillos-cuantos" placeholder="¿Cuántos?">
          <input type="text" class="form-control form-control-sm mt-2 d-none" id="rodillos-nota" placeholder="¿Por qué? (opcional)">
        </div>

        <!-- ADF -->
        <div class="mb-3 pb-2 border-bottom">
          <label class="form-label fw-semibold">¿Se realizó limpieza del ADF?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="ado" value="si" id="ado-si">
              <label class="form-check-label" for="ado-si">Sí</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="ado" value="no" id="ado-no">
              <label class="form-check-label" for="ado-no">No</label>
            </div>
          </div>
          <input type="text" class="form-control form-control-sm mt-2 d-none" id="ado-nota" placeholder="¿Por qué? (opcional)">
        </div>

        <!-- Limpieza externa -->
        <div class="mb-3 pb-2 border-bottom">
          <label class="form-label fw-semibold">¿Se dio limpieza externa?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="limpieza_externa" value="si" id="ext-si">
              <label class="form-check-label" for="ext-si">Sí</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="limpieza_externa" value="no" id="ext-no">
              <label class="form-check-label" for="ext-no">No</label>
            </div>
          </div>
          <input type="text" class="form-control form-control-sm mt-2 d-none" id="limpieza_externa-nota" placeholder="¿Por qué? (opcional)">
        </div>

        <!-- Consumibles -->
        <div class="mb-3 pb-2 border-bottom">
          <label class="form-label fw-semibold">¿La impresora requiere consumibles?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="consumibles" value="si" id="cons-si">
              <label class="form-check-label" for="cons-si">Sí</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="consumibles" value="no" id="cons-no">
              <label class="form-check-label" for="cons-no">No</label>
            </div>
          </div>
          <input type="text" class="form-control form-control-sm mt-2 d-none" id="consumibles-cuales" placeholder="¿Cuáles?">
          <input type="text" class="form-control form-control-sm mt-2 d-none" id="consumibles-nota" placeholder="Comentario (opcional)">
        </div>

        <!-- Equipo funcionando -->
        <div class="mb-3 pb-2 border-bottom">
          <label class="form-label fw-semibold">¿El equipo quedó funcionando?</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="funciona" value="si" id="fun-si">
              <label class="form-check-label" for="fun-si">Sí</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="funciona" value="no" id="fun-no">
              <label class="form-check-label" for="fun-no">No</label>
            </div>
          </div>
          <div id="fun-requiere-wrap" class="d-none mt-2 ps-3 border-start">
            <label class="form-label small">¿Qué requiere?</label>
            <div class="d-flex gap-3 mb-2">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="requiere" value="consumibles" id="req-cons">
                <label class="form-check-label" for="req-cons">Consumibles</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="requiere" value="piezas" id="req-piezas">
                <label class="form-check-label" for="req-piezas">Piezas</label>
              </div>
            </div>
            <input type="text" class="form-control form-control-sm d-none" id="requiere-consumible-cual" placeholder="¿Qué consumible?">
            <input type="text" class="form-control form-control-sm d-none" id="requiere-pieza-cual" placeholder="¿Qué pieza?">
          </div>
        </div>

        <!-- Nota libre -->
        <div class="mb-2">
          <label class="form-label fw-semibold">Nota adicional (opcional)</label>
          <textarea class="form-control form-control-sm" id="nota-libre" rows="2" placeholder="Cualquier otro detalle…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-finalizar-check" onclick="finalizarConChecklist()">
          <i class="bi bi-check-circle me-1"></i> Finalizar y mandar a notas
        </button>
      </div>
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

// ══════════════════════════════════════════════════════════════
//  Cuestionario de finalización
// ══════════════════════════════════════════════════════════════

function setupToggle(name, mapping) {
    document.querySelectorAll(`input[name="${name}"]`).forEach(radio => {
        radio.addEventListener('change', function() {
            Object.keys(mapping).forEach(val => {
                mapping[val].forEach(id => {
                    document.getElementById(id).classList.toggle('d-none', this.value !== val);
                });
            });
        });
    });
}
setupToggle('aspirado',         { no: ['aspirado-nota'] });
setupToggle('sensores',         { no: ['sensores-nota'] });
setupToggle('rodillos',         { si: ['rodillos-cuantos'], no: ['rodillos-nota'] });
setupToggle('ado',              { no: ['ado-nota'] });
setupToggle('limpieza_externa', { no: ['limpieza_externa-nota'] });
setupToggle('consumibles',      { si: ['consumibles-cuales'], no: ['consumibles-nota'] });
setupToggle('funciona',         { no: ['fun-requiere-wrap'] });
setupToggle('requiere',         { consumibles: ['requiere-consumible-cual'], piezas: ['requiere-pieza-cual'] });

const CAMPOS_TEXTO_MODAL = [
    'aspirado-nota','sensores-nota','rodillos-cuantos','rodillos-nota','ado-nota',
    'limpieza_externa-nota','consumibles-cuales','consumibles-nota',
    'requiere-consumible-cual','requiere-pieza-cual','nota-libre'
];

function abrirModalFinalizar() {
    document.getElementById('error-finalizar').classList.add('d-none');
    document.querySelectorAll('#modalFinalizar input[type=radio]').forEach(r => r.checked = false);
    CAMPOS_TEXTO_MODAL.forEach(id => document.getElementById(id).value = '');
    CAMPOS_TEXTO_MODAL.concat(['fun-requiere-wrap']).forEach(id => document.getElementById(id).classList.add('d-none'));
    new bootstrap.Modal(document.getElementById('modalFinalizar')).show();
}

function radioVal(name) {
    const el = document.querySelector(`input[name="${name}"]:checked`);
    return el ? el.value : null;
}
function txt(id) { return document.getElementById(id).value.trim(); }

async function finalizarConChecklist() {
    const errEl = document.getElementById('error-finalizar');
    errEl.classList.add('d-none');

    const preguntas = [
        ['aspirado', '¿Se aspiró la impresora?'],
        ['sensores', '¿Se limpiaron sensores?'],
        ['rodillos', '¿Se limpiaron rodillos y/o alimentadores?'],
        ['ado', '¿Se realizó limpieza del ADF?'],
        ['limpieza_externa', '¿Se dio limpieza externa?'],
        ['consumibles', '¿La impresora requiere consumibles?'],
        ['funciona', '¿El equipo quedó funcionando?'],
    ];
    const datos = {};
    for (const [name, label] of preguntas) {
        const v = radioVal(name);
        if (!v) { errEl.textContent = `Responde: "${label}"`; errEl.classList.remove('d-none'); return; }
        datos[name] = v;
    }
    if (datos.rodillos === 'si' && !txt('rodillos-cuantos')) {
        errEl.textContent = 'Indica cuántos rodillos/alimentadores se limpiaron.'; errEl.classList.remove('d-none'); return;
    }
    if (datos.consumibles === 'si' && !txt('consumibles-cuales')) {
        errEl.textContent = 'Indica qué consumibles requiere.'; errEl.classList.remove('d-none'); return;
    }
    let requiere = null;
    if (datos.funciona === 'no') {
        requiere = radioVal('requiere');
        if (!requiere) { errEl.textContent = 'Indica qué requiere el equipo.'; errEl.classList.remove('d-none'); return; }
        if (requiere === 'consumibles' && !txt('requiere-consumible-cual')) {
            errEl.textContent = 'Indica qué consumible requiere.'; errEl.classList.remove('d-none'); return;
        }
        if (requiere === 'piezas' && !txt('requiere-pieza-cual')) {
            errEl.textContent = 'Indica qué pieza requiere.'; errEl.classList.remove('d-none'); return;
        }
    }

    const checklist = {
        aspirado: { valor: datos.aspirado, nota: txt('aspirado-nota') || null },
        sensores: { valor: datos.sensores, nota: txt('sensores-nota') || null },
        rodillos: { valor: datos.rodillos, cuantos: txt('rodillos-cuantos') || null, nota: txt('rodillos-nota') || null },
        ado: { valor: datos.ado, nota: txt('ado-nota') || null },
        limpieza_externa: { valor: datos.limpieza_externa, nota: txt('limpieza_externa-nota') || null },
        consumibles: { valor: datos.consumibles, cuales: txt('consumibles-cuales') || null, nota: txt('consumibles-nota') || null },
        funciona: {
            valor: datos.funciona, requiere,
            consumible_cual: requiere === 'consumibles' ? txt('requiere-consumible-cual') : null,
            pieza_cual: requiere === 'piezas' ? txt('requiere-pieza-cual') : null,
        },
        nota_libre: txt('nota-libre') || null,
    };

    const lineas = [
        `Aspirado: ${datos.aspirado.toUpperCase()}${checklist.aspirado.nota ? ' — ' + checklist.aspirado.nota : ''}`,
        `Sensores: ${datos.sensores.toUpperCase()}${checklist.sensores.nota ? ' — ' + checklist.sensores.nota : ''}`,
        `Rodillos/Alimentadores: ${datos.rodillos.toUpperCase()}${datos.rodillos === 'si' ? ' (Cantidad: ' + checklist.rodillos.cuantos + ')' : (checklist.rodillos.nota ? ' — ' + checklist.rodillos.nota : '')}`,
        `Limpieza ADF: ${datos.ado.toUpperCase()}${checklist.ado.nota ? ' — ' + checklist.ado.nota : ''}`,
        `Limpieza externa: ${datos.limpieza_externa.toUpperCase()}${checklist.limpieza_externa.nota ? ' — ' + checklist.limpieza_externa.nota : ''}`,
        `Requiere consumibles: ${datos.consumibles.toUpperCase()}${datos.consumibles === 'si' ? ' (Cuáles: ' + checklist.consumibles.cuales + ')' : (checklist.consumibles.nota ? ' — ' + checklist.consumibles.nota : '')}`,
    ];
    if (datos.funciona === 'si') {
        lineas.push('Equipo funcionando: SÍ');
    } else {
        const reqTxt = requiere === 'consumibles' ? `Consumibles (${checklist.funciona.consumible_cual})` : `Piezas (${checklist.funciona.pieza_cual})`;
        lineas.push(`Equipo funcionando: NO — Requiere: ${reqTxt}`);
    }
    if (checklist.nota_libre) lineas.push(`Nota adicional: ${checklist.nota_libre}`);

    const notaTexto = 'CHECKLIST DE FINALIZACIÓN:\n' + lineas.map(l => '• ' + l).join('\n');

    const btn = document.getElementById('btn-finalizar-check');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    try {
        await post({
            accion: 'cambiar_estatus',
            id: REP_ID,
            estatus: 'finalizado',
            nota: notaTexto,
            checklist: JSON.stringify(checklist),
        });
        bootstrap.Modal.getInstance(document.getElementById('modalFinalizar')).hide();
        mostrarAlerta('success', 'Reporte finalizado correctamente.');
        setTimeout(() => location.reload(), 1200);
    } catch(e) {
        errEl.textContent = e.message;
        errEl.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Finalizar y mandar a notas';
    }
}
</script>

<?php require_once INCLUDES . '/footer.php'; ?>