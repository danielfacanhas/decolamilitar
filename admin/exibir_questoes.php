<?php
session_start();
if (!isset($_SESSION['nome'])) {
  header("Location: login.php");
  exit;
}

require_once '../config/verificar_admin.php';
include("../config/db_connect.php");
// Recebe os filtros da URL
$disciplina = $_GET['disciplina'] ?? '';
$conteudo = $_GET['conteudo'] ?? '';
$fonte = $_GET['fonte'] ?? '';

// PAGINAÇÃO
$questoes_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $questoes_por_pagina;

// Monta query dinâmica - FILTRAR APENAS QUESTÕES DO BANCO
$where = ["(tipo_questao = 'banco' OR tipo_questao = 'ambos' OR tipo_questao IS NULL)"];
if (!empty($disciplina)) {
    $where[] = "disciplina = '".mysqli_real_escape_string($conn, $disciplina)."'";
}
if (!empty($conteudo)) {
    $where[] = "conteudo LIKE '%".mysqli_real_escape_string($conn, $conteudo)."%'";
}
if (!empty($fonte)) {
    $where[] = "fonte LIKE '%".mysqli_real_escape_string($conn, $fonte)."%'";
}

// SQL base
$sql_base = "SELECT * FROM questoes";
if (count($where) > 0) {
    $sql_base .= " WHERE " . implode(" AND ", $where);
}

// Conta total de questões
$sql_count = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql_base);
$res_count = mysqli_query($conn, $sql_count);
$total_questoes = mysqli_fetch_assoc($res_count)['total'];
$total_paginas = ceil($total_questoes / $questoes_por_pagina);

// Query com limite e offset
$sql = $sql_base . " ORDER BY id ASC LIMIT $questoes_por_pagina OFFSET $offset";
$resultado = mysqli_query($conn, $sql);

// Pega todas disciplinas para o select
$disciplinas_res = mysqli_query($conn, "SELECT DISTINCT disciplina FROM questoes ORDER BY disciplina ASC");
$disciplinas = [];
while($row = mysqli_fetch_assoc($disciplinas_res)) {
    $disciplinas[] = $row['disciplina'];
}

// Função para construir URL com filtros
function construirUrl($params) {
    $filtros = [
        'disciplina' => $_GET['disciplina'] ?? '',
        'conteudo' => $_GET['conteudo'] ?? '',
        'fonte' => $_GET['fonte'] ?? ''
    ];
    return 'exibir_questoes.php?' . http_build_query(array_merge($filtros, $params));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Questões Filtradas - Decola Militar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">
  <link rel="stylesheet" href="../stylesheet/global.css">


  <style>
    .questao-card {
      background: white;
      border: 10px;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      margin-bottom: 20px;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .questao-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 12px rgba(0,0,0,0.15);
      border-color: #6a9762;
    }

    .questao-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e9ecef;
    }

    .questao-numero {
      font-size: 1.5rem;
      font-weight: bold;
      color: #495846;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 8px 20px;
      border-radius: 25px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .questao-info {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .badge-custom {
      padding: 8px 15px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
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

    /* ============================================
       SOLUÇÃO DEFINITIVA PARA QUEBRA DE LINHA
       ============================================ */
    
    .questao-enunciado {
      font-size: 1.05rem;
      line-height: 1.8;
      color: #333;
      margin-bottom: 20px;
      
      /* Quebra de palavras */
      word-wrap: break-word;
      overflow-wrap: break-word;
      word-break: break-word;
      
      /* Hifenização */
      -webkit-hyphens: auto;
      -moz-hyphens: auto;
      hyphens: auto;
      
      /* Impedir overflow horizontal */
      overflow-x: hidden;
      max-width: 100%;
      width: 100%;
    }
    
    /* Forçar todos os elementos MathJax a respeitar o container */
    .questao-enunciado * {
      max-width: 100% !important;
      box-sizing: border-box !important;
    }
    
    /* Containers MathJax */
    .questao-enunciado mjx-container {
      display: inline-block !important;
      max-width: 100% !important;
      overflow-x: auto !important;
      overflow-y: hidden !important;
      margin: 5px 0 !important;
    }
    
    .questao-enunciado mjx-container[display="block"] {
      display: block !important;
      max-width: 100% !important;
      overflow-x: auto !important;
    }
    
    /* Elementos internos do MathJax */
    .questao-enunciado mjx-math,
    .questao-enunciado mjx-mrow,
    .questao-enunciado mjx-semantics {
      max-width: 100% !important;
    }
    
    /* SVG do MathJax */
    .questao-enunciado svg {
      max-width: 100% !important;
      height: auto !important;
    }
    
    /* Se ainda assim houver overflow, adicionar scroll suave */
    .questao-enunciado mjx-container::-webkit-scrollbar {
      height: 6px;
    }
    
    .questao-enunciado mjx-container::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }
    
    .questao-enunciado mjx-container::-webkit-scrollbar-thumb {
      background: #495846;
      border-radius: 3px;
    }
    
    .questao-enunciado mjx-container::-webkit-scrollbar-thumb:hover {
      background: #6a9762;
    }

    .questao-imagem {
      text-align: center;
      margin: 20px 0;
    }

    .questao-imagem img {
      max-width: 100%;
      max-height: 400px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-resolver {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      border: none;
      padding: 12px 30px;
      border-radius: 25px;
      font-weight: 600;
      transition: all 0.3s ease;
      width: 100%;
      margin-top: 15px;
    }

    .btn-resolver:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(73, 88, 70, 0.3);
      color: white;
    }

    .filtros-aplicados {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border: 2px solid #495846;
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
    }

    .filtros-aplicados h5 {
      color: #495846;
      margin-bottom: 15px;
      font-weight: bold;
    }

    .filtro-tag {
      display: inline-block;
      background: white;
      border: 2px solid #495846;
      padding: 8px 15px;
      border-radius: 20px;
      margin: 5px;
      font-weight: 600;
    }

    .filtro-tag .remove {
      color: #dc3545;
      margin-left: 8px;
      cursor: pointer;
      font-weight: bold;
      text-decoration: none;
    }

    .stats-bar {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 15px 25px;
      border-radius: 10px;
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      flex-wrap: wrap;
    }

    .stats-item {
      text-align: center;
      margin: 5px;
    }

    .stats-number {
      font-size: 2rem;
      font-weight: bold;
    }

    .stats-label {
      font-size: 0.9rem;
      opacity: 0.9;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }

    .empty-state .icon {
      font-size: 5rem;
      margin-bottom: 20px;
    }

    /* PAGINAÇÃO */
    .pagination-container {
      display: flex;
      justify-content: center;
      margin: 40px 0;
    }

    .pagination-custom {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: center;
    }

    .page-btn {
      background: white;
      border: 2px;
      color: #495846;
      padding: 10px 18px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);

    }

    .page-btn:hover {
      background: #495846;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(73, 88, 70, 0.3);
    }

    .page-btn.active {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
    }

    .page-btn.disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
    }

    .btn-custom {
      border-radius: 30px;
      padding: 12px 25px;
      min-width: 180px;
      font-weight: 600;
      transition: 0.3s;
    }

    .btn-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .btn-voltar {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
      border: none;
    }

    .btn-voltar:hover {
      color: white;
      box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
      .questao-enunciado {
        font-size: 0.95rem;
        line-height: 1.6;
      }
      
      .questao-card {
        padding: 15px;
      }
    }

    
