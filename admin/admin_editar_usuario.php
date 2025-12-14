<?php
require_once '../config/verificar_admin.php';
include("../config/db_connect.php");

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar dados do usuário
$sql = "SELECT * FROM usuarios WHERE id = $user_id";
$resultado = mysqli_query($conn, $sql);
$usuario = mysqli_fetch_assoc($resultado);

if (!$usuario) {
  die("Usuário não encontrado!");
}

$mensagem = '';
$tipo_mensagem = '';

// ATUALIZAR USUÁRIO
if (isset($_POST['atualizar'])) {
  $nome = mysqli_real_escape_string($conn, $_POST['nome']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $role = $_POST['role'];
  $nova_senha = $_POST['nova_senha'] ?? '';
  
  // Verificar se email já existe em outro usuário
  $check_email = mysqli_query($conn, "SELECT id FROM usuarios WHERE email = '$email' AND id != $user_id");
  
  if (mysqli_num_rows($check_email) > 0) {
    $mensagem = "Este email já está sendo usado por outro usuário!";
    $tipo_mensagem = "danger";
  } else {
    
    $foto_atual = $usuario['foto'];
    
    // Upload nova foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
      $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
      $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
      
      if (in_array($extensao, $extensoes_permitidas)) {
        // Deletar foto antiga se não for default
        if (!empty($foto_atual) && $foto_atual != 'default.png' && file_exists("perfil/" . $foto_atual)) {
          unlink("perfil/" . $foto_atual);
        }
        
        $nome_arquivo = time() . "_" . uniqid() . "." . $extensao;
        move_uploaded_file($_FILES['foto']['tmp_name'], "perfil/" . $nome_arquivo);
        $foto_atual = $nome_arquivo;
      } else {
        $mensagem = "Formato de imagem não permitido! Use JPG, JPEG, PNG ou GIF.";
        $tipo_mensagem = "warning";
      }
    }
    
    // Preparar query de atualização
    $sql_update = "UPDATE usuarios SET 
                   nome = '$nome',
                   email = '$email',
                   role = '$role',
                   foto = '$foto_atual'";
    
    // Adicionar senha se foi fornecida
    if (!empty($nova_senha)) {
      $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
      $sql_update .= ", senha = '$senha_hash'";
    }
    
    $sql_update .= " WHERE id = $user_id";
    
    if (mysqli_query($conn, $sql_update)) {
      $mensagem = "Usuário atualizado com sucesso!";
      $tipo_mensagem = "success";
      
      // Log da ação
      $admin_email = $_SESSION['email'];
      $detalhes = "ID: $user_id - Email: $email";
      if (!empty($nova_senha)) {
        $detalhes .= " - Senha alterada";
      }
      mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) VALUES ('$admin_email', 'Editar Usuário', '$detalhes')");
      
      // Atualizar dados do usuário na página
      $resultado = mysqli_query($conn, $sql);
      $usuario = mysqli_fetch_assoc($resultado);
      
    } else {
      $mensagem = "Erro ao atualizar: " . mysqli_error($conn);
      $tipo_mensagem = "danger";
    }
  }
}

// Buscar estatísticas do usuário
$stats_questoes = 0;
$stats_simulados = 0;

// Verificar se a tabela respostas existe e buscar estatísticas
$result_questoes = mysqli_query($conn, "SELECT COUNT(*) as total FROM respostas WHERE email_usuario = '{$usuario['email']}'");
if ($result_questoes) {
  $stats_questoes = mysqli_fetch_assoc($result_questoes)['total'];
}

