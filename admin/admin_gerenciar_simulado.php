<?php
require_once '../config/verificar_admin.php';
include("../config/db_connect.php");

$mensagem = '';
$tipo_mensagem = '';

// DELETAR SIMULADO
if (isset($_POST['deletar'])) {
  $simulado_id = intval($_POST['simulado_id']);
  
  $sql = "DELETE FROM simulados WHERE id = $simulado_id";
  if (mysqli_query($conn, $sql)) {
    $mensagem = "Simulado deletado com sucesso!";
    $tipo_mensagem = "success";
    
    $admin_email = $_SESSION['email'];
    mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) VALUES ('$admin_email', 'Deletar Simulado', 'ID: $simulado_id')");
  } else {
    $mensagem = "Erro ao deletar: " . mysqli_error($conn);
    $tipo_mensagem = "danger";
  }
}

// ATIVAR/DESATIVAR SIMULADO
if (isset($_POST['toggle_ativo'])) {
  $simulado_id = intval($_POST['simulado_id']);
  $novo_status = intval($_POST['novo_status']);
  
  $sql = "UPDATE simulados SET ativo = $novo_status WHERE id = $simulado_id";
  if (mysqli_query($conn, $sql)) {
    $status_texto = $novo_status ? "ativado" : "desativado";
    $mensagem = "Simulado $status_texto com sucesso!";
    $tipo_mensagem = "success";
  }
}

// FILTROS
$vestibular_filtro = $_GET['vestibular'] ?? '';
$status_filtro = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';

$where = [];
if (!empty($vestibular_filtro)) {
  $where[] = "vestibular = '".mysqli_real_escape_string($conn, $vestibular_filtro)."'";
}
if ($status_filtro !== '') {
  $where[] = "ativo = ".intval($status_filtro);
}
if (!empty($busca)) {
  $busca_safe = mysqli_real_escape_string($conn, $busca);
  $where[] = "(titulo LIKE '%$busca_safe%' OR descricao LIKE '%$busca_safe%')";
}

$where_sql = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// PAGINAÇÃO
$simulados_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $simulados_por_pagina;

$sql_count = "SELECT COUNT(*) as total FROM simulados $where_sql";
$total_simulados = mysqli_fetch_assoc(mysqli_query($conn, $sql_count))['total'];
$total_paginas = ceil($total_simulados / $simulados_por_pagina);

$sql = "SELECT s.*, 
        (SELECT COUNT(*) FROM respostas_simulado WHERE simulado_id = s.id) as total_realizacoes,
        (SELECT COUNT(*) FROM questoes_simulado WHERE simulado_id = s.id) as total_questoes_real
        FROM simulados s 
        $where_sql 
        ORDER BY s.id DESC 
        LIMIT $simulados_por_pagina OFFSET $offset";
$res_simulados = mysqli_query($conn, $sql);

// Vestibulares disponíveis
$vestibulares_res = mysqli_query($conn, "SELECT DISTINCT vestibular FROM simulados WHERE vestibular IS NOT NULL AND vestibular != '' ORDER BY vestibular ASC");
$vestibulares = [];
while($row = mysqli_fetch_assoc($vestibulares_res)) {
  $vestibulares[] = $row['vestibular'];
}

// ESTATÍSTICAS
$stats = [
  'total' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM simulados"))['n'],
  'ativos' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM simulados WHERE ativo=1"))['n'],
  'inativos' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM simulados WHERE ativo=0"))['n'],
  'realizacoes' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM respostas_simulado"))['n']
];

