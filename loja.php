<?php
session_start();
require_once 'db.php';

$pdo = getConexao();
$mensagem = '';
$imagemPadrao = 'assets/img/produto-sem-foto.svg';
$isAdmin = isset($_SESSION['usuario_is_admin']) && (int) $_SESSION['usuario_is_admin'] === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $produtoId = (int) ($_POST['produto_id'] ?? 0);
    $quantidade = max(1, (int) ($_POST['quantidade'] ?? 1));

    $stmtProduto = $pdo->prepare('SELECT id, nome, estoque FROM produtos WHERE id = :id AND ativo = 1');
    $stmtProduto->execute([':id' => $produtoId]);
    $produto = $stmtProduto->fetch();

    if ($produto) {
        if (!isset($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }

        $atual = (int) ($_SESSION['carrinho'][$produtoId] ?? 0);
        $novoTotal = $atual + $quantidade;
        $_SESSION['carrinho'][$produtoId] = min($novoTotal, (int) $produto['estoque']);
        $mensagem = 'Item adicionado ao carrinho.';
    }
}

$stmt = $pdo->query('SELECT * FROM produtos WHERE ativo = 1 ORDER BY criado_em DESC');
$produtos = $stmt->fetchAll();
$itensCarrinho = array_sum($_SESSION['carrinho'] ?? []);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EcoArte Tech Store</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #063b2f, #0f766e);
            color: #fff;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card-img-top {
            height: 180px;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="loja.php">EcoArte Tech Store</a>
        <div class="d-flex gap-2">
            <a href="carrinho.php" class="btn btn-outline-light btn-sm">Carrinho (<?= (int) $itensCarrinho ?>)</a>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="dashboard.php" class="btn btn-success btn-sm">Minha conta</a>
                <a href="perfil.php" class="btn btn-outline-light btn-sm">Meu perfil</a>
                <?php if ($isAdmin): ?>
                    <a href="admin_produtos.php" class="btn btn-warning btn-sm">Cadastrar produto</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Entrar</a>
                <a href="cadastro.php" class="btn btn-outline-info btn-sm">Criar conta</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-4">
    <section class="hero shadow-sm">
        <h1 class="h3 mb-2">Arte com impacto positivo</h1>
        <p class="mb-0">Pecas autorais criadas com lixo eletronico: placas, leds, motores e componentes reaproveitados.</p>
    </section>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($produtos as $produto): ?>
            <?php $imagemProduto = trim((string) ($produto['imagem_url'] ?? '')); ?>
            <?php if ($imagemProduto === '') {
                $imagemProduto = $imagemPadrao;
            } ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <img src="<?= htmlspecialchars($imagemProduto) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="card-img-top" onerror="this.onerror=null;this.src='<?= htmlspecialchars($imagemPadrao) ?>';">
                    <div class="card-body d-flex flex-column">
                        <span class="badge text-bg-secondary mb-2 align-self-start"><?= htmlspecialchars($produto['categoria']) ?></span>
                        <h2 class="h5"><?= htmlspecialchars($produto['nome']) ?></h2>
                        <p class="text-muted flex-grow-1"><?= htmlspecialchars($produto['descricao']) ?></p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-success">R$ <?= number_format((float) $produto['preco'], 2, ',', '.') ?></strong>
                            <small class="text-muted">Estoque: <?= (int) $produto['estoque'] ?></small>
                        </div>
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="acao" value="adicionar">
                            <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                            <input type="number" name="quantidade" value="1" min="1" max="<?= (int) $produto['estoque'] ?>" class="form-control form-control-sm" style="max-width: 80px;">
                            <button type="submit" class="btn btn-sm btn-success w-100">Adicionar</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
