<?php
// ============================================================
//  WEBDDS — Header + Navbar reutilizable
//  Incluir al INICIO de cada página: require_once INCLUDES . '/header.php';
//  Variable opcional antes de incluir: $pagina_activa = 'clientes';
// ============================================================

// Valores por defecto
$titulo_pagina  = $titulo_pagina  ?? APP_NAME;
$pagina_activa  = $pagina_activa  ?? '';
$subtitulo      = $subtitulo      ?? '';

$usuario = class_exists('Auth') ? Auth::usuario() : null;

// Helper para marcar el nav-link activo
function navActivo(string $pagina, string $actual): string {
    return $pagina === $actual ? 'active" aria-current="page' : '';
}
?>
<!DOCTYPE html>
<html lang="es-MX" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo_pagina) ?> — <?= APP_NAME ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- Estilos propios -->
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">
</head>

<body class="bg-body-tertiary">

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= BASE_PATH ?>/index.php">
            <i class="bi bi-printer me-1"></i> DDS
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navPrincipal"
                aria-controls="navPrincipal" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link <?= navActivo('inicio', $pagina_activa) ?>"
                       href="<?= BASE_PATH ?>/index.php">
                       <i class="bi bi-house me-1"></i>Inicio
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= navActivo('reportes', $pagina_activa) ?>"
                       href="<?= BASE_PATH ?>/pages/reportes/lista.php">
                       <i class="bi bi-file-text me-1"></i>Reportes
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= navActivo('clientes', $pagina_activa) ?>"
                       href="<?= BASE_PATH ?>/pages/clientes/lista.php">
                       <i class="bi bi-people me-1"></i>Clientes
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= navActivo('impresoras', $pagina_activa) ?>"
                       href="<?= BASE_PATH ?>/pages/impresoras/lista.php">
                       <i class="bi bi-printer me-1"></i>Impresoras
                    </a>
                </li>

                <?php if (Auth::tieneRol(defined('ROL_ADMIN') ? ROL_ADMIN : 'admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= navActivo('usuarios', $pagina_activa) ?>"
                       href="<?= BASE_PATH ?>/pages/usuarios/lista.php">
                       <i class="bi bi-person-gear me-1"></i>Usuarios
                    </a>
                </li>
                <?php endif; ?>

                <?php if (Auth::tieneRol(defined('ROL_ADMIN') ? ROL_ADMIN : 'admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= navActivo('usuarios', $pagina_activa) ?>"
                        href="<?= BASE_PATH ?>/pages/usuarios/lista.php">
                        Usuarios
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

            <!-- Búsqueda global -->
            <div class="position-relative me-2" id="buscador-global-wrap">
                <div class="input-group input-group-sm" style="min-width:240px">
                    <input type="text" id="busqueda-global" class="form-control"
                           placeholder="Buscar cliente, serie, reporte…"
                           autocomplete="off">
                    <span class="input-group-text bg-secondary border-secondary">
                        <i class="bi bi-search text-white"></i>
                    </span>
                </div>
                <!-- Resultados dropdown -->
                <div id="resultados-busqueda"
                     class="d-none position-absolute bg-body border rounded shadow-sm"
                     style="top:calc(100% + 4px); left:0; right:0; min-width:340px;
                            max-height:400px; overflow-y:auto; z-index:1050;">
                </div>
            </div>

            <!-- Usuario activo y logout -->
            <?php if ($usuario): ?>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary text-uppercase">
                    <?= htmlspecialchars($usuario['rol']) ?>
                </span>
                <span class="text-white-50 small">
                    <?= htmlspecialchars($usuario['nombre']) ?>
                </span>
                <a href="<?= BASE_PATH ?>/pages/perfil/index.php"
                   class="btn btn-outline-light btn-sm" title="Mi perfil">
                    <i class="bi bi-person-circle"></i>
                </a>
                <a href="<?= BASE_PATH ?>/logout.php"
                   class="btn btn-outline-light btn-sm" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ===== CONTENIDO PRINCIPAL ===== -->
<main class="container py-4">

    <!-- Encabezado de sección (opcional) -->
<!-- JS Búsqueda global (disponible en todas las páginas) -->
<script>
(function(){
    const input = document.getElementById('busqueda-global');
    const caja  = document.getElementById('resultados-busqueda');
    if (!input) return;
    const BASE  = '<?= BASE_PATH ?>';
    let timer   = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { caja.classList.add('d-none'); return; }
        timer = setTimeout(() => buscar(q), 300);
    });

    document.addEventListener('click', e => {
        if (!caja.contains(e.target) && e.target !== input)
            caja.classList.add('d-none');
    });

    async function buscar(q) {
        try {
            const res  = await fetch(`${BASE}/api/busqueda.php?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            renderResultados(data, q);
        } catch(e) {}
    }

    function renderResultados(data, q) {
        const { clientes = [], equipos = [], reportes = [] } = data;
        const total = clientes.length + equipos.length + reportes.length;

        if (total === 0) {
            caja.innerHTML = `<div class="p-3 text-secondary small text-center">
                Sin resultados para "<strong>${esc(q)}</strong>"</div>`;
            caja.classList.remove('d-none');
            return;
        }

        let html = '';

        if (clientes.length) {
            html += `<div class="px-3 py-1 text-secondary small fw-bold border-bottom"
                          style="font-size:.7rem;letter-spacing:.5px">
                          CLIENTES</div>`;
            clientes.forEach(c => {
                html += `<a href="${BASE}/pages/clientes/detalle.php?id=${c.id}"
                            class="d-block px-3 py-2 text-decoration-none hover-item border-bottom">
                            <div class="fw-semibold small">${esc(c.razon)}</div>
                            <div class="text-secondary" style="font-size:.75rem">
                                ${esc(c.reporto)} · ${esc(c.telefono)}
                            </div>
                         </a>`;
            });
        }

        if (equipos.length) {
            html += `<div class="px-3 py-1 text-secondary small fw-bold border-bottom"
                          style="font-size:.7rem;letter-spacing:.5px">
                          EQUIPOS</div>`;
            equipos.forEach(e => {
                html += `<a href="${BASE}/pages/impresoras/detalle.php?id=${e.id}"
                            class="d-block px-3 py-2 text-decoration-none hover-item border-bottom">
                            <div class="fw-semibold small">
                                ${esc(e.marca)} ${esc(e.modelo)}
                            </div>
                            <div class="text-secondary" style="font-size:.75rem">
                                Serie: ${esc(e.serie||'—')} · ${esc(e.razon)}
                            </div>
                         </a>`;
            });
        }

        if (reportes.length) {
            html += `<div class="px-3 py-1 text-secondary small fw-bold border-bottom"
                          style="font-size:.7rem;letter-spacing:.5px">
                          REPORTES</div>`;
            reportes.forEach(r => {
                const falla = r.falla.length > 60 ? r.falla.slice(0,60)+'…' : r.falla;
                html += `<a href="${BASE}/pages/reportes/detalle.php?id=${r.id}"
                            class="d-block px-3 py-2 text-decoration-none hover-item border-bottom">
                            <div class="fw-semibold small">
                                #${String(r.id).padStart(5,'0')} · ${esc(r.razon)}
                            </div>
                            <div class="text-secondary" style="font-size:.75rem">
                                ${esc(falla)}
                            </div>
                         </a>`;
            });
        }

        caja.innerHTML = html;
        caja.classList.remove('d-none');
    }

    function esc(str) {
        return String(str||'')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
<style>
.hover-item:hover { background: rgba(255,255,255,.06); }
</style>

    <?php if ($subtitulo): ?>
    <div class="d-flex align-items-center p-3 mb-3 text-white bg-body rounded shadow-sm">
        <i class="bi bi-tools fs-3 me-3 text-secondary"></i>
        <div>
            <h6 class="mb-0 text-white">Portal de Servicio Técnico</h6>
            <small class="text-secondary"><?= htmlspecialchars($subtitulo) ?></small>
        </div>
    </div>
    <?php endif; ?>