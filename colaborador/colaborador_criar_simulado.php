<?php
require_once '../config/verificar_colaborador.php';
include("../config/db_connect.php");

// Criar tabelas se não existirem
$criar_simulados = "CREATE TABLE IF NOT EXISTS simulados (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT,
  duracao_minutos INT NOT NULL,
  total_questoes INT NOT NULL,
  vestibular VARCHAR(50),
  logo_vestibular VARCHAR(255),
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ativo TINYINT(1) DEFAULT 1,
  autor_email VARCHAR(255) DEFAULT NULL,
  autor_nome VARCHAR(255) DEFAULT NULL
)";
mysqli_query($conn, $criar_simulados);

$criar_questoes_simulado = "CREATE TABLE IF NOT EXISTS questoes_simulado (
  id INT AUTO_INCREMENT PRIMARY KEY,
  simulado_id INT NOT NULL,
  questao_id INT NOT NULL,
  ordem INT NOT NULL,
  FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE,
  FOREIGN KEY (questao_id) REFERENCES questoes(id) ON DELETE CASCADE
)";
mysqli_query($conn, $criar_questoes_simulado);

$criar_respostas_simulado = "CREATE TABLE IF NOT EXISTS respostas_simulado (
  id INT AUTO_INCREMENT PRIMARY KEY,
  simulado_id INT NOT NULL,
  email_usuario VARCHAR(255) NOT NULL,
  respostas TEXT,
  tempo_gasto INT,
  nota DECIMAL(5,2),
  data_realizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (simulado_id) REFERENCES simulados(id) ON DELETE CASCADE
)";
mysqli_query($conn, $criar_respostas_simulado);

$mensagem = '';
$tipo_mensagem = '';

// Buscar questões disponíveis
$sql_questoes = "SELECT * FROM questoes 
WHERE (tipo_questao = 'simulado' OR tipo_questao = 'ambos' OR tipo_questao IS NULL)
ORDER BY disciplina, id ASC";
$res_questoes = mysqli_query($conn, $sql_questoes);

