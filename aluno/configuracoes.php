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

// 🔎 Buscar usuário atual
$sql = "SELECT * FROM usuarios WHERE email = '$email_sessao'";
$res = mysqli_query($conn, $sql);
$usuario = mysqli_fetch_assoc($res);


// ==========================
//     ATUALIZAR DADOS
// ==========================
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

                // Remove foto antiga
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

        $sql_update = "
            UPDATE usuarios SET
                nome='$nome',
                email='$email_novo',
                foto='$foto_nova' " .

                (!empty($senha_nova) ? ", senha='$senha_nova'" : "") .

            " WHERE email='$email_sessao'
        ";

        if (mysqli_query($conn, $sql_update)) {
            $_SESSION['nome'] = $nome;
            $_SESSION['email'] = $email_novo;
            $_SESSION['foto'] = $foto_nova;

            $mensagem = "Informações atualizadas com sucesso!";
            $tipo_mensagem = "success";

            // Atualiza variável de email
            $email_sessao = $email_novo;

        } else {
            $mensagem = "Erro ao atualizar!";
            $tipo_mensagem = "danger";
        }
    }
}


// ==========================
//     DELETAR CONTA
// ==========================
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
        <link rel="stylesheet" href="../stylesheet/configuracoes.css">

</head>
<body>

<?php include("navbar.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="container mt-5 mb-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      
      <div class="titulo">
        <h1 class="mb-4 text-center">CONFIGURACOES DO USUARIO</h1>
      </div>

      <?php if ($mensagem): ?>
        <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
          <?= $mensagem ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>



      <!-- FORMULÁRIO DE ATUALIZAÇÃO -->
      <form method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow">
    <!-- CABEÇALHO COM TÍTULO + FOTO -->

        <h4 class="m-0">Informações Pessoais</h4>
        <br>
        <div class="text-center">
            <img src="../perfil/<?= htmlspecialchars($foto_web); ?>"
                alt="Foto atual"
                class="rounded-circle border border-3 border-success shadow-sm"
                style="width: 130px; height: 130px; object-fit: cover;">
            <p class="mt-2 text-muted small">Foto atual</p>
        </div>

        <div class="mb-3">
          <label class="form-label">Nome</label>
          <input type="name" name="nome" class="form-control" 
                 value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" 
                 value="<?= htmlspecialchars($usuario['email']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Nova Foto de Perfil</label>
          <input type="file" name="foto" class="form-control" accept="image/*">
          <small class="text-muted">Deixe em branco para manter a foto atual</small>
        </div>

        <hr class="my-4">

        <h4 class="mb-3">Alterar Senha</h4>
        <small class="text-muted">Deixe em branco se não quiser alterar a senha</small>

        <div class="mb-3 mt-2">
          <label class="form-label">Senha Atual</label>
          <input type="password" name="senha_atual" class="form-control">
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


        <button type="submit" name="atualizar" class="btn btn-primary w-100">
          Salvar Alterações
        </button>
      </form>

      <!-- ZONA DE PERIGO -->
      <div class="mt-5 p-4 border border-danger rounded bg-light">
        <h4 class="text-danger">Deleção da Conta</h4>
        <p class="text-muted">Esta ação é irreversível. Todos os seus dados serão apagados permanentemente.</p>
        
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalDeletar">
          Deletar Conta
        </button>
      </div>

    </div>
  </div>
</div>

<!-- MODAL DE CONFIRMAÇÃO -->
<div class="modal fade" id="modalDeletar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header text-white">
        <h5 class="modal-title">⚠️ Confirmar Exclusão de Conta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <p><strong>Tem certeza que deseja deletar sua conta?</strong></p>
          <p class="text-muted">Esta ação não pode ser desfeita. Todos os seus dados serão perdidos.</p>
          
          <div class="mb-3">
            <label class="form-label">Digite sua senha para confirmar:</label>
            <input type="password" name="senha_confirmacao" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" name="deletar_conta" class="btn btn-danger">Deletar Conta</button>
        </div>
      </form>
    </div>
  </div>
</div>
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
<?php include("footer.php"); ?>
</body>
</html>