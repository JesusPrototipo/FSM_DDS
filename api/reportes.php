<?php
// ============================================================
//  WEBDDS — api/reportes.php
//  Reemplaza: guardar_datos.php (parcialmente), lógica dispersa
//
//  Rutas GET:
//    ?accion=lista                          → reportes paginados + filtros
//    ?accion=detalle&id=N                   → reporte completo
//    ?accion=tecnicos                       → lista de técnicos para select
//
//  Rutas POST:
//    accion=crear                           → nuevo reporte
//    accion=cambiar_estatus                 → pendiente/en proceso/finalizado/cancelado
//                                              (acepta 'checklist' JSON opcional al finalizar)
//    accion=agregar_nota                    → nota del técnico en bitácora
// ============================================================

require_once dirname(__DIR__) . '/includes/init.php';
Auth::requerir();

$pdo    = Database::get();
$accion = $_REQUEST['accion'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────────────────────────
if ($method === 'GET') {

    // Lista paginada con filtros
    if ($accion === 'lista') {
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 20;
        $offset    = ($pagina - 1) * $porPagina;

        // Filtros opcionales
        $estatus    = $_GET['estatus']    ?? '';
        $tecnico_id = (int)($_GET['tecnico_id'] ?? 0);
        $cliente_id = (int)($_GET['cliente_id'] ?? 0);
        $fecha_ini  = $_GET['fecha_ini']  ?? '';
        $fecha_fin  = $_GET['fecha_fin']  ?? '';
        $q          = trim($_GET['q']     ?? '');

        $where  = ['1=1'];
        $params = [];

        if ($estatus)    { $where[] = 'r.estatus = :estatus';       $params[':estatus']    = $estatus; }
        if ($tecnico_id) { $where[] = 'r.tecnico_id = :tecnico_id'; $params[':tecnico_id'] = $tecnico_id; }
        if ($cliente_id) { $where[] = 'r.id_cliente = :cliente_id'; $params[':cliente_id'] = $cliente_id; }
        if ($fecha_ini)  { $where[] = 'r.fecha >= :fecha_ini';      $params[':fecha_ini']  = $fecha_ini; }
        if ($fecha_fin)  { $where[] = 'r.fecha <= :fecha_fin';      $params[':fecha_fin']  = $fecha_fin; }
        if ($q)          {
            $where[] = '(c.razon LIKE :q1 OR i.modelo LIKE :q2 OR i.serie LIKE :q3 OR r.falla LIKE :q4)';
            $params[':q1'] = "%$q%";
            $params[':q2'] = "%$q%";
            $params[':q3'] = "%$q%";
            $params[':q4'] = "%$q%";
        }

        $whereStr = implode(' AND ', $where);

        $baseJoin = "FROM reportes_fallas r
                     INNER JOIN clientes      c ON r.id_cliente      = c.id
                     INNER JOIN departamentos d ON r.id_departamento  = d.id
                     INNER JOIN impresoras    i ON r.id_impresora     = i.id
                     LEFT  JOIN usuarios      u ON r.tecnico_id       = u.id
                     WHERE $whereStr";

        // Total
        $stmtTotal = $pdo->prepare("SELECT COUNT(*) $baseJoin");
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        // Datos
        $stmt = $pdo->prepare(
            "SELECT r.id, r.fecha, r.falla, r.estatus, r.observaciones, r.tservicio,
                    c.id AS cliente_id, c.razon,
                    d.departamento,
                    i.id AS equipo_id, i.marca, i.modelo, i.serie,
                    u.nombre AS tecnico_nombre
             $baseJoin
             ORDER BY r.id DESC
             LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        jsonResponse([
            'data'       => $stmt->fetchAll(),
            'total'      => $total,
            'pagina'     => $pagina,
            'por_pagina' => $porPagina,
            'paginas'    => (int)ceil($total / $porPagina),
        ]);
    }

    // Detalle completo de un reporte
    if ($accion === 'detalle') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID inválido'], 400);

        $stmt = $pdo->prepare(
            "SELECT r.*,
                    c.razon, c.reporto, c.telefono, c.direccion, c.horario,
                    d.departamento,
                    i.marca, i.modelo, i.serie, i.status AS equipo_status,
                    u.nombre AS tecnico_nombre
             FROM reportes_fallas r
             INNER JOIN clientes      c ON r.id_cliente     = c.id
             INNER JOIN departamentos d ON r.id_departamento = d.id
             INNER JOIN impresoras    i ON r.id_impresora   = i.id
             LEFT  JOIN usuarios      u ON r.tecnico_id     = u.id
             WHERE r.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $reporte = $stmt->fetch();

        if (!$reporte) jsonResponse(['error' => 'Reporte no encontrado'], 404);
        jsonResponse($reporte);
    }

    // Lista de técnicos (para selects)
    if ($accion === 'tecnicos') {
        $stmt = $pdo->query(
            "SELECT id, nombre, rol FROM usuarios
             WHERE activo = 1 AND rol IN ('admin','tecnico')
             ORDER BY nombre"
        );
        jsonResponse($stmt->fetchAll());
    }

    jsonResponse(['error' => 'Acción no reconocida'], 400);
}

// ── POST ─────────────────────────────────────────────────────
if ($method === 'POST') {

    // ── Crear reporte ────────────────────────────────────────
    if ($accion === 'crear') {
        Auth::requerirRol([ROL_ADMIN, ROL_ADMINISTRATIVO, ROL_TECNICO, ROL_GERENCIA]);

        $clienteId     = (int)($_POST['cliente_id']      ?? 0);
        $deptoId       = (int)($_POST['departamento_id'] ?? 0);
        $equipoId      = (int)($_POST['equipo_id']       ?? 0);
        $falla         = trim($_POST['falla']            ?? '');
        $tecnicoId     = (int)($_POST['tecnico_id']      ?? 0) ?: null;
        $fecha         = trim($_POST['fecha']            ?? date('Y-m-d'));
        $tipo_servicio  = trim($_POST['tipo_servicio']    ?? ''); // 👈 NUEVO

        if (!$clienteId || !$deptoId || !$equipoId || !$falla) {
            jsonResponse(['error' => 'Faltan datos obligatorios (cliente, departamento, equipo, falla).'], 422);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO reportes_fallas
                (id_cliente, id_departamento, id_impresora, fecha, falla, estatus, tecnico_id, observaciones, tservicio)
            VALUES
                (:cid, :did, :iid, :fecha, :falla, 'pendiente', :tecnico_id, '', :tservicio)"
        );
        $stmt->execute([
            ':cid'        => $clienteId,
            ':did'        => $deptoId,
            ':iid'        => $equipoId,
            ':fecha'      => $fecha,
            ':falla'      => $falla,
            ':tecnico_id' => $tecnicoId,
            ':tservicio'  => $tipo_servicio,  // 👈 NUEVO
        ]);
        $nuevoId = (int)$pdo->lastInsertId();
        jsonResponse(['ok' => true, 'id' => $nuevoId]);
    }

    // ── Cambiar estatus ──────────────────────────────────────
    if ($accion === 'cambiar_estatus') {
        Auth::requerirRol([ROL_ADMIN, ROL_TECNICO]);

        $id        = (int)($_POST['id']      ?? 0);
        $estatus   = trim($_POST['estatus'] ?? '');
        $nota      = trim($_POST['nota']    ?? '');
        $checklist = $_POST['checklist']    ?? null; // JSON, solo viene al finalizar

        $permitidos = ['pendiente', 'en proceso', 'finalizado', 'cancelado'];
        if (!$id || !in_array($estatus, $permitidos, true)) {
            jsonResponse(['error' => 'Datos inválidos.'], 422);
        }

        // Validar que el checklist sea JSON válido antes de guardarlo
        $checklistValido = null;
        if ($checklist) {
            json_decode($checklist, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $checklistValido = $checklist;
            }
        }

        // Guardar estatus + concatenar nota en observaciones con timestamp
        $usuario   = Auth::usuario();
        $timestamp = date('d/m/Y H:i');
        $entrada   = "[$timestamp — {$usuario['nombre']} → $estatus]" . ($nota ? ": $nota" : '');

        $stmt = $pdo->prepare(
            "UPDATE reportes_fallas
             SET estatus = :estatus,
                 observaciones = CONCAT(IFNULL(observaciones,''), '\n', :entrada),
                 tecnico_id    = COALESCE(tecnico_id, :uid),
                 checklist_finalizacion = COALESCE(:checklist, checklist_finalizacion)
             WHERE id = :id"
        );
        $stmt->execute([
            ':estatus'   => $estatus,
            ':entrada'   => $entrada,
            ':uid'       => $usuario['id'],
            ':checklist' => $checklistValido,
            ':id'        => $id,
        ]);
        jsonResponse(['ok' => true]);
    }

    // ── Agregar nota del técnico ─────────────────────────────
    if ($accion === 'agregar_nota') {
        Auth::requerirRol([ROL_ADMIN, ROL_TECNICO, ROL_ADMINISTRATIVO, ROL_GERENCIA]);

        $id   = (int)($_POST['id']   ?? 0);
        $nota = trim($_POST['nota'] ?? '');

        if (!$id || !$nota) jsonResponse(['error' => 'Nota vacía.'], 422);

        $usuario   = Auth::usuario();
        $timestamp = date('d/m/Y H:i');
        $entrada   = "[$timestamp — {$usuario['nombre']}]: $nota";

        $stmt = $pdo->prepare(
            "UPDATE reportes_fallas
             SET observaciones = CONCAT(IFNULL(observaciones,''), '\n', :entrada)
             WHERE id = :id"
        );
        $stmt->execute([':entrada' => $entrada, ':id' => $id]);
        jsonResponse(['ok' => true]);
    }

    // ── Reasignar técnico ────────────────────────────────────
    if ($accion === 'reasignar_tecnico') {
        Auth::requerirRol([ROL_ADMIN]);

        $id        = (int)($_POST['id']         ?? 0);
        $tecnicoId = (int)($_POST['tecnico_id'] ?? 0) ?: null;
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 400);

        $usuario   = Auth::usuario();
        $timestamp = date('d/m/Y H:i');
        $nombre    = $tecnicoId
            ? $pdo->query("SELECT nombre FROM usuarios WHERE id=$tecnicoId")->fetchColumn()
            : 'Sin asignar';
        $entrada = "[$timestamp — {$usuario['nombre']}]: Técnico reasignado a $nombre";

        $stmt = $pdo->prepare(
            "UPDATE reportes_fallas
             SET tecnico_id    = :tid,
                 observaciones = CONCAT(IFNULL(observaciones,''), '\n', :entrada)
             WHERE id = :id"
        );
        $stmt->execute([':tid' => $tecnicoId, ':entrada' => $entrada, ':id' => $id]);
        jsonResponse(['ok' => true]);
    }

    // ── Exportar CSV ─────────────────────────────────────────
    if ($accion === 'exportar_csv') {
        $estatus    = $_GET['estatus']    ?? '';
        $tecnico_id = (int)($_GET['tecnico_id'] ?? 0);
        $fecha_ini  = $_GET['fecha_ini']  ?? '';
        $fecha_fin  = $_GET['fecha_fin']  ?? '';

        $where  = ['1=1'];
        $params = [];
        if ($estatus)    { $where[] = 'r.estatus = :estatus';   $params[':estatus']    = $estatus; }
        if ($tecnico_id) { $where[] = 'r.tecnico_id = :tid';    $params[':tid']        = $tecnico_id; }
        if ($fecha_ini)  { $where[] = 'r.fecha >= :fecha_ini';  $params[':fecha_ini']  = $fecha_ini; }
        if ($fecha_fin)  { $where[] = 'r.fecha <= :fecha_fin';  $params[':fecha_fin']  = $fecha_fin; }
        $whereStr = implode(' AND ', $where);

        $stmt = $pdo->prepare(
            "SELECT r.id, r.fecha, r.estatus,
                    c.razon, c.telefono, c.direccion,
                    d.departamento,
                    i.marca, i.modelo, i.serie,
                    r.falla, r.observaciones,
                    u.nombre AS tecnico
             FROM reportes_fallas r
             INNER JOIN clientes      c ON r.id_cliente     = c.id
             INNER JOIN departamentos d ON r.id_departamento = d.id
             INNER JOIN impresoras    i ON r.id_impresora   = i.id
             LEFT  JOIN usuarios      u ON r.tecnico_id     = u.id
             WHERE $whereStr
             ORDER BY r.id DESC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Encabezados HTTP para descarga
        $filename = 'reportes_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        // BOM para que Excel abra UTF-8 correctamente
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Folio','Fecha','Estatus','Cliente','Telefono',
            'Direccion','Departamento','Marca','Modelo','Serie',
            'Falla','Observaciones','Tecnico'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                str_pad($r['id'], 5, '0', STR_PAD_LEFT),
                $r['fecha'], $r['estatus'],
                $r['razon'], $r['telefono'], $r['direccion'],
                $r['departamento'],
                $r['marca'], $r['modelo'], $r['serie'],
                $r['falla'], $r['observaciones'],
                $r['tecnico'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    jsonResponse(['error' => 'Acción no reconocida'], 400);
}

jsonResponse(['error' => 'Método no permitido'], 405);