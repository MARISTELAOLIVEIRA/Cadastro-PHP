<?php
session_start();
require_once 'db.php';

$pdo = getConexao();
$_SESSION['carrinho'] = $_SESSION['carrinho'] ?? [];
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'atualizar') {
        $quantidades = $_POST['quantidades'] ?? [];
        foreach ($quantidades as $produtoId => $quantidade) {
            $produtoId = (int) $produtoId;
            $quantidade = (int) $quantidade;

            if ($quantidade <= 0) {
                unset($_SESSION['carrinho'][$produtoId]);
            } else {
                $_SESSION['carrinho'][$produtoId] = $quantidade;
            }
        }
        $mensagem = 'Carrinho atualizado com sucesso.';
    }

    if ($acao === 'limpar') {
        $_SESSION['carrinho'] = [];
        $mensagem = 'Carrinho esvaziado.';
    }

    if ($acao === 'finalizar') {
        if (!isset($_SESSION['usuario_id'])) {
            $_SESSION['redirect_pos_login'] = 'carrinho.php';
            header('Location: login.php');
            exit;
        }

        $stmtPerfil = $pdo->prepare('SELECT cpf, endereco, numero, bairro, cidade, estado, cep, telefone FROM usuarios WHERE id = :id');
        $stmtPerfil->execute([':id' => $_SESSION['usuario_id']]);
        $perfil = $stmtPerfil->fetch();

        $camposObrigatorios = ['cpf', 'endereco', 'numero', 'bairro', 'cidade', 'estado', 'cep', 'telefone'];
        $perfilIncompleto = false;

        foreach ($camposObrigatorios as $campo) {
            if (empty(trim((string) ($perfil[$campo] ?? '')))) {
                $perfilIncompleto = true;
                break;
            }
        }

        if ($perfilIncompleto) {
            $_SESSION['redirect_apos_perfil'] = 'carrinho.php';
            header('Location: perfil.php');
            exit;
        }

        if (empty($_SESSION['carrinho'])) {
            $erro = 'Seu carrinho esta vazio.';
        } else {
            $ids = array_keys($_SESSION['carrinho']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, nome, preco, estoque FROM produtos WHERE id IN ($placeholders) AND ativo = 1");
            $stmt->execute($ids);
            $produtos = $stmt->fetchAll();

            $mapaProdutos = [];
            foreach ($produtos as $produto) {
                $mapaProdutos[(int) $produto['id']] = $produto;
            }

            $itensPedido = [];
            $totalPedido = 0.0;

            foreach ($_SESSION['carrinho'] as $produtoId => $quantidade) {
                $produtoId = (int) $produtoId;
                $quantidade = (int) $quantidade;

                if (!isset($mapaProdutos[$produtoId])) {
                    continue;
                }

                $produto = $mapaProdutos[$produtoId];
                if ($quantidade > (int) $produto['estoque']) {
                    $erro = 'Estoque insuficiente para o item: ' . $produto['nome'];
                    break;
                }

                $preco = (float) $produto['preco'];
                $subtotal = $preco * $quantidade;
                $totalPedido += $subtotal;

                $itensPedido[] = [
                    'produto_id' => $produtoId,
                    'nome' => $produto['nome'],
                    'preco' => $preco,
                    'quantidade' => $quantidade,
                    'subtotal' => $subtotal,
                ];
            }

            if (!$erro && !empty($itensPedido)) {
                try {
                    $pdo->beginTransaction();

                    $insertPedido = $pdo->prepare('
                        INSERT INTO pedidos (usuario_id, valor_total, status)
                        VALUES (:usuario_id, :valor_total, :status)
                    ');
                    $insertPedido->execute([
                        ':usuario_id' => $_SESSION['usuario_id'],
                        ':valor_total' => $totalPedido,
                        ':status' => 'recebido',
                    ]);

                    $pedidoId = (int) $pdo->lastInsertId();

                    $insertItem = $pdo->prepare('
                        INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, preco_unitario, quantidade, subtotal)
                        VALUES (:pedido_id, :produto_id, :nome_produto, :preco_unitario, :quantidade, :subtotal)
                    ');

                    $updateEstoque = $pdo->prepare('UPDATE produtos SET estoque = estoque - :quantidade WHERE id = :id');

                    foreach ($itensPedido as $item) {
                        $insertItem->execute([
                            ':pedido_id' => $pedidoId,
                            ':produto_id' => $item['produto_id'],
                            ':nome_produto' => $item['nome'],
                            ':preco_unitario' => $item['preco'],
                            ':quantidade' => $item['quantidade'],
                            ':subtotal' => $item['subtotal'],
                        ]);

                        $updateEstoque->execute([
                            ':quantidade' => $item['quantidade'],
                            ':id' => $item['produto_id'],
                        ]);
                    }

                    $pdo->commit();
                    $_SESSION['carrinho'] = [];
                    $mensagem = 'Pedido #' . $pedidoId . ' finalizado com sucesso!';
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $erro = 'Nao foi possivel finalizar o pedido agora. Tente novamente.';
                }
            }
        }
    }
}

$itens = [];
$total = 0.0;

if (!empty($_SESSION['carrinho'])) {
    $ids = array_keys($_SESSION['carrinho']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nome, preco, estoque FROM produtos WHERE id IN ($placeholders) AND ativo = 1");
    $stmt->execute($ids);
    $produtos = $stmt->fetchAll();

    foreach ($produtos as $produto) {
        $id = (int) $produto['id'];
        $quantidade = (int) ($_SESSION['carrinho'][$id] ?? 0);
        if ($quantidade <= 0) {
            continue;
        }

        $preco = (float) $produto['preco'];
        $subtotal = $preco * $quantidade;
        $total += $subtotal;

        $itens[] = [
            'id' => $id,
            'nome' => $produto['nome'],
            'preco' => $preco,
            'quantidade' => $quantidade,
            'subtotal' => $subtotal,
            'estoque' => (int) $produto['estoque'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrinho</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a href="loja.php" class="navbar-brand">EcoArte Tech Store</a>
        <div class="d-flex gap-2">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="meus_pedidos.php" class="btn btn-outline-light btn-sm">Meus pedidos</a>
                <a href="dashboard.php" class="btn btn-outline-info btn-sm">Minha conta</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h1 class="h3 mb-3">Seu carrinho</h1>

    <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if (empty($itens)): ?>
        <div class="alert alert-info">Seu carrinho esta vazio. <a href="loja.php">Voltar para a loja</a>.</div>
    <?php else: ?>
        <form method="post" class="card shadow-sm mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Preco</th>
                                <th>Quantidade</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nome']) ?></td>
                                    <td>R$ <?= number_format($item['preco'], 2, ',', '.') ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            class="form-control"
                                            name="quantidades[<?= (int) $item['id'] ?>]"
                                            value="<?= (int) $item['quantidade'] ?>"
                                            min="0"
                                            max="<?= (int) $item['estoque'] ?>"
                                            style="max-width: 100px;"
                                        >
                                    </td>
                                    <td>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>Total: R$ <?= number_format($total, 2, ',', '.') ?></strong>
                <div class="d-flex gap-2">
                    <button type="submit" name="acao" value="atualizar" class="btn btn-outline-secondary">Atualizar carrinho</button>
                    <button type="submit" name="acao" value="limpar" class="btn btn-outline-danger">Limpar</button>
                    <button type="submit" name="acao" value="finalizar" class="btn btn-success">Finalizar pedido</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
