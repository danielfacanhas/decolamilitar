<?php
require_once '../config/verificar_admin.php';
include("../config/db_connect.php");

$mensagem = '';
$tipo_mensagem = '';

// Promover usuário para admin
if (isset($_POST['promover'])) {
  $user_id = intval($_POST['user_id']);
  $sql = "UPDATE usuarios SET role = 'admin' WHERE id = $user_id";
  if (mysqli_query($conn, $sql)) {
    $mensagem = "Usuário promovido para administrador com sucesso!";
    $tipo_mensagem = "success";
    
    // Log da ação
    $admin_email = $_SESSION['email'];
    mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) VALUES ('$admin_email', 'Promover Usuário', 'ID: $user_id')");
  }
}

// Rebaixar admin para aluno
if (isset($_POST['rebaixar'])) {
  $user_id = intval($_POST['user_id']);
  
  // Verificar se não é o último admin
  $total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE role='admin'"))['total'];
  
  if ($total_admins <= 1) {
    $mensagem = "Não é possível rebaixar o último administrador do sistema!";
    $tipo_mensagem = "danger";
  } else {
    $sql = "UPDATE usuarios SET role = 'aluno' WHERE id = $user_id";
    if (mysqli_query($conn, $sql)) {
      $mensagem = "Usuário rebaixado para aluno com sucesso!";
      $tipo_mensagem = "success";
      
      // Log da ação
      $admin_email = $_SESSION['email'];
      mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) VALUES ('$admin_email', 'Rebaixar Usuário', 'ID: $user_id')");
    }
  }
}

// Deletar usuário
if (isset($_POST['deletar'])) {
  $user_id = intval($_POST['user_id']);
  
  // Buscar info do usuário
  $user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM usuarios WHERE id = $user_id"));
  
  // Verificar se não está tentando deletar a si mesmo
  if ($user_info['email'] === $_SESSION['email']) {
    $mensagem = "Você não pode deletar sua própria conta!";
    $tipo_mensagem = "danger";
  } else {
    // Deletar foto se existir
    if ($user_info['foto'] && file_exists("perfil/" . $user_info['foto'])) {
      unlink("perfil/" . $user_info['foto']);
    }
    
    $sql = "DELETE FROM usuarios WHERE id = $user_id";
    if (mysqli_query($conn, $sql)) {
      $mensagem = "Usuário deletado com sucesso!";
      $tipo_mensagem = "success";
      
      // Log da ação
      $admin_email = $_SESSION['email'];
      mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) VALUES ('$admin_email', 'Deletar Usuário', 'ID: $user_id - Email: {$user_info['email']}')");
    }
  }
}

// Buscar todos os usuários
$filtro = $_GET['filtro'] ?? 'todos';
$busca = $_GET['busca'] ?? '';

$where = [];
if ($filtro === 'admin') {
  $where[] = "role = 'admin'";
} elseif ($filtro === 'aluno') {
  $where[] = "role = 'aluno'";
}

if (!empty($busca)) {
  $busca_safe = mysqli_real_escape_string($conn, $busca);
  $where[] = "(nome LIKE '%$busca_safe%' OR email LIKE '%$busca_safe%')";
}

$where_sql = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";
$sql_usuarios = "SELECT * FROM usuarios $where_sql ORDER BY id DESC";
$res_usuarios = mysqli_query($conn, $sql_usuarios);

