# Sistema de Cadastro e Login em PHP (com SQLite)

> Projeto didatico para aula de PHP da Professora Maristela  
> Faculdade de Tecnologia e Inovacao Senac DF

Seja bem-vindo(a) ao repositorio do sistema de cadastro e login!
Aqui a ideia e aprender **PHP na pratica**, sem drama, sem servidor de banco pesado e sem perder o bom humor.

Se algo der erro, respire fundo, tome agua e lembre: ate o ponto e virgula tem seus dias de protagonismo.

---

## 1. Objetivo academico

Este projeto foi criado para apoiar aulas introdutorias de desenvolvimento web com PHP, cobrindo:

- Cadastro de usuarios
- Autenticacao (login)
- Sessao de usuario
- Logout seguro
- Persistencia de dados com SQLite3
- Boas praticas de seguranca para iniciantes

### Competencias desenvolvidas

Ao final da atividade, o(a) aluno(a) deve ser capaz de:

- Estruturar um fluxo basico de autenticacao web
- Criar e consultar dados com PDO
- Aplicar validacoes de formulario
- Armazenar senhas com hash seguro
- Proteger paginas restritas com sessao

---

## 2. Tecnologias utilizadas

- PHP 7.4+ (testado em PHP 8.x)
- SQLite3
- PDO (PHP Data Objects)
- Bootstrap 5 (via CDN)

### Por que SQLite3 para aula?

- Nao precisa instalar servidor de banco separado
- Banco em arquivo unico (`database.db`)
- Facil de copiar, resetar e compartilhar
- Excelente para foco em logica de negocio e CRUD

---

## 3. Estrutura do projeto

- index.php: entrada da aplicacao e redirecionamento
- cadastro.php: formulario e processamento de cadastro
- login.php: formulario e processamento de login
- dashboard.php: area restrita para usuario autenticado
- logout.php: encerramento da sessao
- db.php: conexao PDO e criacao da tabela de usuarios
- database.db: banco SQLite criado automaticamente
- Leia-Me.txt: documentacao resumida do sistema

---

## 4. Fluxo funcional do sistema

1. Usuario acessa o sistema
2. Se nao tiver conta, realiza cadastro
3. Sistema valida dados e grava usuario com senha em hash
4. Usuario faz login com email e senha
5. Sessao e criada e usuario acessa o dashboard
6. Ao sair, sessao e encerrada com seguranca

---

## 5. Conceitos de seguranca ensinados

- `password_hash()` para armazenar senha com hash seguro
- `password_verify()` para validar senha no login
- Prepared statements com PDO para reduzir risco de SQL Injection
- `htmlspecialchars()` para evitar XSS na exibicao
- `session_regenerate_id(true)` apos login para mitigar session fixation
- Protecao de rotas restritas por verificacao de sessao

---

## 6. Como executar localmente

### Requisitos

- PHP instalado com extensao `pdo_sqlite` habilitada

### Passo a passo

1. Abra o terminal na pasta do projeto
2. Execute:

```bash
php -S localhost:8000
```
Ou coloque a pasta do projeto no diretório do servidor Web (se for o Xampp: c:xampp/htdocs

3. Abra no navegador:

```text
http://localhost:8000
```

Pronto. O arquivo `database.db` sera criado automaticamente na primeira requisicao.

---

## 7. Sugestao de roteiro de aula

- Parte 1: Apresentacao do problema (autenticacao)
- Parte 2: Estrutura de arquivos e papel de cada pagina
- Parte 3: Cadastro com validacao
- Parte 4: Login e comparacao de hash
- Parte 5: Sessao e controle de acesso
- Parte 6: Logout e limpeza de sessao
- Parte 7: Boas praticas e melhorias futuras

Tempo sugerido: 2 aulas de 50 minutos (ou 1 encontro de 2h).

---

## 8. Ideias de extensao para os alunos

- Recuperacao de senha
- Edicao de perfil
- Listagem de usuarios (CRUD completo)
- Separacao em camadas (MVC simples)
- Validacao front-end com JavaScript
- Mensagens flash em sessao

---

## 9. Mensagem da professora

"Programar e como aprender um novo idioma: no inicio parece estranho,
depois voce pensa em logica naturalmente... e sonha com bugs.
Mas tudo bem, porque agora voce sabe depurar!"

Com carinho,  
**Professora Maristela**  
Faculdade de Tecnologia e Inovacao Senac DF

---

## 10. Licenca e uso didatico

Material destinado a fins educacionais.
Sinta-se a vontade para reutilizar em sala, adaptar exercicios e evoluir o projeto com as turmas.