// Verificar se a tabela respostas_simulado existe e buscar estatísticas
$result_simulados = mysqli_query($conn, "SELECT COUNT(*) as total FROM respostas_simulado WHERE email_usuario = '{$usuario['email']}'");
if ($result_simulados) {
  $stats_simulados = mysqli_fetch_assoc($result_simulados)['total'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Usuário - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    .form-container {
      max-width: 900px;
      margin: 0 auto;
    }
    
    .form-card {
      background: white;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      animation: slideIn 0.5s ease;
      font-family: "Montserrat", sans-serif;
    }
    
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .form-card h3 {
      color: #2e3d2f;
      font-weight: bold;
      margin-bottom: 10px;
      padding-bottom: 15px;
      border-bottom: 3px solid #495846;
    }
    
    .form-section {
      margin-top: 30px;
      padding-top: 25px;
      border-top: 2px solid #e9ecef;
    }
    
    .form-section:first-of-type {
      border-top: none;
      margin-top: 20px;
    }
    
    .section-title {
      font-size: 1.2rem;
      font-weight: bold;
      color: #495846;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }
    
    .form-control, .form-select {
      border: 2px solid #d0d0d0;
      border-radius: 10px;
      padding: 12px 15px;
      transition: all 0.3s ease;
      font-size: 0.95rem;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #495846;
      box-shadow: 0 0 0 0.25rem rgba(73, 88, 70, 0.15);
    }

    .user-photo-preview {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #495846;
      margin: 20px auto;
      display: block;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .file-upload-wrapper {
      position: relative;
      border: 2px dashed #d0d0d0;
      border-radius: 10px;
      padding: 30px;
      text-align: center;
      background: #f8f9fa;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .file-upload-wrapper:hover {
      border-color: #495846;
      background: #e9ecef;
    }
    
    .file-upload-wrapper input[type="file"] {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      cursor: pointer;
    }

    .btn-salvar {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 25px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .btn-salvar:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(73, 88, 70, 0.3);
      color: white;
    }

    .btn-voltar {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
      border: none;
      padding: 10px 25px;
      border-radius: 20px;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn-voltar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
      color: white;
    }

    .info-box {
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
      border-left: 4px solid #2196f3;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
    }

    .role-selector {
      background: white;
      border: 2px solid #495846;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .role-option {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s;
      margin: 8px 0;
    }

    .role-option:hover {
      background: #f8f9fa;
    }

    .role-option input[type="radio"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }

    .role-option label {
      cursor: pointer;
      margin: 0;
      flex: 1;
      font-weight: 600;
    }

    .password-info {
      background: linear-gradient(135deg, #fff3cd, #ffeaa7);
      border-left: 4px solid #ffc107;
      padding: 15px 20px;
      border-radius: 8px;
      margin-top: 10px;
      font-size: 0.9rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin: 20px 0;
    }

    .stat-box {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      border: 2px solid #28a745;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
    }

    .stat-box .number {
      font-size: 2rem;
      font-weight: bold;
      color: #155724;
      display: block;
    }

    .stat-box .label {
      color: #155724;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .user-info-badge {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
      margin-right: 10px;
    }

    .badge-admin-role {
      background: linear-gradient(135deg, #ffc107, #ffca2c);
      color: #333;
    }

    .badge-aluno-role {
      background: linear-gradient(135deg, #17a2b8, #48b9db);
      color: white;
    }

    @media (max-width: 768px) {
      .form-card { padding: 25px; }
    }
  </style>
</head>
<body>

<?php include("navbar_admin.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>✏️ EDITAR USUÁRIO #<?= $user_id ?></h1>
</div>

<div class="container mt-4 mb-5">
  <div class="form-container">

    <?php if($mensagem): ?>
    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
      <?= $mensagem ?>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="mb-4">
      <a href="admin_gerenciar_usuarios.php" class="btn-voltar">
        ← Voltar para Gerenciar Usuários
      </a>
    </div>

    <div class="form-card">
      <h3>👤 Editando Usuário</h3>

      <div class="text-center mb-4">
        <span class="user-info-badge <?= $usuario['role'] === 'admin' ? 'badge-admin-role' : 'badge-aluno-role' ?>">
          <?= $usuario['role'] === 'admin' ? '🛡️ Administrador' : '👨‍🎓 Aluno' ?>
        </span>
        <span class="user-info-badge" style="background: #e9ecef; color: #495846;">
          ID: <?= $usuario['id'] ?>
        </span>
      </div>

      <!-- Estatísticas do Usuário -->
      <div class="form-section">
        <div class="section-title"><span>📊</span> Estatísticas do Usuário</div>
        <div class="stats-grid">
          <div class="stat-box">
            <span class="number"><?= $stats_questoes ?></span>
            <span class="label">Questões Respondidas</span>
          </div>
          <div class="stat-box">
            <span class="number"><?= $stats_simulados ?></span>
            <span class="label">Simulados Realizados</span>
          </div>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">

        <!-- Foto de Perfil -->
        <div class="form-section">
          <div class="section-title"><span>📷</span> Foto de Perfil</div>
          
          <?php if($usuario['foto']): ?>
          <img src="./perfil/<?= htmlspecialchars($usuario['foto']) ?>" 
               class="user-photo-preview" 
               alt="Foto atual">
          <?php endif; ?>

          <div class="file-upload-wrapper">
            <input type="file" name="foto" accept="image/*">
            <div style="font-size:3rem;color:#495846;margin-bottom:10px;">📸</div>
            <div><strong>Clique para selecionar nova foto</strong></div>
            <small class="text-muted">JPG, JPEG, PNG ou GIF</small>
          </div>
        </div>

        <!-- Informações Básicas -->
        <div class="form-section">
          <div class="section-title"><span>📋</span> Informações Básicas</div>

          <div class="mb-3">
            <label class="form-label">Nome Completo</label>
            <input type="text" name="nome" class="form-control" 
                   value="<?= htmlspecialchars($usuario['nome']) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" 
                   value="<?= htmlspecialchars($usuario['email']) ?>" required>
          </div>
        </div>

        <!-- Tipo de Conta -->
        <div class="form-section">
          <div class="section-title"><span>🎯</span> Tipo de Conta</div>
          
          <div class="info-box">
            <strong>⚠️ Atenção:</strong> Ao alterar o tipo de conta, o usuário terá suas permissões modificadas imediatamente.
          </div>

          <div class="role-selector">
            <div class="role-option">
              <input type="radio" name="role" value="aluno" id="role_aluno" 
                     <?= $usuario['role'] === 'aluno' ? 'checked' : '' ?>>
              <label for="role_aluno">
                <strong>👨‍🎓 Aluno</strong><br>
                <small class="text-muted">Acesso às questões, simulados e resultados</small>
              </label>
            </div>

            <div class="role-option">
              <input type="radio" name="role" value="admin" id="role_admin" 
                     <?= $usuario['role'] === 'admin' ? 'checked' : '' ?>>
              <label for="role_admin">
                <strong>🛡️ Administrador</strong><br>
                <small class="text-muted">Acesso total ao sistema, gerenciar questões, simulados e usuários</small>
              </label>
            </div>
          </div>
        </div>

        <!-- Alterar Senha -->
        <div class="form-section">
          <div class="section-title"><span>🔐</span> Alterar Senha</div>
          
          <div class="password-info">
            <strong>💡 Opcional:</strong> Deixe em branco para manter a senha atual.
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label">Nova Senha</label>
            <input type="password" name="nova_senha" class="form-control" 
                   placeholder="Digite a nova senha (opcional)" minlength="6">
            <small class="text-muted">Mínimo de 6 caracteres</small>
          </div>
        </div>

        <!-- Botão Salvar -->
        <div class="d-flex justify-content-end mt-4">
          <button type="submit" name="atualizar" class="btn-salvar">
            💾 Salvar Alterações
          </button>
        </div>

      </form>
    </div>

  </div>
</div>

<?php include("footer.php"); ?>
</body>
</html>