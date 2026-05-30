<?php
// ============================================================
// logout.php — Encerra a sessão do usuário
// ============================================================

session_start();

// Remove todas as variáveis de sessão
$_SESSION = [];

// Apaga o cookie de sessão do navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destrói a sessão no servidor
session_destroy();

header('Location: index.php');
exit;
