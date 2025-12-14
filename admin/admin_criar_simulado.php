<?php
require_once '../config/verificar_admin.php';
include("../config/db_connect.php");

// CRIAÇÃO / AJUSTE DAS TABELAS
// ===============================

$mensagem = '';
$tipo_mensagem = '';

// ===============================
// BUSCAR QUESTÕES
// ===============================
$sql_questoes = "SELECT * FROM questoes 
WHERE (tipo_questao = 'simulado' OR tipo_questao = 'ambos' OR tipo_questao IS NULL)
ORDER BY disciplina, id ASC";
$res_questoes = mysqli_query($conn, $sql_questoes);

// ===============================
// PROCESSAR CRIAÇÃO DO SIMULADO
// ===============================
if (isset($_POST['criar_simulado'])) {

  $titulo = mysqli_real_escape_string($conn, $_POST['titulo']);
  $descricao = mysqli_real_escape_string($conn, $_POST['descricao']);
  $duracao = intval($_POST['duracao']);
  $vestibular = mysqli_real_escape_string($conn, $_POST['vestibular']);

  $logo_vestibular = $vestibular . ".png";

  $questoes_selecionadas = $_POST['questoes'] ?? [];

  if (empty($questoes_selecionadas)) {
    $mensagem = "Selecione pelo menos uma questão!";
    $tipo_mensagem = "danger";

  } else {

    $total_questoes = count($questoes_selecionadas);
    
    $autor_email = $_SESSION['email'];
    $autor_nome = $_SESSION['nome'];

    // Inserir simulado
    $sql_insert = "INSERT INTO simulados 
      (titulo, descricao, duracao_minutos, total_questoes, vestibular, logo_vestibular, autor_email, autor_nome) 
    VALUES 
      ('$titulo', '$descricao', $duracao, $total_questoes, '$vestibular', '$logo_vestibular', '$autor_email', '$autor_nome')";

    if (mysqli_query($conn, $sql_insert)) {

      $simulado_id = mysqli_insert_id($conn);

      // Inserir questões selecionadas
      $ordem = 1;
      foreach ($questoes_selecionadas as $questao_id) {
        $sql_questao = "INSERT INTO questoes_simulado (simulado_id, questao_id, ordem)
                        VALUES ($simulado_id, $questao_id, $ordem)";
        mysqli_query($conn, $sql_questao);
        $ordem++;
      }

      $mensagem = "Simulado criado com sucesso!";
      $tipo_mensagem = "success";
      
      // Log da ação (admin)
      $admin_email = $_SESSION['email'];
      mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) 
                          VALUES ('$admin_email', 'Cadastrar Simulado', 'ID: $simulado_id - $titulo')");

      echo "<script>setTimeout(() => window.location.href='admin_criar_simulado.php', 1500);</script>";

    } else {
      $mensagem = "Erro ao criar simulado: " . mysqli_error($conn);
      $tipo_mensagem = "danger";
    }
  }
}

// contar questões disponíveis
$total_disponiveis = mysqli_num_rows($res_questoes);

// Simulados mais realizados
$sql_simulados_top = "SELECT s.titulo, s.vestibular, COUNT(rs.id) as realizacoes 
                      FROM simulados s 
                      LEFT JOIN respostas_simulado rs ON s.id = rs.simulado_id 
                      GROUP BY s.id 
                      ORDER BY realizacoes DESC 
                      LIMIT 5";
