<?php
session_start();
if (!isset($_SESSION['nome'])) {
    header("Location: ../login.php");
    exit;
}

include("../config/db_connect.php");

$email_usuario = $_SESSION['email'] ?? '';
$simulado_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar simulado
$sql_simulado = "SELECT * FROM simulados WHERE id = $simulado_id";
$res_simulado = mysqli_query($conn, $sql_simulado);
$simulado = mysqli_fetch_assoc($res_simulado);

// Buscar resultado do usuário
$sql_resultado = "SELECT * FROM respostas_simulado 
                  WHERE simulado_id = $simulado_id AND email_usuario = '$email_usuario'";
$res_resultado = mysqli_query($conn, $sql_resultado);
$resultado = mysqli_fetch_assoc($res_resultado);

if (!$resultado) {
  die("Você ainda não realizou este simulado!");
}

$respostas_usuario = json_decode($resultado['respostas'], true);

// Buscar questões e gabarito
$sql_questoes = "SELECT q.*, qs.ordem 
                 FROM questoes_simulado qs 
                 JOIN questoes q ON qs.questao_id = q.id 
                 WHERE qs.simulado_id = $simulado_id 
                 ORDER BY qs.ordem ASC";
$res_questoes = mysqli_query($conn, $sql_questoes);

// Calcular estatísticas
$acertos = 0;
$erros = 0;
$ordem = 1;
$detalhes = [];

while($q = mysqli_fetch_assoc($res_questoes)) {
  $resposta_user = $respostas_usuario[$ordem] ?? null;
  $correto = ($resposta_user == $q['resposta_correta']);
  
  if ($correto) {
    $acertos++;
  } else {
    $erros++;
  }
  
  $detalhes[] = [
    'numero' => $ordem,
    'disciplina' => $q['disciplina'],
    'enunciado' => $q['enunciado'],
    'sua_resposta' => $resposta_user,
    'resposta_correta' => $q['resposta_correta'],
    'acertou' => $correto,
    'alternativa_a' => $q['alternativa_a'],
    'alternativa_b' => $q['alternativa_b'],
    'alternativa_c' => $q['alternativa_c'],
    'alternativa_d' => $q['alternativa_d'],
    'alternativa_e' => $q['alternativa_e'] ?? ''
  ];
  
  $ordem++;
}

$taxa_acerto = ($acertos / $simulado['total_questoes']) * 100;
$tempo_gasto_min = floor($resultado['tempo_gasto'] / 60);
$tempo_gasto_seg = $resultado['tempo_gasto'] % 60;

