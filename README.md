# 🃏 CardVerse

**Colecione • Descubra • Evolua**

O **CardVerse** é um projeto web de jogo de cartas colecionáveis em desenvolvimento. A proposta é permitir que jogadores criem uma conta, acumulem moedas virtuais, descubram cartas de diferentes raridades e construam suas próprias coleções.

O visual segue uma identidade de jogo de fantasia, com tons escuros, roxo e dourado, elementos inspirados em cartas e interações sutis.

> **Status:** em desenvolvimento — etapas 1, 2 e 3 concluídas, além da loja, compra e abertura de pacotes.

## ✨ Funcionalidades disponíveis

- Cadastro de jogadores com senha protegida por hash.
- Login e logout com sessões PHP.
- Dashboard com saudação personalizada.
- Saldo de moedas virtuais (500 moedas iniciais por jogador).
- Indicadores de cartas diferentes e total de cartas na coleção, consultados no banco.
- Página da coleção com nome, imagem, raridade, descrição e quantidade de cada carta.
- Filtro por raridade, busca por nome e visualização detalhada em modal.
- Mensagens específicas para coleção vazia e para filtros sem resultados.
- Loja com três pacotes, descrição, preço, selo, quantidade de cartas e regras de raridade.
- Compra de pacotes com validação de saldo e registro na tabela `compras`.
- Página **Abrir pacotes** com pacotes pendentes, chances detalhadas e histórico de aberturas.
- Sorteio ponderado por raridade, com garantia mínima e chance de carta lendária por pacote.
- Revelação animada das cartas (flip 3D), com brilho por raridade e respeito a `prefers-reduced-motion`.
- Cartas entregues automaticamente na coleção e abertura registrada na tabela `aberturas`.
- Proteção contra duplo envio da compra e da abertura com token de uso único e transações no banco.
- Código organizado em classes de domínio (`Usuario`, `Carta`, `Pacote` e `Abertura`) com autoload próprio.
- Validação dos formulários, tokens CSRF e consultas preparadas com PDO.
- Interface responsiva com Bootstrap e Bootstrap Icons.

