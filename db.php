<?php
// ============================================================
// db.php — Conexão com o banco de dados SQLite via PDO
// ============================================================

define('DB_PATH', __DIR__ . '/database.db');

function getConexao(): PDO {
    static $pdo = null; // Reutiliza a conexão durante a requisição

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            // Lança exceções em caso de erro (bom para depuração em aula)
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Retorna resultados como array associativo por padrão
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Cria a tabela de usuários se ainda não existir
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS usuarios (
                    id       INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome     TEXT    NOT NULL,
                    email    TEXT    NOT NULL UNIQUE,
                    senha    TEXT    NOT NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } catch (PDOException $e) {
            // Em produção, nunca exiba detalhes do erro para o usuário
            die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    return $pdo;
}
