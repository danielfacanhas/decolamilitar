<?php
session_start();
if (!isset($_SESSION['nome'])) {
    header("Location: ../login.php");
    exit;
}

include("../config/db_connect.php");

$email_sessao = $_SESSION['email'];
$mensagem = '';
$tipo_mensagem = '';

// Buscar usuário atual
$sql = "SELECT * FROM usuarios WHERE email = '$email_sessao'";
$res = mysqli_query($conn, $sql);
$usuario = mysqli_fetch_assoc($res);

// ATUALIZAR DADOS
if (isset($_POST['atualizar'])) {
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $email_novo = mysqli_real_escape_string($conn, $_POST['email']);
    $senha_atual = $_POST['senha_atual'];
    $senha_nova = $_POST['senha_nova'];
    $senha_confirma = $_POST['senha_confirma'];

    // Verifica duplicação de email
    if ($email_novo != $email_sessao) {
        $check = mysqli_query($conn, "SELECT id FROM usuarios WHERE email='$email_novo'");
        if (mysqli_num_rows($check) > 0) {
            $mensagem = "Este e-mail já está sendo usado!";
            $tipo_mensagem = "danger";
        }
    }

    // Upload de foto
    $foto_nova = $usuario['foto'];
    if (!$mensagem && !empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $permitidas)) {
            $mensagem = "Formato de imagem inválido!";
            $tipo_mensagem = "danger";
        } else {
            $novo_nome = uniqid() . "." . $ext;
            $destino = "perfil/$novo_nome";

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                if ($usuario['foto'] && file_exists("perfil/" . $usuario['foto'])) {
                    unlink("perfil/" . $usuario['foto']);
                }
                $foto_nova = $novo_nome;
            } else {
                $mensagem = "Erro ao enviar imagem!";
                $tipo_mensagem = "danger";
            }
        }
    }

    // Verificar e trocar senha
    if (!$mensagem && !empty($senha_nova)) {
        if ($senha_atual != $usuario['senha']) {
            $mensagem = "Senha atual incorreta!";
            $tipo_mensagem = "danger";
        } elseif ($senha_nova != $senha_confirma) {
            $mensagem = "As senhas não coincidem!";
            $tipo_mensagem = "danger";
        }
    }

    // Atualizar banco
    if (!$mensagem) {
        $sql_update = "UPDATE usuarios SET nome='$nome', email='$email_novo', foto='$foto_nova' " .
                      (!empty($senha_nova) ? ", senha='$senha_nova'" : "") .
                      " WHERE email='$email_sessao'";

        if (mysqli_query($conn, $sql_update)) {
            $_SESSION['nome'] = $nome;
            $_SESSION['email'] = $email_novo;
            $_SESSION['foto'] = $foto_nova;
            $mensagem = "Informações atualizadas com sucesso!";
            $tipo_mensagem = "success";
            $email_sessao = $email_novo;
        } else {
            $mensagem = "Erro ao atualizar!";
            $tipo_mensagem = "danger";
        }
    }
}

