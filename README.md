# 🃏 CardVerse

**Colecione • Descubra • Evolua**

O **CardVerse** é um jogo web de cartas colecionáveis desenvolvido com **PHP e MySQL**. Nele, cada jogador pode criar sua conta, adquirir pacotes com moedas virtuais, ampliar sua coleção, trocar cartas com outros jogadores e evoluir por meio de missões e conquistas.

O projeto utiliza uma interface responsiva com identidade visual inspirada em jogos de fantasia, combinando tons escuros, roxo e dourado.

> **Status:** projeto em desenvolvimento, com sistemas de coleção, loja, pacotes, perfil, progressão, trocas, ranking e administração presentes no código.

## ✨ Funcionalidades

### 👤 Contas e perfil
- Cadastro, login e logout de jogadores.
- Autenticação por sessões PHP e armazenamento seguro de senhas por hash.
- Saldo inicial de **500 moedas virtuais** por conta.
- Perfil com avatar selecionável, estatísticas da coleção, pontos, experiência e nível.

### 🃏 Coleção de cartas
- Catálogo de cartas com nome, descrição, imagem e raridade.
- Coleção individual vinculada à conta do jogador.
- Consulta da quantidade de cada carta e visualização de detalhes.
- Pesquisa e filtros para facilitar a navegação.
- Raridades comuns, raras, épicas e lendárias.

### 🛍️ Loja e pacotes
- Loja de pacotes comprados com moedas do jogo.
- Informações de preço, quantidade de cartas e probabilidades de raridade.
- Verificação de saldo antes de confirmar a compra.
- Registro das compras no banco de dados.
- Abertura de pacotes com animação de revelação.
- Entrega das cartas obtidas diretamente à coleção e registro das aberturas.

### 🔄 Trocas entre jogadores
- Envio de propostas de troca entre contas.
- Seleção de cartas oferecidas e solicitadas.
- Aceitação, recusa e cancelamento de propostas.
- Verificação da disponibilidade das cartas e atualização das coleções durante a troca.

### 🎯 Missões, conquistas e evolução
- Missões diárias e semanais com acompanhamento de progresso.
- Resgate de recompensas de missões concluídas.
- Conquistas associadas a objetivos do jogo.
- Sistema de pontos, experiência e níveis.
- Ranking de jogadores com ordenação por pontuação e experiência.

### 🛡️ Administração
- Área administrativa com verificação de permissão.
- Gerenciamento de cartas e pacotes.
- Cadastro e edição de informações dos pacotes.
- Configuração das cartas disponíveis em pacotes e das probabilidades por raridade.

## 🛠️ Tecnologias utilizadas

| Tecnologia | Finalidade |
| --- | --- |
| PHP 8+ | Regras do jogo, páginas e processamento das ações |
| MySQL | Armazenamento de contas, cartas, coleções e progresso |
| PDO | Acesso ao banco de dados com consultas preparadas |
| HTML5, CSS3 e JavaScript | Estrutura, aparência e interações da interface |
| Bootstrap 5 | Componentes e layout responsivo |
| Bootstrap Icons | Ícones da interface |
| XAMPP | Execução local com Apache, PHP e MySQL |

## 📁 Organização do projeto

```text
CardVerse-main/
├── actions/                 # Processamento de cadastro, login, compras,
│                            # aberturas, trocas, perfil e recompensas
├── admin/                   # Páginas e ações de administração
├── assets/
│   ├── css/                 # Estilos visuais
│   ├── js/                  # Scripts da interface
│   └── img/                 # Recursos visuais utilizados pelo jogo
├── classes/                 # Classes de domínio e regras de negócio
├── config/                  # Conexão com banco e autenticação
├── database/
│   └── cardverse.sql        # Estrutura e dados SQL do projeto
├── includes/                # Componentes compartilhados da interface
├── index.php                # Entrada e redirecionamento inicial
├── cadastro.php             # Cadastro de jogadores
├── login.php                # Login
├── logout.php               # Logout
├── dashboard.php            # Painel principal
├── colecao.php              # Coleção do jogador
├── carta.php                # Detalhes de carta
├── loja.php                 # Loja de pacotes
├── abrir-pacote.php         # Abertura de pacotes
├── trocas.php               # Sistema de trocas
├── perfil.php               # Perfil e progressão
├── ranking.php              # Classificação dos jogadores
├── teste-conexao.php        # Teste de conexão para desenvolvimento
└── README.md                # Documentação
```