$simulados_top = mysqli_query($conn, $sql_simulados_top);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar Simulado - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    .questao-item {
      border: 2px solid #d0d0d0;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 15px;
      transition: all 0.3s ease;
      overflow: hidden;
      font-family: "Montserrat", sans-serif;      
    }

    .questao-item:hover {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .questao-item input[type="checkbox"]:checked ~ .questao-content {
      background: #f0fff4;
    }

    .questao-content {
      width: 100%;
      overflow: hidden;
    }

    .questao-preview {
      max-height: 100px;
      overflow: hidden;
      text-overflow: ellipsis;
      word-wrap: break-word;
      white-space: normal;
    }

    .form-simulado {
      background: white;
      border: 3px;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      font-family: "Montserrat", sans-serif;

    }

    .badge-disciplina {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 5px 12px;
      border-radius: 15px;
      font-size: 0.85rem;
    }
    
    .badge-tipo {
      background: linear-gradient(135deg, #ffc107, #ffca2c);
      color: #333;
      padding: 5px 12px;
      border-radius: 15px;
      font-size: 0.75rem;
      margin-left: 8px;
    }

    .questoes-container {
      max-height: 500px;
      overflow-y: auto;
      overflow-x: hidden;
      border-radius: 10px;
      padding: 15px;
      background: #fafafa;
    }

    .contador-selecionadas {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
      font-size: 1.2rem;
    }
    
    .info-box {
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
      border-left: 4px solid #2196f3;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
    }

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

    .questao-item, .questao-card {
      width: 100%;
      min-height: 220px;
      max-height: 220px;
      padding: 15px;
      background: white;
      border: 2px solid #d0d0d0;
      border-radius: 10px;
      overflow: hidden;
      white-space: normal !important;
      word-break: break-word !important;
      overflow-wrap: break-word !important;
    }

    .enunciado {
      white-space: normal !important;
      word-break: break-word !important;
      overflow-wrap: break-word !important;
      text-align: justify;
      max-height: 160px;
      overflow: hidden;
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
  <h1>CRIAR SIMULADO</h1>
</div>

<div class="container mt-4 mb-5">
  <!-- BOTÃO VOLTAR -->
  <div class="top-actions">
    <a href="painel_admin.php" class="btn-modern btn-voltar">
      ← Voltar ao Painel
    </a>
  </div>
  <br>
  
  <?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
      <?= $mensagem ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- SIMULADOS POPULARES -->
  <div class="info-table">
    <h5>🔥 Simulados Mais Realizados</h5>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Simulado</th>
            <th>Realizações</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          mysqli_data_seek($simulados_top, 0);
          while($sim = mysqli_fetch_assoc($simulados_top)): 
          ?>
          <tr>
            <td>
              <?= htmlspecialchars($sim['titulo']) ?>
              <?php if (!empty($sim['vestibular'])): ?>
                <span class="badge bg-primary"><?= $sim['vestibular'] ?></span>
              <?php endif; ?>
            </td>
            <td><strong class="text-success"><?= $sim['realizacoes'] ?></strong></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <form method="POST" class="form-simulado">
    
    <h4 class="mb-4" style="color: #2e3d2f;">📋 Informações do Simulado</h4>
    
    <?php if ($total_disponiveis > 0): ?>
    <div class="info-box">
      <strong>💡 Info:</strong> Exibindo apenas questões marcadas como "Simulado" ou "Ambos". 
      Total disponível: <strong><?= $total_disponiveis ?></strong> questões.
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
      ⚠️ <strong>Atenção:</strong> Não há questões disponíveis para simulados. 
      <a href="cadastrar_questao.php">Cadastre questões</a> marcadas como "Simulado" ou "Ambos".
    </div>
    <?php endif; ?>
    
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label fw-bold">Título do Simulado</label>
        <input type="text" name="titulo" class="form-control" 
               placeholder="Ex: Simulado ITA 2025 - Matemática" required>
      </div>
      
      <div class="col-md-3">
        <label class="form-label fw-bold">Duração (minutos)</label>
        <input type="number" name="duracao" class="form-control" 
               placeholder="120" min="1" required>
      </div>
      
      <div class="col-md-3">
        <label class="form-label fw-bold">Vestibular</label>
        <select name="vestibular" class="form-select" required>
          <option value="">Selecione...</option>
          <option value="ITA">ITA</option>
          <option value="IME">IME</option>
          <option value="AFA">AFA</option>
          <option value="EFOMM">EFOMM</option>
          <option value="EN">EN</option>
          <option value="EsPCEx">EsPCEx</option>
          <option value="ESA">ESA</option>
        </select>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label fw-bold">Descrição</label>
      <textarea name="descricao" class="form-control" rows="3" 
                placeholder="Descreva o simulado..." required></textarea>
    </div>

    <hr class="my-4">

    <h4 class="mb-3" style="color: #2e3d2f;">📚 Selecionar Questões</h4>
    
    <div class="contador-selecionadas" id="contador">
      0 questões selecionadas
    </div>

    <?php if ($total_disponiveis > 0): ?>
    <div class="questoes-container">
      <?php 
      mysqli_data_seek($res_questoes, 0);
      while($questao = mysqli_fetch_assoc($res_questoes)): 
      ?>
      
      <label class="questao-item">
        <input type="checkbox" name="questoes[]" value="<?= $questao['id'] ?>" 
               class="form-check-input me-2" onchange="atualizarContador()">
        
        <div class="questao-content">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <strong>ID: <?= $questao['id'] ?></strong>
            <div>
              <span class="badge-disciplina"><?= htmlspecialchars($questao['disciplina']) ?></span>
              <?php 
              $tipo = $questao['tipo_questao'] ?? 'ambos';
              $tipo_label = $tipo == 'simulado' ? '📝 Simulado' : '🔄 Ambos';
              ?>
              <span class="badge-tipo"><?= $tipo_label ?></span>
            </div>
          </div>
          <div class="text-muted small">
            <strong>Conteúdo:</strong> <?= htmlspecialchars($questao['conteudo']) ?>
          </div>
          <div class="mt-2 questao-preview">
            <?= substr(strip_tags($questao['enunciado']), 0, 150) ?>...
          </div>
        </div>
      </label>

      <?php endwhile; ?>
    </div>

    <button type="submit" name="criar_simulado" class="btn btn-success w-100 mt-4 btn-lg">
      ✅ Criar Simulado
    </button>
    <?php endif; ?>
  </form>

</div>

<?php include("footer.php"); ?>

<script>
function atualizarContador() {
  const checkboxes = document.querySelectorAll('input[name="questoes[]"]:checked');
  const contador = document.getElementById('contador');
  const total = checkboxes.length;
  
  contador.textContent = total + ' questões selecionadas';
  
  if (total > 0) {
    contador.style.background = 'linear-gradient(135deg, #28a745, #5cb85c)';
  } else {
    contador.style.background = 'linear-gradient(135deg, #495846, #6a9762)';
  }
}
</script>

</body>
</html>