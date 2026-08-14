<?php
// ============================================================
//  WEBDDS — pages/clientes/agregar_depto.php
//  Agrega departamentos y equipos a un cliente YA existente
//  Llamado desde: detalle.php → botón "Agregar"
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerirRol([ROL_ADMIN, ROL_GERENCIA, ROL_ADMINISTRATIVO, ROL_VENDEDOR]);

$clienteId = (int)($_GET['cliente_id'] ?? 0);
if (!$clienteId) {
    header('Location: ' . BASE_PATH . '/pages/clientes/lista.php');
    exit;
}

$pdo  = Database::get();
$stmt = $pdo->prepare("SELECT id, razon FROM clientes WHERE id = :id");
$stmt->execute([':id' => $clienteId]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: ' . BASE_PATH . '/pages/clientes/lista.php');
    exit;
}

// Departamentos actuales del cliente (para el select de equipos)
$deptos = $pdo->prepare(
    "SELECT id, departamento FROM departamentos WHERE cliente_id = :id ORDER BY departamento"
);
$deptos->execute([':id' => $clienteId]);
$deptosActuales = $deptos->fetchAll();

$titulo_pagina = 'Agregar a ' . e($cliente['razon']);
$pagina_activa = 'clientes';
$subtitulo     = 'Agregar departamento o equipo';

require_once INCLUDES . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=<?= $clienteId ?>"
       class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Regresar a <?= e($cliente['razon']) ?>
    </a>
</div>

<div id="alerta-global" class="alert d-none mb-3"></div>

<!-- Tabs: Departamento / Equipo -->
<ul class="nav nav-tabs mb-4" id="tabOpciones">
    <li class="nav-item">
        <a class="nav-link active" href="#" onclick="mostrarTab('depto'); return false;">
            <i class="bi bi-building me-1"></i> Nuevo Departamento
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" onclick="mostrarTab('equipo'); return false;">
            <i class="bi bi-printer me-1"></i> Nuevo Equipo
        </a>
    </li>
</ul>