// Verificar se deseja ver questões detalhadas
$ver_questoes = isset($_GET['ver_questoes']) ? true : false;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resultado - <?= htmlspecialchars($simulado['titulo']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">
  <link rel="stylesheet" href="../stylesheet/global.css">


  <style>
    .resultado-header {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 40px;
      border-radius: 15px;
      margin-bottom: 30px;
      text-align: center;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: white;
      border: 3px;
      border-radius: 15px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
    }
    
    .stat-icon {
      font-size: 3rem;
      margin-bottom: 10px;
    }
    
    .stat-value {
      font-size: 2.5rem;
      font-weight: bold;
      color: #495846;
    }
    
    .stat-label {
      color: #666;
      font-size: 1rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .nota-display {
      font-size: 5rem;
      font-weight: bold;
      margin: 20px 0;
    }
    
    .nota-excelente { color: #28a745; }
    .nota-boa { color: #17a2b8; }
    .nota-regular { color: #ffc107; }
    .nota-baixa { color: #dc3545; }
    
    .progress-custom {
      height: 40px;
      border-radius: 20px;
      background: #e9ecef;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
      margin: 20px 0;
    }
    
    .progress-bar-custom {
      border-radius: 20px;
      font-weight: bold;
      font-size: 1.2rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .gabarito-table {
      background: white;
      border: 3px;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      
    }
    
    .gabarito-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px;
      margin: 10px 0;
      border-radius: 10px;
      border: 2px solid #e0e0e0;
    }
    
    .gabarito-item.correto {
      background: #d4edda;
      border-color: #28a745;
    }
    
    .gabarito-item.errado {
      background: #f8d7da;
      border-color: #dc3545;
    }
    
    .badge-resposta {
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: bold;
      font-size: 1rem;
    }
    
    .badge-certo {
      background: #28a745;
      color: white;
    }
    
    .badge-errado {
      background: #dc3545;
      color: white;
    }
    
    .questao-detalhada {
      background: white;
      border: 3px solid #e0e0e0;
      border-radius: 15px;
      padding: 25px;
      margin: 20px 0;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .questao-detalhada.correto {
      border-color: #28a745;
    }
    
    .questao-detalhada.errado {
      border-color: #dc3545;
    }
    
    .alternativa-item {
      padding: 12px;
      margin: 8px 0;
      border-radius: 8px;
      border: 2px solid #e0e0e0;
    }
    
    .alternativa-item.correta {
      background: #d4edda;
      border-color: #28a745;
      font-weight: bold;
    }
    
    .alternativa-item.sua-resposta {
      background: #f8d7da;
      border-color: #dc3545;
    }
    
    .btn-toggle {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      border: none;
      padding: 12px 30px;
      border-radius: 25px;
      font-weight: 600;
      transition: all 0.3s ease;
      margin: 10px 5px;
      text-decoration: none;
    }
    
    .btn-toggle:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(73, 88, 70, 0.3);
      color: white;
    }

    /* --- QUEBRA DE LINHA, JUSTIFICADO E PREVENÇÃO DE SCROLL HORIZONTAL --- */

.questao-detalhada,
.gabarito-item,
.alternativa-item,
.resultado-header,
.stat-card,
.progress-custom,
.gabarito-table {
  max-width: 100%;
  overflow-x: hidden !important;
}

/* Enunciados */
.questao-enunciado,
.questao-detalhada > div,
.questao-detalhada .mb-3 {
  font-size: 1.1rem;
  line-height: 1.8;
  text-align: justify;
  word-wrap: break-word;
  overflow-wrap: break-word;
  white-space: normal !important;
  overflow-x: hidden !important;
  width: 100%;
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

/* Alternativas com conteúdo LaTeX */
.alternativa-item {
  word-wrap: break-word;
  overflow-wrap: break-word;
  white-space: normal !important;
  overflow-x: hidden !important;
}

.alternativa-item mjx-container {
  overflow-x: hidden !important;
  max-width: 100% !important;
}
  </style>

  <!-- CONFIGURAÇÃO COMPLETA DO MATHJAX CHTML COM QUEBRA DE LINHA -->
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

<?php include("navbar.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>📊 RESULTADO DO SIMULADO</h1>
</div>

<div class="container mt-4 mb-5">
  
  <!-- HEADER -->
  <div class="resultado-header">
    <h2><?= htmlspecialchars($simulado['titulo']) ?></h2>
    <div class="nota-display <?= $resultado['nota'] >= 8 ? 'nota-excelente' : ($resultado['nota'] >= 6 ? 'nota-boa' : ($resultado['nota'] >= 4 ? 'nota-regular' : 'nota-baixa')) ?>">
      <?= number_format($resultado['nota'], 1) ?>
    </div>
    <p class="mb-0">
      <?php
      if ($resultado['nota'] >= 8) {
        echo "🎉 Excelente! Você está muito bem preparado!";
      } elseif ($resultado['nota'] >= 6) {
        echo "👍 Bom trabalho! Continue estudando!";
      } elseif ($resultado['nota'] >= 4) {
        echo "📚 Você está no caminho certo. Pratique mais!";
      } else {
        echo "💪 Não desanime! Continue se dedicando!";
      }
      ?>
    </p>
  </div>

  <!-- ESTATÍSTICAS -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-value"><?= $acertos ?></div>
      <div class="stat-label">Acertos</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">❌</div>
      <div class="stat-value"><?= $erros ?></div>
      <div class="stat-label">Erros</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">⏱️</div>
      <div class="stat-value"><?= $tempo_gasto_min ?>:<?= str_pad($tempo_gasto_seg, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="stat-label">Tempo Gasto</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">🎯</div>
      <div class="stat-value"><?= number_format($taxa_acerto, 1) ?>%</div>
      <div class="stat-label">Taxa de Acerto</div>
    </div>
  </div>

  <!-- BARRA DE PROGRESSO -->
<div class="progress-custom">
    <div class="progress-bar-custom"
         style="width: <?= $taxa_acerto ?>%; height: 100%;
                background: linear-gradient(180deg,
                <?= $resultado['nota'] >= 6 ? '#28a745, #5cb85c' : '#dc3545, #e57373' ?>);">
        <?= $acertos ?>/<?= $simulado['total_questoes'] ?>
    </div>
</div>

  <!-- GABARITO RESUMIDO -->
  <div class="gabarito-table">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 style="color: #2e3d2f;">📋 Gabarito</h3>
      <?php if (!$ver_questoes): ?>
        <a href="?id=<?= $simulado_id ?>&ver_questoes=1" class="btn-toggle">
          👁️ Ver Questões Detalhadas
        </a>
      <?php else: ?>
        <a href="?id=<?= $simulado_id ?>" class="btn-toggle">
          📋 Ver Apenas Gabarito
        </a>
      <?php endif; ?>
    </div>
    
    <?php if (!$ver_questoes): ?>
      <!-- MODO RESUMIDO -->
      <?php foreach($detalhes as $item): ?>
      <div class="gabarito-item <?= $item['acertou'] ? 'correto' : 'errado' ?>">
        <div>
          <strong>Questão <?= $item['numero'] ?></strong>
          <span class="text-muted ms-2"><?= htmlspecialchars($item['disciplina']) ?></span>
        </div>
        
        <div class="d-flex gap-2 align-items-center">
          <?php if ($item['sua_resposta']): ?>
            <span class="badge-resposta <?= $item['acertou'] ? 'badge-certo' : 'badge-errado' ?>">
              Sua: <?= $item['sua_resposta'] ?>
            </span>
          <?php else: ?>
            <span class="badge-resposta badge-errado">Não respondida</span>
          <?php endif; ?>
          
          <?php if (!$item['acertou']): ?>
            <span class="badge-resposta badge-certo">
              Correta: <?= $item['resposta_correta'] ?>
            </span>
          <?php endif; ?>
          
          <span style="font-size: 1.5rem;">
            <?= $item['acertou'] ? '✅' : '❌' ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
    
    <?php else: ?>
      <!-- MODO DETALHADO -->
      <?php foreach($detalhes as $item): ?>
      <div class="questao-detalhada <?= $item['acertou'] ? 'correto' : 'errado' ?>">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5>
            <span style="font-size: 1.5rem;"><?= $item['acertou'] ? '✅' : '❌' ?></span>
            Questão <?= $item['numero'] ?> - <?= htmlspecialchars($item['disciplina']) ?>
          </h5>
        </div>
        
        <div class="mb-3" style="font-size: 1.1rem; line-height: 1.6;">
          <?= $item['enunciado'] ?>
        </div>
        
        <div class="alternativas-revisao">
          <div class="alternativa-item <?= $item['resposta_correta'] == 'A' ? 'correta' : '' ?> <?= $item['sua_resposta'] == 'A' && !$item['acertou'] ? 'sua-resposta' : '' ?>">
            A) <?= $item['alternativa_a'] ?>
            <?php if ($item['resposta_correta'] == 'A'): ?>
              <span class="float-end">✅ Correta</span>
            <?php elseif ($item['sua_resposta'] == 'A'): ?>
              <span class="float-end">❌ Sua resposta</span>
            <?php endif; ?>
          </div>
          
          <div class="alternativa-item <?= $item['resposta_correta'] == 'B' ? 'correta' : '' ?> <?= $item['sua_resposta'] == 'B' && !$item['acertou'] ? 'sua-resposta' : '' ?>">
            B) <?= $item['alternativa_b'] ?>
            <?php if ($item['resposta_correta'] == 'B'): ?>
              <span class="float-end">✅ Correta</span>
            <?php elseif ($item['sua_resposta'] == 'B'): ?>
              <span class="float-end">❌ Sua resposta</span>
            <?php endif; ?>
          </div>
          
          <div class="alternativa-item <?= $item['resposta_correta'] == 'C' ? 'correta' : '' ?> <?= $item['sua_resposta'] == 'C' && !$item['acertou'] ? 'sua-resposta' : '' ?>">
            C) <?= $item['alternativa_c'] ?>
            <?php if ($item['resposta_correta'] == 'C'): ?>
              <span class="float-end">✅ Correta</span>
            <?php elseif ($item['sua_resposta'] == 'C'): ?>
              <span class="float-end">❌ Sua resposta</span>
            <?php endif; ?>
          </div>
          
          <div class="alternativa-item <?= $item['resposta_correta'] == 'D' ? 'correta' : '' ?> <?= $item['sua_resposta'] == 'D' && !$item['acertou'] ? 'sua-resposta' : '' ?>">
            <?= $item['alternativa_d'] ?>
            <?php if ($item['resposta_correta'] == 'D'): ?>
              <span class="float-end">✅ Correta</span>
            <?php elseif ($item['sua_resposta'] == 'D'): ?>
              <span class="float-end">❌ Sua resposta</span>
            <?php endif; ?>
          </div>
          
          <?php if (!empty($item['alternativa_e'])): ?>
          <div class="alternativa-item <?= $item['resposta_correta'] == 'E' ? 'correta' : '' ?> <?= $item['sua_resposta'] == 'E' && !$item['acertou'] ? 'sua-resposta' : '' ?>">
            E) <?= $item['alternativa_e'] ?>
            <?php if ($item['resposta_correta'] == 'E'): ?>
              <span class="float-end">✅ Correta</span>
            <?php elseif ($item['sua_resposta'] == 'E'): ?>
              <span class="float-end">❌ Sua resposta</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- AÇÕES -->
  <div class="text-center mt-5">
    <a href="simulados_main.php" class="btn btn-primary btn-lg me-3" 
       style="background: linear-gradient(135deg, #495846, #6a9762); border: none; padding: 15px 40px; border-radius: 25px;">
      ← Voltar aos Simulados
    </a>
  </div>

</div>

<?php include("footer.php"); ?>
</body>
</html>