<?php
include("./config/db_connect.php");

$mensagem = "";

if (isset($_POST['cadastrar'])) {
  
  $nome = $_POST['nome'];
  $email = $_POST['email'];

  // ==============================
  // HASH DA SENHA
  // ==============================
  $senha = $_POST['senha'];
  $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

  // ==============================
  // FOTO DE PERFIL
  // ==============================
  $foto = "";
  if (!empty($_FILES['foto']['name'])) {
    $foto = uniqid() . "_" . $_FILES['foto']['name'];
    move_uploaded_file($_FILES['foto']['tmp_name'], "perfil/" . $foto);
  }

  // ============================================
  // INSERT NO BANCO AGORA USANDO A SENHA HASH
  // ============================================
  $sql = "INSERT INTO usuarios (nome, email, senha, foto) 
          VALUES ('$nome', '$email', '$senha_hash', '$foto')";

  if (mysqli_query($conn, $sql)) {
      $mensagem = "<div class='alert alert-success'>Usuário cadastrado com sucesso!</div>";
  } else {
      $mensagem = "<div class='alert alert-danger'>Erro ao cadastrar: " . mysqli_error($conn) . "</div>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./stylesheet/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<section class="h-100 gradient-form">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-xl-10">
        <div class="card rounded-3 text-black">
          <div class="row g-0">
            <div class="col-lg-6">
              <div class="card-body p-md-5 mx-md-4">

                <div class="text-center">
                  <img src="./imgs/aviao1.png" style="width: 100px; height:135px" alt="logo">
                  <div class="titulo">
                  <h4>BEM-VINDO(A) AO DECOLA MILITAR!</h4>
                </div>
                </div>

                <form method="post" enctype="multipart/form-data">
                <p>Por favor, cadastre sua conta</p>

<div class="mb-3">
  <label class="form-label">Nome</label>
  <div class="input-container">
      <input type="text" name="nome" class="form-control input-estilizado" placeholder="Seu nome" required>
      <i class="input-icon fas fa-user"></i>
  </div>
</div>


<div class="mb-3">
  <label class="form-label">E-mail</label>
  <div class="input-container">
      <input type="email" name="email" class="form-control input-estilizado" placeholder="Seu e-mail" required>
      <i class="input-icon fas fa-envelope"></i>
  </div>
</div>

                <!-- Senha -->
                <div class="row mb-4">
                  <div class="col-md-6">
                    <label class="form-label">Senha *</label>
                    <input type="password" name="senha" id="senha" class="form-control" 
                          placeholder="Mínimo 6 caracteres" 
                          minlength="6" required>
                    <div class="password-strength">
                      <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <small class="text-muted">Força da senha</small>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Confirmar Senha *</label>
                    <input type="password" name="senha_confirma" id="senha_confirma" class="form-control" 
                          placeholder="Digite a senha novamente" 
                          minlength="6" required>
                    <small class="text-muted" id="passwordMatch"></small>
                  </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Foto de perfil</label>
                    <input type="file" name="foto" class="form-control"/>
                </div>

                <div class="text-center pt-1 mb-5 pb-1">
                    <button type="submit" name="cadastrar" class="btn btn-outline-danger w-100">Cadastrar</button>
                    <br>
                    <a class="text-muted" href="login.php">Já tem uma conta cadastrada?</a>
                </div>
                </form>

              </div>
            </div>
            <div class="col-lg-6 d-flex align-items-center gradient-custom-2">
              <div class="text-white px-3 py-4 p-md-5 mx-md-4">
                <h4 class="mb-4">Lorem</h4>
                <p class="small mb-0">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                  tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
                  exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
  // Verificar força da senha
  document.getElementById('senha').addEventListener('input', function() {
    const senha = this.value;
    const strengthBar = document.getElementById('strengthBar');
    
    let strength = 0;
    if (senha.length >= 6) strength++;
    if (senha.length >= 10) strength++;
    if (/[A-Z]/.test(senha) && /[a-z]/.test(senha)) strength++;
    if (/[0-9]/.test(senha)) strength++;
    if (/[^A-Za-z0-9]/.test(senha)) strength++;
    
    strengthBar.className = 'password-strength-bar';
    
    if (strength <= 2) {
      strengthBar.classList.add('strength-weak');
    } else if (strength <= 3) {
      strengthBar.classList.add('strength-medium');
    } else {
      strengthBar.classList.add('strength-strong');
    }
  });

  // Verificar se senhas coincidem
  document.getElementById('senha_confirma').addEventListener('input', function() {
    const senha = document.getElementById('senha').value;
    const confirma = this.value;
    const matchText = document.getElementById('passwordMatch');
    
    if (confirma === '') {
      matchText.textContent = '';
      matchText.style.color = '';
    } else if (senha === confirma) {
      matchText.textContent = '✅ As senhas coincidem';
      matchText.style.color = '#28a745';
    } else {
      matchText.textContent = '❌ As senhas não coincidem';
      matchText.style.color = '#dc3545';
    }
  });

  // Validação antes de enviar
  document.getElementById('formCadastro').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha').value;
    const confirma = document.getElementById('senha_confirma').value;
    
    if (senha !== confirma) {
      e.preventDefault();
      alert('As senhas não coincidem!');
      return false;
    }
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>