// DELETAR CONTA
if (isset($_POST['deletar_conta'])) {
    if ($_POST['senha_confirmacao'] == $usuario['senha']) {
        if ($usuario['foto'] && file_exists("perfil/" . $usuario['foto'])) {
            unlink("perfil/" . $usuario['foto']);
        }
        mysqli_query($conn, "DELETE FROM usuarios WHERE email='$email_sessao'");
        session_destroy();
        header("Location: login.php?msg=conta_deletada");
        exit;
    } else {
        $mensagem = "Senha incorreta!";
        $tipo_mensagem = "danger";
    }
}
$foto_atual = "";
if ($usuario['foto'] == "") {
  $foto_atual = "padrao.webp";
}
else {
  $foto_atual = $usuario['foto'];
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configurações - Decola Militar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/global.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Black+Han+Sans&display=swap');

    body {
      font-family: "Montserrat", sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .content-wrapper {
      flex: 1;
      padding: 40px 0;
    }

    /* Card principal */
    .config-card {
      background: #ffffff;
      border: 3px solid #495846;
      border-radius: 25px;
      padding: 40px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
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

    /* Foto de perfil */
    .profile-section {
      text-align: center;
      padding: 30px;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-radius: 20px;
      margin-bottom: 30px;
    }

    .profile-img-large {
      width: 160px;
      height: 160px;
      object-fit: cover;
      border-radius: 50%;
      border: 5px solid #495846;
      box-shadow: 0 6px 15px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
    }

    .profile-img-large:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(73,88,70,0.4);
    }

    /* Seções do formulário */
    .form-section {
      margin-bottom: 35px;
    }

    .section-title {
      font-family: "Black Han Sans", sans-serif;
      color: #2e3d2f;
      font-size: 1.5rem;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 3px solid #495846;
    }

    /* Inputs personalizados */
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

    /* Botões */
    .btn-save {
      background: linear-gradient(135deg, #495846 0%, #6a9762 100%);
      border: none;
      color: white;
      padding: 14px 40px;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(73,88,70,0.3);
    }

    .btn-save:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(73,88,70,0.4);
      background: linear-gradient(135deg, #6a9762 0%, #495846 100%);
    }

    /* Área de perigo */
    .danger-zone {
      background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
      border: 3px solid #dc3545;
      border-radius: 20px;
      padding: 30px;
      animation: slideUp 0.6s ease;
    }

    .danger-title {
      font-family: "Black Han Sans", sans-serif;
      color: #dc3545;
      font-size: 1.4rem;
      margin-bottom: 15px;
    }

    .btn-danger-custom {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      border: none;
      color: white;
      padding: 12px 30px;
      border-radius: 25px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(220,53,69,0.3);
    }

    .btn-danger-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(220,53,69,0.4);
    }

    /* Alertas */
    .alert {
      border-radius: 15px;
      border: none;
      padding: 15px 20px;
      font-weight: 500;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      animation: slideDown 0.4s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-15px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Modal customizado */
    .modal-content {
      border-radius: 20px;
      border: 3px solid #dc3545;
    }

    .modal-header {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
      border-radius: 17px 17px 0 0;
    }

    /* Upload de arquivo */
    .file-upload-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      width: 100%;
    }

    .file-upload-input {
      font-size: 100px;
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      cursor: pointer;
    }

    .file-upload-label {
      display: inline-block;
      padding: 12px 20px;
      background: #f8f9fa;
      border: 2px dashed #495846;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
      width: 100%;
    }

    .file-upload-label:hover {
      background: #e9ecef;
      border-color: #6a9762;
    }

    /* Hint text */
    .hint-text {
      font-size: 0.85rem;
      color: #6c757d;
      margin-top: 5px;
      display: block;
    }

    /* Separador */
    .divider {
      height: 3px;
      background: linear-gradient(90deg, transparent 0%, #495846 50%, transparent 100%);
      margin: 40px 0;
    }
  </style>
</head>
<body>

<?php include("navbar_colaborador.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="content-wrapper">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9 col-xl-8">
        
        <div class="titulo">
          <h1>CONFIGURAÇÕES DE CONTA</h1>
        </div>

        <?php if ($mensagem): ?>
          <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
            <strong><?= $tipo_mensagem === 'success' ? '✓' : '✗' ?></strong> <?= $mensagem ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- CARD PRINCIPAL -->
        <div class="config-card">
          
      <!-- FOTO ATUAL -->
<div class="text-center mb-4">
  <img src="../perfil/<?= $foto_atual ?>"
       alt="Foto atual"
       class="rounded-circle border border-3 border-success"
       style="width: 150px; height: 150px; object-fit: cover;">
  <p class="mt-2 text-muted">Foto atual</p>
</div>

          <!-- FORMULÁRIO -->
          <form method="POST" enctype="multipart/form-data">
            
            <!-- INFORMAÇÕES PESSOAIS -->
            <div class="form-section">
              <h2 class="section-title">Informações Pessoais</h2>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Nome completo</label>
                  <input type="text" name="nome" class="form-control" 
                         value="<?= htmlspecialchars($usuario['nome']) ?>" 
                         placeholder="Digite seu nome" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">E-mail</label>
                  <input type="email" name="email" class="form-control" 
                         value="<?= htmlspecialchars($usuario['email']) ?>" 
                         placeholder="seu@email.com" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Nova foto de perfil</label>
                <div class="file-upload-wrapper">
                  <label class="file-upload-label">
                    📷 Clique para escolher uma nova foto
                    <input type="file" name="foto" class="file-upload-input" accept="image/*">
                  </label>
                </div>
                <small class="hint-text">Formatos aceitos: JPG, PNG, GIF (máx. 5MB)</small>
              </div>
            </div>

            <div class="divider"></div>

            <!-- ALTERAR SENHA -->
            <div class="form-section">
              <h2 class="section-title">Segurança da Conta</h2>
              <p class="text-muted mb-3">Deixe os campos vazios se não deseja alterar sua senha</p>
              
              <div class="mb-3">
                <label class="form-label">Senha atual</label>
                <input type="password" name="senha_atual" class="form-control" 
                       placeholder="Digite sua senha atual">
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Nova senha</label>
                  <input type="password" name="senha_nova" class="form-control" 
                         placeholder="Digite a nova senha">
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Confirmar nova senha</label>
                  <input type="password" name="senha_confirma" class="form-control" 
                         placeholder="Confirme a nova senha">
                </div>
              </div>
            </div>

            <div class="text-center mt-4">
              <button type="submit" name="atualizar" class="btn-save">
                💾 Salvar Alterações
              </button>
            </div>
          </form>
        </div>

        <!-- ZONA DE PERIGO -->
        <div class="danger-zone">
          <h3 class="danger-title">⚠️ Zona de Perigo</h3>
          <p class="text-muted mb-3">
            <strong>Atenção:</strong> Esta ação é permanente e irreversível. 
            Todos os seus dados, incluindo histórico de simulados e questões respondidas, 
            serão apagados definitivamente.
          </p>
          
          <button type="button" class="btn-danger-custom" data-bs-toggle="modal" data-bs-target="#modalDeletar">
            🗑️ Excluir Minha Conta
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMAÇÃO -->
<div class="modal fade" id="modalDeletar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header text-white">
        <h5 class="modal-title fw-bold">⚠️ Confirmar Exclusão Permanente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body p-4">
          <div class="alert alert-danger mb-3">
            <strong>ATENÇÃO!</strong> Esta ação não pode ser desfeita.
          </div>
          
          <p class="mb-3">Você está prestes a deletar permanentemente sua conta <strong><?= htmlspecialchars($usuario['email']) ?></strong>.</p>
          
          <p class="mb-4">Será perdido:</p>
          <ul class="mb-4">
            <li>Todo seu histórico de simulados</li>
            <li>Suas estatísticas e progresso</li>
            <li>Questões respondidas</li>
            <li>Dados pessoais</li>
          </ul>
          
          <div class="mb-3">
            <label class="form-label fw-bold">Digite sua senha para confirmar:</label>
            <input type="password" name="senha_confirmacao" class="form-control" 
                   placeholder="Sua senha" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancelar
          </button>
          <button type="submit" name="deletar_conta" class="btn btn-danger">
            Confirmar Exclusão
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include("footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Preview da foto antes de enviar
  document.querySelector('input[name="foto"]').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.querySelector('.profile-img-large').src = e.target.result;
      }
      reader.readAsDataURL(e.target.files[0]);
    }
  });

  // Atualiza o texto do label quando um arquivo é escolhido
  document.querySelector('input[name="foto"]').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'Nenhum arquivo selecionado';
    const label = e.target.closest('.file-upload-wrapper').querySelector('.file-upload-label');
    label.innerHTML = `📷 ${fileName}`;
  });
</script>

</body>
</html>