$total_usuarios = mysqli_num_rows($res_usuarios);
// Usuários recentes
$usuarios_recentes = mysqli_query($conn, "SELECT * FROM usuarios ORDER BY id DESC LIMIT 5");

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciar Usuários - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    .user-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      transition: all 0.3s ease;
      border: none;
      font-family: "Montserrat", sans-serif;
    }
    
    .user-card:hover {
      transform: translateY(-5px);
    }
    
    .user-photo {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #f0f0f0;
      transition: all 0.3s ease;
    }

    .user-card:hover .user-photo {
      border-color: #a5fa9aff;
    }
    
    .badge-role {
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
      display: inline-block;
    }
    
    .badge-admin {
      background: linear-gradient(135deg, #ebdc57ff, #ffed49ff);
      color: white;
    }
    
    .badge-aluno {
      background: linear-gradient(135deg, #2d7e9eff, #307375ff);
      color: white;
    }
    
    .btn-action {
      padding: 10px 20px;
      border-radius: 25px;
      font-weight: 600;
      font-size: 0.85rem;
      border: none;
      transition: all 0.3s ease;
      margin: 3px;
    }
    

    .btn-promover:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 176, 155, 0.4);
      color: white;
    }
    
    .btn-rebaixar {
      background: linear-gradient(135deg, #ffa581ff, #e68979ff);
      color: #333;
    }

    .btn-rebaixar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(253, 203, 110, 0.4);
    }
    
    .btn-editar {
      background-color: #709469ff;
      color: white;
      text-decoration: none;
    }

      .btn-editar:hover {
      background-color: #74a06bff;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(130, 196, 124, 0.4);

    }
    .btn-deletar {
      background: linear-gradient(135deg, #da3a3aff, #e77281ff);
      color: white;
    }

    .btn-deletar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
      color: white;
    }
    
    .filtro-bar {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      font-family: "Montserrat", sans-serif;
    }

    .filtro-bar h5 {
      color: #2e3d2f;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .stats-container {
      background: #495846;
      color: white;
      padding: 20px;
      border-radius: 15px;
      text-align: center;
      margin-bottom: 25px;      
    }
    
    .stats-container strong {
      font-size: 1.5rem;
      display: block;
      margin-bottom: 5px;
      
    }

    .user-info {
      flex: 1;
    }

    .user-info h5 {
      margin: 0 0 5px 0;
      color: #2e3d2f;
      font-weight: 700;
    }

    .user-info .email {
      color: #666;
      margin: 0;
      font-size: 0.95rem;
    }

    .user-info .user-id {
      color: #999;
      font-size: 0.85rem;
      margin-top: 5px;
    }

    .actions-container {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      justify-content: flex-end;
      align-items: center;
      
    }

    .top-actions {
      display: flex;
      gap: 12px;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }

    .btn-modern {
      padding: 12px 24px;
      border-radius: 25px;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn-voltar {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
    }

    .btn-voltar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
      color: white;
    }

    .badge-voce {
      background: linear-gradient(135deg, #4787a0ff, #4b82a2ff);
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.85rem;
    }

    .user-card-content {
      display: flex;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
      
    }

    .user-role-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      min-width: 120px;
      
    }

    @media (max-width: 768px) {
      .user-card-content {
        flex-direction: column;
        text-align: center;
      }

      .actions-container {
        justify-content: center;
        width: 100%;
        
      }
    }

      .info-table {
      background: white;
      border: 3px;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      margin-bottom: 20px;
      font-family: "Montserrat", sans-serif;
    }
    
    .info-table h5 {
      color: #2e3d2f;
      margin-bottom: 20px;
      font-weight: bold;
      padding-bottom: 10px;
      border-bottom: 2px solid #e9ecef;
      
    }
  </style>
</head>
<body>

