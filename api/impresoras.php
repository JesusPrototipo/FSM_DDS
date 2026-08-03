<?php
// ============================================================
//  WEBDDS — api/impresoras.php
//
//  GET  ?accion=lista                    → todas las impresoras paginadas
//  GET  ?accion=buscar&q=texto           → por serie, modelo o cliente
//  GET  ?accion=detalle&id=N             → equipo + historial de reportes
//  POST accion=actualizar                → editar datos del equipo
// ============================================================

require_once dirname(__DIR__) . '/includes/init.php';
Auth::requerir();

$pdo    = Database::get();
$accion = $_REQUEST['accion'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    // Lista paginada
    if ($accion === 'lista') {
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 20;
        $offset    = ($pagina - 1) * $porPagina;
        $q         = trim($_GET['q'] ?? '');

        $where  = ['1=1'];
        $params = [];
        if ($q) {
            $where[]       = '(i.serie LIKE :q1 OR i.modelo LIKE :q2 OR c.razon LIKE :q3)';
            $params[':q1'] = "%$q%";
            $params[':q2'] = "%$q%";
            $params[':q3'] = "%$q%";
        }
        $whereStr = implode(' AND ', $where);

        $base = "FROM impresoras i
                 INNER JOIN clientes      c ON i.cliente_id      = c.id
                 INNER JOIN departamentos d ON i.departamento_id = d.id
                 WHERE $whereStr";

        $total = $pdo->prepare("SELECT COUNT(*) $base");
        $total->execute($params);
        $total = (int)$total->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT i.id, i.marca, i.modelo, i.serie, i.status,
                    c.id AS cliente_id, c.razon,
                    d.departamento
             $base
             ORDER BY c.razon, d.departamento, i.modelo
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

    // Búsqueda rápida
    if ($accion === 'buscar') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) jsonResponse(['data' => []]);

        $stmt = $pdo->prepare(
            "SELECT i.id, i.marca, i.modelo, i.serie, i.status,
                    c.razon, d.departamento
             FROM impresoras i
             INNER JOIN clientes      c ON i.cliente_id      = c.id
             INNER JOIN departamentos d ON i.departamento_id = d.id
             WHERE i.serie LIKE :q1 OR i.modelo LIKE :q2 OR c.razon LIKE :q3
             ORDER BY c.razon LIMIT 15"
        );
        $stmt->execute([':q1' => "%$q%", ':q2' => "%$q%", ':q3' => "%$q%"]);
        jsonResponse(['data' => $stmt->fetchAll()]);
    }

    // Detalle + historial de reportes del equipo (bitácora)
    if ($accion === 'detalle') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 400);

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
        $equipo = $stmt->fetch();
        if (!$equipo) jsonResponse(['error' => 'Equipo no encontrado.'], 404);

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

        jsonResponse([
            'equipo'   => $equipo,
            'historial'=> $hist->fetchAll(),
        ]);
    }

    jsonResponse(['error' => 'Acción no reconocida.'], 400);
}

if ($method === 'POST') {

    if ($accion === 'actualizar') {
        Auth::requerirRol([ROL_ADMIN, ROL_VENDEDOR, ROL_CALLCENTER]);

        $id    = (int)($_POST['id'] ?? 0);
        $serie = trim($_POST['serie']  ?? '');
        if (!$id) jsonResponse(['error' => 'ID inválido.'], 400);

        // Verificar serie duplicada en otro equipo
        if ($serie) {
            $check = $pdo->prepare(
                "SELECT id FROM impresoras WHERE serie = :s AND id != :id"
            );
            $check->execute([':s' => $serie, ':id' => $id]);
            if ($check->fetch()) {
                jsonResponse(['error' => 'Ese número de serie ya está registrado.'], 409);
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE impresoras
             SET marca=:marca, modelo=:modelo, serie=:serie, status=:status
             WHERE id=:id"
        );
        $stmt->execute([
            ':marca'  => trim($_POST['marca']  ?? ''),
            ':modelo' => trim($_POST['modelo'] ?? ''),
            ':serie'  => $serie ?: null,
            ':status' => trim($_POST['status'] ?? 'Renta'),
            ':id'     => $id,
        ]);
        jsonResponse(['ok' => true]);
    }

    jsonResponse(['error' => 'Acción no reconocida.'], 400);
}

jsonResponse(['error' => 'Método no permitido.'], 405);
