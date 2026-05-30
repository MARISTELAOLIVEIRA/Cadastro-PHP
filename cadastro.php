<?php
// ============================================================
// cadastro.php — Formulário e processamento do cadastro
// ============================================================

session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'db.php';

$erro  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha']      ?? '';
    $confirma = $_POST['confirma'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha) || empty($confirma)) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } else {
        $pdo = getConexao();

        // Verifica se o e-mail já está cadastrado
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            // password_hash() gera um hash seguro com bcrypt
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $insert = $pdo->prepare('
                INSERT INTO usuarios (nome, email, senha, is_admin)
                VALUES (:nome, :email, :senha, :is_admin)
            ');
            $insert->execute([
                ':nome'  => $nome,
                ':email' => $email,
                ':senha' => $hash,
                ':is_admin' => 0,
            ]);

            $_SESSION['flash_sucesso'] = 'Cadastro realizado com sucesso! Agora faca login.';
            header('Location: login.php');
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
    <title>Cadastro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow" style="width: 100%; max-width: 450px;">
        <div class="card-body p-4">
            <h4 class="card-title text-center mb-4">Criar conta</h4>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="post" action="cadastro.php" novalidate>
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome completo</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                        required
                        autofocus
                    >
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                    >
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha <small class="text-muted">(mín. 6 caracteres)</small></label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        class="form-control"
                        required
                    >
                </div>
                <div class="mb-3">
                    <label for="confirma" class="form-label">Confirmar senha</label>
                    <input
                        type="password"
                        id="confirma"
                        name="confirma"
                        class="form-control"
                        required
                    >
                </div>
                <button type="submit" class="btn btn-success w-100">Cadastrar</button>
            </form>

            <hr>
            <p class="text-center mb-0">
                Já tem conta? <a href="login.php">Fazer login</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
