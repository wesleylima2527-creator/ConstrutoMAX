<?php
// API do painel admin. Só responde para a conta admin logada.
//
// GET  admin_api.php?action=counts
// GET  admin_api.php?action=stats
// GET  admin_api.php?action=revenue&period=7d|30d|90d|month|year
// GET  admin_api.php?action=orders|quotes|users&q=&status=&page=
// GET  admin_api.php?action=order&id=   |  action=quote&id=
// GET  admin_api.php?action=export&type=orders|quotes|users&q=&status=
// POST admin_api.php?action=set_status   { type: order|quote, id, status }   (header X-CSRF-Token)
declare(strict_types=1);

require __DIR__ . '/admin_auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const QUOTE_STATUS = ['novo', 'em_atendimento', 'respondido', 'fechado', 'cancelado'];
const ORDER_STATUS = ['aguardando_pagamento', 'pago', 'em_separacao', 'enviado', 'entregue', 'cancelado'];
const CONFIRMED_SQL = "('pago','em_separacao','enviado','entregue')";
const PAGE_SIZE = 20;
const EXPORT_LIMIT = 5000;
const LOCAL_TZ = 'America/Fortaleza';

function out(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function fail(string $msg, int $status = 400): void {
    out(['ok' => false, 'error' => $msg], $status);
}

/* ---------- utilidades ---------- */

function like(string $q): string {
    return '%' . addcslashes($q, '\\%_') . '%';
}

function id_from(string $q): int {
    $d = ltrim($q, '# ');
    return ctype_digit($d) ? (int)$d : 0;
}

function fetch_rows(PDO $pdo, string $sql, array $args = [], ?int $limit = null, int $offset = 0): array {
    if ($limit !== null) {
        $sql .= ' LIMIT ? OFFSET ?';
        $args[] = $limit;
        $args[] = $offset;
    }
    $st = $pdo->prepare($sql);
    foreach (array_values($args) as $i => $v) {
        $st->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll();
}

function scalar(PDO $pdo, string $sql, array $args = []): int {
    $rows = fetch_rows($pdo, $sql, $args);
    if (!$rows) return 0;
    return (int)array_values($rows[0])[0];
}

function fmt_local(?string $utc): string {
    if (!$utc) return '';
    try {
        $d = new DateTime($utc, new DateTimeZone('UTC'));
        $d->setTimezone(new DateTimeZone(LOCAL_TZ));
        return $d->format('d/m/Y H:i');
    } catch (Exception $e) {
        return (string)$utc;
    }
}

function label(string $slug): string {
    return ucfirst(str_replace('_', ' ', $slug));
}

/* ---------- filtros e consultas ---------- */

const ORDER_SELECT = 'SELECT p.id, p.nome, p.whatsapp, p.mensagem, p.forma_pagamento, p.status, p.total_cents,
                             p.criado_em AS created_at, u.email
                        FROM pedidos p LEFT JOIN usuarios u ON u.id = p.usuario_id';
const ORDER_COUNT = 'SELECT COUNT(*) FROM pedidos p LEFT JOIN usuarios u ON u.id = p.usuario_id';

const QUOTE_SELECT = 'SELECT q.id, q.nome, q.whatsapp, q.mensagem, q.status, q.created_at, u.email
                        FROM quotes q LEFT JOIN usuarios u ON u.id = q.user_id';
const QUOTE_COUNT = 'SELECT COUNT(*) FROM quotes q LEFT JOIN usuarios u ON u.id = q.user_id';

const USER_SELECT = 'SELECT u.id, u.nome, u.email, u.whatsapp, u.created_at,
                            (SELECT COUNT(*) FROM pedidos p WHERE p.usuario_id = u.id) AS pedidos,
                            (SELECT COUNT(*) FROM quotes q WHERE q.user_id = u.id) AS orcamentos
                       FROM usuarios u';
const USER_COUNT = 'SELECT COUNT(*) FROM usuarios u';

function orders_filter(string $q, string $status): array {
    $where = [];
    $args = [];
    if ($q !== '') {
        $l = like($q);
        $where[] = '(p.nome LIKE ? OR p.whatsapp LIKE ? OR u.email LIKE ? OR p.id = ?)';
        array_push($args, $l, $l, $l, id_from($q));
    }
    if ($status !== '' && in_array($status, ORDER_STATUS, true)) {
        $where[] = 'p.status = ?';
        $args[] = $status;
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $args];
}

function quotes_filter(string $q, string $status): array {
    $where = [];
    $args = [];
    if ($q !== '') {
        $l = like($q);
        $where[] = '(q.nome LIKE ? OR q.whatsapp LIKE ? OR q.mensagem LIKE ? OR u.email LIKE ? OR q.id = ?)';
        array_push($args, $l, $l, $l, $l, id_from($q));
    }
    if ($status !== '' && in_array($status, QUOTE_STATUS, true)) {
        $where[] = 'q.status = ?';
        $args[] = $status;
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $args];
}

function users_filter(string $q): array {
    if ($q === '') return ['', []];
    $l = like($q);
    return [' WHERE (u.nome LIKE ? OR u.email LIKE ? OR u.whatsapp LIKE ? OR u.id = ?)', [$l, $l, $l, id_from($q)]];
}

function norm_orders(array $rows): array {
    return array_map(function (array $r): array {
        $r['id'] = (int)$r['id'];
        $r['total_cents'] = (int)$r['total_cents'];
        return $r;
    }, $rows);
}

function norm_quotes(array $rows): array {
    return array_map(function (array $r): array {
        $r['id'] = (int)$r['id'];
        return $r;
    }, $rows);
}

function norm_users(array $rows): array {
    return array_map(function (array $r): array {
        $r['id'] = (int)$r['id'];
        $r['pedidos'] = (int)$r['pedidos'];
        $r['orcamentos'] = (int)$r['orcamentos'];
        $r['is_admin'] = strtolower((string)$r['email']) === ADMIN_EMAIL;
        return $r;
    }, $rows);
}

/* ---------- ações ---------- */

function api_counts(PDO $pdo): void {
    out([
        'ok' => true,
        'orders_pending' => scalar($pdo, "SELECT COUNT(*) FROM pedidos WHERE status = 'aguardando_pagamento'"),
        'quotes_new'     => scalar($pdo, "SELECT COUNT(*) FROM quotes WHERE status = 'novo'"),
    ]);
}

function api_stats(PDO $pdo): void {
    $sales = fetch_rows($pdo, 'SELECT COALESCE(SUM(total_cents), 0) AS s, COUNT(*) AS c FROM pedidos WHERE status IN ' . CONFIRMED_SQL);

    // Gráfico dos últimos 14 dias, no horário de Fortaleza.
    $tz = new DateTimeZone(LOCAL_TZ);
    $first = (new DateTimeImmutable('today', $tz))->modify('-13 days');
    $start = $first->format('Y-m-d') . ' 00:00:00';

    $pdo->exec("SET time_zone = '-03:00'");
    $o = fetch_rows($pdo, 'SELECT DATE(criado_em) AS d, COUNT(*) AS c FROM pedidos WHERE criado_em >= ? GROUP BY DATE(criado_em)', [$start]);
    $q = fetch_rows($pdo, 'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM quotes WHERE created_at >= ? GROUP BY DATE(created_at)', [$start]);
    $pdo->exec("SET time_zone = '+00:00'");

    $po = [];
    foreach ($o as $r) $po[(string)$r['d']] = (int)$r['c'];
    $pq = [];
    foreach ($q as $r) $pq[(string)$r['d']] = (int)$r['c'];

    $days = [];
    for ($i = 0; $i < 14; $i++) {
        $d = $first->modify('+' . $i . ' days')->format('Y-m-d');
        $days[] = ['date' => $d, 'orders' => $po[$d] ?? 0, 'quotes' => $pq[$d] ?? 0];
    }

    [$ow, $oa] = orders_filter('', '');
    [$qw, $qa] = quotes_filter('', '');

    out([
        'ok' => true,
        'sales_cents'     => (int)$sales[0]['s'],
        'sales_count'     => (int)$sales[0]['c'],
        'orders_total'    => scalar($pdo, 'SELECT COUNT(*) FROM pedidos'),
        'orders_pending'  => scalar($pdo, "SELECT COUNT(*) FROM pedidos WHERE status = 'aguardando_pagamento'"),
        'quotes_total'    => scalar($pdo, 'SELECT COUNT(*) FROM quotes'),
        'quotes_new'      => scalar($pdo, "SELECT COUNT(*) FROM quotes WHERE status = 'novo'"),
        'users_total'     => scalar($pdo, 'SELECT COUNT(*) FROM usuarios'),
        'users_week'      => scalar($pdo, 'SELECT COUNT(*) FROM usuarios WHERE created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY)'),
        'days'            => $days,
        'recent_orders'   => norm_orders(fetch_rows($pdo, ORDER_SELECT . $ow . ' ORDER BY p.id DESC', $oa, 5)),
        'recent_quotes'   => norm_quotes(fetch_rows($pdo, QUOTE_SELECT . $qw . ' ORDER BY q.id DESC', $qa, 5)),
    ]);
}

function api_revenue(PDO $pdo): void {
    $period = (string)($_GET['period'] ?? '30d');
    $tz = new DateTimeZone(LOCAL_TZ);
    $today = new DateTimeImmutable('today', $tz);
    $group = 'day';

    switch ($period) {
        case '7d':    $first = $today->modify('-6 days'); break;
        case '90d':   $first = $today->modify('-89 days'); break;
        case 'month': $first = $today->modify('first day of this month'); break;
        case 'year':  $first = $today->setDate((int)$today->format('Y'), 1, 1); $group = 'month'; break;
        default:      $period = '30d'; $first = $today->modify('-29 days');
    }
    $start = $first->format('Y-m-d') . ' 00:00:00';
    $bucket = $group === 'month' ? "DATE_FORMAT(criado_em, '%Y-%m')" : 'DATE(criado_em)';

    // Datas no horário de Fortaleza (mesmo critério do gráfico da visão geral).
    $pdo->exec("SET time_zone = '-03:00'");
    try {
        $sales = fetch_rows($pdo,
            "SELECT $bucket AS b, COALESCE(SUM(total_cents), 0) AS s, COUNT(*) AS c
               FROM pedidos WHERE status IN " . CONFIRMED_SQL . " AND criado_em >= ? GROUP BY $bucket", [$start]);
        $pay = fetch_rows($pdo,
            'SELECT forma_pagamento AS f, COALESCE(SUM(total_cents), 0) AS s, COUNT(*) AS c
               FROM pedidos WHERE status IN ' . CONFIRMED_SQL . ' AND criado_em >= ?
              GROUP BY forma_pagamento ORDER BY s DESC', [$start]);
        $other = fetch_rows($pdo,
            "SELECT status, COALESCE(SUM(total_cents), 0) AS s, COUNT(*) AS c
               FROM pedidos WHERE status IN ('aguardando_pagamento','cancelado') AND criado_em >= ? GROUP BY status", [$start]);
    } finally {
        $pdo->exec("SET time_zone = '+00:00'");
    }

    $map = [];
    foreach ($sales as $r) $map[(string)$r['b']] = [(int)$r['s'], (int)$r['c']];

    $buckets = [];
    $revenue = 0;
    $count = 0;
    for ($d = $first; $d <= $today; $d = $d->modify($group === 'month' ? '+1 month' : '+1 day')) {
        $k = $d->format($group === 'month' ? 'Y-m' : 'Y-m-d');
        $v = $map[$k] ?? [0, 0];
        $revenue += $v[0];
        $count += $v[1];
        $buckets[] = ['key' => $k, 'cents' => $v[0], 'orders' => $v[1]];
    }

    $pending = ['s' => 0, 'c' => 0];
    $canceled = ['s' => 0, 'c' => 0];
    foreach ($other as $r) {
        if ($r['status'] === 'aguardando_pagamento') $pending = ['s' => (int)$r['s'], 'c' => (int)$r['c']];
        if ($r['status'] === 'cancelado')            $canceled = ['s' => (int)$r['s'], 'c' => (int)$r['c']];
    }

    out([
        'ok'             => true,
        'period'         => $period,
        'group'          => $group,
        'revenue_cents'  => $revenue,
        'orders_count'   => $count,
        'avg_cents'      => $count > 0 ? (int)round($revenue / $count) : 0,
        'pending_cents'  => $pending['s'],
        'pending_count'  => $pending['c'],
        'canceled_cents' => $canceled['s'],
        'canceled_count' => $canceled['c'],
        'buckets'        => $buckets,
        'by_payment'     => array_map(function (array $r): array {
            return ['forma_pagamento' => (string)$r['f'], 'total_cents' => (int)$r['s'], 'orders' => (int)$r['c']];
        }, $pay),
    ]);
}

function api_list(PDO $pdo, string $action): void {
    $q = trim((string)($_GET['q'] ?? ''));
    if (mb_strlen($q) > 100) $q = mb_substr($q, 0, 100);
    $status = (string)($_GET['status'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * PAGE_SIZE;

    if ($action === 'orders') {
        [$w, $a] = orders_filter($q, $status);
        $total = scalar($pdo, ORDER_COUNT . $w, $a);
        $rows = norm_orders(fetch_rows($pdo, ORDER_SELECT . $w . ' ORDER BY p.id DESC', $a, PAGE_SIZE, $offset));
    } elseif ($action === 'quotes') {
        [$w, $a] = quotes_filter($q, $status);
        $total = scalar($pdo, QUOTE_COUNT . $w, $a);
        $rows = norm_quotes(fetch_rows($pdo, QUOTE_SELECT . $w . ' ORDER BY q.id DESC', $a, PAGE_SIZE, $offset));
    } else {
        [$w, $a] = users_filter($q);
        $total = scalar($pdo, USER_COUNT . $w, $a);
        $rows = norm_users(fetch_rows($pdo, USER_SELECT . $w . ' ORDER BY u.id DESC', $a, PAGE_SIZE, $offset));
    }

    out(['ok' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'page_size' => PAGE_SIZE]);
}

function api_order(PDO $pdo): void {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) fail('Pedido inválido.', 422);

    $rows = norm_orders(fetch_rows($pdo, ORDER_SELECT . ' WHERE p.id = ?', [$id], 1));
    if (!$rows) fail('Pedido não encontrado.', 404);

    $items = fetch_rows(
        $pdo,
        'SELECT produto_id, nome, unidade, preco_cents, quantidade FROM pedido_itens WHERE pedido_id = ? ORDER BY id',
        [$id]
    );
    $items = array_map(function (array $it): array {
        $it['preco_cents'] = (int)$it['preco_cents'];
        $it['quantidade'] = (int)$it['quantidade'];
        return $it;
    }, $items);
    out(['ok' => true, 'order' => $rows[0], 'items' => $items]);
}

function api_quote(PDO $pdo): void {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) fail('Orçamento inválido.', 422);

    $rows = norm_quotes(fetch_rows($pdo, QUOTE_SELECT . ' WHERE q.id = ?', [$id], 1));
    if (!$rows) fail('Orçamento não encontrado.', 404);
    out(['ok' => true, 'quote' => $rows[0]]);
}

function api_set_status(PDO $pdo): void {
    $sent = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($sent === '' || !hash_equals(admin_csrf(), $sent)) {
        fail('Sessão do painel expirada. Recarregue a página.', 419);
    }

    $in = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($in)) fail('Dados inválidos.', 400);

    $type = (string)($in['type'] ?? '');
    $id = (int)($in['id'] ?? 0);
    $status = (string)($in['status'] ?? '');

    if ($type === 'order') {
        $table = 'pedidos';
        $allowed = ORDER_STATUS;
    } elseif ($type === 'quote') {
        $table = 'quotes';
        $allowed = QUOTE_STATUS;
    } else {
        fail('Tipo inválido.', 422);
        return;
    }
    if ($id <= 0 || !in_array($status, $allowed, true)) fail('Status inválido.', 422);

    $st = $pdo->prepare('UPDATE ' . $table . ' SET status = ? WHERE id = ?');
    $st->execute([$status, $id]);

    if ($st->rowCount() === 0 && scalar($pdo, 'SELECT COUNT(*) FROM ' . $table . ' WHERE id = ?', [$id]) === 0) {
        fail('Registro não encontrado.', 404);
    }
    out(['ok' => true, 'id' => $id, 'status' => $status]);
}

function csv_cell($v): string {
    $s = (string)$v;
    // Evita que o Excel execute fórmulas vindas de dados digitados por clientes.
    if ($s !== '' && strpos("=+-@\t\r", $s[0]) !== false) $s = "'" . $s;
    return $s;
}

function send_csv(string $name, array $head, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '-' . date('Y-m-d') . '.csv"');
    $f = fopen('php://output', 'w');
    fwrite($f, "\xEF\xBB\xBF"); // BOM: o Excel abre com acentos corretos
    fputcsv($f, $head, ';', '"', '\\');
    foreach ($rows as $r) {
        fputcsv($f, array_map('csv_cell', $r), ';', '"', '\\');
    }
    fclose($f);
    exit;
}

function api_export(PDO $pdo): void {
    $type = (string)($_GET['type'] ?? '');
    $q = trim((string)($_GET['q'] ?? ''));
    if (mb_strlen($q) > 100) $q = mb_substr($q, 0, 100);
    $status = (string)($_GET['status'] ?? '');

    if ($type === 'orders') {
        [$w, $a] = orders_filter($q, $status);
        $rows = fetch_rows($pdo, ORDER_SELECT . $w . ' ORDER BY p.id DESC', $a, EXPORT_LIMIT);
        $data = [];
        foreach ($rows as $r) {
            $data[] = [$r['id'], fmt_local($r['created_at']), $r['nome'], $r['whatsapp'], $r['email'] ?? '',
                       label((string)$r['forma_pagamento']), label((string)$r['status']),
                       number_format(((int)$r['total_cents']) / 100, 2, ',', '')];
        }
        send_csv('pedidos', ['Pedido', 'Data', 'Cliente', 'WhatsApp', 'E-mail', 'Pagamento', 'Status', 'Total (R$)'], $data);
    } elseif ($type === 'quotes') {
        [$w, $a] = quotes_filter($q, $status);
        $rows = fetch_rows($pdo, QUOTE_SELECT . $w . ' ORDER BY q.id DESC', $a, EXPORT_LIMIT);
        $data = [];
        foreach ($rows as $r) {
            $data[] = [$r['id'], fmt_local($r['created_at']), $r['nome'], $r['whatsapp'], $r['email'] ?? '',
                       label((string)$r['status']), $r['mensagem']];
        }
        send_csv('orcamentos', ['Orçamento', 'Data', 'Cliente', 'WhatsApp', 'E-mail', 'Status', 'Mensagem'], $data);
    } elseif ($type === 'users') {
        [$w, $a] = users_filter($q);
        $rows = fetch_rows($pdo, USER_SELECT . $w . ' ORDER BY u.id DESC', $a, EXPORT_LIMIT);
        $data = [];
        foreach ($rows as $r) {
            $data[] = [$r['id'], fmt_local($r['created_at']), $r['nome'], $r['email'], $r['whatsapp'] ?? '',
                       $r['pedidos'], $r['orcamentos']];
        }
        send_csv('clientes', ['ID', 'Cadastro', 'Nome', 'E-mail', 'WhatsApp', 'Pedidos', 'Orçamentos'], $data);
    } else {
        fail('Exportação inválida.', 422);
    }
}

/* ---------- roteamento ---------- */

try {
    if (!admin_user()) {
        fail('Acesso negado.', 403);
    }

    $pdo = db();
    // O painel interpreta created_at como UTC (igual ao index.html).
    $pdo->exec("SET time_zone = '+00:00'");

    $action = (string)($_GET['action'] ?? '');
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        if ($action !== 'set_status') fail('Ação inválida.', 400);
        api_set_status($pdo);
    }
    if ($method !== 'GET') {
        fail('Método não permitido.', 405);
    }

    switch ($action) {
        case 'counts':  api_counts($pdo); break;
        case 'stats':   api_stats($pdo); break;
        case 'revenue': api_revenue($pdo); break;
        case 'orders':
        case 'quotes':
        case 'users':   api_list($pdo, $action); break;
        case 'order':   api_order($pdo); break;
        case 'quote':   api_quote($pdo); break;
        case 'export':  api_export($pdo); break;
        default:        fail('Ação inválida.', 400);
    }
} catch (PDOException $e) {
    error_log('admin_api.php PDO: ' . $e->getMessage());
    fail('Erro ao acessar o banco de dados.', 500);
}