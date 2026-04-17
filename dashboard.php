<?php
// ============================================================
// dashboard.php — Área restrita (somente usuários logados)
// ============================================================

session_start();

// Proteção: redireciona para login se não houver sessão ativa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

// Busca dados atualizados do usuário logado no banco
$pdo  = getConexao();
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Se o usuário não existir mais no banco, encerra a sessão
if (!$usuario) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Sistema de Cadastro PHP</span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Bem-vindo, <?= htmlspecialchars($usuario['nome']) ?>!</h5>
            <p class="card-text text-muted">Você está autenticado com sucesso.</p>
            <hr>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <strong>ID:</strong> <?= htmlspecialchars($usuario['id']) ?>
                </li>
                <li class="list-group-item">
                    <strong>Nome:</strong> <?= htmlspecialchars($usuario['nome']) ?>
                </li>
                <li class="list-group-item">
                    <strong>E-mail:</strong> <?= htmlspecialchars($usuario['email']) ?>
                </li>
                <li class="list-group-item">
                    <strong>Cadastrado em:</strong> <?= htmlspecialchars($usuario['criado_em']) ?>
                </li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>
