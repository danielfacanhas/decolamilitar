<?php
require_once '../config/verificar_colaborador.php';
include("../config/db_connect.php");

$questao_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar questão
$sql = "SELECT * FROM questoes WHERE id = $questao_id";
$resultado = mysqli_query($conn, $sql);
$questao = mysqli_fetch_assoc($resultado);

if (!$questao) {
  die("Questão não encontrada!");
}

$mensagem = '';
$tipo_mensagem = '';

// ATUALIZAR QUESTÃO
if (isset($_POST['atualizar'])) {
  $disciplina = mysqli_real_escape_string($conn, $_POST['disciplina']);
  $conteudo = mysqli_real_escape_string($conn, $_POST['conteudo']);
  $enunciado = mysqli_real_escape_string($conn, $_POST['enunciado']);
  $a = mysqli_real_escape_string($conn, $_POST['a']);
  $b = mysqli_real_escape_string($conn, $_POST['b']);
  $c = mysqli_real_escape_string($conn, $_POST['c']);
  $d = mysqli_real_escape_string($conn, $_POST['d']);
  $e = mysqli_real_escape_string($conn, $_POST['e']);
  $resposta = $_POST['resposta'];
  $nivel = $_POST['nivel'];
  $fonte = mysqli_real_escape_string($conn, $_POST['fonte']);
  $tipo_questao = $_POST['tipo_questao'] ?? 'banco';

  $imagem_atual = $questao['imagem'];

  // Upload nova imagem
  if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
    if (!empty($imagem_atual) && file_exists("uploads/" . $imagem_atual)) {
      unlink("uploads/" . $imagem_atual);
    }
    $nomeArquivo = time() . "_" . basename($_FILES['imagem']['name']);
    move_uploaded_file($_FILES['imagem']['tmp_name'], "uploads/" . $nomeArquivo);
    $imagem_atual = $nomeArquivo;
  }

  $sql_update = "UPDATE questoes SET 
                 disciplina = '$disciplina',
                 conteudo = '$conteudo',
                 enunciado = '$enunciado',
                 alternativa_a = '$a',
                 alternativa_b = '$b',
                 alternativa_c = '$c',
                 alternativa_d = '$d',
                 alternativa_e = '$e',
                 resposta_correta = '$resposta',
                 nivel_dificuldade = '$nivel',
                 fonte = '$fonte',
                 imagem = '$imagem_atual',
                 tipo_questao = '$tipo_questao'
                 WHERE id = $questao_id";

  if (mysqli_query($conn, $sql_update)) {
    $mensagem = "Questão atualizada com sucesso!";
    $tipo_mensagem = "success";
  } else {
    $mensagem = "Erro ao atualizar: " . mysqli_error($conn);
    $tipo_mensagem = "danger";
  }
}

