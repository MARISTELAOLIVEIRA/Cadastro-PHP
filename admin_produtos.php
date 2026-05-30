<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_pos_login'] = 'admin_produtos.php';
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$pdo = getConexao();

$stmtPerfil = $pdo->prepare('SELECT is_admin FROM usuarios WHERE id = :id');
$stmtPerfil->execute([':id' => $_SESSION['usuario_id']]);
$perfil = $stmtPerfil->fetch();

$isAdmin = (int) ($perfil['is_admin'] ?? 0) === 1;
$_SESSION['usuario_is_admin'] = $isAdmin ? 1 : 0;

if (!$isAdmin) {
    http_response_code(403);
    echo 'Acesso negado. Apenas administradores podem cadastrar produtos.';
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $preco = (float) ($_POST['preco'] ?? 0);
    $estoque = (int) ($_POST['estoque'] ?? 0);

    if ($nome === '' || $descricao === '' || $categoria === '') {
        $erro = 'Preencha nome, descricao e categoria.';
    } elseif ($preco <= 0) {
        $erro = 'Informe um preco valido.';
    } elseif ($estoque < 0) {
        $erro = 'Estoque nao pode ser negativo.';
    }

    $imagemUrl = '';
    if (!$erro && isset($_FILES['imagem']) && (int) $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
        $arquivo = $_FILES['imagem'];

        if ((int) $arquivo['error'] !== UPLOAD_ERR_OK) {
            $erro = 'Falha no upload da imagem. Tente novamente.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $arquivo['tmp_name']) : '';

            $permitidos = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];

            if (!isset($permitidos[$mime])) {
                $erro = 'Formato de imagem invalido. Use JPG, PNG, WEBP ou GIF.';
            } else {
                $dirUpload = __DIR__ . '/uploads/produtos';
                if (!is_dir($dirUpload)) {
                    mkdir($dirUpload, 0775, true);
                }

                $ext = $permitidos[$mime];
                $nomeArquivo = 'produto_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destino = $dirUpload . '/' . $nomeArquivo;

                if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
                    $erro = 'Nao foi possivel salvar a imagem enviada.';
                } else {
                    $imagemUrl = 'uploads/produtos/' . $nomeArquivo;
                }
            }
        }
    }

    if (!$erro) {
        $insert = $pdo->prepare('
            INSERT INTO produtos (nome, descricao, categoria, preco, estoque, imagem_url, ativo)
            VALUES (:nome, :descricao, :categoria, :preco, :estoque, :imagem_url, 1)
        ');

        $insert->execute([
            ':nome' => $nome,
            ':descricao' => $descricao,
            ':categoria' => $categoria,
            ':preco' => $preco,
            ':estoque' => $estoque,
            ':imagem_url' => $imagemUrl,
        ]);

        $sucesso = 'Produto cadastrado com sucesso!';
        $_POST = [];
    }
}

$stmt = $pdo->query('SELECT id, nome, categoria, preco, estoque, criado_em FROM produtos ORDER BY id DESC LIMIT 12');
$ultimosProdutos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro de Produtos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand">Painel EcoArte</a>
        <div class="d-flex gap-2">
            <a href="loja.php" class="btn btn-outline-light btn-sm">Ver loja</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h1 class="h5 mb-0">Novo produto</h1>
                </div>
                <div class="card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>

                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da peca</label>
                            <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="categoria" class="form-label">Categoria</label>
                            <input type="text" id="categoria" name="categoria" class="form-control" value="<?= htmlspecialchars($_POST['categoria'] ?? '') ?>" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="preco" class="form-label">Preco (R$)</label>
                                <input type="number" id="preco" name="preco" class="form-control" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['preco'] ?? '') ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="estoque" class="form-label">Estoque</label>
                                <input type="number" id="estoque" name="estoque" class="form-control" min="0" value="<?= htmlspecialchars($_POST['estoque'] ?? '0') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="descricao" class="form-label">Descricao</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="4" required><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="imagem" class="form-label">Imagem do produto (opcional)</label>
                            <input type="file" id="imagem" name="imagem" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                            <small class="text-muted">Se nao enviar imagem, o sistema usara a imagem padrao automaticamente.</small>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Salvar produto</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Ultimos produtos</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Preco</th>
                                    <th>Estoque</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosProdutos as $item): ?>
                                    <tr>
                                        <td><?= (int) $item['id'] ?></td>
                                        <td><?= htmlspecialchars($item['nome']) ?></td>
                                        <td><?= htmlspecialchars($item['categoria']) ?></td>
                                        <td>R$ <?= number_format((float) $item['preco'], 2, ',', '.') ?></td>
                                        <td><?= (int) $item['estoque'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($ultimosProdutos)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Nenhum produto cadastrado.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
