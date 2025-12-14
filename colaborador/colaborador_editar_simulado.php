<?php
require_once '../config/verificar_colaborador.php';
include("../config/db_connect.php");

use Dompdf\Dompdf;
use Dompdf\Options;

$simulado_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar dados do simulado
$sql_simulado = "SELECT * FROM simulados WHERE id = $simulado_id";
$resultado = mysqli_query($conn, $sql_simulado);
$simulado = mysqli_fetch_assoc($resultado);

if (!$simulado) {
  die("Simulado não encontrado!");
}

// Buscar questões do simulado
$sql_questoes_simulado = "SELECT qs.*, q.* 
                          FROM questoes_simulado qs 
                          JOIN questoes q ON qs.questao_id = q.id 
                          WHERE qs.simulado_id = $simulado_id 
                          ORDER BY qs.ordem ASC";
$res_questoes_simulado = mysqli_query($conn, $sql_questoes_simulado);

// Buscar todas as questões disponíveis (para adicionar novas)
$sql_todas_questoes = "SELECT * FROM questoes 
WHERE (tipo_questao = 'simulado' OR tipo_questao = 'ambos' OR tipo_questao IS NULL)
ORDER BY disciplina, id ASC";
$res_todas_questoes = mysqli_query($conn, $sql_todas_questoes);

$mensagem = '';
$tipo_mensagem = '';

// ATUALIZAR SIMULADO
if (isset($_POST['atualizar'])) {
  $titulo = mysqli_real_escape_string($conn, $_POST['titulo']);
  $descricao = mysqli_real_escape_string($conn, $_POST['descricao']);
  $duracao = intval($_POST['duracao']);
  $vestibular = mysqli_real_escape_string($conn, $_POST['vestibular']);
  $ativo = isset($_POST['ativo']) ? 1 : 0;
  
  $logo_vestibular = $vestibular . ".png";
  
  $questoes_selecionadas = $_POST['questoes'] ?? [];
  
  if (empty($questoes_selecionadas)) {
    $mensagem = "Selecione pelo menos uma questão!";
    $tipo_mensagem = "danger";
  } else {
    $total_questoes = count($questoes_selecionadas);
    
    // Atualizar dados básicos do simulado
    $sql_update = "UPDATE simulados SET 
                   titulo = '$titulo',
                   descricao = '$descricao',
                   duracao_minutos = $duracao,
                   total_questoes = $total_questoes,
                   vestibular = '$vestibular',
                   logo_vestibular = '$logo_vestibular',
                   ativo = $ativo
                   WHERE id = $simulado_id";
    
    if (mysqli_query($conn, $sql_update)) {
      
      // Deletar questões antigas
      mysqli_query($conn, "DELETE FROM questoes_simulado WHERE simulado_id = $simulado_id");
      
      // Inserir questões atualizadas
      $ordem = 1;
      foreach ($questoes_selecionadas as $questao_id) {
        $sql_questao = "INSERT INTO questoes_simulado (simulado_id, questao_id, ordem)
                        VALUES ($simulado_id, $questao_id, $ordem)";
        mysqli_query($conn, $sql_questao);
        $ordem++;
      }
      
      // REGENERAR PDF
      require_once 'dompdf/autoload.inc.php';
      
      $options = new Options();
      $options->set('isHtml5ParserEnabled', true);
      $options->set('isRemoteEnabled', true);
      
      $dompdf = new Dompdf($options);
      
      // Deletar PDF antigo
      if (!empty($simulado['arquivo_pdf']) && file_exists("simulados_pdf/" . $simulado['arquivo_pdf'])) {
        unlink("simulados_pdf/" . $simulado['arquivo_pdf']);
      }
      
      // Construção do HTML
      $html = '
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <style>
          body { font-family: Arial, sans-serif; margin: 40px; }
          h1 { color: #495846; text-align: center; border-bottom: 3px solid #495846; padding-bottom: 10px; }
          h2 { color: #495846; margin-top: 30px; }
          .info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
          .questao { margin: 30px 0; padding: 20px; border: 2px solid #e0e0e0; border-radius: 8px; page-break-inside: avoid; }
          .questao-numero { font-weight: bold; color: #495846; font-size: 18px; margin-bottom: 10px; }
          .enunciado { margin: 15px 0; line-height: 1.6; }
          .alternativas { margin-top: 15px; }
          .alternativa { margin: 8px 0; padding: 8px; }
          .gabarito { margin-top: 40px; page-break-before: always; }
          .resposta-linha { margin: 5px 0; }
        </style>
      </head>
      <body>
        <h1>' . htmlspecialchars($titulo) . '</h1>

        <div class="info">
          <p><strong>Vestibular:</strong> ' . htmlspecialchars($vestibular) . '</p>
          <p><strong>Descrição:</strong> ' . htmlspecialchars($descricao) . '</p>
          <p><strong>Duração:</strong> ' . $duracao . ' minutos</p>
          <p><strong>Total de Questões:</strong> ' . $total_questoes . '</p>
        </div>
      ';
      
      // Questões no PDF
      $ordem_pdf = 1;
      foreach ($questoes_selecionadas as $qid) {
        $sql_q = "SELECT * FROM questoes WHERE id = $qid";
        $res_q = mysqli_query($conn, $sql_q);
        $q = mysqli_fetch_assoc($res_q);
        
        $html .= '
        <div class="questao">
          <div class="questao-numero">Questão ' . $ordem_pdf . ' - ' . htmlspecialchars($q['disciplina']) . '</div>
          <div class="enunciado">' . nl2br(htmlspecialchars($q['enunciado'])) . '</div>';
        
        if (!empty($q['imagem'])) {
          $html .= '<p><em>[Imagem disponível no sistema online]</em></p>';
        }
        
        $html .= '
          <div class="alternativas">
            <div class="alternativa">A) ' . htmlspecialchars($q['alternativa_a']) . '</div>
            <div class="alternativa">B) ' . htmlspecialchars($q['alternativa_b']) . '</div>
            <div class="alternativa">C) ' . htmlspecialchars($q['alternativa_c']) . '</div>
            <div class="alternativa">D) ' . htmlspecialchars($q['alternativa_d']) . '</div>';
        
        if (!empty($q['alternativa_e'])) {
          $html .= '<div class="alternativa">E) ' . htmlspecialchars($q['alternativa_e']) . '</div>';
        }
        
        $html .= '
          </div>
        </div>';
        
        $ordem_pdf++;
      }
      
      // Gabarito
      $html .= '<div class="gabarito"><h2>Gabarito</h2>';
      $ordem_gab = 1;
      
      foreach ($questoes_selecionadas as $qid) {
        $sql_g = "SELECT resposta_correta FROM questoes WHERE id = $qid";
        $res_g = mysqli_query($conn, $sql_g);
        $g = mysqli_fetch_assoc($res_g);
        
        $html .= '<div class="resposta-linha">' . $ordem_gab . ': <strong>' . $g['resposta_correta'] . '</strong></div>';
        $ordem_gab++;
      }
      
      $html .= '</div></body></html>';
      
      // Gerar PDF
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'portrait');
      $dompdf->render();
      
      // Nome do arquivo
      $pdf_filename = 'simulado_' . $simulado_id . '_' . time() . '.pdf';
      $pdf_path = 'simulados_pdf/' . $pdf_filename;
      
      if (!file_exists('simulados_pdf')) {
        mkdir('simulados_pdf', 0777, true);
      }
      
      file_put_contents($pdf_path, $dompdf->output());
      
      // Atualizar PDF no banco
      mysqli_query($conn, "UPDATE simulados SET arquivo_pdf = '$pdf_filename' WHERE id = $simulado_id");
      
      $mensagem = "Simulado atualizado com sucesso! PDF regenerado.";
      $tipo_mensagem = "success";
      
      // Log da ação
      $admin_email = $_SESSION['email'];
      mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) VALUES ('$admin_email', 'Editar Simulado', 'ID: $simulado_id')");
      
      echo "<script>setTimeout(() => window.location.href='admin_editar_simulado.php?id=$simulado_id', 1500);</script>";
      
    } else {
      $mensagem = "Erro ao atualizar: " . mysqli_error($conn);
      $tipo_mensagem = "danger";
    }
  }
}

