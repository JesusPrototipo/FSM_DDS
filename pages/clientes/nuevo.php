<?php
// ============================================================
//  WEBDDS — pages/clientes/nuevo.php
//  Reemplaza: Cliente.php + Departamento.php + Equipo.php + NuevoCliente.php
//  Wizard de 3 pasos en una sola página (sin redirecciones intermedias)
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerirRol([ROL_ADMIN, ROL_ADMINISTRATIVO, ROL_TECNICO, ROL_GERENCIA, ROL_VENDEDOR]);

$titulo_pagina = 'Nuevo Cliente';
$pagina_activa = 'clientes';
$subtitulo     = 'Registrar nuevo cliente';

require_once INCLUDES . '/header.php';

$pdo = Database::get();

// Ciudades existentes (para el select de Ciudad, con opción de agregar una nueva)
$ciudadesExistentes = $pdo->query(
    "SELECT DISTINCT ciudad FROM clientes
     WHERE ciudad IS NOT NULL AND ciudad <> ''
     ORDER BY ciudad"
)->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Indicador de pasos -->
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center">
            <?php foreach ([
                1 => ['icon' => 'bi-person',   'label' => 'Cliente'],
                2 => ['icon' => 'bi-building',  'label' => 'Departamento'],
                3 => ['icon' => 'bi-printer',   'label' => 'Equipo'],
            ] as $n => $info): ?>
            <div class="d-flex align-items-center gap-2 step-indicator" id="step-ind-<?= $n ?>">
                <span class="badge rounded-circle fs-6
                    <?= $n === 1 ? 'bg-primary' : 'bg-secondary' ?>"
                    style="width:32px;height:32px;line-height:20px"
                    id="step-badge-<?= $n ?>">
                    <?= $n ?>
                </span>
                <span class="d-none d-md-inline <?= $n === 1 ? 'fw-bold' : 'text-secondary' ?>"
                      id="step-label-<?= $n ?>">
                    <?= $info['label'] ?>
                </span>
            </div>
            <?php if ($n < 3): ?>
            <div class="flex-grow-1 border-top mx-2"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── PASO 1: Datos del cliente ─────────────────────────── -->
<div id="paso-1">
    <div class="card">
        <div class="card-header text-bg-secondary">
            <i class="bi bi-person me-1"></i> Información del Cliente
        </div>
        <div class="card-body">
            <div id="error-paso1" class="alert alert-danger d-none"></div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Razón Social *</label>
                    <input type="text" class="form-control" id="razon" placeholder="Nombre del negocio" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contacto</label>
                    <input type="text" class="form-control" id="reporto" placeholder="Juan Pérez">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Dirección *</label>
                    <input type="text" class="form-control" id="direccion" placeholder="Calle #, Colonia">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ciudad</label>
                    <select class="form-select" id="ciudad-select">
                        <option value="">— Selecciona una ciudad —</option>
                        <?php foreach ($ciudadesExistentes as $c): ?>
                        <option value="<?= e($c) ?>"><?= e($c) ?></option>
                        <?php endforeach; ?>
                        <option value="__nueva__">+ Agregar nueva ciudad…</option>
                    </select>
                    <input type="text" class="form-control mt-2 d-none" id="ciudad-nueva"
                           placeholder="Escribe el nombre de la nueva ciudad">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" placeholder="878 000 0000">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Horario de Atención</label>
                    <input type="text" class="form-control" id="horario" placeholder="8am - 6pm">
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="<?= BASE_PATH ?>/pages/clientes/lista.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Cancelar
            </a>
            <button class="btn btn-primary" id="btn-guardar-cliente" onclick="guardarCliente()">
                Siguiente <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</div>

