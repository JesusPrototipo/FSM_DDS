<?php
// ============================================================
//  WEBDDS — pages/reportes/nuevo.php
//  Reemplaza: Reportes.php + guardar_datos.php
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerirRol([ROL_ADMIN, ROL_GERENCIA, ROL_ADMINISTRATIVO, ROL_TECNICO]);

$titulo_pagina = 'Nuevo Reporte';
$pagina_activa = 'reportes';
$subtitulo     = 'Crear Orden de Servicio';

require_once INCLUDES . '/header.php';

$pdo = Database::get();

// Técnicos para el select
$tecnicos = $pdo->query(
    "SELECT id, nombre FROM usuarios WHERE activo=1 AND rol IN ('admin','tecnico') ORDER BY nombre"
)->fetchAll();

// Precarga si viene cliente_id o equipo_id desde detalle de cliente
$preClienteId = (int)($_GET['cliente_id'] ?? 0);
$preEquipoId  = (int)($_GET['equipo_id']  ?? 0);

$preCliente = null;
if ($preClienteId) {
    $s = $pdo->prepare("SELECT id, razon FROM clientes WHERE id = :id");
    $s->execute([':id' => $preClienteId]);
    $preCliente = $s->fetch();
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= BASE_PATH ?>/pages/reportes/lista.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Regresar
    </a>
</div>

<div id="alerta-global" class="alert d-none mb-3"></div>

<div class="card">
    <div class="card-header text-bg-secondary">
        <i class="bi bi-file-plus me-1"></i> Nueva Orden de Servicio
    </div>
    <div class="card-body">
        <div class="row g-3">

            <!-- ── Búsqueda de cliente ── -->
            <div class="col-12" style="position:relative">
                <label class="form-label fw-semibold">
                    <i class="bi bi-person me-1"></i>Cliente *
                </label>
                <div class="input-group">
                    <input type="text" id="buscar-cliente" class="form-control"
                           placeholder="Escribe el nombre o teléfono del cliente…"
                           value="<?= $preCliente ? e($preCliente['razon']) : '' ?>"
                           autocomplete="off">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                </div>
                <!-- Sugerencias -->
                <ul id="sugerencias-cliente"
                    class="list-group position-absolute d-none"
                    style="top:100%; left:0; right:0; z-index:1050;
                           max-height:220px; overflow-y:auto;
                           box-shadow:0 4px 12px rgba(0,0,0,.3)"></ul>
                <!-- Valor real oculto -->
                <input type="hidden" id="cliente_id" value="<?= $preClienteId ?>">
            </div>

            <!-- ── Info del cliente seleccionado ── -->
            <div class="col-12 d-none" id="bloque-cliente">
                <div class="alert alert-secondary py-2 mb-0 small">
                    <div class="row">
                        <div class="col-md-4">
                            <span class="text-secondary">Contacto:</span>
                            <span id="info-contacto"></span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-secondary">Tel:</span>
                            <span id="info-tel"></span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-secondary">Horario:</span>
                            <span id="info-horario"></span>
                        </div>
                        <div class="col-8 mt-1">
                            <span class="text-secondary">Dirección:</span>
                            <span id="info-dir"></span>
                        </div>
                        <div class="col-4 mt-1">
                            <span class="text-secondary">Ciudad:</span>
                            <span id="info-ciudad"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Departamento ── -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">
                    <i class="bi bi-building me-1"></i>Departamento *
                </label>
                <select id="departamento_id" class="form-select" disabled>
                    <option value="">— Selecciona cliente primero —</option>
                </select>
            </div>

            <!-- ── Equipo ── -->
            <div class="col-md-8">
                <label class="form-label fw-semibold">
                    <i class="bi bi-printer me-1"></i>Equipo *
                </label>
                <select id="equipo_id" class="form-select" disabled>
                    <option value="">— Selecciona departamento primero —</option>
                </select>
            </div>

            <!-- ── Falla ── -->
            <div class="col-12">
                <label class="form-label fw-semibold">
                    <i class="bi bi-exclamation-triangle me-1"></i>Falla Reportada *
                </label>
                <textarea id="falla" class="form-control" rows="3"
                          placeholder="Describe el problema reportado por el cliente…"></textarea>
            </div>

            <!-- ── Técnico asignado ── -->
            <div class="col-md-4">
                <label class="form-label">
                    <i class="bi bi-person-badge me-1"></i>Técnico Asignado
                </label>
                <select id="tecnico_id" class="form-select">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($tecnicos as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= e($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ── Tipo de Servicio ── -->
            <div class="col-md-4">
                <label class="form-label">
                    <i class="bi bi-person-badge me-1"></i>Tipo de Servicio
                </label>
                <select class="form-select" id="tipo_servicio">
                    <option value="">— Sin asignar —</option>
                    <?php foreach (['Instalacion','Mantenimiento','Conexion','Asesoria','Revision'] as $m): ?>
                    <option value="<?= $m ?>"><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ── Fecha ── -->
            <div class="col-md-4">
                <label class="form-label">
                    <i class="bi bi-calendar me-1"></i>Fecha del Reporte
                </label>
                <input type="date" id="fecha" class="form-control"
                       value="<?= date('Y-m-d') ?>">
            </div>

        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <a href="<?= BASE_PATH ?>/pages/reportes/lista.php" class="btn btn-outline-secondary">
            Cancelar
        </a>
        <button class="btn btn-primary" id="btn-guardar" onclick="guardarReporte()">
            <i class="bi bi-check-circle me-1"></i> Crear Reporte
        </button>
    </div>
</div>

<script>
const API_CLI = '<?= BASE_PATH ?>/api/clientes.php';
const API_REP = '<?= BASE_PATH ?>/api/reportes.php';

// ── Autocompletado de cliente ────────────────────────────────
let buscarTimer = null;
const inputCliente    = document.getElementById('buscar-cliente');
const sugerencias     = document.getElementById('sugerencias-cliente');
const inputClienteId  = document.getElementById('cliente_id');
const selDepto        = document.getElementById('departamento_id');
const selEquipo       = document.getElementById('equipo_id');
let deptosPorId       = {}; // cache deptos del cliente

inputCliente.addEventListener('input', () => {
    clearTimeout(buscarTimer);
    const q = inputCliente.value.trim();
    if (q.length < 2) { sugerencias.classList.add('d-none'); return; }
    buscarTimer = setTimeout(() => buscarClientes(q), 300);
});

document.addEventListener('click', e => {
    if (!sugerencias.contains(e.target) && e.target !== inputCliente) {
        sugerencias.classList.add('d-none');
    }
});

async function buscarClientes(q) {
    try {
        const res = await fetch(
            `${API_CLI}?accion=buscar&q=${encodeURIComponent(q)}`,
            {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }
        );

        const texto = await res.text();

        let data;
        try {
            data = JSON.parse(texto);
        } catch(parseErr) {
            sugerencias.innerHTML =
                '<li class="list-group-item text-danger small">' +
                'Error inesperado. Recarga la página e intenta de nuevo.' +
                '</li>';
            sugerencias.classList.remove('d-none');
            return;
        }

        // Sesión expirada
        if (res.status === 401) {
            sugerencias.innerHTML =
                '<li class="list-group-item text-danger small">' +
                'Sesión expirada. <a href="<?= BASE_PATH ?>/login.php">Inicia sesión de nuevo</a>' +
                '</li>';
            sugerencias.classList.remove('d-none');
            return;
        }

        renderSugerencias(data.data ?? []);
    } catch(e) {
        sugerencias.innerHTML =
            '<li class="list-group-item text-danger small">Sin conexión con el servidor</li>';
        sugerencias.classList.remove('d-none');
    }
}

function renderSugerencias(lista) {
    sugerencias.innerHTML = '';
    if (!lista.length) {
        sugerencias.innerHTML = '<li class="list-group-item text-secondary small">Sin resultados</li>';
        sugerencias.classList.remove('d-none');
        return;
    }
    lista.forEach(c => {
        const li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action small';
        li.innerHTML = `<strong>${c.razon}</strong> <span class="text-secondary ms-2">${c.reporto ?? ''}</span>`;
        li.style.cursor = 'pointer';
        li.addEventListener('click', () => seleccionarCliente(c));
        sugerencias.appendChild(li);
    });
    sugerencias.classList.remove('d-none');
}

async function seleccionarCliente(c) {
    inputCliente.value   = c.razon;
    inputClienteId.value = c.id;
    sugerencias.classList.add('d-none');

    try {
        // Cargar detalle completo para info + deptos/equipos
        const res  = await fetch(`${API_CLI}?accion=detalle&id=${c.id}`, { credentials: 'same-origin' });
        if (!res.ok) { mostrarAlerta('danger', 'No se pudo cargar el detalle del cliente.'); return; }
        const data = await res.json();

        // Mostrar info
        document.getElementById('info-contacto').textContent = data.cliente?.reporto  ?? '—';
        document.getElementById('info-tel').textContent      = data.cliente?.telefono  ?? '—';
        document.getElementById('info-horario').textContent  = data.cliente?.horario   ?? '—';
        document.getElementById('info-dir').textContent      = data.cliente?.direccion ?? '—';
        document.getElementById('info-ciudad').textContent   = data.cliente?.ciudad ?? '—';
        document.getElementById('bloque-cliente').classList.remove('d-none');

        // Poblar select de departamentos
        selDepto.innerHTML = '<option value="">— Selecciona departamento —</option>';
        deptosPorId = {};
        (data.departamentos ?? []).forEach(d => {
            deptosPorId[d.id] = d.equipos;
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.departamento;
            selDepto.appendChild(opt);
        });
        selDepto.disabled = false;
        selEquipo.innerHTML = '<option value="">— Selecciona depto primero —</option>';
        selEquipo.disabled = true;

        // Si no tiene departamentos, avisar
        if ((data.departamentos ?? []).length === 0) {
            mostrarAlerta('warning',
                'Este cliente no tiene departamentos registrados. ' +
                '<a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=' + c.id + '">Agrégalos aquí</a> antes de crear el reporte.');
        }
    } catch(e) {
        mostrarAlerta('danger', 'Error al cargar los datos del cliente.');
    }
}

// Al cambiar departamento, cargar equipos
selDepto.addEventListener('change', () => {
    const did    = selDepto.value;
    const equipos = deptosPorId[did] ?? [];
    selEquipo.innerHTML = '<option value="">— Selecciona equipo —</option>';
    equipos.forEach(eq => {
        const opt = document.createElement('option');
        opt.value = eq.id;
        opt.textContent = `${eq.marca} ${eq.modelo}${eq.serie ? ' · ' + eq.serie : ''}`;
        selEquipo.appendChild(opt);
    });
    selEquipo.disabled = equipos.length === 0;
});


// ── Prueba Whats reporte ──────────────────────────────────────────


function compartirWhatsApp() {
    const selTecnico    = document.getElementById('tecnico_id');
    const optionTecnico = selTecnico.options[selTecnico.selectedIndex];
    
    // Extraer nombre y teléfono
    const nombreTecnico = optionTecnico ? optionTecnico.text : 'Sin asignar';
    const telTecnico    = optionTecnico ? optionTecnico.getAttribute('data-tel') : '';

    // Formatear mención (ejemplo: @521878xxxxxxx o @Nombre)
    const mencionTecnico = telTecnico ? `@${telTecnico} (${nombreTecnico})` : `@${nombreTecnico}`;

    const clienteNombre = document.getElementById('buscar-cliente').value || '—';
    const direccion     = document.getElementById('info-dir').textContent || '—';
    
    const selDepto      = document.getElementById('departamento_id');
    const departamento  = selDepto.options[selDepto.selectedIndex]?.text || '—';
    
    const horario       = document.getElementById('info-horario').textContent || '—';
    const telefono      = document.getElementById('info-tel').textContent || '—';

    const selEquipo     = document.getElementById('equipo_id');
    const infoEquipo    = selEquipo.options[selEquipo.selectedIndex]?.text || '—';

    const falla         = document.getElementById('falla').value.trim() || '—';

    if (!clienteNombre || clienteNombre === '—') {
        mostrarAlerta('warning', 'Selecciona un cliente antes de generar el mensaje.');
        return;
    }

    // Plantilla con el formato exacto de WhatsApp
    const mensaje = 
`*Ingeniero Asignado:* ${mencionTecnico}

*Info del Cliente*
Nombre: ${clienteNombre}
Dirección: ${direccion}
Departamento: ${departamento}
Horario: ${horario}
Teléfono: ${telefono}

*Info del Equipo*
${infoEquipo}

*Info del Problema*
${falla}`;

    const url = `https://api.whatsapp.com/send?text=${encodeURIComponent(mensaje)}`;
    window.open(url, '_blank');
}

// ── Guardar reporte ──────────────────────────────────────────
async function guardarReporte() {
    const alerta = document.getElementById('alerta-global');
    alerta.className = 'alert d-none';

    const clienteId = inputClienteId.value;
    const deptoId   = selDepto.value;
    const equipoId  = selEquipo.value;
    const falla     = document.getElementById('falla').value.trim();

    if (!clienteId) return mostrarAlerta('danger', 'Selecciona un cliente.');
    if (!deptoId)   return mostrarAlerta('danger', 'Selecciona el departamento.');
    if (!equipoId)  return mostrarAlerta('danger', 'Selecciona el equipo.');
    if (!falla)     return mostrarAlerta('danger', 'Describe la falla reportada.');

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    const form = new FormData();
    form.append('accion',          'crear');
    form.append('cliente_id',      clienteId);
    form.append('departamento_id', deptoId);
    form.append('equipo_id',       equipoId);
    form.append('falla',           falla);
    form.append('tecnico_id',      document.getElementById('tecnico_id').value);
    form.append('fecha',           document.getElementById('fecha').value);
    form.append('tipo_servicio',   document.getElementById('tipo_servicio').value);

    try {
        const res  = await fetch(API_REP, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) return mostrarAlerta('danger', data.error || 'Error al crear el reporte.');

        // Redirigir al detalle del reporte recién creado
        window.location.href = '<?= BASE_PATH ?>/pages/reportes/detalle.php?id=' + data.id;
    } catch(e) {
        mostrarAlerta('danger', 'Error de conexión con el servidor.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Crear Reporte';
    }
}

function mostrarAlerta(tipo, msg) {
    const el = document.getElementById('alerta-global');
    el.className = `alert alert-${tipo}`;
    el.textContent = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Precargar cliente si viene por URL
<?php if ($preClienteId && $preCliente): ?>
(async () => {
    await seleccionarCliente({ id: <?= $preClienteId ?>, razon: '<?= e($preCliente['razon']) ?>' });
    <?php if ($preEquipoId): ?>
    // Preseleccionar equipo si viene por URL
    setTimeout(() => {
        const equipoSel = document.getElementById('equipo_id');
        for (let opt of equipoSel.options) {
            if (opt.value == '<?= $preEquipoId ?>') { opt.selected = true; break; }
        }
    }, 400);
    <?php endif; ?>
})();
<?php endif; ?>

</script>

<?php require_once INCLUDES . '/footer.php'; ?>