function construirUrl($params) {
  $filtros = [
    'vestibular' => $_GET['vestibular'] ?? '',
    'status' => $_GET['status'] ?? '',
    'busca' => $_GET['busca'] ?? ''
  ];
  return 'admin_gerenciar_simulado.php?' . http_build_query(array_merge($filtros, $params));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciar Simulados - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    /* Cards de Estatísticas */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: all 0.3s ease;
      border: 3px solid transparent;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
    }

    .stat-card.total {
      border-color: #495846;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }

    .stat-card.ativos {
      border-color: #28a745;
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
    }

    .stat-card.inativos {
      border-color: #6c757d;
      background: linear-gradient(135deg, #e2e3e5, #d6d8db);
    }

    .stat-card.realizacoes {
      border-color: #17a2b8;
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: bold;
      color: #495846;
      margin: 10px 0;
    }

    .stat-label {
      font-size: 0.9rem;
      color: #666;
      font-weight: 600;
    }

    .stat-icon {
      font-size: 2rem;
      margin-bottom: 5px;
    }

    /* Filtros */
    .filtro-bar {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.08), 0 6px 20px 0 rgba(0, 0, 0, 0.07);
      border: 3px;
      font-family: "Montserrat", sans-serif;
    }

    .filtro-bar h5 {
      color: #2e3d2f;
      font-weight: bold;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #e9ecef;
    }

    /* Cards de Simulados */
    .simulado-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      border: 2px solid #e9ecef;
    }

    .simulado-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 8px 0 rgba(68, 207, 40, 0.2) 0 6px 20px 0 rgba(50, 184, 38, 0.8);
    }

    .simulado-card.inativo {
      opacity: 0.7;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }

    .simulado-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e9ecef;
      flex-wrap: wrap;
      gap: 10px;
    }

    .simulado-id {
      font-size: 1.3rem;
      font-weight: bold;
      color: #495846;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 8px 20px;
      border-radius: 25px;
    }

    .logo-vestibular {
      width: 50px;
      height: 50px;
      object-fit: contain;
      border-radius: 8px;
      padding: 5px;
      background: white;
      border: 2px solid #e9ecef;
    }

    .simulado-titulo {
      font-size: 1.4rem;
      font-weight: 700;
      color: #2e3d2f;
      margin: 15px 0 10px 0;
    }

    .simulado-descricao {
      color: #666;
      line-height: 1.6;
      margin-bottom: 15px;
    }

    .badges-container {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin: 15px 0;
    }

    .badge-custom {
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .badge-vestibular {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
    }

    .badge-duracao {
      background: linear-gradient(135deg, #17a2b8, #48b9db);
      color: white;
    }

    .badge-questoes {
      background: linear-gradient(135deg, #ffc107, #ffca2c);
      color: #333;
    }

    .badge-realizacoes {
      background: linear-gradient(135deg, #28a745, #5cb85c);
      color: white;
    }

    .badge-status {
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .badge-ativo {
      background: linear-gradient(135deg, #28a745, #5cb85c);
      color: white;
    }

    .badge-inativo {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 10px;
      margin: 15px 0;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 10px;
    }

    .info-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .info-label {
      font-size: 0.85rem;
      color: #666;
      font-weight: 600;
    }

    .info-value {
      font-size: 1.1rem;
      font-weight: bold;
      color: #495846;
    }

    /* Botões de Ação */
    .actions-container {
      display: flex;
      gap: 10px;
      margin-top: 15px;
      padding-top: 15px;
      border-top: 2px solid #e9ecef;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    .btn-action {
      padding: 10px 20px;
      border-radius: 25px;
      font-weight: 600;
      font-size: 0.9rem;
      border: none;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-editar {
      background: linear-gradient(135deg, #ffc107, #ffca2c);
      color: #333;
    }

    .btn-editar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
      color: #333;
    }

    .btn-toggle {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
    }

    .btn-toggle:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
      color: white;
    }

    .btn-toggle.ativar {
      background: linear-gradient(135deg, #28a745, #5cb85c);
    }

    .btn-deletar {
      background: linear-gradient(135deg, #dc3545, #e57373);
      color: white;
    }

    .btn-deletar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
      color: white;
    }

    .btn-pdf {
      background: linear-gradient(135deg, #e74c3c, #c0392b);
      color: white;
    }

    .btn-pdf:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
      color: white;
    }

    .btn-voltar, .btn-novo {
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

    .btn-voltar {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
    }

    .btn-voltar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
      color: white;
    }

    .btn-novo {
      background: linear-gradient(135deg, #28a745, #5cb85c);
      color: white;
    }

    .btn-novo:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
      color: white;
    }

    /* Paginação */
    .pagination-custom {
      display: flex;
      gap: 8px;
      justify-content: center;
      flex-wrap: wrap;
      margin: 30px 0;
    }

    .page-btn {
      background: white;
      border: 2px solid #495846;
      color: #495846;
      padding: 10px 18px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      min-width: 45px;
      text-align: center;
    }

    .page-btn:hover {
      background: #495846;
      color: white;
      transform: translateY(-2px);
    }

    .page-btn.active {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      border-color: #495846;
    }

    .page-btn.disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
    }

    .top-actions {
      display: flex;
      gap: 12px;
      margin-bottom: 25px;
      flex-wrap: wrap;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .empty-state-icon {
      font-size: 4rem;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    @media (max-width: 768px) {
      .simulado-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .actions-container {
        justify-content: center;
      }

      .info-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<?php include("navbar_admin.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>GERENCIAR SIMULADOS</h1>
</div>

<div class="container mt-4 mb-5">
  
  <?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
      <?= $mensagem ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- BOTÕES SUPERIORES -->
  <div class="top-actions">
    <a href="painel_admin.php" class="btn-voltar">
      ← Voltar ao Painel
    </a>
    <a href="admin_criar_simulado.php" class="btn-novo">
      ➕ Criar Novo Simulado
    </a>
  </div>

  <!-- ESTATÍSTICAS -->
  <div class="stats-grid">
    <div class="stat-card total">
      <div class="stat-icon">📊</div>
      <div class="stat-number"><?= $stats['total'] ?></div>
      <div class="stat-label">Total de Simulados</div>
    </div>

    <div class="stat-card ativos">
      <div class="stat-icon">✅</div>
      <div class="stat-number"><?= $stats['ativos'] ?></div>
      <div class="stat-label">Simulados Ativos</div>
    </div>

    <div class="stat-card inativos">
      <div class="stat-icon">⏸️</div>
      <div class="stat-number"><?= $stats['inativos'] ?></div>
      <div class="stat-label">Simulados Inativos</div>
    </div>

    <div class="stat-card realizacoes">
      <div class="stat-icon">👥</div>
      <div class="stat-number"><?= $stats['realizacoes'] ?></div>
      <div class="stat-label">Total de Realizações</div>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="filtro-bar">
    <h5>🔍 Filtros de Busca</h5>
    <form method="GET">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label fw-bold">Vestibular</label>
          <select name="vestibular" class="form-select">
            <option value="">Todos</option>
            <?php foreach($vestibulares as $v): ?>
              <option value="<?= htmlspecialchars($v) ?>" <?= $v == $vestibular_filtro ? 'selected' : '' ?>>
                <?= htmlspecialchars($v) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">Status</label>
          <select name="status" class="form-select">
            <option value="">Todos</option>
            <option value="1" <?= $status_filtro === '1' ? 'selected' : '' ?>>Ativos</option>
            <option value="0" <?= $status_filtro === '0' ? 'selected' : '' ?>>Inativos</option>
          </select>
        </div>

        <div class="col-md-5">
          <label class="form-label fw-bold">Buscar</label>
          <input type="text" name="busca" class="form-control" 
                 value="<?= htmlspecialchars($busca) ?>" 
                 placeholder="Título ou descrição...">
        </div>

        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100" 
                  style="background: linear-gradient(135deg, #495846, #6a9762); border: none; border-radius: 10px; padding: 12px; font-weight: 600;">
            🔎 Buscar
          </button>
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <a href="admin_gerenciar_simulado.php" class="btn btn-secondary" style="border-radius: 10px; padding: 12px 20px;">
          🔄 Limpar Filtros
        </a>
      </div>
    </form>
  </div>

  <!-- LISTA DE SIMULADOS -->
  <?php if (mysqli_num_rows($res_simulados) > 0): ?>
    
    <?php while($sim = mysqli_fetch_assoc($res_simulados)): ?>
      <div class="simulado-card <?= $sim['ativo'] ? '' : 'inativo' ?>">
        
        <div class="simulado-header">
          <div class="d-flex align-items-center gap-3">
            <span class="simulado-id">ID: <?= $sim['id'] ?></span>
            
            <?php if (!empty($sim['logo_vestibular']) && file_exists("./imgs/" . $sim['logo_vestibular'])): ?>
              <img src="./imgs/<?= htmlspecialchars($sim['logo_vestibular']) ?>" 
                   alt="<?= htmlspecialchars($sim['vestibular']) ?>" 
                   class="logo-vestibular">
            <?php endif; ?>
          </div>
          
          <span class="badge-status <?= $sim['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>">
            <?= $sim['ativo'] ? '✅ Ativo' : '⏸️ Inativo' ?>
          </span>
        </div>

        <h3 class="simulado-titulo"><?= htmlspecialchars($sim['titulo']) ?></h3>
        
        <p class="simulado-descricao">
          <?= htmlspecialchars($sim['descricao']) ?>
        </p>

        <div class="badges-container">
          <?php if (!empty($sim['vestibular'])): ?>
            <span class="badge-custom badge-vestibular">
              🎓 <?= htmlspecialchars($sim['vestibular']) ?>
            </span>
          <?php endif; ?>
          
          <span class="badge-custom badge-duracao">
            ⏱️ <?= $sim['duracao_minutos'] ?> minutos
          </span>
          
          <span class="badge-custom badge-questoes">
            📝 <?= $sim['total_questoes_real'] ?> questões
          </span>
          
          <span class="badge-custom badge-realizacoes">
            👥 <?= $sim['total_realizacoes'] ?> realizações
          </span>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Data de Criação</span>
            <span class="info-value">
              <?= date('d/m/Y', strtotime($sim['data_criacao'])) ?>
            </span>
          </div>
          
          <div class="info-item">
            <span class="info-label">Hora de Criação</span>
            <span class="info-value">
              <?= date('H:i', strtotime($sim['data_criacao'])) ?>
            </span>
          </div>
          
          <?php if (!empty($sim['arquivo_pdf'])): ?>
            <div class="info-item">
              <span class="info-label">PDF</span>
              <span class="info-value">✅ Disponível</span>
            </div>
          <?php endif; ?>
        </div>

        <div class="actions-container">


<!-- BOTÃO EDITAR -->
<form action="admin_editar_simulado.php" method="GET" class="d-inline">
    <input type="hidden" name="id" value="<?= $sim['id'] ?>">
    <button type="submit" class="btn-action btn-editar">
        ✏️ Editar
    </button>
</form>
          <form method="POST" class="d-inline">
            <input type="hidden" name="simulado_id" value="<?= $sim['id'] ?>">
            <input type="hidden" name="novo_status" value="<?= $sim['ativo'] ? 0 : 1 ?>">
            <button type="submit" name="toggle_ativo" 
                    class="btn-action btn-toggle <?= $sim['ativo'] ? '' : 'ativar' ?>"
                    onclick="return confirm('<?= $sim['ativo'] ? 'Desativar' : 'Ativar' ?> este simulado?')">
              <?= $sim['ativo'] ? '⏸️ Desativar' : '▶️ Ativar' ?>
            </button>
          </form>
          
          <form method="POST" class="d-inline">
            <input type="hidden" name="simulado_id" value="<?= $sim['id'] ?>">
            <button type="submit" name="deletar" class="btn-action btn-deletar"
                    onclick="return confirm('⚠️ ATENÇÃO!\n\nDeletar o simulado: <?= htmlspecialchars($sim['titulo']) ?>?\n\nEsta ação não pode ser desfeita e todos os dados relacionados serão perdidos!')">
              🗑️ Deletar
            </button>
          </form>
        </div>

      </div>
    <?php endwhile; ?>

    <!-- PAGINAÇÃO -->
    <?php if ($total_paginas > 1): ?>
      <div class="pagination-custom">
        <?php if ($pagina_atual > 1): ?>
          <a href="<?= construirUrl(['pagina' => $pagina_atual - 1]) ?>" class="page-btn">
            ← Anterior
          </a>
        <?php else: ?>
          <span class="page-btn disabled">← Anterior</span>
        <?php endif; ?>

        <?php 
        $inicio = max(1, $pagina_atual - 2);
        $fim = min($total_paginas, $pagina_atual + 2);
        
        if ($inicio > 1): ?>
          <a href="<?= construirUrl(['pagina' => 1]) ?>" class="page-btn">1</a>
          <?php if ($inicio > 2): ?>
            <span class="page-btn disabled">...</span>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fim; $i++): ?>
          <a href="<?= construirUrl(['pagina' => $i]) ?>" 
             class="page-btn <?= $i == $pagina_atual ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($fim < $total_paginas): ?>
          <?php if ($fim < $total_paginas - 1): ?>
            <span class="page-btn disabled">...</span>
          <?php endif; ?>
          <a href="<?= construirUrl(['pagina' => $total_paginas]) ?>" class="page-btn">
            <?= $total_paginas ?>
          </a>
        <?php endif; ?>

        <?php if ($pagina_atual < $total_paginas): ?>
          <a href="<?= construirUrl(['pagina' => $pagina_atual + 1]) ?>" class="page-btn">
            Próximo →
          </a>
        <?php else: ?>
          <span class="page-btn disabled">Próximo →</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon">🔭</div>
      <h4 class="text-muted">Nenhum simulado encontrado</h4>
      <p>Tente ajustar os filtros ou <a href="admin_criar_simulado.php">crie um novo simulado</a></p>
    </div>
  <?php endif; ?>

</div>

<?php include("footer.php"); ?>
</body>
</html>