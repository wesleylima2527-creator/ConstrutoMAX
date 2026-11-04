<?php
// Autenticação do painel admin. Usado por admin.php e admin_api.php.
// Mesma pasta de: db.php, login.php, index.html...
require_once __DIR__ . '/db.php';

// Única conta com acesso ao painel.
const ADMIN_EMAIL = 'wesleygomes150220@gmail.com';

function admin_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Devolve os dados do admin logado, ou null se não houver sessão
 * ou se a conta logada não for a conta admin.
 * A verificação é sempre feita no servidor, a cada requisição.
 */
function admin_user(): ?array {
    admin_session();
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id <= 0) return null;

    $st = db()->prepare('SELECT id, nome, email FROM usuarios WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $u = $st->fetch();

    if (!$u || strtolower((string)$u['email']) !== ADMIN_EMAIL) return null;
    return $u;
}

/** Token anti-CSRF da sessão (usado nas ações que alteram dados). */
function admin_csrf(): string {
    admin_session();
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['admin_csrf'];
}