<!-- ── PASO 2: Departamentos ─────────────────────────────── -->
<div id="paso-2" class="d-none">
    <div class="card">
        <div class="card-header text-bg-secondary">
            <i class="bi bi-building me-1"></i> Departamentos del cliente
            <span class="text-white fw-bold ms-1" id="razon-display-2"></span>
        </div>
        <div class="card-body">
            <div id="error-paso2" class="alert alert-danger d-none"></div>

            <!-- Lista de departamentos añadidos -->
            <div id="lista-deptos" class="mb-3"></div>

            <!-- Formulario para añadir departamento -->
            <div class="input-group">
                <input type="text" class="form-control" id="nuevo-depto"
                       placeholder="Nombre del departamento (ej. Oficinas, Copiado…)">
                <button class="btn btn-outline-primary" onclick="agregarDepto()">
                    <i class="bi bi-plus-circle me-1"></i> Agregar
                </button>
            </div>
            <div class="form-text">Agrega todos los departamentos del cliente. Mínimo 1.</div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button class="btn btn-outline-secondary" onclick="irPaso(1)">
                <i class="bi bi-arrow-left me-1"></i> Atrás
            </button>
            <button class="btn btn-primary" onclick="irPaso(3)" id="btn-paso3">
                Siguiente <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>
</div>

<!-- ── PASO 3: Equipo / Impresora ────────────────────────── -->
<div id="paso-3" class="d-none">
    <div class="card">
        <div class="card-header text-bg-secondary">
            <i class="bi bi-printer me-1"></i> Equipos del cliente
            <span class="text-white fw-bold ms-1" id="razon-display-3"></span>
        </div>
        <div class="card-body">
            <div id="error-paso3" class="alert alert-danger d-none"></div>

            <!-- Lista de equipos añadidos -->
            <div id="lista-equipos" class="mb-3"></div>

            <!-- Formulario para añadir equipo -->
            <div class="row g-2 align-items-end" id="form-equipo">
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Departamento *</label>
                    <select class="form-select form-select-sm" id="eq-depto">
                        <option value="">Selecciona…</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">Marca *</label>
                    <select class="form-select form-select-sm" id="eq-marca">
                        <option value="">Selecciona</option>
                        <option value="Xerox">Xerox</option>
                        <option value="Sharp">Sharp</option>
                        <option value="Samsung">Samsung</option>
                        <option value="HP">HP</option>
                        <option value="Kyocera">Kyocera</option>
                        <option value="Canon">Canon</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Modelo *</label>
                    <input type="text" class="form-control form-control-sm" id="eq-modelo"
                           placeholder="AltaLink B8145">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm">No. de Serie</label>
                    <input type="text" class="form-control form-control-sm" id="eq-serie"
                           placeholder="HQH258539">
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm">Status</label>
                    <select class="form-select form-select-sm" id="eq-status">
                        <option value="Renta">Renta</option>
                        <option value="Propio">Propio</option>
                        <option value="Poliza">Póliza</option>
                        <option value="Garantia">Garantía</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-primary btn-sm w-100" onclick="agregarEquipo()">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                </div>
            </div>
            <div class="form-text">Puedes agregar varios equipos. También puedes terminar sin equipos.</div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button class="btn btn-outline-secondary" onclick="irPaso(2)">
                <i class="bi bi-arrow-left me-1"></i> Atrás
            </button>
            <button class="btn btn-success" onclick="terminar()">
                <i class="bi bi-check-circle me-1"></i> Terminar y ver cliente
            </button>
        </div>
    </div>
</div>

<script>
// ── Estado del wizard ────────────────────────────────────────
const state = {
    clienteId:   null,
    clienteNombre: '',
    deptos:      [],   // [{id, nombre}]
    equipos:     [],   // solo para mostrar en UI, ya se guardaron en BD
};

const API = '<?= BASE_PATH ?>/api/clientes.php';