<?php include("navbar_admin.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>GERENCIAR USUARIOS</h1>
</div>
<div class="container mt-4 mb-5">
  <!-- MENSAGENS -->
  <?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
      <?= $mensagem ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- BOTÃO VOLTAR -->
  <div class="top-actions">
    <a href="painel_admin.php" class="btn-modern btn-voltar">
      ← Voltar ao Painel
    </a>
  </div>

  <!-- ESTATÍSTICAS -->
  <div class="stats-container">
    <strong><?= $total_usuarios ?></strong>
    <div>usuários encontrados</div>
  </div>

  <!-- FILTROS -->
  <div class="filtro-bar">
    <h5>🔍 Filtros de Busca</h5>
    <form method="GET" class="row g-3">
      <div class="col-md-4">
        <label class="form-label fw-bold">Filtrar por Tipo</label>
        <select name="filtro" class="form-select" onchange="this.form.submit()">
          <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos os Usuários</option>
          <option value="admin" <?= $filtro === 'admin' ? 'selected' : '' ?>>🛡️ Administradores</option>
          <option value="aluno" <?= $filtro === 'aluno' ? 'selected' : '' ?>>👨‍🎓 Alunos</option>
        </select>
      </div>
      
      <div class="col-md-6">
        <label class="form-label fw-bold">Buscar</label>
        <input type="text" name="busca" class="form-control" 
               placeholder="Nome ou email..." 
               value="<?= htmlspecialchars($busca) ?>">
      </div>
      
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100" 
                style="background: linear-gradient(135deg, #668368ff, #3f6941ff); border: none; border-radius: 10px; padding: 12px; font-weight: 600;">
          🔎 Buscar
        </button>
      </div>
    </form>
  </div>

  <!-- tabela de usuarios cadastrados-->
<div class="info-table">
        <h5>👤 Últimos Usuários Cadastrados</h5>
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Tipo</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              mysqli_data_seek($usuarios_recentes, 0);
              while($user = mysqli_fetch_assoc($usuarios_recentes)): 
              ?>
              <tr>
                <td><?= htmlspecialchars($user['nome']) ?></td>
                <td><small><?= htmlspecialchars($user['email']) ?></small></td>
                <td>
                  <span class="badge-role <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-aluno' ?>">
                    <?= $user['role'] === 'admin' ? '🛡️ Admin' : '👨‍🎓 Aluno' ?>
                  </span>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
  <!-- LISTA DE USUÁRIOS -->
  <?php if (mysqli_num_rows($res_usuarios) > 0): ?>
    <?php while($user = mysqli_fetch_assoc($res_usuarios)): ?>
      <div class="user-card">
        <div class="user-card-content">
          <img src="../perfil/<?= htmlspecialchars($user['foto']) ?>" 
               class="user-photo" 
               alt="Foto de <?= htmlspecialchars($user['nome']) ?>">
          
          <div class="user-info">
            <h5><?= htmlspecialchars($user['nome']) ?></h5>
            <p class="email"><?= htmlspecialchars($user['email']) ?></p>
            <small class="user-id">ID: <?= $user['id'] ?></small>
          </div>
          
          <div class="user-role-container">
            <span class="badge-role <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-aluno' ?>">
              <?= $user['role'] === 'admin' ? '🛡️ Admin' : '👨‍🎓 Aluno' ?>
            </span>
          </div>
          
       <div class="actions-container">
            <?php if ($user['email'] !== $_SESSION['email']): ?>
              
              <a href="admin_editar_usuario.php?id=<?= $user['id'] ?>" 
                 class="btn-action btn-editar">
                ✏️ Editar
              </a>
              
              <?php if ($user['role'] === 'aluno'): ?>

              <?php else: ?>

              <?php endif; ?>
              
              <form method="POST" class="d-inline">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <button type="submit" name="deletar" class="btn-action btn-deletar"
                        onclick="return confirm('⚠️ ATENÇÃO! Deletar permanentemente <?= htmlspecialchars($user['nome']) ?>?\n\nEsta ação não pode ser desfeita!')">
                  🗑️ Deletar
                </button>
              </form>
              
            <?php else: ?>
              <span class="badge-voce">👤 Você</span>
            <?php endif; ?>
          </div>
          
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="text-center py-5">
      <h4 class="text-muted">🔭 Nenhum usuário encontrado</h4>
      <p>Tente ajustar os filtros de busca</p>
    </div>
  <?php endif; ?>

</div>



<?php include("footer.php"); ?>
</body>
</html>