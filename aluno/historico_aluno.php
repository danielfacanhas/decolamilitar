<?php
session_start();
if (!isset($_SESSION['nome'])) {
    header("Location: ../login.php");
    exit;
}

include("../config/db_connect.php");

$email_usuario = $_SESSION['email'];
$nome_usuario = $_SESSION['nome'];

// Configuração de paginação
$questoes_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $questoes_por_pagina;

// Filtros
$filtro_disciplina = isset($_GET['disciplina']) ? mysqli_real_escape_string($conn, $_GET['disciplina']) : '';
$filtro_resultado = isset($_GET['resultado']) ? $_GET['resultado'] : '';

// Construir query com filtros
$where_conditions = ["ru.email_usuario = '$email_usuario'"];

if ($filtro_disciplina) {
    $where_conditions[] = "q.disciplina = '$filtro_disciplina'";
}

if ($filtro_resultado === 'corretas') {
    $where_conditions[] = "ru.correta = 1";
} elseif ($filtro_resultado === 'incorretas') {
    $where_conditions[] = "ru.correta = 0";
}

$where_clause = implode(' AND ', $where_conditions);

// Contar total de questões respondidas (para paginação)
$sql_total = "SELECT COUNT(DISTINCT ru.id_questao) as total 
              FROM respostas_usuarios ru
              JOIN questoes q ON ru.id_questao = q.id
              WHERE $where_clause";

$res_total = mysqli_query($conn, $sql_total);
$total_questoes = mysqli_fetch_assoc($res_total)['total'];
$total_paginas = ceil($total_questoes / $questoes_por_pagina);

// ele busca as questoes respondidas com paginacao
$sql_questoes = "SELECT ru.*, q.*, 
                 ru.data_resposta,
                 ru.resposta_usuario,
                 ru.correta,
                 q.disciplina,
                 q.conteudo,
                 q.enunciado,
                 q.resposta_correta,
                 q.alternativa_a,
                 q.alternativa_b,
                 q.alternativa_c,
                 q.alternativa_d,
                 q.alternativa_e
                 FROM respostas_usuarios ru
                 JOIN questoes q ON ru.id_questao = q.id
                 WHERE $where_clause
                 GROUP BY ru.id_questao  /* <--- ADICIONADO: Agrupa questões iguais */
                 ORDER BY MAX(ru.data_resposta) DESC /* <--- MUDANÇA: Ordena pela data mais recente do grupo */
                 LIMIT $questoes_por_pagina OFFSET $offset";
$res_questoes = mysqli_query($conn, $sql_questoes);

// Estatísticas gerais
$sql_stats = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN correta = 1 THEN 1 ELSE 0 END) as acertos,
              SUM(CASE WHEN correta = 0 THEN 1 ELSE 0 END) as erros
              FROM respostas_usuarios
              WHERE email_usuario = '$email_usuario'";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $sql_stats));

$percentual_acerto = $stats['total'] > 0 ? round(($stats['acertos'] / $stats['total']) * 100, 1) : 0;

// Disciplinas disponíveis para filtro
$sql_disciplinas = "SELECT DISTINCT q.disciplina 
                    FROM respostas_usuarios ru
                    JOIN questoes q ON ru.id_questao = q.id
                    WHERE ru.email_usuario = '$email_usuario'
                    ORDER BY q.disciplina";
$res_disciplinas = mysqli_query($conn, $sql_disciplinas);