// ── Helpers ──────────────────────────────────────────────────
function mostrarError(paso, msg) {
    const el = document.getElementById(`error-paso${paso}`);
    el.textContent = msg;
    el.classList.remove('d-none');
}
function ocultarError(paso) {
    document.getElementById(`error-paso${paso}`).classList.add('d-none');
}
function setLoading(btn, loading) {
    btn.disabled = loading;
    btn.dataset.original = btn.dataset.original || btn.innerHTML;
    btn.innerHTML = loading
        ? '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…'
        : btn.dataset.original;
}
function actualizarIndicador(pasoActivo) {
    for (let i = 1; i <= 3; i++) {
        const badge = document.getElementById(`step-badge-${i}`);
        const label = document.getElementById(`step-label-${i}`);
        if (i < pasoActivo) {
            badge.className = badge.className.replace('bg-secondary','bg-success');
            badge.innerHTML = '<i class="bi bi-check"></i>';
        } else if (i === pasoActivo) {
            badge.classList.replace('bg-secondary','bg-primary');
            label?.classList.replace('text-secondary','fw-bold');
        }
    }
}
function irPaso(n) {
    [1,2,3].forEach(i => document.getElementById(`paso-${i}`).classList.add('d-none'));
    document.getElementById(`paso-${n}`).classList.remove('d-none');
    actualizarIndicador(n);
    window.scrollTo(0,0);
}

// ── Selector de ciudad (con opción "Agregar nueva ciudad…") ───
const selCiudad   = document.getElementById('ciudad-select');
const inputCiudad = document.getElementById('ciudad-nueva');

selCiudad.addEventListener('change', () => {
    if (selCiudad.value === '__nueva__') {
        inputCiudad.classList.remove('d-none');
        inputCiudad.value = '';
        inputCiudad.focus();
    } else {
        inputCiudad.classList.add('d-none');
        inputCiudad.value = '';
    }
});

function obtenerCiudad() {
    if (selCiudad.value === '__nueva__') {
        return inputCiudad.value.trim();
    }
    return selCiudad.value;
}

