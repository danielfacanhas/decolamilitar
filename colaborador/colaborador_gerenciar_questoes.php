<?php
require_once '../config/verificar_colaborador.php';
include("../config/db_connect.php");

$mensagem = '';
$tipo_mensagem = '';

// DELETAR QUESTÃO
if (isset($_POST['deletar'])) {
  $questao_id = intval($_POST['questao_id']);
  
  $q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT imagem FROM questoes WHERE id = $questao_id"));
  if ($q && !empty($q['imagem']) && file_exists("uploads/" . $q['imagem'])) {
    unlink("uploads/" . $q['imagem']);
  }
  
  $sql = "DELETE FROM questoes WHERE id = $questao_id";
  if (mysqli_query($conn, $sql)) {
    $mensagem = "Questão deletada com sucesso!";
    $tipo_mensagem = "success";
    
    $admin_email = $_SESSION['email'];
    mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) VALUES ('$admin_email', 'Deletar Questão', 'ID: $questao_id')");
  } else {
    $mensagem = "Erro ao deletar: " . mysqli_error($conn);
    $tipo_mensagem = "danger";
  }
}

// FILTROS
$disciplina_filtro = $_GET['disciplina'] ?? '';
$conteudo_filtro = $_GET['conteudo'] ?? '';
$fonte_filtro = $_GET['fonte'] ?? '';
$tipo_filtro = $_GET['tipo'] ?? '';
$busca = $_GET['busca'] ?? '';

$where = [];
if (!empty($disciplina_filtro)) {
  $where[] = "disciplina = '".mysqli_real_escape_string($conn, $disciplina_filtro)."'";
}
if (!empty($conteudo_filtro)) {
  $where[] = "conteudo LIKE '%".mysqli_real_escape_string($conn, $conteudo_filtro)."%'";
}
if (!empty($fonte_filtro)) {
  $where[] = "fonte LIKE '%".mysqli_real_escape_string($conn, $fonte_filtro)."%'";
}
if (!empty($tipo_filtro)) {
  $where[] = "tipo_questao = '".mysqli_real_escape_string($conn, $tipo_filtro)."'";
}
if (!empty($busca)) {
  $busca_safe = mysqli_real_escape_string($conn, $busca);
  $where[] = "(enunciado LIKE '%$busca_safe%' OR alternativa_a LIKE '%$busca_safe%' OR alternativa_b LIKE '%$busca_safe%')";
}

$where_sql = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// PAGINAÇÃO
$questoes_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $questoes_por_pagina;

$sql_count = "SELECT COUNT(*) as total FROM questoes $where_sql";
$total_questoes = mysqli_fetch_assoc(mysqli_query($conn, $sql_count))['total'];
$total_paginas = ceil($total_questoes / $questoes_por_pagina);

$sql = "SELECT * FROM questoes $where_sql ORDER BY id DESC LIMIT $questoes_por_pagina OFFSET $offset";
$res_questoes = mysqli_query($conn, $sql);

// Disciplinas e estatísticas
$disciplinas_res = mysqli_query($conn, "SELECT DISTINCT disciplina FROM questoes ORDER BY disciplina ASC");
$disciplinas = [];
while($row = mysqli_fetch_assoc($disciplinas_res)) {
  $disciplinas[] = $row['disciplina'];
}

// Estatísticas rápidas
$stats = [
  'total' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM questoes"))['n'],
  'banco' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM questoes WHERE tipo_questao='banco'"))['n'],
  'simulado' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM questoes WHERE tipo_questao='simulado'"))['n'],
  'ambos' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM questoes WHERE tipo_questao='ambos'"))['n']
];