mjx-container {
  max-width: 100% !important;
  white-space: normal !important;
  overflow-x: hidden !important;
  overflow-wrap: break-word !important;
  word-break: break-word !important;

  /* JUSTIFICAR TEXTO DO LATEX */
  text-align: justify !important;
}

mjx-container[display="block"] {
  width: 100% !important;
  display: block !important;

  /* Garantir que blocos também fiquem justificados */
  text-align: justify !important;
}

mjx-container mjx-math {
  width: 100% !important;
  display: block !important;
  white-space: normal !important;

  /* Justificar conteúdo interno de fórmulas */
  text-align: justify !important;
}

/* Truque ESSENCIAL para justificar corretamente o último trecho */
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

<?php include("navbar_admin.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>QUESTOES FILTRADAS</h1>
</div>

<div class="container mt-4 mb-5">
  <a href="painel_admin.php" class="btn btn-voltar btn-custom mb-3">
    ← Voltar ao Painel do Administrador
  </a>

  <?php if (!empty($disciplina) || !empty($conteudo) || !empty($fonte)): ?>
  <!-- FILTROS APLICADOS -->
  <div class="filtros-aplicados">
    <h5>🔍 Filtros Aplicados:</h5>
    <?php if (!empty($disciplina)): ?>
      <span class="filtro-tag">
        Disciplina: <?= htmlspecialchars($disciplina) ?>
        <a href="?conteudo=<?= urlencode($conteudo) ?>&fonte=<?= urlencode($fonte) ?>" class="remove">×</a>
      </span>
    <?php endif; ?>
    
    <?php if (!empty($conteudo)): ?>
      <span class="filtro-tag">
        Conteúdo: <?= htmlspecialchars($conteudo) ?>
        <a href="?disciplina=<?= urlencode($disciplina) ?>&fonte=<?= urlencode($fonte) ?>" class="remove">×</a>
      </span>
    <?php endif; ?>
    
    <?php if (!empty($fonte)): ?>
      <span class="filtro-tag">
        Fonte: <?= htmlspecialchars($fonte) ?>
        <a href="?disciplina=<?= urlencode($disciplina) ?>&conteudo=<?= urlencode($conteudo) ?>" class="remove">×</a>
      </span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ESTATÍSTICAS -->
  <?php if ($total_questoes > 0): ?>
  <div class="stats-bar">
    <div class="stats-item">
      <div class="stats-number"><?= $total_questoes ?></div>
      <div class="stats-label">Questões Encontradas</div>
    </div>
    <div class="stats-item">
      <div class="stats-number">📄 <?= $pagina_atual ?>/<?= $total_paginas ?></div>
      <div class="stats-label">Página Atual</div>
    </div>
    <div class="stats-item">
      <div class="stats-number">📚</div>
      <div class="stats-label">Prontas para Resolver</div>
    </div>
  </div>

  <!-- LISTA DE QUESTÕES -->
  <?php 
  $contador = $offset + 1;
  while($questao = mysqli_fetch_assoc($resultado)): 
  ?>
  
  <div class="questao-card">
    <!-- HEADER -->
    <div class="questao-header">
      <div class="questao-numero">
        Questão #<?= $contador ?>
      </div>
      <div class="questao-info">
        <span class="badge-custom badge-disciplina">
          📖 <?= htmlspecialchars($questao['disciplina']) ?>
        </span>
        <span class="badge-custom badge-conteudo">
          🎯 <?= htmlspecialchars($questao['conteudo']) ?>
        </span>
        <?php if (!empty($questao['fonte'])): ?>
          <span class="badge-custom badge-fonte">
            📝 <?= htmlspecialchars($questao['fonte']) ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- ENUNCIADO -->
    <div class="questao-enunciado">
      <?= nl2br($questao['enunciado']) ?>
    </div>

    <!-- IMAGEM (se existir) -->
    <?php if (!empty($questao['imagem'])): ?>
    <div class="questao-imagem">
      <img src="../uploads/<?= htmlspecialchars($questao['imagem']) ?>" 
           alt="Imagem da questão">
    </div>
    <?php endif; ?>

    <!-- BOTÃO RESOLVER COM FILTROS -->
    <?php
    $params_filtro = http_build_query([
      'id' => $questao['id'],
      'disciplina' => $disciplina,
      'conteudo' => $conteudo,
      'fonte' => $fonte
    ]);
    ?>
    <a href="responder_questao.php?<?= $params_filtro ?>" class="btn btn-resolver">
      ✏️ Resolver Esta Questão
    </a>
  </div>

  <?php 
  $contador++;
  endwhile; 
  ?>

  <!-- PAGINAÇÃO -->
  <?php if ($total_paginas > 1): ?>
  <div class="pagination-container">
    <div class="pagination-custom">
      
      <!-- Botão Anterior -->
      <?php if ($pagina_atual > 1): ?>
        <a href="<?= construirUrl(['pagina' => $pagina_atual - 1]) ?>" class="page-btn">
          ← Anterior
        </a>
      <?php else: ?>
        <span class="page-btn disabled">← Anterior</span>
      <?php endif; ?>

      <!-- Primeiras páginas -->
      <?php for ($i = 1; $i <= min(3, $total_paginas); $i++): ?>
        <a href="<?= construirUrl(['pagina' => $i]) ?>" 
           class="page-btn <?= $i == $pagina_atual ? 'active' : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <!-- Reticências iniciais -->
      <?php if ($pagina_atual > 5): ?>
        <span class="page-btn disabled">...</span>
      <?php endif; ?>

      <!-- Páginas ao redor da atual -->
      <?php 
      $inicio = max(4, $pagina_atual - 1);
      $fim = min($total_paginas - 3, $pagina_atual + 1);
      for ($i = $inicio; $i <= $fim; $i++): 
        if ($i > 3 && $i < $total_paginas - 2):
      ?>
        <a href="<?= construirUrl(['pagina' => $i]) ?>" 
           class="page-btn <?= $i == $pagina_atual ? 'active' : '' ?>">
          <?= $i ?>
        </a>
      <?php 
        endif;
      endfor; 
      ?>

      <!-- Reticências finais -->
      <?php if ($pagina_atual < $total_paginas - 4): ?>
        <span class="page-btn disabled">...</span>
      <?php endif; ?>

      <!-- Últimas páginas -->
      <?php for ($i = max(4, $total_paginas - 2); $i <= $total_paginas; $i++): ?>
        <a href="<?= construirUrl(['pagina' => $i]) ?>" 
           class="page-btn <?= $i == $pagina_atual ? 'active' : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <!-- Botão Próximo -->
      <?php if ($pagina_atual < $total_paginas): ?>
        <a href="<?= construirUrl(['pagina' => $pagina_atual + 1]) ?>" class="page-btn">
          Próximo →
        </a>
      <?php else: ?>
        <span class="page-btn disabled">Próximo →</span>
      <?php endif; ?>

    </div>
  </div>
  <?php endif; ?>

  <?php else: ?>
  
  <!-- ESTADO VAZIO -->
  <div class="empty-state">
    <div class="icon">🔍</div>
    <h3>Nenhuma questão encontrada</h3>
    <p class="text-muted">Tente ajustar os filtros ou buscar por outros termos.</p>
    <a href="painel_admin.php" class="btn btn-primary mt-3" 
       style="background-color: #495846;">
      ← Voltar ao Painel do Administrador
    </a>
  </div>

  <?php endif; ?>

</div>

<?php include("footer.php"); ?>
</body>
</html>