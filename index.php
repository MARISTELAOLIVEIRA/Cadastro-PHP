<?php
// ============================================================
// index.php — Ponto de entrada da aplicação
// ============================================================

session_start();

// Redireciona para o dashboard se já estiver logado,
// ou para a loja publica caso contrário
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: loja.php');
}
exit;
