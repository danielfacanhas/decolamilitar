<?php
session_start();
if (!isset($_SESSION['nome'])) {
  header("Location: login.php");
  exit;
}

require_once '../config/verificar_colaborador.php';
include("../config/db_connect.php");

// ATUALIZAR TABELA - Adicionar campo tipo_questao
$sql_alter = "ALTER TABLE questoes ADD COLUMN IF NOT EXISTS tipo_questao ENUM('banco', 'simulado', 'ambos') DEFAULT 'banco'";
@mysqli_query($conn, $sql_alter);

$mensagem = '';
$tipo_mensagem = '';

if (isset($_POST['salvar'])) {
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

    // Upload da imagem (opcional)
    $nomeArquivo = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($extensao, $extensoes_permitidas)) {
            $nomeArquivo = time() . "_" . basename($_FILES['imagem']['name']);
            $caminhoDestino = "uploads/" . $nomeArquivo;
            
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminhoDestino)) {
                $mensagem = "Imagem enviada com sucesso! ";
            } else {
                $mensagem = "Erro ao fazer upload da imagem. ";
                $tipo_mensagem = "warning";
            }
        } else {
            $mensagem = "Formato de imagem inválido. Use JPG, PNG ou GIF. ";
            $tipo_mensagem = "warning";
        }
    }

    // Inserir no banco
    $sql = "INSERT INTO questoes (disciplina, conteudo, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e, resposta_correta, nivel_dificuldade, fonte, imagem, tipo_questao)
            VALUES ('$disciplina', '$conteudo', '$enunciado', '$a', '$b', '$c', '$d', '$e', '$resposta', '$nivel', '$fonte', '$nomeArquivo', '$tipo_questao')";

    if (mysqli_query($conn, $sql)) {
        $mensagem .= "Questão cadastrada com sucesso!";
        $tipo_mensagem = $tipo_mensagem ?: "success";
        
        // Log se for admin
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $admin_email = $_SESSION['email'];
            $questao_id = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO logs_admin (admin_email, acao, detalhes) VALUES ('$admin_email', 'Cadastrar Questão', 'ID: $questao_id - $disciplina')");
        }
        
        echo "<script>setTimeout(() => window.location.href='cadastrar_questao.php', 2000);</script>";
    } else {
        $mensagem = "Erro ao cadastrar questão: " . mysqli_error($conn);
        $tipo_mensagem = "danger";
    }
}

// Buscar disciplinas existentes para sugestões
$disciplinas_res = mysqli_query($conn, "SELECT DISTINCT disciplina FROM questoes ORDER BY disciplina ASC");
$disciplinas = [];
while($row = mysqli_fetch_assoc($disciplinas_res)) {
    $disciplinas[] = $row['disciplina'];
}

// Disciplinas mais populares
$sql_disciplinas = "SELECT disciplina, COUNT(*) as total 
                    FROM questoes 
                    GROUP BY disciplina 
                    ORDER BY total DESC
                    LIMIT 5";
$disciplinas_stats = mysqli_query($conn, $sql_disciplinas);


