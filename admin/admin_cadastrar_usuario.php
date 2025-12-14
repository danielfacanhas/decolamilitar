<?php
require_once '../config/verificar_admin.php'; // verifica se é admin ou não
include("../config/db_connect.php"); // conexao com bd

$mensagem = ''; // variavel de estado mutavel pra definir a mensagem que vai ser imprimida
$tipo_mensagem = '';

// processamento do cadastro, ele recebe nome, email, senha, senha confirma e role
if (isset($_POST['cadastrar_usuario'])) {
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = mysqli_real_escape_string($conn, $_POST['senha']);
    $senha_confirma = mysqli_real_escape_string($conn, $_POST['senha_confirma']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($role)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios!";
        $tipo_mensagem = "danger";
    } elseif ($senha !== $senha_confirma) {
        $mensagem = "As senhas não coincidem!";
        $tipo_mensagem = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "E-mail inválido!";
        $tipo_mensagem = "danger";
    } else {
        // Verificar se email já existe
        $check_email = mysqli_query($conn, "SELECT id FROM usuarios WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $mensagem = "Este e-mail já está cadastrado no sistema!";
            $tipo_mensagem = "danger";
        } else {
            // Processar upload de foto
            $foto_nome = 'padrao.webp'; // Foto padrão
            
            if (!empty($_FILES['foto']['name'])) {
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($ext, $permitidas)) {
                    $foto_nome = uniqid() . '_' . basename($_FILES['foto']['name']);
                    $destino = "perfil/" . $foto_nome;
                    
                    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                        $mensagem = "Erro ao fazer upload da foto. Usando foto padrão.";
                        $tipo_mensagem = "warning";
                        $foto_nome = 'padrao.webp';
                    }
                } else {
                    $mensagem = "Formato de imagem inválido! Usando foto padrão.";
                    $tipo_mensagem = "warning";
                }
            }
            
            // Inserir usuário
            $sql_insert = "INSERT INTO usuarios (nome, email, senha, foto, role) 
                          VALUES ('$nome', '$email', '$senha', '$foto_nome', '$role')";
            
            if (mysqli_query($conn, $sql_insert)) {
                $usuario_id = mysqli_insert_id($conn);
                
                // Registrar log
                $admin_email = $_SESSION['email'];
                $detalhes = "Usuário: $nome ($email) - Tipo: $role";
                mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) 
                                    VALUES ('$admin_email', 'Cadastrar Usuário', '$detalhes')");
                
                $mensagem = "Usuário cadastrado com sucesso!";
                $tipo_mensagem = "success";
                
                // Limpar campos
                $_POST = array();
            } else {
                $mensagem = "Erro ao cadastrar usuário: " . mysqli_error($conn);
                $tipo_mensagem = "danger";
            }
        }
    }
}

