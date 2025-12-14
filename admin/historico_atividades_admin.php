<?php
require_once '../config/verificar_admin.php';
include("../config/db_connect.php");

// Estatísticas gerais
$total_usuarios = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios"))['total'];
$total_questoes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM questoes"))['total'];
$total_simulados = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM simulados"))['total'];
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE role='admin'"))['total'];

// Estatísticas de respostas
$total_respostas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM respostas_usuarios"))['total'];
$total_simulados_realizados = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM respostas_simulado"))['total'];

// Usuários recentes
$usuarios_recentes = mysqli_query($conn, "SELECT * FROM usuarios ORDER BY id DESC LIMIT 5");

// Disciplinas mais populares
$sql_disciplinas = "SELECT disciplina, COUNT(*) as total 
                    FROM questoes 
                    GROUP BY disciplina 
                    ORDER BY total DESC
                    LIMIT 5";
$disciplinas_stats = mysqli_query($conn, $sql_disciplinas);

// Últimas atividades dos logs
$sql_logs = "SELECT * FROM logs_admin ORDER BY data_hora DESC LIMIT 10";
$res_logs = mysqli_query($conn, $sql_logs);
$tem_logs = mysqli_num_rows($res_logs) > 0;


?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel Administrativo - Decola Militar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">
    <link rel="stylesheet" href="../stylesheet/global.css">


  <style>
    .admin-header {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 40px;
      border-radius: 15px;
      margin-bottom: 30px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      position: relative;
      overflow: hidden;
      font-family: "Montserrat", sans-serif;
    }
    
    .admin-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      animation: pulse 8s infinite;
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

    .log-item {
      padding: 12px;
      border-left: 3px solid #495846;
      background: #f8f9fa;
      border-radius: 5px;
      margin-bottom: 10px;
      transition: all 0.3s ease;
    }
    
    .log-item:hover {
      background: #e9ecef;
      transform: translateX(5px);
    }
    
    .log-acao {
      font-weight: bold;
      color: #495846;
    }
    
    .log-data {
      font-size: 0.85rem;
      color: #666;
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
    
  </style>
</head>
<body>

<?php include("navbar_admin.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>REGISTRO DE ATIVIDADES</h1>
</div>

<div class="container mt-4 mb-5">
    <!-- BOTÃO VOLTAR -->
  <div class="top-actions">
    <a href="painel_admin.php" class="btn-modern btn-voltar">
      ← Voltar ao Painel
    </a>
  </div>
  <!-- HEADER ADMIN -->
  <div class="admin-header">
    <h2 style="position: relative; z-index: 1;">Bem-vindo, Administrador <?= htmlspecialchars($_SESSION['nome']) ?>! 👋</h2>
    <p class="mb-0" style="position: relative; z-index: 1;">Gerencie o sistema Decola Militar de forma eficiente</p>
  </div>

      <!-- ÚLTIMAS ATIVIDADES -->
      <?php if ($tem_logs): ?>
      <div class="info-table">
        <h5>📋 Últimas Atividades</h5>
        <?php while($log = mysqli_fetch_assoc($res_logs)): ?>
          <div class="log-item">
            <div class="log-acao"><?= htmlspecialchars($log['acao']) ?></div>
            <small class="log-data">
              <?= date('d/m/Y H:i', strtotime($log['data_hora'])) ?> - 
              <?= htmlspecialchars($log['admin_email']) ?>
            </small>
            <?php if (!empty($log['detalhes'])): ?>
              <div><small class="text-muted"><?= htmlspecialchars($log['detalhes']) ?></small></div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include("footer.php"); ?>
</body>
</html>