**Atenção:** a seção de trocas exibida no dashboard é uma prévia visual; sua funcionalidade ainda não foi implementada.

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
│   ├── abrir-pacote.php     # Processamento da abertura e sorteio
│   ├── cadastrar.php        # Processamento do cadastro
│   ├── comprar-pacote.php   # Processamento da compra de pacotes
│   └── entrar.php           # Processamento do login
├── assets/
│   ├── css/
│   │   └── style.css        # Estilos do CardVerse
│   └── img/
│       └── cartas/          # Imagens das cartas (adicionadas depois)
├── classes/
│   ├── Abertura.php         # Sorteio, registro e entrega das cartas
│   ├── AberturaException.php # Erros de domínio da abertura
│   ├── autoload.php         # Carregamento automático das classes
│   ├── Carta.php            # Catálogo e coleção do jogador
│   ├── LojaException.php    # Erros de domínio da loja
│   ├── Pacote.php           # Pacotes e compra transacional
│   └── Usuario.php          # Contas, cadastro e autenticação
├── config/
│   ├── auth.php             # Sessões, autenticação e CSRF
│   └── conexao.php          # Conexão PDO com MySQL
├── database/
│   └── cardverse.sql        # Estrutura e dados iniciais
├── abrir-pacote.php         # Pacotes pendentes, chances e revelação
├── cadastro.php             # Tela de cadastro
├── colecao.php              # Coleção de cartas do jogador
├── dashboard.php            # Painel do jogador
├── login.php                # Tela de login
├── logout.php               # Encerramento da sessão
├── loja.php                 # Loja de pacotes
├── teste-conexao.php        # Teste local de conexão (desenvolvimento)
└── README.md
```

> A estrutura acima representa os arquivos atuais do projeto. A pasta `database` e o teste de conexão vêm da etapa 1; mantenha-os no projeto ao aplicar atualizações visuais.

## 🧱 Organização em classes

As regras de negócio ficam em `classes/`, carregadas por `classes/autoload.php`:

| Classe | Responsabilidade |
| --- | --- |
| `Usuario` | Consulta, cadastro, autenticação e validação de e-mail |
| `Carta` | Catálogo, coleção, filtros, resumo e adição de cartas |
| `Pacote` | Pacotes ativos, cartas do pacote e compra transacional |
| `LojaException` | Erros de domínio da compra (saldo, pacote e duplicidade) |
| `Abertura` | Pendentes, chances, sorteio, registro da abertura e entrega das cartas |
| `AberturaException` | Erros de domínio da abertura (compra inválida ou já aberta) |

As páginas e ações validam a requisição e chamam essas classes; as consultas SQL e as regras de negócio ficam concentradas nessa camada.

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
| `pacotes` | Pacotes, descrição, selo, preço, quantidade e regras de raridade |
| `pacote_cartas` | Relação entre pacotes e cartas |
| `pacote_chances` | Pesos de raridade usados no sorteio de cada pacote |
| `compras` | Registro das compras de pacotes pelos jogadores |
| `aberturas` | Registro de aberturas, cartas recebidas e vínculo com a compra |
| `trocas` | Negociações entre jogadores |
| `resgates_diarios` | Controle de recompensas diárias |

A existência dessas tabelas **não significa** que todas as funcionalidades já estejam disponíveis na interface.

### Pacotes da loja

| Pacote | Preço | Cartas | Regras |
| --- | ---: | ---: | --- |
| Pacote Mistico | 100 | 5 | Comuns, raras e épicas |
| Pacote Arcano | 250 | 5 | Raras/épicas + 10% de ao menos uma lendária |
| Pacote Celestial | 500 | 5 | Épica garantida + 25% de ao menos uma lendária |

As colunas `raridade_garantida` e `chance_lendaria` ficam em `pacotes`, os pesos por raridade ficam em `pacote_chances` e os vínculos em `pacote_cartas` definem o pool de cartas de cada pacote.

### Abertura de pacotes

1. A compra debita as moedas e registra a compra; o pacote aparece em **Abrir pacotes**.
2. Na prévia, o jogador confere as chances e confirma a abertura.
3. O sorteio roda no servidor: cada slot sorteia uma raridade pelos pesos de `pacote_chances` e depois uma carta do pool.
4. `raridade_garantida` promove um slot quando necessário; `chance_lendaria` força uma lendária com a probabilidade configurada.
5. A abertura é gravada em `aberturas` e as cartas são somadas em `colecao` na mesma transação. Repetidas aumentam a quantidade da carta.
6. Cada compra tem `compra_id` único em `aberturas`: duplo clique, refresh ou reenvio não abrem o pacote duas vezes.

A revelação é registrada antes da animação, então recarregar a página repete apenas a animação com as mesmas cartas. O botão **Revelar todas** pula a sequência e, com `prefers-reduced-motion`, as cartas aparecem imediatamente.

> **Imagens das cartas:** coloque os arquivos em `assets/img/cartas/` usando o mesmo nome registrado na coluna `imagem` da tabela `cartas`. Enquanto o arquivo não existir, a página da coleção exibe um espaço reservado com o aviso "Arte em breve".

## 🧭 Próximas etapas

- [x] **Etapa 1:** banco de dados e conexão com PDO.
- [x] **Etapa 2:** cadastro, login, logout e dashboard.
- [x] **Identidade visual inicial:** layout inspirado em jogos de cartas e logo feita com HTML/CSS/ícones.
- [x] **Etapa 3:** coleção de cartas — listagem, raridade e quantidades.
- [x] Loja e compra de pacotes com moedas.
- [x] Abertura de pacotes, sorteio de cartas e animação de revelação.
- [ ] Sistema de trocas entre jogadores.
- [ ] Recompensas diárias e ranking.
- [ ] Painel administrativo para gerenciamento das cartas.

## 🔐 Observações de segurança

O projeto utiliza hash de senha, sessões, proteção CSRF e consultas preparadas com PDO nas funcionalidades atuais. Compras e aberturas usam transações com bloqueio de registro e token de uso único; a coluna `aberturas.compra_id` é única para impedir que a mesma compra seja aberta duas vezes. Antes de disponibilizá-lo publicamente, será necessário revisar a configuração de produção, o tratamento de erros, as permissões e a segurança geral.

## 📌 Sobre o projeto

O CardVerse é desenvolvido de forma incremental: cada etapa adiciona funcionalidades sem perder a organização dos arquivos nem a identidade visual do jogo.

---

**CardVerse** — *Sua próxima carta lendária pode estar a um pacote de distância.*
