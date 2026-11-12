# 🃏 CardVerse

**Colecione • Descubra • Evolua**

O **CardVerse** é um projeto web de jogo de cartas colecionáveis em desenvolvimento. A proposta é permitir que jogadores criem uma conta, acumulem moedas virtuais, descubram cartas de diferentes raridades e construam suas próprias coleções.

O visual segue uma identidade de jogo de fantasia, com tons escuros, roxo e dourado, elementos inspirados em cartas e interações sutis.

> **Status:** em desenvolvimento — etapas 1, 2 e 3 concluídas. A próxima etapa será a loja e a compra de pacotes.

## ✨ Funcionalidades disponíveis

- Cadastro de jogadores com senha protegida por hash.
- Login e logout com sessões PHP.
- Dashboard com saudação personalizada.
- Saldo de moedas virtuais (500 moedas iniciais por jogador).
- Indicadores de cartas diferentes e total de cartas na coleção, consultados no banco.
- Página da coleção com nome, imagem, raridade, descrição e quantidade de cada carta.
- Filtro por raridade, busca por nome e visualização detalhada em modal.
- Mensagens específicas para coleção vazia e para filtros sem resultados.
- Validação dos formulários, tokens CSRF e consultas preparadas com PDO.
- Interface responsiva com Bootstrap e Bootstrap Icons.

**Atenção:** as seções de pacotes e trocas exibidas no dashboard são prévias visuais; suas funcionalidades ainda não foram implementadas.

## 🛠️ Tecnologias

| Tecnologia | Utilização |
| --- | --- |
| PHP 8+ | Lógica do servidor, autenticação e sessões |
| MySQL | Armazenamento dos dados |
| PDO | Conexão e consultas ao banco |
| HTML5 e CSS3 | Estrutura e identidade visual |
| Bootstrap 5 | Layout responsivo e componentes |
| Bootstrap Icons | Ícones da interface |
| XAMPP | Ambiente local com Apache e MySQL |

## 📁 Estrutura do projeto

```text
CardVerse/
├── actions/
│   ├── cadastrar.php        # Processamento do cadastro
│   └── entrar.php           # Processamento do login
├── assets/
│   ├── css/
│   │   └── style.css        # Estilos do CardVerse
│   └── img/
│       └── cartas/          # Imagens das cartas (adicionadas depois)
├── config/
│   ├── auth.php             # Sessões, autenticação e CSRF
│   └── conexao.php          # Conexão PDO com MySQL
├── database/
│   └── cardverse.sql        # Estrutura e dados iniciais
├── cadastro.php             # Tela de cadastro
├── colecao.php              # Coleção de cartas do jogador
├── dashboard.php            # Painel do jogador
├── login.php                # Tela de login
├── logout.php               # Encerramento da sessão
├── teste-conexao.php        # Teste local de conexão (desenvolvimento)
└── README.md
```

> A estrutura acima representa os arquivos atuais do projeto. A pasta `database` e o teste de conexão vêm da etapa 1; mantenha-os no projeto ao aplicar atualizações visuais.

## 🚀 Como executar localmente

### Pré-requisitos

- [XAMPP](https://www.apachefriends.org/pt_br/index.html) com Apache, PHP e MySQL.
- Navegador atualizado.
- Conexão com a internet para carregar Bootstrap e Bootstrap Icons via CDN.

### Instalação

1. Coloque a pasta do projeto em `C:\xampp\htdocs\CardVerse`.
2. Abra o painel do XAMPP e inicie **Apache** e **MySQL**.
3. Acesse `http://localhost/phpmyadmin`.
4. Importe o arquivo `database/cardverse.sql` pelo phpMyAdmin. Ele cria o banco `cardverse` e suas tabelas.
5. Confira as configurações de acesso ao banco em `config/conexao.php` para o seu ambiente local.
6. Acesse `http://localhost/CardVerse/cadastro.php` para criar um jogador.
7. Entre pelo endereço `http://localhost/CardVerse/login.php` e confira o dashboard.

**Dica:** o arquivo `teste-conexao.php` pode ser usado durante o desenvolvimento para verificar a conexão. Evite deixá-lo acessível ao publicar o projeto.

## 🗄️ Banco de dados

O banco **`cardverse`** possui as seguintes tabelas planejadas para o jogo:

| Tabela | Finalidade |
| --- | --- |
| `usuarios` | Contas e saldo de moedas |
| `cartas` | Catálogo de cartas |
| `colecao` | Cartas pertencentes aos jogadores |
| `pacotes` | Tipos de pacotes |
| `pacote_cartas` | Relação entre pacotes e cartas |
| `aberturas` | Registro de aberturas |
| `trocas` | Negociações entre jogadores |
| `resgates_diarios` | Controle de recompensas diárias |

A existência dessas tabelas **não significa** que todas as funcionalidades já estejam disponíveis na interface.

> **Imagens das cartas:** coloque os arquivos em `assets/img/cartas/` usando o mesmo nome registrado na coluna `imagem` da tabela `cartas`. Enquanto o arquivo não existir, a página da coleção exibe um espaço reservado com o aviso "Arte em breve".

## 🧭 Próximas etapas

- [x] **Etapa 1:** banco de dados e conexão com PDO.
- [x] **Etapa 2:** cadastro, login, logout e dashboard.
- [x] **Identidade visual inicial:** layout inspirado em jogos de cartas e logo feita com HTML/CSS/ícones.
- [x] **Etapa 3:** coleção de cartas — listagem, raridade e quantidades.
- [ ] Loja e compra de pacotes com moedas.
- [ ] Abertura de pacotes e sorteio de cartas.
- [ ] Sistema de trocas entre jogadores.
- [ ] Recompensas diárias e ranking.
- [ ] Painel administrativo para gerenciamento das cartas.

## 🔐 Observações de segurança

O projeto utiliza hash de senha, sessões, proteção CSRF e consultas preparadas com PDO nas funcionalidades atuais. Antes de disponibilizá-lo publicamente, será necessário revisar a configuração de produção, o tratamento de erros, as permissões e a segurança geral.

## 📌 Sobre o projeto

O CardVerse é desenvolvido de forma incremental: cada etapa adiciona funcionalidades sem perder a organização dos arquivos nem a identidade visual do jogo.

---

**CardVerse** — *Sua próxima carta lendária pode estar a um pacote de distância.*