// ── PASO 1: Guardar cliente ──────────────────────────────────
async function guardarCliente() {
    ocultarError(1);
    const razon     = document.getElementById('razon').value.trim();
    const reporto   = document.getElementById('reporto').value.trim();
    const direccion = document.getElementById('direccion').value.trim();
    const ciudad    = obtenerCiudad();
    const telefono  = document.getElementById('telefono').value.trim();
    const horario   = document.getElementById('horario').value.trim();

    if (!razon)     return mostrarError(1, 'La Razón Social es obligatoria.');
    if (!direccion) return mostrarError(1, 'La Dirección es obligatoria.');
    if (selCiudad.value === '__nueva__' && !ciudad) {
        return mostrarError(1, 'Escribe el nombre de la nueva ciudad o selecciona una existente.');
    }

    const btn = document.getElementById('btn-guardar-cliente');
    setLoading(btn, true);

    const form = new FormData();
    form.append('accion',    'crear_cliente');
    form.append('razon',     razon);
    form.append('reporto',   reporto);
    form.append('direccion', direccion);
    form.append('ciudad',    ciudad);
    form.append('telefono',  telefono);
    form.append('horario',   horario);

    try {
        const res  = await fetch(API, { 
            method: 'POST', 
            body: form,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        const texto = await res.text();
        let data;
        try { data = JSON.parse(texto); } catch(_) { return mostrarError(1, 'La sesión expiró. Recarga la página.'); }
        if (!res.ok) return mostrarError(1, data.error || 'Error al guardar.');

        state.clienteId     = data.id;
        state.clienteNombre = razon;
        document.getElementById('razon-display-2').textContent = razon;
        document.getElementById('razon-display-3').textContent = razon;
        irPaso(2);
    } catch(e) {
        mostrarError(1, 'Error: ' + e.message);
    } finally {
        setLoading(btn, false);
    }
}

// ── PASO 2: Departamentos ────────────────────────────────────
async function agregarDepto() {
    ocultarError(2);
    const input = document.getElementById('nuevo-depto');
    const nombre = input.value.trim();
    if (!nombre) return mostrarError(2, 'Escribe el nombre del departamento.');

    const form = new FormData();
    form.append('accion',       'crear_depto');
    form.append('cliente_id',   state.clienteId);
    form.append('departamento', nombre);

    try {
        const res  = await fetch(API, { 
            method: 'POST', 
            body: form,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        const texto = await res.text();
        let data;
        try { data = JSON.parse(texto); } catch(_) { return mostrarError(2, 'La sesión expiró. Recarga la página.'); }
        if (!res.ok) return mostrarError(2, data.error || 'Error al guardar.');

        state.deptos.push({ id: data.id, nombre });
        input.value = '';
        renderDeptos();
        // Actualizar select del paso 3
        const opt = document.createElement('option');
        opt.value = data.id;
        opt.textContent = nombre;
        document.getElementById('eq-depto').appendChild(opt);
    } catch(e) {
        mostrarError(2, 'Error: ' + e.message);
    }
}

function renderDeptos() {
    const cont = document.getElementById('lista-deptos');
    if (state.deptos.length === 0) {
        cont.innerHTML = '<p class="text-secondary small">Aún no has agregado departamentos.</p>';
        return;
    }
    cont.innerHTML = state.deptos.map(d =>
        `<span class="badge bg-primary me-1 mb-1 fs-6">
            <i class="bi bi-building me-1"></i>${d.nombre}
         </span>`
    ).join('');
}

// Validar que haya al menos 1 depto antes de pasar al paso 3
document.getElementById('btn-paso3').addEventListener('click', function(e) {
    e.preventDefault();
    if (state.deptos.length === 0) {
        mostrarError(2, 'Agrega al menos un departamento antes de continuar.');
        return;
    }
    irPaso(3);
});

// ── PASO 3: Equipos ──────────────────────────────────────────
async function agregarEquipo() {
    ocultarError(3);
    const deptoId = document.getElementById('eq-depto').value;
    const marca   = document.getElementById('eq-marca').value;
    const modelo  = document.getElementById('eq-modelo').value.trim();
    const serie   = document.getElementById('eq-serie').value.trim();
    const status  = document.getElementById('eq-status').value;

    if (!deptoId) return mostrarError(3, 'Selecciona el departamento del equipo.');
    if (!marca)   return mostrarError(3, 'Selecciona la marca.');
    if (!modelo)  return mostrarError(3, 'Escribe el modelo.');

    const form = new FormData();
    form.append('accion',          'crear_equipo');
    form.append('departamento_id', deptoId);
    form.append('cliente_id',      state.clienteId);
    form.append('marca',           marca);
    form.append('modelo',          modelo);
    form.append('serie',           serie);
    form.append('status',          status);

    try {
        const res  = await fetch(API, { 
            method: 'POST', 
            body: form,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        const texto = await res.text();
        let data;
        try { data = JSON.parse(texto); } catch(_) { return mostrarError(3, 'La sesión expiró. Recarga la página.'); }
        if (!res.ok) return mostrarError(3, data.error || 'Error al guardar equipo.');

        const deptoNombre = document.getElementById('eq-depto')
                              .options[document.getElementById('eq-depto').selectedIndex].text;
        state.equipos.push({ marca, modelo, serie, status, deptoNombre });

        // Limpiar campos
        ['eq-depto','eq-marca'].forEach(id => document.getElementById(id).value = '');
        ['eq-modelo','eq-serie'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('eq-status').value = 'Renta';

        renderEquipos();
    } catch(e) {
        mostrarError(3, 'Error de conexión.');
    }
}

function renderEquipos() {
    const cont = document.getElementById('lista-equipos');
    if (state.equipos.length === 0) {
        cont.innerHTML = '';
        return;
    }
    cont.innerHTML = `
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-secondary">
                <tr><th>Depto</th><th>Marca</th><th>Modelo</th><th>Serie</th><th>Status</th></tr>
            </thead>
            <tbody>
                ${state.equipos.map(eq => `
                <tr>
                    <td>${eq.deptoNombre}</td>
                    <td>${eq.marca}</td>
                    <td>${eq.modelo}</td>
                    <td><code>${eq.serie || '—'}</code></td>
                    <td><span class="badge bg-secondary">${eq.status}</span></td>
                </tr>`).join('')}
            </tbody>
        </table>`;
}

function terminar() {
    window.location.href = '<?= BASE_PATH ?>/pages/clientes/detalle.php?id=' + state.clienteId;
}

// Inicializar
renderDeptos();
renderEquipos();
</script>

<?php require_once INCLUDES . '/footer.php'; ?>
