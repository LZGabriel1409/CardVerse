<?php
require_once __DIR__ . '/config/auth.php';

if (usuarioLogado()) {
    header('Location: dashboard.php');
    exit;
}

$flash = pegarMensagem();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <title>Entrar | CardVerse</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<main class="container auth-layout d-flex align-items-center py-4 py-lg-5">
    <div class="row g-4 align-items-center w-100 mx-auto">
        <div class="col-lg-6">
            <aside class="auth-aside">
                <div>
                    <div class="brand mb-4"><span class="brand-mark"><i class="bi bi-stars logo-star"></i></span>CardVerse</div>
                    <div class="eyebrow mb-3">Colecione • Descubra • Evolua</div>
                    <h2>Toda lenda começa com uma carta.</h2>
                    <p class="muted mt-3">Descubra cartas raras, complete sua coleção e prepare-se para novas aventuras.</p>
                </div>
                <div class="card-stack" aria-hidden="true">
                    <div class="preview-card"><i class="bi bi-stars"></i></div>
                    <div class="preview-card"><i class="bi bi-shield-shaded"></i></div>
                </div>
                <span class="tag"><i class="bi bi-gem me-1"></i> Um universo para colecionadores</span>
            </aside>
        </div>
        <div class="col-lg-6 d-flex justify-content-center">
            <section class="auth-card">
                <div class="eyebrow mb-2">Portal do jogador</div>
                <h1 class="h2 mb-2">Entrar</h1>
                <p class="muted mb-4">Sua coleção está esperando. Acesse sua conta para continuar.</p>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>" role="alert">
                        <?= htmlspecialchars($flash['texto']) ?>
                    </div>
                <?php endif; ?>

                <form action="actions/entrar.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control" type="email" id="email" name="email"   autocomplete="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="senha">Senha</label>
                    <input class="form-control" type="password" id="senha" name="senha"   autocomplete="current-password" required>
                </div>
                    <button class="btn btn-magic w-100 mt-2" type="submit">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar no CardVerse
                    </button>
                </form>
                <p class="text-center muted mt-4 mb-0">
                    Ainda não tem uma conta?
                    <a href="cadastro.php">Cadastre-se</a>
                </p>
            </section>
        </div>
    </div>
</main>
</body>
</html>
