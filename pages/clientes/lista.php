<?php
// ============================================================
//  WEBDDS — pages/clientes/lista.php
//  Reemplaza: Clientes.php (paginación decorativa y sin búsqueda)
// ============================================================
require_once dirname(__DIR__, 2) . '/includes/init.php';
Auth::requerir();

$titulo_pagina = 'Clientes';
$pagina_activa = 'clientes';
$subtitulo     = 'Clientes registrados';

require_once INCLUDES . '/header.php';

$pdo      = Database::get();
$pagina   = max(1, (int)($_GET['pagina'] ?? 1));
$busqueda = trim($_GET['q'] ?? '');
$porPagina = 15;
$offset    = ($pagina - 1) * $porPagina;

// Consulta con o sin búsqueda
if ($busqueda !== '') {
    $like  = "%$busqueda%";
    $total = $pdo->prepare(
        "SELECT COUNT(*) FROM clientes WHERE razon LIKE :q1 OR reporto LIKE :q2 OR telefono LIKE :q3"
    );
    $total->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
    $total = (int)$total->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT id, razon, reporto, telefono
         FROM clientes
         WHERE razon LIKE :q1 OR reporto LIKE :q2 OR telefono LIKE :q3
         ORDER BY razon ASC
         LIMIT :lim OFFSET :off"
    );
    $stmt->bindValue(':q1', $like);
    $stmt->bindValue(':q2', $like);
    $stmt->bindValue(':q3', $like);
    $stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,    PDO::PARAM_INT);
    $stmt->execute();
} else {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $stmt  = $pdo->prepare(
        "SELECT id, razon, reporto, telefono
         FROM clientes ORDER BY razon ASC
         LIMIT :lim OFFSET :off"
    );
    $stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,    PDO::PARAM_INT);
    $stmt->execute();
}

$clientes   = $stmt->fetchAll();
$totalPaginas = (int)ceil($total / $porPagina);

// Helper para armar URLs de paginación conservando búsqueda
function urlPagina(int $p, string $q): string {
    $params = ['pagina' => $p];
    if ($q !== '') $params['q'] = $q;
    return '?' . http_build_query($params);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        Clientes
        <span class="badge bg-secondary ms-1"><?= $total ?></span>
    </h5>

    <?php if (Auth::tieneRol(ROL_ADMIN, ROL_CALLCENTER, ROL_VENDEDOR)): ?>
    <a href="<?= BASE_PATH ?>/pages/clientes/nuevo.php" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i> Nuevo Cliente
    </a>
    <?php endif; ?>
</div>

<!-- Búsqueda -->
<form method="GET" action="" class="mb-3">
    <div class="input-group search-bar">
        <input type="text" name="q" class="form-control"
               placeholder="Nombre, contacto o teléfono…"
               value="<?= e($busqueda) ?>" autofocus>
        <button class="btn btn-secondary" type="submit">
            <i class="bi bi-search"></i>
        </button>
        <?php if ($busqueda): ?>
        <a href="?" class="btn btn-outline-secondary">
            <i class="bi bi-x"></i>
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- Tabla -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th style="width:60px">#</th>
                    <th>Razón Social</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clientes)): ?>
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        <?= $busqueda ? 'Sin resultados para "' . e($busqueda) . '"' : 'No hay clientes registrados' ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td class="text-secondary small"><?= e($c['id']) ?></td>
                    <td><?= e($c['razon']) ?></td>
                    <td><?= e($c['reporto']) ?></td>
                    <td><?= e($c['telefono']) ?></td>
                    <td>
                        <a href="<?= BASE_PATH ?>/pages/clientes/detalle.php?id=<?= $c['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginación real -->
<?php if ($totalPaginas > 1): ?>
<nav class="mt-3" aria-label="Paginación de clientes">
    <ul class="pagination justify-content-center mb-0">

        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= urlPagina($pagina - 1, $busqueda) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>

        <?php
        // Mostrar máximo 5 páginas alrededor de la actual
        $inicio = max(1, $pagina - 2);
        $fin    = min($totalPaginas, $pagina + 2);
        if ($inicio > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= urlPagina(1, $busqueda) ?>">1</a>
            </li>
            <?php if ($inicio > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
            <a class="page-link" href="<?= urlPagina($i, $busqueda) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= urlPagina($totalPaginas, $busqueda) ?>">
                    <?= $totalPaginas ?>
                </a>
            </li>
        <?php endif; ?>

        <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= urlPagina($pagina + 1, $busqueda) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>

    </ul>
</nav>
<p class="text-center text-secondary small mt-2">
    Mostrando <?= count($clientes) ?> de <?= $total ?> clientes
</p>
<?php endif; ?>

<?php require_once INCLUDES . '/footer.php'; ?>
