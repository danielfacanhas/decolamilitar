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
      background: linear-gradient(135deg, #dc3545, #e57373);
      color: white;
      padding: 40px;
      border-radius: 15px;
      margin-bottom: 30px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      position: relative;
      overflow: hidden;
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
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 0.5; }
      50% { transform: scale(1.1); opacity: 0.8; }
    }
    
    .stat-card {
      background: white;
      border: 3px;
      border-radius: 15px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      transition: all 0.3s ease;
      height: 100%;
      position: relative;
      overflow: hidden;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(73, 88, 70, 0.1), transparent);
      transition: left 0.5s;
    }
    
    .stat-card:hover::before {
      left: 100%;
    }
    
    .stat-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 12px 20px rgba(0,0,0,0.2);
      border-color: #6a9762;
    }
    
    .stat-icon {
      font-size: 3.5rem;
      margin-bottom: 15px;
      filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.1));
    }
    
    .stat-number {
      font-size: 3rem;
      font-weight: bold;
      color: #495846;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.05);
    }
    
    .stat-label {
      color: #666;
      font-size: 1rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
    }
    
    .action-card {
      background: white;
      border: 3px;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: flex;
      align-items: center;
      gap: 20px;
      position: relative;
      overflow: hidden;
    }
    
    .action-card::after {
      content: '→';
      position: absolute;
      right: 30px;
      font-size: 2rem;
      opacity: 0;
      transition: all 0.3s ease;
      color: #495846;
    }
    
    .action-card:hover {
      transform: translateX(10px);
      box-shadow: 0 4px 8px 0 rgba(75, 167, 32, 0.2), 0 6px 20px 0  rgba(75, 167, 32, 0.2);
      border-color: #6a9762;
      color: inherit;
      text-decoration: none;
    }
    
    .action-card:hover::after {
      opacity: 1;
      right: 20px;
    }
    
    .action-icon {
      font-size: 3rem;
      flex-shrink: 0;
    }
    
    .action-content {
      flex-grow: 1;
    }
    
    .action-title {
      font-size: 1.3rem;
      font-weight: bold;
      color: #2e3d2f;
      margin-bottom: 8px;
    }
    
    .action-desc {
      color: #666;
      font-size: 0.95rem;
      margin: 0;
    }
    
    .info-table {
      background: white;
      border: 3px;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    
    .info-table h5 {
      color: #2e3d2f;
      margin-bottom: 20px;
      font-weight: bold;
      padding-bottom: 10px;
      border-bottom: 2px solid #e9ecef;
    }
    
    .badge-role {
      padding: 5px 12px;
      border-radius: 15px;
      font-weight: 600;
      font-size: 0.85rem;
    }
    
    .badge-admin {
      background: linear-gradient(135deg, #dc3545, #e57373);
      color: white;
    }
    
    .badge-aluno {
      background: linear-gradient(135deg, #17a2b8, #48b9db);
      color: white;
    }
    
    .badge-vestibular {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
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
    
    .quick-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }
    
    .quick-stat {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .quick-stat-number {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .quick-stat-label {
      font-size: 0.9rem;
      opacity: 0.9;
    }
  </style>
</head>
<body>

<?php include("navbar_admin.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>🛡️ PAINEL ADMINISTRATIVO</h1>
</div>

<div class="container mt-4 mb-5">
  
  <!-- HEADER ADMIN -->
  <div class="admin-header">
    <h2 style="position: relative; z-index: 1;">Bem-vindo, Administrador <?= htmlspecialchars($_SESSION['nome']) ?>! 👋</h2>
    <p class="mb-0" style="position: relative; z-index: 1;">Gerencie o sistema Decola Militar de forma eficiente</p>
  </div>

  <!-- ESTATÍSTICAS RÁPIDAS -->
  <div class="quick-stats">
    <div class="quick-stat">
      <div class="quick-stat-number"><?= $total_respostas ?></div>
      <div class="quick-stat-label">Questões Respondidas</div>
    </div>
    <div class="quick-stat">
      <div class="quick-stat-number"><?= $total_simulados_realizados ?></div>
      <div class="quick-stat-label">Simulados Realizados</div>
    </div>
  </div>

  <!-- ESTATÍSTICAS GERAIS -->
  <div class="row mb-5">
    <div class="col-md-3 mb-3">
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?= $total_usuarios ?></div>
        <div class="stat-label">Usuários</div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="stat-card">
        <div class="stat-icon">📚</div>
        <div class="stat-number"><?= $total_questoes ?></div>
        <div class="stat-label">Questões</div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="stat-card">
        <div class="stat-icon">🎯</div>
        <div class="stat-number"><?= $total_simulados ?></div>
        <div class="stat-label">Simulados</div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="stat-card">
        <div class="stat-icon">🛡️</div>
        <div class="stat-number"><?= $total_admins ?></div>
        <div class="stat-label">Administradores</div>
      </div>
    </div>
  </div>

    <!-- AÇÕES RÁPIDAS -->
      <h3 class="mb-4" style="color: #2e3d2f; font-weight: bold;">AÇÕES NO SISTEMA</h3>
      
      <a href="admin_criar_simulado.php" class="action-card">
        <div class="action-icon">➕</div>
        <div class="action-content">
          <div class="action-title">Criar Simulado</div>
          <div class="action-desc">Crie um novo simulado com questões do banco e gere o PDF automaticamente</div>
        </div>
      </a>
      
      <a href="admin_gerenciar_simulado.php" class="action-card">
        <div class="action-icon">🔍</div>
        <div class="action-content">
        <div class="action-title">Gerenciar Simulados</div>
        <div class="action-desc">Gerencie os simulados que estão cadastrados no sistema</div>
      </div>
      </a>

      <a href="cadastrar_questao.php" class="action-card">
        <div class="action-icon">📝➕</div>
        <div class="action-content">
          <div class="action-title">Cadastrar Questão</div>
          <div class="action-desc">Adicione novas questões ao banco de dados do sistema</div>
        </div>
      </a>
      
      <a href="admin_gerenciar_questoes.php" class="action-card">
        <div class="action-icon">📚</div>
        <div class="action-content">
          <div class="action-title">Gerenciar Questões</div>
          <div class="action-desc">Visualize, edite e delete questões do banco</div>
        </div>
      </a>
      
      <a href="admin_cadastrar_usuario.php" class="action-card">
        <div class="action-icon">👥➕</div>
        <div class="action-content">
          <div class="action-title">Cadastrar Usuários</div>
          <div class="action-desc">Visualize, edite e promova usuários do sistema</div>
        </div>
      </a>

      <a href="admin_gerenciar_usuarios.php" class="action-card">
        <div class="action-icon">👥🔍</div>
        <div class="action-content">
          <div class="action-title">Gerenciar Usuários</div>
          <div class="action-desc">Visualize, edite e promova usuários do sistema</div>
        </div>
      </a>

        <a href="historico_atividades_admin.php" class="action-card">
        <div class="action-icon">🔐</div>
        <div class="action-content">
          <div class="action-title">Registro de Atividades</div>
          <div class="action-desc">Visualize as últimas atividades realizadas no sistema</div>
        </div>
      </a>
    </div>

<?php include("footer.php"); ?>
</body>
</html>