// Processar criação do simulado
if (isset($_POST['criar_simulado'])) {
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
    $autor_email = $_SESSION['email'];
    $autor_nome = $_SESSION['nome'];

    $sql_insert = "INSERT INTO simulados 
      (titulo, descricao, duracao_minutos, total_questoes, vestibular, logo_vestibular, ativo, autor_email, autor_nome) 
    VALUES 
      ('$titulo', '$descricao', $duracao, $total_questoes, '$vestibular', '$logo_vestibular', $ativo, '$autor_email', '$autor_nome')";

    if (mysqli_query($conn, $sql_insert)) {
      $simulado_id = mysqli_insert_id($conn);

      // Inserir questões
      $ordem = 1;
      foreach ($questoes_selecionadas as $questao_id) {
        $sql_questao = "INSERT INTO questoes_simulado (simulado_id, questao_id, ordem)
                        VALUES ($simulado_id, $questao_id, $ordem)";
        mysqli_query($conn, $sql_questao);
        $ordem++;
      }

      $mensagem = "Simulado criado com sucesso!";
      $tipo_mensagem = "success";
      
      // Log
      $colab_email = $_SESSION['email'];
      mysqli_query($conn, "INSERT INTO logs_colaboradores (colaborador_email, acao, detalhes) 
                          VALUES ('$colab_email', 'Cadastrar Simulado', 'ID: $simulado_id - $titulo')");

      echo "<script>setTimeout(() => window.location.href='colaborador_criar_simulado.php', 1500);</script>";
    } else {
      $mensagem = "Erro ao criar simulado: " . mysqli_error($conn);
      $tipo_mensagem = "danger";
    }
  }
}

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
  <title>Criar Simulado - Colaborador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Black+Han+Sans&display=swap');

    body {
      font-family: "Montserrat", sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .content-wrapper {
      flex: 1;
      padding: 40px 0;
    }

    /* Card do formulário */
    .form-simulado {
      background: white;
      border: 3px solid #495846;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      margin-bottom: 30px;
      animation: slideUp 0.6s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-simulado h3, .form-simulado h4 {
      font-family: "Black Han Sans", sans-serif;
      color: #2e3d2f;
      margin-bottom: 20px;
    }

    .form-simulado h3 {
      padding-bottom: 15px;
      border-bottom: 3px solid #495846;
    }

    /* Status toggle */
    .status-toggle {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border: 2px solid #495846;
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 25px;
      transition: all 0.3s ease;
    }

    .status-toggle:hover {
      box-shadow: 0 4px 12px rgba(73,88,70,0.15);
    }

    .status-toggle label {
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      font-weight: 600;
      color: #495846;
      margin: 0;
    }

    .status-toggle input[type="checkbox"] {
      width: 24px;
      height: 24px;
      cursor: pointer;
      accent-color: #495846;
    }

    /* Inputs */
    .form-control, .form-select {
      border: 2px solid #d0d0d0;
      border-radius: 12px;
      padding: 12px 18px;
      transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
      border-color: #495846;
      box-shadow: 0 0 0 0.25rem rgba(73,88,70,0.15);
      background-color: #f8fdf8;
    }

    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }

    /* Info box */
    .info-box {
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
      border-left: 4px solid #2196f3;
      padding: 15px 20px;
      border-radius: 10px;
      margin-bottom: 25px;
    }

    /* Tabela de simulados populares */
    .info-table {
      background: white;
      border: 3px solid #495846;
      border-radius: 20px;
      padding: 25px;
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
      margin-bottom: 30px;
      animation: slideUp 0.5s ease;
    }
    
    .info-table h5 {
      font-family: "Black Han Sans", sans-serif;
      color: #2e3d2f;
      margin-bottom: 20px;
      font-size: 1.4rem;
      padding-bottom: 10px;
      border-bottom: 3px solid #495846;
    }

    /* Contador */
    .contador-selecionadas {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 15px;
      border-radius: 15px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
      font-size: 1.2rem;
      box-shadow: 0 4px 10px rgba(73,88,70,0.3);
      transition: all 0.3s ease;
    }

    /* Container de questões */
    .questoes-container {
      max-height: 500px;
      overflow-y: auto;
      overflow-x: hidden;
      border: 2px solid #495846;
      border-radius: 15px;
      padding: 15px;
      background: #fafafa;
    }

    .questao-item {
      width: 49%;
      position: relative;
      background: white;
      border: 2px solid #d0d0d0;
      border-radius: 12px;
      padding: 15px;
      margin-bottom: 15px;
      min-height: 180px;
      max-height: 180px;
      transition: all 0.3s ease;
      overflow: hidden;
      cursor: pointer;
    }

    .questao-item:hover {
      border-color: #495846;
      box-shadow: 0 4px 12px rgba(73,88,70,0.15);
      transform: translateX(5px);
    }

    .questao-item input[type="checkbox"]:checked ~ .questao-content {
      background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
      border-radius: 8px;
      padding: 2px;
    }

    .questao-content {
      width: 100%;
    }

    .questao-preview {
      max-height: 80px;
      overflow: hidden;
      text-overflow: ellipsis;
      word-wrap: break-word;
      white-space: normal;
      color: #666;
    }

    .badge-disciplina {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    .badge-tipo {
      background: linear-gradient(135deg, #ffc107, #ffca2c);
      color: #333;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-left: 8px;
    }

    /* Botões */
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

    .btn-criar {
      background: linear-gradient(135deg, #28a745, #5cb85c);
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(40,167,69,0.3);
    }

    .btn-criar:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(40,167,69,0.4);
      color: white;
    }

    /* MathJax */
    mjx-container {
      max-width: 100% !important;
      overflow-x: auto !important;
    }

    /* Scrollbar customizada */
    .questoes-container::-webkit-scrollbar {
      width: 10px;
    }

    .questoes-container::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .questoes-container::-webkit-scrollbar-thumb {
      background: #495846;
      border-radius: 10px;
    }

    .questoes-container::-webkit-scrollbar-thumb:hover {
      background: #6a9762;
    }

    /* Badge do vestibular */
    .badge-vestibular {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 8px;
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
        matchFontHeight: true
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

<div class="content-wrapper">
  <div class="container">
    
    <div class="titulo">
      <h1>CRIAR SIMULADO</h1>
    </div>

    <!-- Botão Voltar -->
    <div class="mb-4">
      <a href="painel_colaborador.php" class="btn-voltar">
        <span>←</span> Voltar ao Painel
      </a>
    </div>

    <!-- Mensagens -->
    <?php if ($mensagem): ?>
      <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
        <strong><?= $tipo_mensagem === 'success' ? '✅' : '⚠️' ?></strong> <?= $mensagem ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Simulados Populares -->
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
                  <span class="badge-vestibular"><?= $sim['vestibular'] ?></span>
                <?php endif; ?>
              </td>
              <td><strong class="text-success"><?= $sim['realizacoes'] ?></strong></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Formulário -->
    <form method="POST" class="form-simulado">
      
      <h3>📋 Informações do Simulado</h3>
      
      <!-- Status Ativo/Inativo -->
      <div class="status-toggle">
        <label>
          <input type="checkbox" name="ativo" checked>
          <span>✅ Simulado ativo (visível para os alunos)</span>
        </label>
      </div>

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
          <label class="form-label">Título do Simulado</label>
          <input type="text" name="titulo" class="form-control" 
                 placeholder="Ex: Simulado ITA 2025 - Matemática" required>
        </div>
        
        <div class="col-md-3">
          <label class="form-label">Duração (minutos)</label>
          <input type="number" name="duracao" class="form-control" 
                 placeholder="120" min="1" required>
        </div>
        
        <div class="col-md-3">
          <label class="form-label">Vestibular</label>
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
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3" 
                  placeholder="Descreva o simulado..." required></textarea>
      </div>

      <hr class="my-4">

      <h4>📚 Selecionar Questões</h4>
      
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
            <div class="text-muted small mb-2">
              <strong>Conteúdo:</strong> <?= htmlspecialchars($questao['conteudo']) ?>
            </div>
            <div class="questao-preview">
              <?= substr(strip_tags($questao['enunciado']), 0, 120) ?>...
            </div>
          </div>
        </label>

        <?php endwhile; ?>
      </div>

      <button type="submit" name="criar_simulado" class="btn-criar w-100 mt-4">
        ✅ Criar Simulado
      </button>
      <?php endif; ?>
    </form>

  </div>
</div>

<?php include("footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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