?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastrar Questão - Decola Militar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">
  <link rel="stylesheet" href="../stylesheet/global.css">

  <style>
    .form-container {
      max-width: 900px;
      margin: 0 auto;
      
    }
    
    .form-card {
      background: white;
      border: 3px;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
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
      border-bottom: 3px;
      
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
      border: 2px solid #e9e9e9ff;
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
      border: 2px;
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

    .caixa {
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      border-radius: 20px;

    }
  </style>

  <script>
    window.MathJax = { 
      tex: { 
        inlineMath: [['$', '$'], ['\\(', '\\)']],
        displayMath: [['$$', '$$'], ['\\[', '\\]']]
      },
      svg: { 
        fontCache: 'global',
        scale: 1,
        minScale: 0.5,
        matchFontHeight: true
      },
      options: {
        enableMenu: false
      }
    };

function insertLatex(textareaId, latex) {
  const textarea = document.getElementById(textareaId);
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const text = textarea.value;
  
  // Insere com $ para inline math
  const before = text.substring(0, start);
  const after = text.substring(end);
  
  // Se for texto, não adiciona $
  if (latex.includes('\\text{')) {
    textarea.value = before + latex + after;
    const newPos = start + latex.length - 1;
    textarea.setSelectionRange(newPos, newPos);
  } else {
    textarea.value = before + '$' + latex + '$' + after;
    const newPos = start + latex.length + 1;
    textarea.setSelectionRange(newPos, newPos);
  }
  
  textarea.focus();
  updatePreview(textareaId);
}

function updatePreview(textareaId) {
  const textarea = document.getElementById(textareaId);
  const preview = document.getElementById(textareaId + '-preview');
  
  if (textarea.value.trim()) {
    preview.innerHTML = textarea.value;
    if (window.MathJax) {
      MathJax.typesetPromise([preview]).catch((err) => console.log(err));
    }
  } else {
    preview.innerHTML = '<em class="text-muted">A pré-visualização aparecerá aqui...</em>';
  }
}
</script>

  <script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
</head>

<body>
<?php include("navbar_colaborador.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>📝 CADASTRAR QUESTÃO</h1>
</div>
  <div class="form-container">
        <!-- BOTÃO VOLTAR -->
  <div class="top-actions">
    <a href="painel_admin.php" class="btn-modern btn-voltar">
      ← Voltar ao Painel
    </a>
  </div>
<div class="container mt-4 mb-5">

    <?php if ($mensagem): ?>
      <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
        <?= $mensagem ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

      <div class="caixa">
      <!-- DISCIPLINAS -->
      <div class="info-table">
        <h5>📖 Disciplinas Cadastradas + Número de Questões</h5>
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Disciplina</th>
                <th>Total de Questões</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              mysqli_data_seek($disciplinas_stats, 0);
              while($disc = mysqli_fetch_assoc($disciplinas_stats)): 
              ?>
              <tr>
                <td><?= htmlspecialchars($disc['disciplina']) ?></td>
                <td><strong class="text-primary"><?= $disc['total'] ?></strong></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  <div class="caixa">
      <form method="POST" enctype="multipart/form-data" id="formQuestao">
           <h3>📚 Nova Questão</h3>
      <p class="text-muted">Preencha os campos abaixo para adicionar uma nova questão ao banco</p>
   
        <!-- TIPO DE QUESTÃO -->
        <div class="tipo-questao-box">
          <div class="fw-bold mb-2" style="color: #856404;">🎯 Onde esta questão aparecerá?</div>
          <label>
            <input type="radio" name="tipo_questao" value="banco" checked> 
            <span>📚 Apenas no Banco de Questões</span>
          </label>
          <label>
            <input type="radio" name="tipo_questao" value="simulado">
            <span>📝 Apenas em Simulados</span>
          </label>
          <label>
            <input type="radio" name="tipo_questao" value="ambos">
            <span>🔄 Em Ambos (Banco e Simulados)</span>
          </label>
        </div>

        <!-- INFORMAÇÕES BÁSICAS -->
        <div class="form-section">
          <div class="section-title">
            <span>📋</span> Informações Básicas
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Disciplina</label>
              <input type="text" name="disciplina" class="form-control" list="disciplinas-list"
                     placeholder="Ex: Matemática, Física, Química..." required>
              <datalist id="disciplinas-list">
                <?php foreach($disciplinas as $disc): ?>
                  <option value="<?= htmlspecialchars($disc) ?>">
                <?php endforeach; ?>
              </datalist>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Conteúdo</label>
              <input type="text" name="conteudo" class="form-control" 
                     placeholder="Ex: Trigonometria, Cinemática..." required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nível de Dificuldade</label>
              <select name="nivel" class="form-select">
                <option value="Fácil">😊 Fácil</option>
                <option value="Médio" selected>😐 Médio</option>
                <option value="Difícil">😰 Difícil</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Fonte</label>
              <input type="text" name="fonte" class="form-control" value="Decola Militar"
                     placeholder="Ex: ITA, IME, ENEM...">
            </div>
          </div>
        </div>

        <!-- ENUNCIADO -->
        <div class="form-section">
          <div class="section-title">
            <span>📖</span> Enunciado
          </div>

          <div class="info-box">
            <strong>💡 Dica:</strong> Use os botões abaixo para inserir fórmulas matemáticas facilmente!
          </div>

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

          <div class="mb-3">
            <label class="form-label">Texto do Enunciado</label>
            <textarea name="enunciado" id="enunciado" class="form-control latex-content" rows="5" 
                      placeholder="Digite o enunciado. Use $ $ para fórmulas inline ou $$ $$ para display" 
                      required oninput="updatePreview('enunciado')"></textarea>
          </div>

          <div class="latex-preview" id="enunciado-preview">
            <em class="text-muted">A pré-visualização aparecerá aqui...</em>
          </div>
        </div>
 <!-- ALTERNATIVAS -->
        <div class="form-section">
          <div class="section-title">
            <span>✅</span> Alternativas
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">A)</span>
            <input type="text" name="a" id="alt-a" class="form-control" placeholder="Digite a alternativa A" required oninput="updatePreview('alt-a')">
            <div class="latex-preview mt-2" id="alt-a-preview" style="min-height: 40px;">
              <em class="text-muted">Preview A</em>
            </div>
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">B)</span>
            <input type="text" name="b" id="alt-b" class="form-control" placeholder="Digite a alternativa B" required oninput="updatePreview('alt-b')">
            <div class="latex-preview mt-2" id="alt-b-preview" style="min-height: 40px;">
              <em class="text-muted">Preview B</em>
            </div>
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">C)</span>
            <input type="text" name="c" id="alt-c" class="form-control" placeholder="Digite a alternativa C" required oninput="updatePreview('alt-c')">
            <div class="latex-preview mt-2" id="alt-c-preview" style="min-height: 40px;">
              <em class="text-muted">Preview C</em>
            </div>
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">D)</span>
            <input type="text" name="d" id="alt-d" class="form-control" placeholder="Digite a alternativa D" required oninput="updatePreview('alt-d')">
            <div class="latex-preview mt-2" id="alt-d-preview" style="min-height: 40px;">
              <em class="text-muted">Preview D</em>
            </div>
          </div>

          <div class="alternativa-input">
            <span class="alternativa-label">E)</span>
            <input type="text" name="e" id="alt-e" class="form-control" placeholder="Digite a alternativa E (opcional)" oninput="updatePreview('alt-e')">
            <div class="latex-preview mt-2" id="alt-e-preview" style="min-height: 40px;">
              <em class="text-muted">Preview E</em>
            </div>
          </div>
          <span class="opcional-badge">Opcional</span>

          <div class="mt-3">
            <label class="form-label">Resposta Correta</label>
            <select name="resposta" class="form-select" required>
              <option value="">Selecione a alternativa correta</option>
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="C">C</option>
              <option value="D">D</option>
              <option value="E">E</option>
            </select>
          </div>
        </div>
        <!-- IMAGEM -->
        <div class="form-section">
          <div class="section-title">
            <span>🖼️</span> Imagem <span class="opcional-badge">Opcional</span>
          </div>

          <div class="file-upload-wrapper">
            <input type="file" name="imagem" accept="image/*" id="fileInput" onchange="updateFileName(this)">
            <div style="font-size: 3rem; color: #495846; margin-bottom: 10px;">📁</div>
            <div id="fileName">
              <strong>Clique para selecionar uma imagem</strong><br>
              <small class="text-muted">Formatos aceitos: JPG, PNG, GIF, WEBP</small>
            </div>
          </div>
        </div>

        <!-- BOTÕES -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; flex-wrap: wrap; gap: 15px;">
          <small class="text-muted">Todos os campos marcados são obrigatórios</small>
          <button type="submit" name="salvar" class="btn-salvar">💾 Salvar Questão</button>
        </div>
      </form>
  </div>
</div>
</div>

<?php include("footer.php"); ?>

<script>
function insertLatex(textareaId, latex) {
  const textarea = document.getElementById(textareaId);
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const text = textarea.value;
  
  // Insere com espaços para inline math
  const before = text.substring(0, start);
  const after = text.substring(end);
  textarea.value = before + '$' + latex + '$' + after;
  
  // Posiciona cursor
  const newPos = start + latex.length + 1;
  textarea.setSelectionRange(newPos, newPos);
  textarea.focus();
  
  updatePreview(textareaId);
}

function updatePreview(textareaId) {
  const textarea = document.getElementById(textareaId);
  const preview = document.getElementById(textareaId + '-preview');
  
  if (textarea.value.trim()) {
    preview.innerHTML = textarea.value;
    if (window.MathJax) {
      MathJax.typesetPromise([preview]).catch((err) => console.log(err));
    }
  } else {
    preview.innerHTML = '<em class="text-muted">A pré-visualização aparecerá aqui...</em>';
  }
}

function updateFileName(input) {
  const fileName = document.getElementById('fileName');
  if (input.files && input.files[0]) {
    const file = input.files[0];
    fileName.innerHTML = `<strong>✅ Arquivo selecionado:</strong><br><small>${file.name} (${(file.size / 1024).toFixed(2)} KB)</small>`;
  }
}

// Confirmação antes de sair
let formModified = false;
document.getElementById('formQuestao').addEventListener('input', () => { formModified = true; });
window.addEventListener('beforeunload', (e) => { if (formModified) { e.preventDefault(); e.returnValue = ''; } });
document.getElementById('formQuestao').addEventListener('submit', () => { formModified = false; });
</script>
</body>
</html>