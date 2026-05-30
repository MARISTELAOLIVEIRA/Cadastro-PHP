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
                    is_admin INTEGER NOT NULL DEFAULT 0,
                    cpf      TEXT,
                    endereco TEXT,
                    numero   TEXT,
                    bairro   TEXT,
                    cidade   TEXT,
                    estado   TEXT,
                    cep      TEXT,
                    telefone TEXT,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            garantirColunaAdminUsuarios($pdo);
            garantirColunasPerfilUsuarios($pdo);

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS produtos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nome TEXT NOT NULL,
                    descricao TEXT NOT NULL,
                    categoria TEXT NOT NULL,
                    preco REAL NOT NULL CHECK (preco >= 0),
                    estoque INTEGER NOT NULL DEFAULT 0 CHECK (estoque >= 0),
                    imagem_url TEXT,
                    ativo INTEGER NOT NULL DEFAULT 1,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS pedidos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    usuario_id INTEGER NOT NULL,
                    valor_total REAL NOT NULL CHECK (valor_total >= 0),
                    status TEXT NOT NULL DEFAULT 'novo',
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS pedido_itens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    pedido_id INTEGER NOT NULL,
                    produto_id INTEGER NOT NULL,
                    nome_produto TEXT NOT NULL,
                    preco_unitario REAL NOT NULL CHECK (preco_unitario >= 0),
                    quantidade INTEGER NOT NULL CHECK (quantidade > 0),
                    subtotal REAL NOT NULL CHECK (subtotal >= 0),
                    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
                    FOREIGN KEY (produto_id) REFERENCES produtos(id)
                )
            ");

            popularProdutosIniciais($pdo);
            garantirAdminInicial($pdo);
        } catch (PDOException $e) {
            // Em produção, nunca exiba detalhes do erro para o usuário
            die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
        }
    }

    return $pdo;
}

function popularProdutosIniciais(PDO $pdo): void {
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM produtos');
    $total = (int) ($stmt->fetch()['total'] ?? 0);

    if ($total > 0) {
        return;
    }

    $produtos = [
        [
            'Luminaria Circuito Neon',
            'Luminaria artistica feita com placa-mae reciclada, fios coloridos e LED de baixo consumo.',
            'Iluminacao Artistica',
            189.90,
            8,
            'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'Escultura Motor Orbital',
            'Escultura cinetica com micro motor recuperado, helices de cooler e base em aluminio reaproveitado.',
            'Escultura Cinetica',
            249.00,
            5,
            'https://images.unsplash.com/photo-1581091215367-59ab6dcef2f1?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'Quadro Pixel de Chips',
            'Composicao em relevo com chips, resistores e trilhas eletricas formando mosaico geometrico.',
            'Quadros',
            139.50,
            12,
            'https://images.unsplash.com/photo-1555617117-08fda3de0a5b?auto=format&fit=crop&w=900&q=80',
        ],
        [
            'Totem RGB Retro-Tech',
            'Totem decorativo com leds RGB, botoes antigos e gabinete de HD transformado em arte.',
            'Decoracao',
            320.00,
            3,
            'https://images.unsplash.com/photo-1553406830-ef2513450d76?auto=format&fit=crop&w=900&q=80',
        ],
    ];

    $insert = $pdo->prepare('
        INSERT INTO produtos (nome, descricao, categoria, preco, estoque, imagem_url)
        VALUES (:nome, :descricao, :categoria, :preco, :estoque, :imagem_url)
    ');

    foreach ($produtos as $produto) {
        $insert->execute([
            ':nome' => $produto[0],
            ':descricao' => $produto[1],
            ':categoria' => $produto[2],
            ':preco' => $produto[3],
            ':estoque' => $produto[4],
            ':imagem_url' => $produto[5],
        ]);
    }
}

function garantirColunaAdminUsuarios(PDO $pdo): void {
    $stmt = $pdo->query("PRAGMA table_info('usuarios')");
    $colunas = $stmt->fetchAll();

    foreach ($colunas as $coluna) {
        if (($coluna['name'] ?? '') === 'is_admin') {
            return;
        }
    }

    $pdo->exec('ALTER TABLE usuarios ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0');
}

function garantirColunasPerfilUsuarios(PDO $pdo): void {
    $stmt = $pdo->query("PRAGMA table_info('usuarios')");
    $colunas = $stmt->fetchAll();
    $nomesColunas = [];

    foreach ($colunas as $coluna) {
        $nomesColunas[] = $coluna['name'] ?? '';
    }

    $faltantes = [
        'cpf' => 'ALTER TABLE usuarios ADD COLUMN cpf TEXT',
        'endereco' => 'ALTER TABLE usuarios ADD COLUMN endereco TEXT',
        'numero' => 'ALTER TABLE usuarios ADD COLUMN numero TEXT',
        'bairro' => 'ALTER TABLE usuarios ADD COLUMN bairro TEXT',
        'cidade' => 'ALTER TABLE usuarios ADD COLUMN cidade TEXT',
        'estado' => 'ALTER TABLE usuarios ADD COLUMN estado TEXT',
        'cep' => 'ALTER TABLE usuarios ADD COLUMN cep TEXT',
        'telefone' => 'ALTER TABLE usuarios ADD COLUMN telefone TEXT',
    ];

    foreach ($faltantes as $coluna => $sql) {
        if (!in_array($coluna, $nomesColunas, true)) {
            $pdo->exec($sql);
        }
    }
}

function garantirAdminInicial(PDO $pdo): void {
    $stmt = $pdo->query('SELECT COUNT(*) AS total_admin FROM usuarios WHERE is_admin = 1');
    $totalAdmin = (int) ($stmt->fetch()['total_admin'] ?? 0);

    if ($totalAdmin > 0) {
        return;
    }

    $stmtPrimeiroUsuario = $pdo->query('SELECT id FROM usuarios ORDER BY id ASC LIMIT 1');
    $primeiroUsuario = $stmtPrimeiroUsuario->fetch();

    if (!$primeiroUsuario) {
        return;
    }

    $update = $pdo->prepare('UPDATE usuarios SET is_admin = 1 WHERE id = :id');
    $update->execute([':id' => (int) $primeiroUsuario['id']]);
}
