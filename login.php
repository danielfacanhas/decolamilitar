<?php
session_start();
include("./config/db_connect.php");

// Se o usuário clicou em "Entrar"
if (isset($_POST['entrar'])) {

    // Escapar entradas para evitar SQL Injection
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = $_POST['senha'];

    // Buscar usuário pelo e-mail
    $sql = "SELECT * FROM usuarios WHERE email = '$email' LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {

        $user = mysqli_fetch_assoc($res);

        // Aqui a senha DEVE estar com password_hash no banco
        if (password_verify($senha, $user['senha'])) {

            // Salvar dados na sessão
            $_SESSION['nome']  = $user['nome'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['foto']  = $user['foto'];
            $_SESSION['role']  = isset($user['role']) ? $user['role'] : 'aluno';

            // Redirecionar conforme a role
            if ($_SESSION['role'] === 'admin') {
                header("Location: ./admin/painel_admin.php");
                exit;
            }
            if ($_SESSION['role'] === 'aluno') {
                header("Location: ./aluno/pagina1.php");
                exit;
            }
            if ($_SESSION['role'] === 'colaborador') {
                header("Location: ./colaborador/painel_colaborador.php");
                exit;
            }

        } else {
            $erro = "Senha incorreta!";
        }

    } else {
        $erro = "Usuário não encontrado!";
    }
}

// Mensagem de conta deletada
if (isset($_GET['msg']) && $_GET['msg'] == 'conta_deletada') {
  $mensagem_info = "Conta deletada com sucesso!";
}


$nome_administrador = "Administrador";
$email_administrador = "decolamilitaradmin@gmail.com";
$senha_administrador = password_hash("administradorDM17", PASSWORD_DEFAULT);
$role_administrador = "admin";

$sql = "SELECT id FROM usuarios WHERE email = '$email_administrador'";
$res = mysqli_query($conn, $sql);

if (mysqli_num_rows($res) == 0) {
    $insert = "INSERT INTO usuarios (nome, email, senha, role) 
               VALUES ('$nome_administrador', '$email_administrador', '$senha_administrador', '$role_administrador')";
    if (mysqli_query($conn, $insert)) {
    } else {
        echo "Erro: " . mysqli_error($conn);
    }
} else {
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Decola Militar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="./stylesheet/login.css">
</head>
<body>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<section class="vh-100 d-flex align-items-center justify-content-center">
  <div class="card p-4 shadow" style="width: 400px;">
    <div class="text-center mb-4">
      <img src="./imgs/aviao1.png" width="80" height="100" alt="logo">
      <div class="titulo">
        <h4 class="mt-3">BEM-VINDO(A) AO DECOLA MILITAR!</h4>
      </div>  
    </div>

    <?php if (isset($erro)): ?>
      <div class="alert alert-danger text-center"><?= $erro ?></div>
    <?php endif; ?>

    <?php if (isset($mensagem_info)): ?>
      <div class="alert alert-info text-center"><?= $mensagem_info ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
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

<div class="mb-3">
  <label for="senha" class="form-label">Senha</label>

  <div class="password-container">
    <input type="password" 
           class="form-control input-estilizado" 
           id="senha"
           name="senha"
           placeholder="Digite sua senha" 
           required>

    <i class="bi bi-eye-fill toggle-visibility" id="toggleSenha"></i>
  </div>
</div>
    
      <button type="submit" name="entrar" class="btn btn-success w-100">Entrar</button>
    </form>

    <div class="text-center mt-3">
      <p class="mb-1">Ainda não tem conta?</p>
      <a class="text-muted" href="cadastro.php">Cadastre aqui</a>
    </div>
        <div class="text-center mt-3 esqueci-senha">
      <a class="text-muted" href="esqueci_senha.php">Esqueci a senha</a>
        </div>
  </div>
</section>

<script>
  const senhaInput = document.getElementById("senha");
  const toggleBtn = document.getElementById("toggleSenha");

  toggleBtn.addEventListener("click", () => {
    const isPassword = senhaInput.type === "password";
    senhaInput.type = isPassword ? "text" : "password";

    // Alternar ícone
    toggleBtn.classList.toggle("bi-eye-fill", !isPassword);
    toggleBtn.classList.toggle("bi-eye-slash-fill", isPassword);
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>