// Buscar disciplinas existentes
$disciplinas_res = mysqli_query($conn, "SELECT DISTINCT disciplina FROM questoes ORDER BY disciplina ASC");
$disciplinas = [];
while($row = mysqli_fetch_assoc($disciplinas_res)) {
    $disciplinas[] = $row['disciplina'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Editar Questão - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../stylesheet/navbar.css">
<link rel="stylesheet" href="../stylesheet/footer.css">
<link rel="stylesheet" href="../stylesheet/configuracoes.css">

<style>
    .form-container {
      max-width: 900px;
      margin: 0 auto;
    }
    
    .form-card {
      background: white;
      border: 3px solid #495846;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
      animation: slideIn 0.5s ease;
    }
    
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .form-card h3 {
      color: #2e3d2f;
      font-weight: bold;
      margin-bottom: 10px;
      padding-bottom: 15px;
      border-bottom: 3px solid #495846;
    }
    
    .form-section {
      margin-top: 30px;
      padding-top: 25px;
      border-top: 2px solid #e9ecef;
    }
    
    .form-section:first-of-type {
      border-top: none;
      margin-top: 20px;
    }
    
    .section-title {
      font-size: 1.2rem;
      font-weight: bold;
      color: #495846;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }
    
    .form-control, .form-select {
      border: 2px solid #d0d0d0;
      border-radius: 10px;
      padding: 12px 15px;
      transition: all 0.3s ease;
      font-size: 0.95rem;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #495846;
      box-shadow: 0 0 0 0.25rem rgba(73, 88, 70, 0.15);
    }
    
    textarea.form-control {
      min-height: 120px;
      font-family: 'Courier New', monospace;
    }
    
    /* FIX: Contém LaTeX dentro do campo */
    .latex-content, .latex-preview {
      word-wrap: break-word;
      overflow-wrap: break-word;
      white-space: pre-wrap;
      max-width: 100%;
    }
    
    .latex-preview {
      background: #f8f9fa;
      border: 2px solid #495846;
      border-radius: 10px;
      padding: 15px;
      margin-top: 10px;
      min-height: 60px;
      overflow-x: auto;
    }
    
    /* FIX: MathJax responsivo */
    mjx-container {
      display: inline-block !important;
      max-width: 100% !important;
      overflow-x: auto;
      overflow-y: hidden;
    }
    
    mjx-container[display="block"] {
      display: block !important;
      overflow-x: auto;
      margin: 10px 0;
    }
    
    /* Editor LaTeX */
    .latex-toolbar {
      background: linear-gradient(135deg, #495846, #6a9762);
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 10px;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
    }
    
    .latex-toolbar-label {
      color: white;
      font-weight: bold;
      margin-right: 10px;
      font-size: 0.9rem;
    }
    
    .latex-btn {
      background: white;
      border: 2px solid #495846;
      border-radius: 8px;
      padding: 6px 12px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: 'Courier New', monospace;
      white-space: nowrap;
    }
    
    .latex-btn:hover {
      background: #495846;
      color: white;
      transform: translateY(-2px);
    }
    
    .alternativa-input {
      position: relative;
      margin-bottom: 12px;
    }
    
    .alternativa-label {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-weight: bold;
      color: #495846;
      background: white;
      padding: 0 5px;
      font-size: 1.1rem;
    }
    
    .alternativa-input input {
      padding-left: 50px;
    }
    
    .opcional-badge {
      display: inline-block;
      background: linear-gradient(135deg, #17a2b8, #48b9db);
      color: white;
      padding: 3px 10px;
      border-radius: 15px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-left: 8px;
    }
    
    .file-upload-wrapper {
      position: relative;
      border: 2px dashed #d0d0d0;
      border-radius: 10px;
      padding: 30px;
      text-align: center;
      background: #f8f9fa;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .file-upload-wrapper:hover {
      border-color: #495846;
      background: #e9ecef;
    }
    
    .file-upload-wrapper input[type="file"] {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      cursor: pointer;
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
      border: none;
      padding: 10px 25px;
      border-radius: 20px;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .info-box {
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
      border-left: 4px solid #2196f3;
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
    }
    
    .tipo-questao-box {
      background: linear-gradient(135deg, #fff3cd, #ffeaa7);
      border: 2px solid #ffc107;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    .tipo-questao-box label {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 8px 0;
      cursor: pointer;
      padding: 8px;
      border-radius: 8px;
      transition: background 0.2s;
    }
    
    .tipo-questao-box label:hover {
      background: rgba(255, 255, 255, 0.5);
    }
    
    @media (max-width: 768px) {
      .form-card { padding: 25px; }
      .latex-toolbar { justify-content: center; }
    }

    .latex-toolbar {
  background: linear-gradient(135deg, #495846, #6a9762);
  border-radius: 10px;
  padding: 15px;
  margin-bottom: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  max-height: 400px;
  overflow-y: auto;
}

.latex-toolbar-label {
  color: white;
  font-weight: bold;
  margin-right: 10px;
  font-size: 0.9rem;
  width: 100%;
  margin-bottom: 10px;
}

.latex-btn {
  background: white;
  border: 2px solid #495846;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: 'Courier New', monospace;
  white-space: nowrap;
}

.latex-btn:hover {
  background: #495846;
  color: white;
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .latex-toolbar {
    max-height: 300px;
  }
  
  .latex-btn {
    font-size: 0.75rem;
    padding: 5px 10px;
  }
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

<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>

<div class="titulo">
  <h1>✏️ EDITAR QUESTÃO #<?= $questao_id ?></h1>
</div>

<div class="container mt-4 mb-5">
  <div class="form-container">

    <?php if($mensagem): ?>
    <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
      <?= $mensagem ?>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

  <div class="top-actions">
    <a href="painel_colaborador.php" class="btn-modern btn-voltar">
      ← Voltar ao Painel
    </a>
  </div>
  <br>


      <form method="POST" enctype="multipart/form-data">
      <h3>📘 Editando Questão</h3>

        <!-- Tipo de Questão -->
        <div class="tipo-questao-box">
          <div class="fw-bold mb-2" style="color:#856404;">🎯 Onde esta questão aparecerá?</div>
          <label>
            <input type="radio" name="tipo_questao" value="banco" <?= $questao['tipo_questao']=='banco'?'checked':'' ?>>
            <span>📚 Banco de Questões</span>
          </label>
          <label>
            <input type="radio" name="tipo_questao" value="simulado" <?= $questao['tipo_questao']=='simulado'?'checked':'' ?>>
            <span>📝 Simulados</span>
          </label>
          <label>
            <input type="radio" name="tipo_questao" value="ambos" <?= $questao['tipo_questao']=='ambos'?'checked':'' ?>>
            <span>🔄 Ambos</span>
          </label>
        </div>

        <!-- Informações Básicas -->
        <div class="form-section">
          <div class="section-title"><span>📋</span> Informações Básicas</div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Disciplina</label>
              <input type="text" name="disciplina" class="form-control" list="disciplinas-list"
                     value="<?= htmlspecialchars($questao['disciplina']) ?>" required>
              <datalist id="disciplinas-list">
                <?php foreach($disciplinas as $disc): ?>
                <option value="<?= htmlspecialchars($disc) ?>">
                <?php endforeach; ?>
              </datalist>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Conteúdo</label>
              <input type="text" name="conteudo" class="form-control"
                     value="<?= htmlspecialchars($questao['conteudo']) ?>" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nível</label>
              <select name="nivel" class="form-select">
                <option value="Fácil" <?= $questao['nivel_dificuldade']=="Fácil"?'selected':'' ?>>😊 Fácil</option>
                <option value="Médio" <?= $questao['nivel_dificuldade']=="Médio"?'selected':'' ?>>😐 Médio</option>
                <option value="Difícil" <?= $questao['nivel_dificuldade']=="Difícil"?'selected':'' ?>>😰 Difícil</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Fonte</label>
              <input type="text" name="fonte" class="form-control" 
                     value="<?= htmlspecialchars($questao['fonte']) ?>">
            </div>
          </div>
        </div>

        <!-- Enunciado -->
        <div class="form-section">
          <div class="section-title"><span>📖</span> Enunciado</div>

     


         <!-- BARRA DE FERRAMENTAS LATEX COMPLETA -->
<div class="latex-toolbar">
  <span class="latex-toolbar-label">Inserir:</span>
  
  <!-- BÁSICOS -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\frac{a}{b}')" title="Fração">
    Fração a/b
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', 'x^{2}')" title="Potência">
    x²
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', 'x_{1}')" title="Índice">
    x₁
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\sqrt{x}')" title="Raiz quadrada">
    √x
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\sqrt[n]{x}')" title="Raiz n">
    ⁿ√x
  </button>
  
  <!-- CÁLCULO -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\int')" title="Integral">
    ∫
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\int_{a}^{b}')" title="Integral definida">
    ∫ₐᵇ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\sum')" title="Somatório">
    Σ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\sum_{i=1}^{n}')" title="Somatório indexado">
    Σⁿᵢ₌₁
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\lim_{x \\to \\infty}')" title="Limite">
    lim
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\frac{dy}{dx}')" title="Derivada">
    dy/dx
  </button>
  
  <!-- CONJUNTOS NUMÉRICOS -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\mathbb{N}')" title="Naturais">
    ℕ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\mathbb{Z}')" title="Inteiros">
    ℤ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\mathbb{Q}')" title="Racionais">
    ℚ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\mathbb{R}')" title="Reais">
    ℝ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\mathbb{C}')" title="Complexos">
    ℂ
  </button>
  
  <!-- LETRAS GREGAS -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\pi')" title="Pi">
    π
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\alpha')" title="Alpha">
    α
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\beta')" title="Beta">
    β
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\gamma')" title="Gamma">
    γ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\delta')" title="Delta">
    δ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\theta')" title="Theta">
    θ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\lambda')" title="Lambda">
    λ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\mu')" title="Mu">
    μ
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\omega')" title="Omega">
    ω
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\Omega')" title="Omega maiúsculo">
    Ω
  </button>
  
  <!-- OPERADORES E SÍMBOLOS -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\leq')" title="Menor ou igual">
    ≤
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\geq')" title="Maior ou igual">
    ≥
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\neq')" title="Diferente">
    ≠
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\approx')" title="Aproximadamente">
    ≈
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\cdot')" title="Multiplicação">
    ·
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\times')" title="Vezes">
    ×
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\div')" title="Divisão">
    ÷
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\pm')" title="Mais ou menos">
    ±
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\infty')" title="Infinito">
    ∞
  </button>
  
  <!-- CONJUNTOS E LÓGICA -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\in')" title="Pertence">
    ∈
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\notin')" title="Não pertence">
    ∉
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\subset')" title="Subconjunto">
    ⊂
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\cup')" title="União">
    ∪
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\cap')" title="Interseção">
    ∩
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\forall')" title="Para todo">
    ∀
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\exists')" title="Existe">
    ∃
  </button>
  
  <!-- TRIGONOMETRIA -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\sin')" title="Seno">
    sin
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\cos')" title="Cosseno">
    cos
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\tan')" title="Tangente">
    tan
  </button>
  
  <!-- VETORES E MATRIZES -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\vec{v}')" title="Vetor">
    v⃗
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\overrightarrow{AB}')" title="Vetor AB">
    AB→
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\begin{pmatrix} a & b \\\\ c & d \\end{pmatrix}')" title="Matriz 2x2">
    Matriz 2×2
  </button>
  
  <!-- OUTROS -->
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\text{texto}')" title="Texto normal">
    Texto
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\left( \\right)')" title="Parênteses grandes">
    ( )
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\left[ \\right]')" title="Colchetes grandes">
    [ ]
  </button>
  <button type="button" class="latex-btn" onclick="insertLatex('enunciado', '\\left\\{ \\right\\}')" title="Chaves grandes">
    { }
  </button>
</div>



          <textarea name="enunciado" id="enunciado" class="form-control latex-content" rows="5"
          oninput="updatePreview('enunciado')" required><?= htmlspecialchars($questao['enunciado']) ?></textarea>

          <div class="latex-preview" id="enunciado-preview"></div>
        </div>

        <!-- Alternativas -->
        <div class="form-section">
          <div class="section-title"><span>✅</span> Alternativas</div>

          <div class="alternativa-input">
            <span class="alternativa-label">A)</span>
            <input type="text" name="a" class="form-control"
                   value="<?= htmlspecialchars($questao['alternativa_a']) ?>" required>
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">B)</span>
            <input type="text" name="b" class="form-control"
                   value="<?= htmlspecialchars($questao['alternativa_b']) ?>" required>
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">C)</span>
            <input type="text" name="c" class="form-control"
                   value="<?= htmlspecialchars($questao['alternativa_c']) ?>" required>
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">D)</span>
            <input type="text" name="d" class="form-control"
                   value="<?= htmlspecialchars($questao['alternativa_d']) ?>" required>
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">E)</span>
            <input type="text" name="e" class="form-control"
                   value="<?= htmlspecialchars($questao['alternativa_e']) ?>">
            <span class="opcional-badge">Opcional</span>
          </div>

          <div class="mt-3">
            <label class="form-label">Resposta Correta</label>
            <select name="resposta" class="form-select" required>
              <option value="A" <?= $questao['resposta_correta']=='A'?'selected':'' ?>>A</option>
              <option value="B" <?= $questao['resposta_correta']=='B'?'selected':'' ?>>B</option>
              <option value="C" <?= $questao['resposta_correta']=='C'?'selected':'' ?>>C</option>
              <option value="D" <?= $questao['resposta_correta']=='D'?'selected':'' ?>>D</option>
              <option value="E" <?= $questao['resposta_correta']=='E'?'selected':'' ?>>E</option>
            </select>
          </div>
        </div>

        <!-- Imagem -->
        <div class="form-section">
          <div class="section-title"><span>🖼️</span> Imagem</div>

          <?php if($questao['imagem']): ?>
          <div class="mb-3 text-center">
            <img src="uploads/<?= $questao['imagem'] ?>" class="img-fluid rounded" style="max-width:300px;">
          </div>
          <?php endif; ?>

          <div class="file-upload-wrapper">
            <input type="file" name="imagem" accept="image/*">
            <div style="font-size:3rem;color:#495846;margin-bottom:10px;">📁</div>
            <div><strong>Selecione nova imagem (opcional)</strong></div>
          </div>
        </div>

        <!-- Botão salvar -->
        <div class="d-flex justify-content-end mt-4">
          <button type="submit" name="atualizar" class="btn-salvar">💾 Salvar Alterações</button>
        </div>

      </form>

  </div>
</div>

<?php include("footer.php"); ?>

<script>
function insertLatex(id, latex) {
  let t = document.getElementById(id);
  let start = t.selectionStart;
  let end = t.selectionEnd;
  t.value = t.value.substring(0, start) + "$" + latex + "$" + t.value.substring(end);
  t.focus();
  updatePreview(id);
}

function updatePreview(id) {
  const textarea = document.getElementById(id);
  const preview = document.getElementById(id + "-preview");
  preview.innerHTML = textarea.value.trim() ? textarea.value : "<em class='text-muted'>A pré-visualização aparecerá aqui...</em>";
  MathJax.typesetPromise([preview]);
}

window.onload = function() {
  updatePreview('enunciado');
}
</script>
</body>
</html>