<!-- ── Formulario Departamento ──────────────────────────────── -->
<div id="tab-depto">
    <div class="card">
        <div class="card-header text-bg-secondary">
            <i class="bi bi-building me-1"></i>
            Agregar Departamento — <strong><?= e($cliente['razon']) ?></strong>
        </div>
        <div class="card-body">
            <div id="error-depto" class="alert alert-danger d-none"></div>

            <!-- Departamentos existentes -->
            <?php if (!empty($deptosActuales)): ?>
            <div class="mb-3">
                <p class="text-secondary small mb-2">Departamentos actuales:</p>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($deptosActuales as $d): ?>
                    <span class="badge bg-primary">
                        <i class="bi bi-building me-1"></i><?= e($d['departamento']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nombre del Departamento *</label>
                    <input type="text" class="form-control" id="nuevo-depto"
                           placeholder="Ej: Oficinas, Centro de Copiado, Recepción…">
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=<?= $clienteId ?>"
               class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary" id="btn-depto" onclick="guardarDepto()">
                <i class="bi bi-check-circle me-1"></i> Guardar Departamento
            </button>
        </div>
    </div>
</div>

<!-- ── Formulario Equipo ─────────────────────────────────────── -->
<div id="tab-equipo" class="d-none">
    <div class="card">
        <div class="card-header text-bg-secondary">
            <i class="bi bi-printer me-1"></i>
            Agregar Equipo — <strong><?= e($cliente['razon']) ?></strong>
        </div>
        <div class="card-body">
            <div id="error-equipo" class="alert alert-danger d-none"></div>

            <?php if (empty($deptosActuales)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Este cliente no tiene departamentos. Primero agrega un departamento en la pestaña anterior.
            </div>
            <?php else: ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Departamento *</label>
                    <select class="form-select" id="eq-depto-id">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($deptosActuales as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= e($d['departamento']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Marca *</label>
                    <select class="form-select" id="eq-marca">
                        <option value="">— Selecciona —</option>
                        <?php foreach (['Xerox','Sharp','Samsung','HP','Kyocera','Canon'] as $m): ?>
                        <option value="<?= $m ?>"><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Modelo *</label>
                    <input type="text" class="form-control" id="eq-modelo"
                           placeholder="AltaLink B8145">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. de Serie</label>
                    <input type="text" class="form-control" id="eq-serie"
                           placeholder="HQH258539">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="eq-status">
                        <option value="Renta">Renta</option>
                        <option value="Propio">Propio</option>
                        <option value="Poliza">Póliza</option>
                        <option value="Garantia">Garantía</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($deptosActuales)): ?>
        <div class="card-footer d-flex justify-content-between">
            <a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=<?= $clienteId ?>"
               class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-primary" id="btn-equipo" onclick="guardarEquipo()">
                <i class="bi bi-check-circle me-1"></i> Guardar Equipo
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const API        = '<?= BASE_PATH ?>/api/clientes.php';
const CLIENTE_ID = <?= $clienteId ?>;
const BASE       = '<?= BASE_PATH ?>';

function mostrarTab(tab) {
    document.getElementById('tab-depto').classList.toggle('d-none', tab !== 'depto');
    document.getElementById('tab-equipo').classList.toggle('d-none', tab !== 'equipo');
    // Actualizar nav-link activo
    document.querySelectorAll('#tabOpciones .nav-link').forEach((el, i) => {
        el.classList.toggle('active', (i === 0 && tab === 'depto') || (i === 1 && tab === 'equipo'));
    });
}

function mostrarAlerta(tipo, msg) {
    const el = document.getElementById('alerta-global');
    el.className = `alert alert-${tipo}`;
    el.innerHTML = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function guardarDepto() {
    const nombre = document.getElementById('nuevo-depto').value.trim();
    const errEl  = document.getElementById('error-depto');
    errEl.classList.add('d-none');

    if (!nombre) {
        errEl.textContent = 'Escribe el nombre del departamento.';
        errEl.classList.remove('d-none');
        return;
    }

    const btn = document.getElementById('btn-depto');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    const form = new FormData();
    form.append('accion',       'crear_depto');
    form.append('cliente_id',   CLIENTE_ID);
    form.append('departamento', nombre);

    try {
        const res  = await fetch(API, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) {
            errEl.textContent = data.error || 'Error al guardar.';
            errEl.classList.remove('d-none');
            return;
        }
        mostrarAlerta('success',
            `Departamento "<strong>${nombre}</strong>" agregado correctamente. ` +
            `<a href="${BASE}/pages/clientes/detalle.php?id=${CLIENTE_ID}">Ver cliente</a> ` +
            `o agrega otro departamento abajo.`);
        document.getElementById('nuevo-depto').value = '';

        // Agregar al select de equipos sin recargar
        const opt = document.createElement('option');
        opt.value = data.id;
        opt.textContent = nombre;
        const sel = document.getElementById('eq-depto-id');
        if (sel) sel.appendChild(opt);

    } catch(e) {
        errEl.textContent = 'Error de conexión.';
        errEl.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Guardar Departamento';
    }
}

async function guardarEquipo() {
    const deptoId = document.getElementById('eq-depto-id').value;
    const marca   = document.getElementById('eq-marca').value;
    const modelo  = document.getElementById('eq-modelo').value.trim();
    const serie   = document.getElementById('eq-serie').value.trim();
    const status  = document.getElementById('eq-status').value;
    const errEl   = document.getElementById('error-equipo');
    errEl.classList.add('d-none');

    if (!deptoId) { errEl.textContent = 'Selecciona el departamento.'; errEl.classList.remove('d-none'); return; }
    if (!marca)   { errEl.textContent = 'Selecciona la marca.';        errEl.classList.remove('d-none'); return; }
    if (!modelo)  { errEl.textContent = 'Escribe el modelo.';          errEl.classList.remove('d-none'); return; }

    const btn = document.getElementById('btn-equipo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    const form = new FormData();
    form.append('accion',          'crear_equipo');
    form.append('departamento_id', deptoId);
    form.append('cliente_id',      CLIENTE_ID);
    form.append('marca',           marca);
    form.append('modelo',          modelo);
    form.append('serie',           serie);
    form.append('status',          status);

    try {
        const res  = await fetch(API, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) {
            errEl.textContent = data.error || 'Error al guardar.';
            errEl.classList.remove('d-none');
            return;
        }
        mostrarAlerta('success',
            `Equipo "<strong>${marca} ${modelo}</strong>" agregado correctamente. ` +
            `<a href="${BASE}/pages/clientes/detalle.php?id=${CLIENTE_ID}">Ver cliente</a> ` +
            `o agrega otro equipo.`);
        // Limpiar campos
        ['eq-marca','eq-depto-id'].forEach(id => document.getElementById(id).value = '');
        ['eq-modelo','eq-serie'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('eq-status').value = 'Renta';

    } catch(e) {
        errEl.textContent = 'Error de conexión.';
        errEl.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Guardar Equipo';
    }
}
</script>

<?php require_once INCLUDES . '/footer.php'; ?>
