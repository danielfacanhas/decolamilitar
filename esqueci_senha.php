<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Decola Militar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./stylesheet/login.css">
</head>
<body>
    <!-- Fundo animado -->
    <div class="bg"></div>
    <div class="bg bg2"></div>
    <div class="bg bg3"></div>

    <section class="vh-100 d-flex align-items-center justify-content-center">
        <div class="card p-4 shadow" style="width: 400px;">
            <div class="text-center mb-4">
                <img src="./imgs/aviao1.png" width="80" height="100" alt="logo">
                <div class="titulo">
                    <h4 class="mt-3">RECUPERAR SENHA</h4>
                </div>  
            </div>

            <!-- Mensagens de erro/sucesso -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center">
                    <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success text-center">
                    Link de recuperação enviado! Verifique seu e-mail.
                </div>
            <?php endif; ?>

            <p class="text-center mb-4" style="color: #555; font-size: 0.95rem;">
                Digite seu e-mail cadastrado para receber o link de recuperação.
            </p>

            <form action="processa_esqueci_senha.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <div class="input-container">
                        <input type="email" 
                               class="form-control input-estilizado" 
                               id="email" 
                               name="email" 
                               placeholder="Digite seu e-mail"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100">Enviar Link</button>
            </form>

            <div class="text-center mt-3">
                <a class="text-muted" href="login.php">Voltar para o login</a>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>