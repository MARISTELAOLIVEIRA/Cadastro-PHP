<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_pos_login'] = 'perfil.php';
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$pdo = getConexao();
$erro = '';
$sucesso = '';

$stmt = $pdo->prepare('SELECT nome, email, cpf, endereco, numero, bairro, cidade, estado, cep, telefone FROM usuarios WHERE id = :id');
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = trim($_POST['cpf'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['estado'] ?? ''));
    $cep = trim($_POST['cep'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if ($cpf === '' || $endereco === '' || $numero === '' || $bairro === '' || $cidade === '' || $estado === '' || $cep === '' || $telefone === '') {
        $erro = 'Preencha todos os campos obrigatorios para finalizar pedidos.';
    } elseif (strlen($estado) !== 2) {
        $erro = 'Informe a UF com 2 letras (ex: DF, SP, RJ).';
    } else {
        $update = $pdo->prepare('
            UPDATE usuarios
            SET cpf = :cpf,
                endereco = :endereco,
                numero = :numero,
                bairro = :bairro,
                cidade = :cidade,
                estado = :estado,
                cep = :cep,
                telefone = :telefone
            WHERE id = :id
        ');

        $update->execute([
            ':cpf' => $cpf,
            ':endereco' => $endereco,
            ':numero' => $numero,
            ':bairro' => $bairro,
            ':cidade' => $cidade,
            ':estado' => $estado,
            ':cep' => $cep,
            ':telefone' => $telefone,
            ':id' => $_SESSION['usuario_id'],
        ]);

        $sucesso = 'Perfil atualizado com sucesso.';

        $stmt->execute([':id' => $_SESSION['usuario_id']]);
        $usuario = $stmt->fetch();

        if (!empty($_SESSION['redirect_apos_perfil'])) {
            $destino = $_SESSION['redirect_apos_perfil'];
            unset($_SESSION['redirect_apos_perfil']);
            header('Location: ' . $destino);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
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
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4">Dados para entrega e faturamento</h1>
            <p class="text-muted mb-4">Esses dados sao obrigatorios para concluir pedidos.</p>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" id="nome" class="form-control" value="<?= htmlspecialchars($usuario['nome']) ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" disabled>
                </div>

                <div class="col-md-4">
                    <label for="cpf" class="form-label">CPF</label>
                    <input type="text" id="cpf" name="cpf" class="form-control" value="<?= htmlspecialchars($usuario['cpf'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="cep" class="form-label">CEP</label>
                    <input type="text" id="cep" name="cep" class="form-control" value="<?= htmlspecialchars($usuario['cep'] ?? '') ?>" required>
                    <small id="cep-status" class="text-muted">Digite o CEP com 8 numeros para preencher endereco automaticamente.</small>
                </div>
                <div class="col-md-4">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="text" id="telefone" name="telefone" class="form-control" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="endereco" class="form-label">Endereco</label>
                    <input type="text" id="endereco" name="endereco" class="form-control" value="<?= htmlspecialchars($usuario['endereco'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label for="numero" class="form-label">Numero</label>
                    <input type="text" id="numero" name="numero" class="form-control" value="<?= htmlspecialchars($usuario['numero'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="bairro" class="form-label">Bairro</label>
                    <input type="text" id="bairro" name="bairro" class="form-control" value="<?= htmlspecialchars($usuario['bairro'] ?? '') ?>" required>
                </div>

                <div class="col-md-8">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" id="cidade" name="cidade" class="form-control" value="<?= htmlspecialchars($usuario['cidade'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label">UF</label>
                    <input type="text" id="estado" name="estado" maxlength="2" class="form-control" value="<?= htmlspecialchars($usuario['estado'] ?? '') ?>" required>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Salvar dados</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const campoCep = document.getElementById('cep');
    const campoEndereco = document.getElementById('endereco');
    const campoBairro = document.getElementById('bairro');
    const campoCidade = document.getElementById('cidade');
    const campoEstado = document.getElementById('estado');
    const statusCep = document.getElementById('cep-status');

    async function buscarCep() {
        const cepLimpo = (campoCep.value || '').replace(/\D/g, '');

        if (cepLimpo.length !== 8) {
            statusCep.textContent = 'CEP invalido. Informe 8 numeros.';
            statusCep.className = 'text-danger';
            return;
        }

        statusCep.textContent = 'Buscando endereco...';
        statusCep.className = 'text-muted';

        try {
            const resposta = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);

            if (!resposta.ok) {
                throw new Error('Falha ao consultar CEP');
            }

            const dados = await resposta.json();

            if (dados.erro) {
                statusCep.textContent = 'CEP nao encontrado.';
                statusCep.className = 'text-danger';
                return;
            }

            if (!campoEndereco.value.trim()) {
                campoEndereco.value = dados.logradouro || '';
            }

            if (!campoBairro.value.trim()) {
                campoBairro.value = dados.bairro || '';
            }

            if (!campoCidade.value.trim()) {
                campoCidade.value = dados.localidade || '';
            }

            if (!campoEstado.value.trim()) {
                campoEstado.value = dados.uf || '';
            }

            statusCep.textContent = 'Endereco preenchido com sucesso.';
            statusCep.className = 'text-success';
        } catch (error) {
            statusCep.textContent = 'Nao foi possivel consultar o CEP no momento.';
            statusCep.className = 'text-danger';
        }
    }

    campoCep.addEventListener('blur', buscarCep);
</script>
</body>
</html>
