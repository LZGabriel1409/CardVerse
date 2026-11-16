
<?php
// ==========================================
// 1. AUTENTICAÇÃO E CONEXÃO
// ==========================================

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/conexao.php';

exigirAdmin($pdo, '../login.php');

// Proteção do nome exibido na página.
$nomeAdmin = htmlspecialchars(
    $_SESSION['usuario_nome'] ?? 'Administrador',
    ENT_QUOTES,
    'UTF-8'
);
?>

<!doctype html>
<html lang="pt-BR">

<head>
    <title>Administração | CardVerse</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CSS principal do CardVerse -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>

<div class="container-fluid admin-layout">

    <div class="row">

        <!-- ==========================================
             2. MENU LATERAL
        =========================================== -->

        <aside class="col-md-3 col-lg-2 admin-sidebar">

            <div class="brand mb-5">
                <span class="brand-mark">
                    <i class="bi bi-stars logo-star"></i>
                </span>
                CardVerse
            </div>

            <div class="eyebrow mb-3">Administração</div>

            <nav class="nav flex-column">

                <a href="index.php" class="nav-link active">
                    <i class="bi bi-grid-1x2"></i>
                    Dashboard
                </a>

                <a href="cartas.php" class="nav-link">
                    <i class="bi bi-card-list"></i>
                    Cartas
                </a>

                <a href="pacotes.php" class="nav-link">
                    <i class="bi bi-box-seam"></i>
                    Pacotes
                </a>

                <hr class="border-secondary my-4">

                <a href="../dashboard.php" class="nav-link">
                    <i class="bi bi-arrow-left-circle"></i>
                    Voltar ao jogo
                </a>

                <form action="../logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="nav-link border-0 bg-transparent text-start w-100"><i class="bi bi-box-arrow-right"></i>
                    Sair</button>
                </form>

            </nav>

        </aside>


        <!-- ==========================================
             3. CONTEÚDO PRINCIPAL
        =========================================== -->

        <main class="col-md-9 col-lg-10 admin-content">

            <!-- Cabeçalho -->

            <header class="admin-header">

                <div>
                    <div class="eyebrow mb-2">
                        Painel de controle
                    </div>

                    <h1 class="section-title">
                        Dashboard
                    </h1>

                    <p class="muted mb-0">
                        Bem-vindo de volta, <?= $nomeAdmin ?>!
                    </p>
                </div>

                <span class="tag">
                    <i class="bi bi-shield-check me-1"></i>
                    Administrador
                </span>

            </header>


            <!-- ==========================================
                 4. GERENCIAMENTO
            =========================================== -->

            <section>

                <div class="eyebrow mb-3">
                    Gerenciamento do sistema
                </div>

                <div class="row g-4">

                    <!-- Cartas -->

                    <div class="col-sm-6 col-xl-4">

                        <a href="cartas.php" class="feature-card admin-feature">

                            <i class="bi bi-card-list"></i>

                            <h3 class="h5 mt-3">
                                Gerenciar cartas
                            </h3>

                            <p class="muted">
                                Cadastre, edite e exclua cartas.
                                Gerencie raridades e atributos.
                            </p>

                            <span class="admin-card-link">
                                Acessar cartas
                                <i class="bi bi-arrow-right"></i>
                            </span>

                        </a>

                    </div>


                    <!-- Pacotes -->

                    <div class="col-sm-6 col-xl-4">

                        <a href="pacotes.php" class="feature-card admin-feature">

                            <i class="bi bi-box-seam"></i>

                            <h3 class="h5 mt-3">
                                Gerenciar pacotes
                            </h3>

                            <p class="muted">
                                Crie pacotes, configure cartas
                                e defina suas probabilidades.
                            </p>

                            <span class="admin-card-link">
                                Acessar pacotes
                                <i class="bi bi-arrow-right"></i>
                            </span>

                        </a>

                    </div>


                    <!-- Administração -->

                    <div class="col-sm-6 col-xl-4">

                        <div class="feature-card admin-feature">

                            <i class="bi bi-shield-lock"></i>

                            <h3 class="h5 mt-3">
                                Área administrativa
                            </h3>

                            <p class="muted">
                                Ambiente exclusivo para gerenciamento
                                e manutenção do CardVerse.
                            </p>

                            <span class="tag">
                                <i class="bi bi-check-circle me-1"></i>
                                Acesso autorizado
                            </span>

                        </div>

                    </div>

                </div>

            </section>

        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
