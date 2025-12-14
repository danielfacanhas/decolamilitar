<?php
session_start();
require_once "./config/db_connect.php";

// Pega o token da URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

if ($token === '') {
    header("Location: esqueci_senha.php?error=Link inválido.");
    exit;
}

// Verifica se o token é válido e não expirou
$query = "SELECT id, nome, token_expira_em FROM usuarios WHERE token_recuperacao = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    $usuario = mysqli_fetch_assoc($resultado);
    $id_usuario = $usuario['id'];
    $nome = $usuario['nome'];
    $token_expira = $usuario['token_expira_em'];
    
    // Verifica se o token expirou
    if (strtotime($token_expira) < time()) {
        mysqli_stmt_close($stmt);
        header("Location: esqueci_senha.php?error=Link expirado. Solicite um novo.");
        exit;
    }
} else {
    mysqli_stmt_close($stmt);
    header("Location: esqueci_senha.php?error=Link inválido ou expirado.");
    exit;
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Decola Militar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
                    <h4 class="mt-3">REDEFINIR SENHA</h4>
                </div>  
            </div>

            <!-- Mensagens de erro -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center">
                    <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <p class="text-center mb-3" style="color: #555; font-size: 0.95rem;">
                Olá, <strong><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></strong>!<br>
                Digite sua nova senha abaixo.
            </p>

            <form action="processa_redefinir_senha.php" method="POST" id="formRedefinir">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                
                <!-- Nova Senha -->
                <div class="mb-3">
                    <label for="nova_senha" class="form-label">Nova Senha</label>
                    <div class="password-container">
                        <input type="password" 
                               class="form-control input-estilizado" 
                               id="nova_senha" 
                               name="nova_senha" 
                               placeholder="Mínimo 8 caracteres"
                               required 
                               minlength="8">
                        <i class="bi bi-eye-fill toggle-visibility" id="toggleNova"></i>
                    </div>
                    
                    <!-- Indicador de força da senha -->
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <small id="strengthText" class="form-text"></small>
                </div>

                <!-- Confirmar Senha -->
                <div class="mb-3">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                    <div class="password-container">
                        <input type="password" 
                               class="form-control input-estilizado" 
                               id="confirmar_senha" 
                               name="confirmar_senha" 
                               placeholder="Digite a senha novamente"
                               required>
                        <i class="bi bi-eye-fill toggle-visibility" id="toggleConfirmar"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100">Redefinir Senha</button>
            </form>

            <div class="text-center mt-3">
                <a class="text-muted" href="login.php">Voltar para o login</a>
            </div>
        </div>
    </section>

    <!-- Modal de Sucesso -->
    <div class="modal fade" id="modalSucesso" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-body text-center py-5">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#28a745" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                    </div>
                    <h4 class="titulo">Senha Alterada!</h4>
                    <p class="mb-4">Sua senha foi atualizada com sucesso.</p>
                    <a href="login.php" class="btn btn-success">Ir para Login</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle visibilidade senha nova
        const novaSenhaInput = document.getElementById("nova_senha");
        const toggleNova = document.getElementById("toggleNova");

        toggleNova.addEventListener("click", () => {
            const isPassword = novaSenhaInput.type === "password";
            novaSenhaInput.type = isPassword ? "text" : "password";
            toggleNova.classList.toggle("bi-eye-fill", !isPassword);
            toggleNova.classList.toggle("bi-eye-slash-fill", isPassword);
        });

        // Toggle visibilidade confirmar senha
        const confirmarSenhaInput = document.getElementById("confirmar_senha");
        const toggleConfirmar = document.getElementById("toggleConfirmar");

        toggleConfirmar.addEventListener("click", () => {
            const isPassword = confirmarSenhaInput.type === "password";
            confirmarSenhaInput.type = isPassword ? "text" : "password";
            toggleConfirmar.classList.toggle("bi-eye-fill", !isPassword);
            toggleConfirmar.classList.toggle("bi-eye-slash-fill", isPassword);
        });

        // Verifica força da senha
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        novaSenhaInput.addEventListener('input', function() {
            const senha = this.value;
            let strength = 0;
            
            if (senha.length >= 8) strength++;
            if (senha.match(/[a-z]+/)) strength++;
            if (senha.match(/[A-Z]+/)) strength++;
            if (senha.match(/[0-9]+/)) strength++;
            if (senha.match(/[$@#&!]+/)) strength++;

            strengthBar.className = 'password-strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Senha fraca';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Senha média';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Senha forte';
                strengthText.style.color = '#28a745';
            }
        });

        // Validação do formulário
        const form = document.getElementById('formRedefinir');
        form.addEventListener('submit', function(e) {
            const senha = novaSenhaInput.value;
            const confirmar = confirmarSenhaInput.value;

            if (senha.length < 8) {
                e.preventDefault();
                alert('A senha deve ter no mínimo 8 caracteres.');
                return false;
            }

            if (senha !== confirmar) {
                e.preventDefault();
                alert('As senhas não conferem.');
                return false;
            }
        });

        // Abre modal se sucesso
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            const modal = new bootstrap.Modal(document.getElementById('modalSucesso'));
            modal.show();
        <?php endif; ?>
    </script>
</body>
</html>