// Estatísticas
$total_usuarios = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios"))['total'];
$total_alunos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE role='aluno'"))['total'];
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE role='admin'"))['total'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastrar Usuário - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Black+Han+Sans&display=swap');

    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .content-wrapper {
      flex: 1;
      padding: 40px 0;
      font-family: "Montserrat", sans-serif;

    }

    /* Card do formulário */
    .form-card {
      background: white;
      border: 3px;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      margin-bottom: 30px;
      animation: slideUp 0.5s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-card h3 {
      font-family: "Black Han Sans", sans-serif;
      color: #2e3d2f;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 3px solid #495846;
    }

    /* Cards de estatísticas */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border: 3px;
      border-radius: 20px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      transition: all 0.3s ease;
      animation: slideUp 0.6s ease;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(73,88,70,0.2);
    }

    .stat-icon {
      font-size: 2.5rem;
      margin-bottom: 10px;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #495846;
      font-family: "Black Han Sans", sans-serif;
    }

    .stat-label {
      color: #666;
      font-size: 0.9rem;
      font-weight: 600;
      margin-top: 5px;
    }

    /* Inputs */
    .form-control, .form-select {
      border: 2px solid #d0d0d0;
      border-radius: 12px;
      padding: 12px 18px;
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
      border-color: #495846;
      box-shadow: 0 0 0 0.25rem rgba(73,88,70,0.15);
      background-color: #f8fdf8;
    }

    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      font-size: 0.9rem;
    }

    /* Upload de foto */
    .photo-upload-container {
      text-align: center;
      padding: 30px;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-radius: 15px;
      margin-bottom: 25px;
      border: 2px dashed #495846;
      transition: all 0.3s ease;
    }

    .photo-upload-container:hover {
      border-color: #6a9762;
      background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    }

    .file-input-label {
      display: inline-block;
      padding: 10px 25px;
      background: #495846;
      color: white;
      border-radius: 20px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .file-input-label:hover {
      background: #6a9762;
      transform: translateY(-2px);
    }

    .file-input-hidden {
      display: none;
    }

    /* Select de role */
    .role-option {
      padding: 15px;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .role-option:hover {
      border-color: #495846;
      background: #f8fdf8;
      transform: translateX(5px);
    }

    .role-option input[type="radio"]:checked ~ .role-content {
      font-weight: bold;
    }

    .role-option input[type="radio"]:checked {
      accent-color: #495846;
    }

    .role-icon {
      font-size: 2rem;
    }

    .role-content {
      flex: 1;
    }

    .role-title {
      font-weight: 600;
      color: #2e3d2f;
      font-size: 1.1rem;
      margin-bottom: 3px;
    }

    .role-description {
      font-size: 0.85rem;
      color: #666;
    }

    /* Botões */
    .btn-cadastrar {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .btn-cadastrar:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(73, 88, 70, 0.3);
      color: white;
    }

    .btn-voltar {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
      padding: 12px 24px;
      border-radius: 25px;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-voltar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
      color: white;
    }

    /* Senha strength indicator */
    .password-strength {
      height: 5px;
      background: #e0e0e0;
      border-radius: 5px;
      margin-top: 8px;
      overflow: hidden;
    }

    .password-strength-bar {
      height: 100%;
      width: 0%;
      transition: all 0.3s ease;
    }

    .strength-weak { width: 33%; background: #dc3545; }
    .strength-medium { width: 66%; background: #ffc107; }
    .strength-strong { width: 100%; background: #28a745; }

    /* Info box */
    .info-box {
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
      border-left: 4px solid #2196f3;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
    }

    .info-box strong {
      color: #1976d2;
    }
  </style>
</head>
<body>

<?php include("navbar_admin.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="content-wrapper">
  <div class="container">
    
    <div class="titulo">
      <h1>CADASTRAR NOVO USUARIO</h1>
    </div>

    <!-- Botão Voltar -->
    <div class="mb-4">
      <a href="painel_admin.php" class="btn-voltar">
        <span>←</span> Voltar ao Painel
      </a>
    </div>

    <!-- Estatísticas -->
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?= $total_usuarios ?></div>
        <div class="stat-label">Total de Usuários</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🎓</div>
        <div class="stat-number"><?= $total_alunos ?></div>
        <div class="stat-label">Alunos</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">👑</div>
        <div class="stat-number"><?= $total_admins ?></div>
        <div class="stat-label">Administradores</div>
      </div>
    </div>

    <!-- Mensagens -->
    <?php if ($mensagem): ?>
      <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
        <strong><?= $tipo_mensagem === 'success' ? '✅' : '⚠️' ?></strong> <?= $mensagem ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="form-card">
      <h3>📋 Dados do Novo Usuario</h3>

      <form method="POST" enctype="multipart/form-data" id="formCadastro">
        
        <!-- Upload de Foto -->
        <div class="photo-upload-container">
          <div>
            <label for="foto" class="file-input-label">
              📷 Escolher Foto de Perfil
            </label>
            <input type="file" name="foto" id="foto" class="file-input-hidden" accept="image/*">
          </div>
          <small class="text-muted d-block mt-2">Opcional - Formatos: JPG, PNG, GIF, WEBP</small>
        </div>

        <!-- Dados Pessoais -->
        <div class="row mb-4">
          <div class="col-md-6">
            <label class="form-label">Nome Completo *</label>
            <input type="text" name="nome" class="form-control" 
                   placeholder="Ex: João Silva" 
                   value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>" 
                   required>
          </div>

          <div class="col-md-6">
            <label class="form-label">E-mail *</label>
            <input type="email" name="email" class="form-control" 
                   placeholder="Ex: joao@exemplo.com" 
                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                   required>
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

        <!-- Tipo de Usuário -->
        <div class="mb-4">
          <label class="form-label d-block mb-3">Tipo de Usuário *</label>

          <label class="role-option">
            <input type="radio" name="role" value="aluno" required>
            <div class="role-content">
              <div class="role-icon">🎓</div>
              <div class="role-title">Aluno</div>
              <div class="role-description">
                Acesso para responder questões e realizar simulados
              </div>
            </div>
          </label>

          <label class="role-option">
            <input type="radio" name="role" value="admin">
            <div class="role-content">
              <div class="role-icon">👑</div>
              <div class="role-title">Administrador</div>
              <div class="role-description">
                Acesso total ao sistema, incluindo gerenciar usuários e logs
              </div>
            </div>
          </label>
        </div>

        <!-- Botões -->
        <div class="text-center mt-4">
          <button type="submit" name="cadastrar_usuario" class="btn-cadastrar">
            ✅ Cadastrar Usuário
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<?php include("footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

</body>
</html>