// Estatísticas por disciplina
$sql_por_disciplina = "SELECT q.disciplina,
                       COUNT(*) as total,
                       SUM(CASE WHEN ru.correta = 1 THEN 1 ELSE 0 END) as acertos,
                       ROUND((SUM(CASE WHEN ru.correta = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as percentual
                       FROM respostas_usuarios ru
                       JOIN questoes q ON ru.id_questao = q.id
                       WHERE ru.email_usuario = '$email_usuario'
                       GROUP BY q.disciplina
                       ORDER BY total DESC
                       LIMIT 5";
$res_por_disciplina = mysqli_query($conn, $sql_por_disciplina);


$grafico_labels = [];
$grafico_acertos = [];
$grafico_erros = [];

mysqli_data_seek($res_por_disciplina, 0);
while ($g = mysqli_fetch_assoc($res_por_disciplina)) {
    $grafico_labels[]  = $g['disciplina'];
    $grafico_acertos[] = $g['acertos'];
    $grafico_erros[]   = $g['total'] - $g['acertos']; // erros = total - acertos
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Histórico de Questões - Decola Militar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Black+Han+Sans&display=swap');
    .content-wrapper {
      flex: 1;
      padding: 40px 0;
    }

    /* Header */
    .header-historico {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 40px;
      border-radius: 20px;
      margin-bottom: 30px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      position: relative;
      overflow: hidden;
      animation: slideUp 0.5s ease;
      font-family: "Montserrat", sans-serif;
    }


    @keyframes pulse {
      0%, 100% { transform: scale(1) rotate(0deg); }
      50% { transform: scale(1.1) rotate(180deg); }
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .header-historico h2,
    .header-historico p {
      position: relative;
      z-index: 1;
    }

    /* Cards de estatísticas */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
      font-family: "Montserrat", sans-serif;
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

    /* Filtros */
    .filtros-card {
      background: white;
      border: 2px;
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      animation: slideUp 0.7s ease;
      font-family: "Montserrat", sans-serif;
    }

    .filtros-card h5 {
      font-family: "Black Han Sans", sans-serif;
      color: #2e3d2f;
      margin-bottom: 20px;
      font-size: 1.3rem;
    }

    /* Card de questão */
    .questao-card {
      background: white;
      border: 3px;
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      transition: all 0.3s ease;
      animation: slideUp 0.8s ease;
      width: 75%;
      height: 50%;
      position: center;
      margin-left: auto;
      margin-right: auto;
    }

    .questao-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(151, 216, 138, 0.74);
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

    .questao-info {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .badge-disciplina {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 6px 15px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .badge-resultado {
      padding: 6px 15px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .badge-correta {
      background: #28a745;
      color: white;
    }

    .badge-incorreta {
      background: #dc3545;
      color: white;
    }

    .questao-data {
      font-size: 0.85rem;
      color: #666;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .questao-enunciado {
      margin: 20px 0;
      font-size: 1rem;
      line-height: 1.6;
      color: #333;
    }

    .alternativas-grid {
      display: grid;
      gap: 10px;
      margin: 20px 0;
    }

    .alternativa {
      padding: 12px 15px;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
      transition: all 0.3s ease;
    }

    .alternativa-selecionada {
      background: #fff3cd;
      border-color: #ffc107;
    }

    .alternativa-correta {
      background: #d4edda;
      border-color: #28a745;
      font-weight: 600;
    }

    .alternativa-incorreta {
      background: #f8d7da;
      border-color: #dc3545;
    }

    /* Paginação */
    .pagination-container {
      display: flex;
      justify-content: center;
      margin-top: 40px;
      font-family: "Montserrat", sans-serif;
    }

    .pagination {
      display: flex;
      gap: 10px;
      list-style: none;
      padding: 0;
    }

    .page-item {
      display: inline-block;
    }

    .page-link {
      display: block;
      padding: 10px 15px;
      background: white;
      border: 2px solid #495846;
      border-radius: 10px;
      color: #495846;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .page-link:hover {
      background: #495846;
      color: white;
      transform: translateY(-2px);
    }

    .page-item.active .page-link {
      background: #495846;
      color: white;
    }

    .page-item.disabled .page-link {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .page-item.disabled .page-link:hover {
      background: white;
      color: #495846;
      transform: none;
    }

    /* Botão voltar */
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

    .btn-filtrar {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 10px 25px;
      border-radius: 20px;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .btn-filtrar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(73, 88, 70, 0.3);
      color: white;
    }

    .btn-limpar {
      background: #6c757d;
      color: white;
      padding: 10px 25px;
      border-radius: 20px;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn-limpar:hover {
      background: #5a6268;
      transform: translateY(-2px);
      color: white;
    }

    /* Estado vazio */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border: 3px;
      border-radius: 20px;
      margin-top: 30px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
    }

    .empty-state-icon {
      font-size: 5rem;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .empty-state-text {
      font-size: 1.2rem;
      color: #666;
    }

    /* Barra de progresso */
    .progress-bar-custom {
      height: 25px;
      border-radius: 15px;
      background: #e9ecef;
      overflow: hidden;
      margin-top: 10px;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(135deg, #28a745, #5cb85c);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 0.85rem;
      transition: width 1s ease;
    }

    .titulo {
      text-align: center;
      font-family: "Black Han Sans", sans-serif;
      margin-top: 30px;
      margin-bottom: 30px;
      color: #2e3d2f;
    }

    .titulo h1 {
        font-size: 2.6rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.12);
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

/* Card do gráfico */
.grafico-card {
  background: white;
  border: 3px;
  border-radius: 20px;
  padding: 25px;
  margin-bottom: 30px;
  box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
  animation: slideUp 0.65s ease;
  font-family: "Montserrat", sans-serif;
}

.grafico-card h4 {
  font-family: "Black Han Sans", sans-serif;
  color: #2e3d2f;
  margin-bottom: 20px;
}

/* Sombra das barras */
.chartjs-render-monitor {
  filter: drop-shadow(0 3px 4px rgba(0,0,0,0.15));
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js"></script>


</head>
<body>

<?php include("navbar.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="content-wrapper">
  <div class="container">
    
    <div class="titulo">
      <h1>MEU DESEMPENHO</h1>
    </div>

    <!-- Botão Voltar -->
    <div class="mb-4">
      <a href="resultados.php" class="btn-voltar">
        <span>←</span> Voltar ao Painel
      </a>
    </div>

    <!-- Header -->
    <div class="header-historico">
      <h2>Olá, <?= htmlspecialchars($nome_usuario) ?>! 📚</h2>
      <p class="mb-0">Acompanhe seu progresso e revise todas as questões que você já respondeu</p>
    </div>

    <!-- Estatísticas Gerais -->
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-number"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Respondidas</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-number"><?= $stats['acertos'] ?></div>
        <div class="stat-label">Acertos</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">❌</div>
        <div class="stat-number"><?= $stats['erros'] ?></div>
        <div class="stat-label">Erros</div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🎯</div>
        <div class="stat-number"><?= $percentual_acerto ?>%</div>
        <div class="stat-label">Taxa de Acerto</div>
        <div class="progress-bar-custom">
          <div class="progress-fill" style="width: <?= $percentual_acerto ?>%">
            <?= $percentual_acerto ?>%
          </div>
        </div>
      </div>
    </div>

<div class="grafico-card mt-4">
  <h4 class="mb-4" style="color: #2e3d2f;">📊 Grafico: Taxa de Acerto por Disciplina</h4>

  <canvas id="graficoDisciplinas" height="50"></canvas>
</div>

    <!-- Filtros -->
    <div class="filtros-card">
      <h5>🔍 Filtrar Questoes</h5>
      <form method="GET" class="row g-3">
        <div class="col-md-5">
          <label class="form-label">Disciplina</label>
          <select name="disciplina" class="form-select">
            <option value="">Todas as disciplinas</option>
            <?php 
            mysqli_data_seek($res_disciplinas, 0);
            while($d = mysqli_fetch_assoc($res_disciplinas)): 
            ?>
              <option value="<?= htmlspecialchars($d['disciplina']) ?>" 
                      <?= $filtro_disciplina == $d['disciplina'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['disciplina']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Resultado</label>
          <select name="resultado" class="form-select">
            <option value="">Todos os resultados</option>
            <option value="corretas" <?= $filtro_resultado == 'corretas' ? 'selected' : '' ?>>Apenas corretas</option>
            <option value="incorretas" <?= $filtro_resultado == 'incorretas' ? 'selected' : '' ?>>Apenas incorretas</option>
          </select>
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
          <button type="submit" class="btn-filtrar flex-grow-1">Filtrar</button>
          <a href="historico_questoes.php" class="btn-limpar">Limpar</a>
        </div>
      </form>
    </div>

    <!-- Lista de Questões -->
    <?php if (mysqli_num_rows($res_questoes) > 0): ?>
      
      <div class="mb-3 text-muted">
        Exibindo <?= mysqli_num_rows($res_questoes) ?> de <?= $total_questoes ?> questões
      </div>

      <?php while($q = mysqli_fetch_assoc($res_questoes)): ?>
        <div class="questao-card">
          <div class="questao-header">
            <div class="questao-info">
              <span class="badge-disciplina"><?= htmlspecialchars($q['disciplina']) ?></span>
              <span class="badge-resultado <?= $q['correta'] ? 'badge-correta' : 'badge-incorreta' ?>">
                <?= $q['correta'] ? '✅ Correto' : '❌ Incorreto' ?>
              </span>
              <small class="text-muted">ID: <?= $q['id_questao'] ?></small>
            </div>
            <div class="questao-data">
              <span>🕐</span>
              <span><?= date('d/m/Y H:i', strtotime($q['data_resposta'])) ?></span>
            </div>
          </div>

          <div class="questao-enunciado">
            <strong>Enunciado:</strong><br>
            <?= nl2br($q['enunciado']) ?>
          </div>

          <?php if (!empty($q['imagem'])): ?>
            <div class="mb-3">
              <img src="imgs/<?= htmlspecialchars($q['imagem']) ?>" 
                   alt="Imagem da questão" 
                   class="img-fluid rounded"
                   style="max-height: 300px; object-fit: contain;">
            </div>
          <?php endif; ?>

          <div class="alternativas-grid">
            <?php
            $alternativas = [
              'A' => $q['alternativa_a'],
              'B' => $q['alternativa_b'],
              'C' => $q['alternativa_c'],
              'D' => $q['alternativa_d'],
              'E' => $q['alternativa_e']
            ];

            foreach ($alternativas as $letra => $texto):
              if (empty($texto)) continue;
              
              $classes = ['alternativa'];
              if ($letra == $q['resposta_usuario']) {
                $classes[] = 'alternativa-selecionada';
              }
              if ($letra == $q['resposta_correta']) {
                $classes[] = 'alternativa-correta';
              }
              if ($letra == $q['resposta_usuario'] && !$q['correta']) {
                $classes[] = 'alternativa-incorreta';
              }
            ?>
              <div class="<?= implode(' ', $classes) ?>">
                <strong><?= $letra ?>)</strong> <?= htmlspecialchars($texto) ?>
                <?php if ($letra == $q['resposta_correta']): ?>
                  <span class="float-end">✅ Resposta correta</span>
                <?php elseif ($letra == $q['resposta_usuario'] && !$q['correta']): ?>
                  <span class="float-end">❌ Sua resposta</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endwhile; ?>

      <!-- Paginação -->
      <?php if ($total_paginas > 1): ?>
      <div class="pagination-container">
        <ul class="pagination">
          <!-- Primeira página -->
          <li class="page-item <?= $pagina_atual == 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=1<?= $filtro_disciplina ? '&disciplina=' . urlencode($filtro_disciplina) : '' ?><?= $filtro_resultado ? '&resultado=' . $filtro_resultado : '' ?>">
              «
            </a>
          </li>

          <!-- Página anterior -->
          <li class="page-item <?= $pagina_atual == 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= max(1, $pagina_atual - 1) ?><?= $filtro_disciplina ? '&disciplina=' . urlencode($filtro_disciplina) : '' ?><?= $filtro_resultado ? '&resultado=' . $filtro_resultado : '' ?>">
              ‹
            </a>
          </li>

          <!-- Páginas numéricas -->
          <?php
          $inicio = max(1, $pagina_atual - 2);
          $fim = min($total_paginas, $pagina_atual + 2);
          
          for ($i = $inicio; $i <= $fim; $i++):
          ?>
            <li class="page-item <?= $i == $pagina_atual ? 'active' : '' ?>">
              <a class="page-link" href="?pagina=<?= $i ?><?= $filtro_disciplina ? '&disciplina=' . urlencode($filtro_disciplina) : '' ?><?= $filtro_resultado ? '&resultado=' . $filtro_resultado : '' ?>">
                <?= $i ?>
              </a>
            </li>
          <?php endfor; ?>

          <!-- Próxima página -->
          <li class="page-item <?= $pagina_atual == $total_paginas ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= min($total_paginas, $pagina_atual + 1) ?><?= $filtro_disciplina ? '&disciplina=' . urlencode($filtro_disciplina) : '' ?><?= $filtro_resultado ? '&resultado=' . $filtro_resultado : '' ?>">
              ›
            </a>
          </li>

          <!-- Última página -->
          <li class="page-item <?= $pagina_atual == $total_paginas ? 'disabled' : '' ?>">
            <a class="page-link" href="?pagina=<?= $total_paginas ?><?= $filtro_disciplina ? '&disciplina=' . urlencode($filtro_disciplina) : '' ?><?= $filtro_resultado ? '&resultado=' . $filtro_resultado : '' ?>">
              »
            </a>
          </li>
        </ul>
      </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <div class="empty-state-icon">📭</div>
        <div class="empty-state-text">
          <?php if ($filtro_disciplina || $filtro_resultado): ?>
            Nenhuma questão encontrada com os filtros selecionados.<br>
            <a href="historico_questoes.php" class="btn-limpar mt-3">Limpar filtros</a>
          <?php else: ?>
            Você ainda não respondeu nenhuma questão.<br>
            <br>
            <a href="bancomain.php" class="btn-filtrar mt-3">Responder questões</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include("footer.php"); ?>

<script>
const ctx = document.getElementById('graficoDisciplinas');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($grafico_labels) ?>,
        datasets: [
            {
                label: 'Acertos',
                data: <?= json_encode($grafico_acertos) ?>,
                backgroundColor: 'rgba(73, 88, 70, 0.85)',
                borderColor: '#3a4637',
                borderWidth: 2,
                borderRadius: 10,
            },
            {
                label: 'Erros',
                data: <?= json_encode($grafico_erros) ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.75)',
                borderColor: '#b02a37',
                borderWidth: 2,
                borderRadius: 10,
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: "#333" }
            },
            x: {
                ticks: { color: "#333" }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: "#2e3d2f",
                    font: { weight: "bold" }
                }
            }
        }
    }
});
</script>

</body>
</html>