### Organização do código

As páginas PHP exibem a interface e utilizam as classes em `classes/` para executar as operações do jogo. Os arquivos em `actions/` recebem e processam formulários e outras ações do usuário. A pasta `admin/` concentra as ferramentas administrativas, enquanto `config/` reúne os recursos de autenticação e conexão com o MySQL.

Entre as principais classes estão `Usuario`, `Carta`, `Pacote`, `Abertura`, `Troca`, `Missao`, `Conquista` e `Ranking`.

## 🚀 Como executar o projeto

### Pré-requisitos

- [XAMPP](https://www.apachefriends.org/pt_br/index.html) com Apache, PHP 8+ e MySQL.
- Navegador atualizado.
- Acesso à internet para os recursos carregados por CDN, como Bootstrap e Bootstrap Icons.

### Instalação passo a passo

1. Extraia o arquivo ZIP do projeto.
2. Coloque a pasta do projeto dentro de `C:\xampp\htdocs\` e renomeie-a para `CardVerse`, se desejar utilizar os endereços abaixo.
3. Abra o painel do **XAMPP** e inicie os serviços **Apache** e **MySQL**.
4. Acesse **http://localhost/phpmyadmin**.
5. Na aba de importação do phpMyAdmin, selecione o arquivo `database/cardverse.sql` e execute a importação. O script contém a criação do banco `cardverse` e suas tabelas.
6. Verifique as credenciais de acesso ao banco em `config/conexao.php`. Na configuração enviada, o host é `127.0.0.1`, o banco é `cardverse`, o usuário é `root` e a senha está vazia (padrão comum do XAMPP local).
7. Acesse **http://localhost/CardVerse/cadastro.php** para criar sua conta.
8. Faça login em **http://localhost/CardVerse/login.php** e comece a utilizar o jogo.

> Se a pasta mantiver o nome `CardVerse-main`, substitua `CardVerse` por `CardVerse-main` nas URLs.

## 🗄️ Banco de dados

O banco de dados é definido no arquivo `database/cardverse.sql`. Ele utiliza tabelas relacionadas por chaves estrangeiras para organizar informações como:

- **Usuários:** contas, moedas, pontos, experiência e avatar.
- **Cartas e coleções:** catálogo e cartas pertencentes a cada jogador.
- **Pacotes e compras:** produtos da loja, configurações de raridade e transações.
- **Aberturas:** histórico dos pacotes abertos e cartas recebidas.
- **Trocas:** propostas e seus respectivos estados.
- **Missões e conquistas:** objetivos, progresso e recompensas.
- **Níveis:** faixas de experiência necessárias para evolução.

O uso de relações entre tabelas ajuda a manter a integridade dos dados do jogo.

## 🔐 Segurança e validações

O código inclui mecanismos como:

- Senhas protegidas por hash.
- Consultas preparadas com PDO.
- Proteção de páginas que exigem autenticação.
- Controle de acesso para administradores.
- Tokens CSRF para ações protegidas.
- Validação de saldo e das operações de compra e troca.
- Transações no banco para operações que precisam ser concluídas de maneira consistente.

> **Antes de publicar na internet:** configure credenciais próprias para o banco, desative a exibição de erros sensíveis e remova ou restrinja arquivos de teste, como `teste-conexao.php`. A presença dessas proteções não substitui uma auditoria de segurança.

## 🎮 Como jogar

1. **Crie sua conta** e entre no CardVerse.
2. **Visite a loja** e escolha um pacote de acordo com seu saldo.
3. **Abra o pacote** para receber cartas.
4. **Organize sua coleção** e acompanhe as cartas que já possui.
5. **Realize trocas** com outros jogadores para ampliar a coleção.
6. **Complete missões**, desbloqueie conquistas e ganhe experiência.
7. **Acompanhe o ranking** e sua evolução no perfil.

## 📌 Desenvolvimento e manutenção

O CardVerse está em desenvolvimento e pode receber ajustes, correções e novas funcionalidades. Para manter o projeto organizado, recomenda-se atualizar este README sempre que uma etapa ou funcionalidade for concluída, explicando as mudanças relevantes para os jogadores e para a instalação do sistema.

## 📄 Sobre o projeto

O CardVerse é um projeto de desenvolvimento web que reúne conceitos de programação orientada a objetos, autenticação, banco de dados relacional, regras de negócio e construção de interfaces responsivas em uma experiência de jogo de cartas colecionáveis.
