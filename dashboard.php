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

$isAdmin = (int) ($usuario['is_admin'] ?? 0) === 1;
$_SESSION['usuario_is_admin'] = $isAdmin ? 1 : 0;
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
        <span class="navbar-brand mb-0 h1">EcoArte Tech Store</span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Bem-vindo, <?= htmlspecialchars($usuario['nome']) ?>!</h5>
            <p class="card-text text-muted">Sua conta esta pronta para comprar arte feita com tecnologia reciclada.</p>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="loja.php" class="btn btn-success">Ver catalogo</a>
                <a href="carrinho.php" class="btn btn-outline-primary">Carrinho</a>
                <a href="meus_pedidos.php" class="btn btn-outline-dark">Meus pedidos</a>
                <a href="perfil.php" class="btn btn-outline-secondary">Meu perfil</a>
                <?php if ($isAdmin): ?>
                    <a href="admin_produtos.php" class="btn btn-warning">Cadastrar produto</a>
                <?php endif; ?>
            </div>

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