function construirUrl($params) {
  $filtros = [
    'disciplina' => $_GET['disciplina'] ?? '',
    'conteudo' => $_GET['conteudo'] ?? '',
    'fonte' => $_GET['fonte'] ?? '',
    'tipo' => $_GET['tipo'] ?? '',
    'busca' => $_GET['busca'] ?? ''
  ];
  return 'admin_gerenciar_questoes.php?' . http_build_query(array_merge($filtros, $params));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciar Questões - Admin</title>
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
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .stat-card.total {
      border-color: #495846;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }

    .stat-card.banco {
      border-color: #17a2b8;
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    }

    .stat-card.simulado {
      border-color: #ffc107;
      background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    }

    .stat-card.ambos {
      border-color: #28a745;
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
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
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      border: 3px;
    }

    .filtro-bar h5 {
      color: #2e3d2f;
      font-weight: bold;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #e9ecef;
    }

    /* Cards de Questões */
    .questao-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      border: 2px solid #e9ecef;
    }

    .questao-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(73, 88, 70, 0.2);
      border-color: #495846;
    }

    .questao-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e9ecef;
      flex-wrap: wrap;
      gap: 10px;
    }

    .questao-id {
      font-size: 1.3rem;
      font-weight: bold;
      color: #495846;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 8px 20px;
      border-radius: 25px;
    }

    .badges-container {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
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

    .badge-disciplina {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
    }

    .badge-conteudo {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
    }

    .badge-fonte {
      background: linear-gradient(135deg, #17a2b8, #48b9db);
      color: white;
    }

    .badge-tipo {
      background: linear-gradient(135deg, #ffc107, #ffca2c);
      color: #333;
    }

    .questao-preview {
      font-size: 0.95rem;
      color: #666;
      line-height: 1.6;
      margin: 15px 0;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 10px;
      border-left: 4px solid #495846;
    }

    .alternativas-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 10px;
      margin: 15px 0;
    }

    .alt-item {
      padding: 10px 15px;
      background: #f8f9fa;
      border-radius: 8px;
      border-left: 3px solid #6c757d;
      font-size: 0.9rem;
      transition: all 0.2s ease;
    }

    .alt-item:hover {
      background: #e9ecef;
    }

    .alt-item.correta {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      border-left-color: #28a745;
      font-weight: 600;
    }

    .info-extra {
      display: flex;
      gap: 15px;
      margin-top: 15px;
      font-size: 0.85rem;
      color: #666;
      flex-wrap: wrap;
    }

    .info-extra span {
      display: inline-flex;
      align-items: center;
      gap: 5px;
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

    .btn-deletar {
      background: linear-gradient(135deg, #dc3545, #e57373);
      color: white;
    }

    .btn-deletar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
      color: white;
    }

    .btn-voltar, .btn-nova {
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

    .btn-nova {
      background: linear-gradient(135deg, #28a745, #5cb85c);
      color: white;
    }

    .btn-nova:hover {
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
      .questao-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .alternativas-grid {
        grid-template-columns: 1fr;
      }

      .actions-container {
        justify-content: center;
      }
    }

    /* BOTÕES PADRONIZADOS */
.btn-admin {
  padding: 10px 22px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: none;
  transition: 0.25s ease;
}

/* EDITAR */
.btn-admin-editar {
  background: linear-gradient(135deg, #ffc107, #ffca2c);
  color: #333;
}

.btn-admin-editar:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
  color: #333;
}

/* DELETAR */
.btn-admin-deletar {
  background: linear-gradient(135deg, #dc3545, #e57373);
  color: white;
}

.btn-admin-deletar:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
  color: white;
}

/* MathJax Containers */
mjx-container {
  max-width: 100% !important;
  white-space: normal !important;
  overflow-x: hidden !important;
  overflow-wrap: break-word !important;
  word-break: break-word !important;
  text-align: justify !important;
}

mjx-container[display="block"] {
  width: 100% !important;
  display: block !important;
  text-align: justify !important;
}

mjx-container mjx-math {
  width: 100% !important;
  display: block !important;
  white-space: normal !important;
  text-align: justify !important;
}

mjx-container::after {
  content: "";
  display: inline-block;
  width: 100%;
}
</style>
<script>
  window.MathJax = {
    tex: {
      inlineMath: [['$', '$'], ['\\(', '\\)']],
      displayMath: [['$$','$$'], ['\\[','\\]']]
    },
    chtml: {
      scale: 1,
      minScale: 1,
      matchFontHeight: true,
      linebreaks: {
        automatic: true,
        width: "container"
      }
    },
    options: {
      enableMenu: false
    }
  };
</script>

<script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>

</head>
<body>

<?php include("navbar_colaborador.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>📚 GERENCIAR QUESTÕES</h1>
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
    <a href="painel_colaborador.php" class="btn-voltar">
      ← Voltar ao Painel
    </a>
    <a href="cadastrar_questao.php" class="btn-nova">
      ➕ Nova Questão
    </a>
  </div>

  <!-- ESTATÍSTICAS -->
  <div class="stats-grid">
    <div class="stat-card total">
      <div class="stat-icon">📊</div>
      <div class="stat-number"><?= $stats['total'] ?></div>
      <div class="stat-label">Total de Questões</div>
    </div>

    <div class="stat-card banco">
      <div class="stat-icon">📚</div>
      <div class="stat-number"><?= $stats['banco'] ?></div>
      <div class="stat-label">Banco de Questões</div>
    </div>

    <div class="stat-card simulado">
      <div class="stat-icon">📝</div>
      <div class="stat-number"><?= $stats['simulado'] ?></div>
      <div class="stat-label">Simulados</div>
    </div>

    <div class="stat-card ambos">
      <div class="stat-icon">🔄</div>
      <div class="stat-number"><?= $stats['ambos'] ?></div>
      <div class="stat-label">Ambos</div>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="filtro-bar">
    <h5>🔍 Filtros de Busca</h5>
    <form method="GET">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label fw-bold">Disciplina</label>
          <select name="disciplina" class="form-select">
            <option value="">Todas</option>
            <?php foreach($disciplinas as $d): ?>
              <option value="<?= htmlspecialchars($d) ?>" <?= $d == $disciplina_filtro ? 'selected' : '' ?>>
                <?= htmlspecialchars($d) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">Tipo</label>
          <select name="tipo" class="form-select">
            <option value="">Todos</option>
            <option value="banco" <?= $tipo_filtro == 'banco' ? 'selected' : '' ?>>Banco</option>
            <option value="simulado" <?= $tipo_filtro == 'simulado' ? 'selected' : '' ?>>Simulado</option>
            <option value="ambos" <?= $tipo_filtro == 'ambos' ? 'selected' : '' ?>>Ambos</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">Conteúdo</label>
          <input type="text" name="conteudo" class="form-control" 
                 value="<?= htmlspecialchars($conteudo_filtro) ?>" 
                 placeholder="Ex: Trigonometria">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">Fonte</label>
          <input type="text" name="fonte" class="form-control" 
                 value="<?= htmlspecialchars($fonte_filtro) ?>" 
                 placeholder="Ex: ITA">
        </div>

        <div class="col-md-3">
          <label class="form-label fw-bold">Buscar texto</label>
          <input type="text" name="busca" class="form-control" 
                 value="<?= htmlspecialchars($busca) ?>" 
                 placeholder="Palavra-chave">
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary flex-grow-1" 
                style="background: linear-gradient(135deg, #495846, #6a9762); border: none; border-radius: 10px; padding: 12px; font-weight: 600;">
          🔎 Buscar
        </button>
        <a href="admin_gerenciar_questoes.php" class="btn btn-secondary" style="border-radius: 10px; padding: 12px 20px;">
          🔄 Limpar
        </a>
      </div>
    </form>
  </div>

  <!-- LISTA DE QUESTÕES -->
  <?php if (mysqli_num_rows($res_questoes) > 0): ?>
    
    <?php while($q = mysqli_fetch_assoc($res_questoes)): ?>
      <div class="questao-card">
        
        <div class="questao-header">
          <span class="questao-id">ID: <?= $q['id'] ?></span>
          
          <div class="badges-container">
            <span class="badge-custom badge-disciplina">
              📖 <?= htmlspecialchars($q['disciplina']) ?>
            </span>
            <span class="badge-custom badge-conteudo">
              🎯 <?= htmlspecialchars($q['conteudo']) ?>
            </span>
            <?php if (!empty($q['fonte'])): ?>
              <span class="badge-custom badge-fonte">
                📄 <?= htmlspecialchars($q['fonte']) ?>
              </span>
            <?php endif; ?>
            <?php 
            $tipo = $q['tipo_questao'] ?? 'banco';
            $tipo_icon = ['banco' => '📚', 'simulado' => '📝', 'ambos' => '🔄'];
            ?>
            <span class="badge-custom badge-tipo">
              <?= $tipo_icon[$tipo] ?? '📚' ?> <?= ucfirst($tipo) ?>
            </span>
          </div>
        </div>

        <div class="questao-preview">
          <strong>Enunciado:</strong> <br>
          <?= $q['enunciado'] ?>
        </div>

        <div class="info-extra">
          <span>
            <strong>Nível:</strong> <?= htmlspecialchars($q['nivel_dificuldade']) ?>
          </span>
          <span>
            <strong>Resposta:</strong> <?= $q['resposta_correta'] ?>
          </span>
          <?php if (!empty($q['imagem'])): ?>
            <span>🖼️ <strong>Com imagem</strong></span>
          <?php endif; ?>
        </div>

        <div class="alternativas-grid">
          <div class="alt-item <?= $q['resposta_correta'] == 'A' ? 'correta' : '' ?>">
            <strong>A)</strong> <?= substr(htmlspecialchars($q['alternativa_a']), 0, 60) ?>...
          </div>
          <div class="alt-item <?= $q['resposta_correta'] == 'B' ? 'correta' : '' ?>">
            <strong>B)</strong> <?= substr(htmlspecialchars($q['alternativa_b']), 0, 60) ?>...
          </div>
          <div class="alt-item <?= $q['resposta_correta'] == 'C' ? 'correta' : '' ?>">
            <strong>C)</strong> <?= substr(htmlspecialchars($q['alternativa_c']), 0, 60) ?>...
          </div>
          <div class="alt-item <?= $q['resposta_correta'] == 'D' ? 'correta' : '' ?>">
            <strong>D)</strong> <?= substr(htmlspecialchars($q['alternativa_d']), 0, 60) ?>...
          </div>
          <?php if (!empty($q['alternativa_e'])): ?>
            <div class="alt-item <?= $q['resposta_correta'] == 'E' ? 'correta' : '' ?>">
              <strong>E)</strong> <?= substr(htmlspecialchars($q['alternativa_e']), 0, 60) ?>...
            </div>
          <?php endif; ?>
        </div>
          
        <div class="actions-container">
          <a href="colaborador_editar_questao.php?id=<?= $q['id'] ?>" class="btn-action btn-editar">
            ✏️ Editar
          </a>
        </form>

          <form method="POST" class="d-inline">
            <input type="hidden" name="questao_id" value="<?= $q['id'] ?>">
            <button type="submit" name="deletar" class="btn-action btn-deletar"
                    onclick="return confirm('⚠️ ATENÇÃO!\n\nDeletar a questão ID: <?= $q['id'] ?>?\n\nEsta ação não pode ser desfeita e a questão será removida de todos os simulados!')">
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
      <h4 class="text-muted">Nenhuma questão encontrada</h4>
      <p>Tente ajustar os filtros ou <a href="cadastrar_questao.php">cadastre uma nova questão</a></p>
    </div>
  <?php endif; ?>

</div>

<?php include("footer.php"); ?>
</body>
</html>