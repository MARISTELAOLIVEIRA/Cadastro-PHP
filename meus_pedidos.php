<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_pos_login'] = 'meus_pedidos.php';
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$pdo = getConexao();

$stmt = $pdo->prepare('
    SELECT p.id, p.valor_total, p.status, p.criado_em,
           COUNT(pi.id) AS itens
    FROM pedidos p
    LEFT JOIN pedido_itens pi ON pi.pedido_id = p.id
    WHERE p.usuario_id = :usuario_id
    GROUP BY p.id, p.valor_total, p.status, p.criado_em
    ORDER BY p.criado_em DESC
');
$stmt->execute([':usuario_id' => $_SESSION['usuario_id']]);
$pedidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meus Pedidos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Minha conta</a>
        <div class="d-flex gap-2">
            <a href="loja.php" class="btn btn-outline-light btn-sm">Loja</a>
            <a href="carrinho.php" class="btn btn-outline-light btn-sm">Carrinho</a>
            <a href="logout.php" class="btn btn-light btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h1 class="h3 mb-3">Meus pedidos</h1>

    <?php if (empty($pedidos)): ?>
        <div class="alert alert-info">
            Voce ainda nao fez nenhum pedido. <a href="loja.php">Comecar compras</a>.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Itens</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?= (int) $pedido['id'] ?></td>
                                <td><?= htmlspecialchars($pedido['criado_em']) ?></td>
                                <td><span class="badge text-bg-secondary"><?= htmlspecialchars($pedido['status']) ?></span></td>
                                <td><?= (int) $pedido['itens'] ?></td>
                                <td>R$ <?= number_format((float) $pedido['valor_total'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