// Criar array com IDs das questões já selecionadas
$questoes_selecionadas_ids = [];
mysqli_data_seek($res_questoes_simulado, 0);
while($q = mysqli_fetch_assoc($res_questoes_simulado)) {
  $questoes_selecionadas_ids[] = $q['questao_id'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar Simulado - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    .form-simulado {
      background: white;
      border: 3px solid #495846;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .form-simulado h3 {
      color: #2e3d2f;
      font-weight: bold;
      margin-bottom: 10px;
      padding-bottom: 15px;
      border-bottom: 3px solid #495846;
    }

    .questao-item {
      border: 2px solid #d0d0d0;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 15px;
      transition: all 0.3s ease;
      overflow: hidden;
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
      border: 2px solid #e0e0e0;
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

    .btn-salvar {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 25px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .btn-salvar:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(73, 88, 70, 0.3);
      color: white;
    }

    .btn-voltar {
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
      padding: 12px 24px;
      border-radius: 25px;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
    }

    .btn-voltar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
      color: white;
    }

    .status-toggle {
      background: white;
      border: 2px solid #495846;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .status-toggle label {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      font-weight: 600;
      color: #495846;
    }

    .status-toggle input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }

    .questoes-atuais {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      border: 2px solid #28a745;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 25px;
    }

    .questoes-atuais h5 {
      color: #155724;
      font-weight: bold;
      margin-bottom: 15px;
    }

    .questao-atual-item {
      background: white;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 10px;
      display: flex;
      justify-content: between;
      align-items: center;
      gap: 10px;
      border: 1px solid #c3e6cb;
    }

    .questao-atual-info {
      flex: 1;
    }

    .ordem-badge {
      background: #28a745;
      color: white;
      padding: 5px 12px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.85rem;
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
  <h1>✏️ EDITAR SIMULADO #<?= $simulado_id ?></h1>
</div>

<div class="container mt-4 mb-5">
  
  <?php if ($mensagem): ?>
    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
      <?= $mensagem ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- BOTÃO VOLTAR -->
  <div class="top-actions mb-4">
    <a href="admin_gerenciar_simulado.php" class="btn-voltar">
      ← Voltar para Gerenciar Simulados
    </a>
  </div>

  <form method="POST" class="form-simulado">
    
    <h3>📋 Informações do Simulado</h3>
    
    <!-- Status Ativo/Inativo -->
    <div class="status-toggle">
      <label>
        <input type="checkbox" name="ativo" <?= $simulado['ativo'] ? 'checked' : '' ?>>
        <span>✅ Simulado ativo (visível para os alunos)</span>
      </label>
    </div>
    
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label fw-bold">Título do Simulado</label>
        <input type="text" name="titulo" class="form-control" 
               value="<?= htmlspecialchars($simulado['titulo']) ?>" required>
      </div>
      
      <div class="col-md-3">
        <label class="form-label fw-bold">Duração (minutos)</label>
        <input type="number" name="duracao" class="form-control" 
               value="<?= $simulado['duracao_minutos'] ?>" min="1" required>
      </div>
      
      <div class="col-md-3">
        <label class="form-label fw-bold">Vestibular</label>
        <select name="vestibular" class="form-select" required>
          <option value="">Selecione...</option>
          <option value="ITA" <?= $simulado['vestibular'] == 'ITA' ? 'selected' : '' ?>>ITA</option>
          <option value="IME" <?= $simulado['vestibular'] == 'IME' ? 'selected' : '' ?>>IME</option>
          <option value="AFA" <?= $simulado['vestibular'] == 'AFA' ? 'selected' : '' ?>>AFA</option>
          <option value="EFOMM" <?= $simulado['vestibular'] == 'EFOMM' ? 'selected' : '' ?>>EFOMM</option>
          <option value="EN" <?= $simulado['vestibular'] == 'EN' ? 'selected' : '' ?>>EN</option>
          <option value="EsPCEx" <?= $simulado['vestibular'] == 'EsPCEx' ? 'selected' : '' ?>>EsPCEx</option>
          <option value="ESA" <?= $simulado['vestibular'] == 'ESA' ? 'selected' : '' ?>>ESA</option>
        </select>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label fw-bold">Descrição</label>
      <textarea name="descricao" class="form-control" rows="3" required><?= htmlspecialchars($simulado['descricao']) ?></textarea>
    </div>

    <hr class="my-4">

    <!-- Questões Atuais -->
    <div class="questoes-atuais">
      <h5>📝 Questões Atuais do Simulado (<?= mysqli_num_rows($res_questoes_simulado) ?>)</h5>
      <?php 
      mysqli_data_seek($res_questoes_simulado, 0);
      $ordem_atual = 1;
      while($q = mysqli_fetch_assoc($res_questoes_simulado)): 
      ?>
      <div class="questao-atual-item">
        <span class="ordem-badge">#<?= $ordem_atual ?></span>
        <div class="questao-atual-info">
          <strong>ID: <?= $q['id'] ?></strong> - 
          <?= htmlspecialchars($q['disciplina']) ?> - 
          <?= htmlspecialchars($q['conteudo']) ?>
        </div>
      </div>
      <?php 
      $ordem_atual++;
      endwhile; 
      ?>
    </div>

    <h4 class="mb-3" style="color: #2e3d2f;">📚 Selecionar Questões</h4>
    
    <div class="info-box">
      <strong>💡 Dica:</strong> Marque as questões que deseja incluir no simulado. 
      As questões atuais já estarão selecionadas automaticamente.
    </div>
    
    <div class="contador-selecionadas" id="contador">
      <?= count($questoes_selecionadas_ids) ?> questões selecionadas
    </div>

    <?php if (mysqli_num_rows($res_todas_questoes) > 0): ?>
    <div class="questoes-container">
      <?php 
      mysqli_data_seek($res_todas_questoes, 0);
      while($questao = mysqli_fetch_assoc($res_todas_questoes)): 
        $is_selected = in_array($questao['id'], $questoes_selecionadas_ids);
      ?>
      
      <label class="questao-item">
        <input type="checkbox" name="questoes[]" value="<?= $questao['id'] ?>" 
               class="form-check-input me-2" 
               <?= $is_selected ? 'checked' : '' ?>
               onchange="atualizarContador()">
        
        <div class="questao-content">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <strong>ID: <?= $questao['id'] ?></strong>
            <div>
              <span class="badge-disciplina"><?= htmlspecialchars($questao['disciplina']) ?></span>
              <?php 
              $tipo = $questao['tipo_questao'] ?? 'ambos';
              $tipo_label = $tipo == 'simulado' ? '📝 Simulado' : '📄 Ambos';
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

    <button type="submit" name="atualizar" class="btn-salvar w-100 mt-4">
      💾 Salvar Alterações e Regenerar PDF
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

// Inicializar contador ao carregar a página
window.onload = function() {
  atualizarContador();
}
</script>
</body>
</html>