<?php
session_start();
if (!isset($_SESSION['nome'])) {
    header("Location: ../login.php");
    exit;
}

include("../config/db_connect.php");

$email_usuario = $_SESSION['email'] ?? '';
$simulado_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar informações do simulado
$sql_simulado = "SELECT * FROM simulados WHERE id = $simulado_id";
$res_simulado = mysqli_query($conn, $sql_simulado);
$simulado = mysqli_fetch_assoc($res_simulado);

if (!$simulado) {
  die("Simulado não encontrado!");
}

// Verificar se já foi realizado
$sql_check = "SELECT * FROM respostas_simulado WHERE simulado_id = $simulado_id AND email_usuario = '$email_usuario'";
$res_check = mysqli_query($conn, $sql_check);
if (mysqli_num_rows($res_check) > 0) {
  header("Location: resultado_simulado.php?id=$simulado_id");
  exit;
}

// Buscar questões do simulado
$sql_questoes = "SELECT q.*, qs.ordem 
                 FROM questoes_simulado qs 
                 JOIN questoes q ON qs.questao_id = q.id 
                 WHERE qs.simulado_id = $simulado_id 
                 ORDER BY qs.ordem ASC";
$res_questoes = mysqli_query($conn, $sql_questoes);


// Processar envio
if (isset($_POST['finalizar'])) {

  $respostas = $_POST['respostas'] ?? [];
  $tempo_gasto = intval($_POST['tempo_gasto']);

  $acertos = 0;
  $ordem = 1;
  mysqli_data_seek($res_questoes, 0);

  while ($q = mysqli_fetch_assoc($res_questoes)) {
    $resposta_usuario = $respostas[$ordem] ?? '';
    if ($resposta_usuario == $q['resposta_correta']) $acertos++;
    $ordem++;
  }

  $nota = ($acertos / $simulado['total_questoes']) * 10;
  $respostas_json = json_encode($respostas);

  $sql_insert = "INSERT INTO respostas_simulado (simulado_id, email_usuario, respostas, tempo_gasto, nota) 
                 VALUES ($simulado_id, '$email_usuario', '$respostas_json', $tempo_gasto, $nota)";
  
  if (mysqli_query($conn, $sql_insert)) {
    header("Location: resultado_simulado.php?id=$simulado_id");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($simulado['titulo']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../stylesheet/navbar.css">
<link rel="stylesheet" href="../stylesheet/footer.css">
<link rel="stylesheet" href="../stylesheet/configuracoes.css">

<style>

/* GRADE NA ESQUERDA - POSIÇÃO ABSOLUTA */
.sidebar-fixed {
  margin-top: 19px;
  margin-left: 5px;
  position: absolute;
  top: 20px;
  left: 20px;
  width: 280px;
  z-index: 999;
}

/* CARD GRADE */
.grade-respostas {
  background: white;
  border: 3px;
  border-radius: 15px;
  padding: 20px;  /* leve aumento */
  box-shadow: 0 4px 6px rgba(0,0,0,0.2);
  overflow: hidden;
}

.grade-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 6px;
  width: 100%;
}

.questao-btn {
  width: 42px;
  height: 42px;
  border-radius: 8px;
  border: 2px solid #ccc;
  background: white;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.questao-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.questao-btn.respondida {
  background: #28a745;
  color: white;
  border-color: #1d7e33;
}

.questao-btn.atual {
  background: #007bff;
  color: white;
  border-color: #0056b3;
}

/* TIMER FIXO */
.timer-container {
  background: white;
  margin-top: 20px;
  padding: 15px;
  border-radius: 15px;
  text-align: center;
  color: black;
  box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

/* ÁREA CENTRAL */
.main-content {
  margin-left: 300px;
  padding: 20px;
  max-width: 900px;
  
}

.questao-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  min-height: 500px;
}

.alternativa-radio {
  display: block;
  padding: 12px 15px;
  margin: 10px 0;
  border: 2px solid #ddd;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1.05rem;
}

.alternativa-radio:hover {
  background: #f0f0f0;
  border-color: #007bff;
}

.alternativa-radio input[type="radio"] {
  margin-right: 10px;
}

.alternativa-radio input[type="radio"]:checked {
  accent-color: #28a745;
}

.btn-nav {
  margin: 10px 5px !important;
  padding: 12px 30px !important;
  border-radius: 10px !important;
  font-size: 1.1rem !important;
  font-weight: bold !important;
  transition: all 0.3s ease !important;
}

.btn-nav:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
}

.navegacao-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #ddd;
}

/* RESPONSIVO */
@media (max-width: 768px) {
  .sidebar-fixed {
    position: relative;
    top: 0;
    left: 0;
    width: 100%;
    margin-bottom: 20px;
  }
  
  .main-content {
    margin-left: 0;
    
  }
}

/* CORREÇÃO COMPLETA DO LATEX (QUEBRA DE LINHA + JUSTIFICADO) */
mjx-container {
  max-width: 100% !important;
  white-space: normal !important;
  overflow-x: hidden !important;
  overflow-wrap: break-word !important;
  word-break: break-word !important;

  /* Justificar bloco */
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

/* Truque ESSENCIAL para justificar a última linha do bloco */
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

<?php include("navbar.php"); ?>

<div class="bg"></div><div class="bg bg2"></div><div class="bg bg3"></div>

<div class="container-fluid" style="position: relative; min-height: 100vh; padding-top: 20px;">

<div class="sidebar-fixed">
  <!-- GRADE -->
  <div class="grade-respostas">
    <h5 class="text-center mb-3">Questões</h5>
    <div class="grade-grid">
      <?php for($i=1;$i<=$simulado['total_questoes'];$i++): ?>
      <button type="button" class="questao-btn" id="btn-q<?= $i ?>" onclick="mostrarQuestao(<?= $i ?>)">
        <?= $i ?>
      </button>
      <?php endfor; ?>
    </div>
  </div>

  <!-- TIMER -->
  <div class="timer-container">
    <div style="font-size:0.9rem">Tempo restante</div>
    <div id="timer" style="font-size:2rem;font-weight:bold;">--:--</div>
  </div>
</div>


<div class="main-content">
<form method="POST" id="formSimulado">
<input type="hidden" name="tempo_gasto" id="tempoGasto">

<?php 
$numero = 1;
mysqli_data_seek($res_questoes, 0);

while($questao = mysqli_fetch_assoc($res_questoes)): ?>

<div class="questao-card" id="q<?= $numero ?>" style="<?= $numero == 1 ? '' : 'display:none' ?>">
  <h4 class="mb-4">Questão <?= $numero ?> – <?= $questao['disciplina'] ?></h4>

  <div class="mb-4" style="font-size:1.1rem; line-height: 1.6;">
    <?= $questao["enunciado"] ?>
  </div>

  <?php if($questao["imagem"]): ?>
  <div class="text-center mb-4">
    <img src="uploads/<?= $questao['imagem'] ?>" class="img-fluid rounded shadow" style="max-width:60%">
  </div>
  <?php endif; ?>

  <label class="alternativa-radio">
    <input type="radio" name="respostas[<?= $numero ?>]" value="A" onchange="marcarRespondida(<?= $numero ?>)">
    <span><?= $questao['alternativa_a'] ?></span>
  </label>

  <label class="alternativa-radio">
    <input type="radio" name="respostas[<?= $numero ?>]" value="B" onchange="marcarRespondida(<?= $numero ?>)">
    <span><?= $questao['alternativa_b'] ?></span>
  </label>

  <label class="alternativa-radio">
    <input type="radio" name="respostas[<?= $numero ?>]" value="C" onchange="marcarRespondida(<?= $numero ?>)">
    <span><?= $questao['alternativa_c'] ?></span>
  </label>

  <label class="alternativa-radio">
    <input type="radio" name="respostas[<?= $numero ?>]" value="D" onchange="marcarRespondida(<?= $numero ?>)">
    <span><?= $questao['alternativa_d'] ?></span>
  </label>

  <?php if($questao['alternativa_e']): ?>
  <label class="alternativa-radio">
    <input type="radio" name="respostas[<?= $numero ?>]" value="E" onchange="marcarRespondida(<?= $numero ?>)">
    <span><?= $questao['alternativa_e'] ?></span>
  </label>
  <?php endif; ?>


  <!-- BOTÕES DE NAVEGAÇÃO -->
  <div class="navegacao-container">
    <div>
      <?php if($numero > 1): ?>
        <button type="button" class="btn btn-secondary btn-nav" onclick="mostrarQuestao(<?= $numero-1 ?>)">
          ← Anterior
        </button>
      <?php endif; ?>
    </div>

    <div>
      <?php if($numero < $simulado['total_questoes']): ?>
        <button type="button" class="btn btn-primary btn-nav" onclick="mostrarQuestao(<?= $numero+1 ?>)">
          Próxima →
        </button>
      <?php else: ?>
        <button type="submit" name="finalizar" class="btn btn-success btn-nav" onclick="return confirm('Tem certeza que deseja finalizar o simulado?')">
          ✓ Enviar Simulado
        </button>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php 
$numero++;
endwhile;
?>

</form>
</div>

</div>


<script>
let totalQuestoes = <?= $simulado['total_questoes'] ?>;
let questaoAtual = 1;

// Mostrar questão única e marcar como atual
function mostrarQuestao(n) {
  // Esconder todas
  for (let i = 1; i <= totalQuestoes; i++) {
    document.getElementById("q" + i).style.display = "none";
    document.getElementById("btn-q" + i).classList.remove("atual");
  }
  
  // Mostrar a selecionada
  document.getElementById("q" + n).style.display = "block";
  document.getElementById("btn-q" + n).classList.add("atual");
  questaoAtual = n;
  
  // Scroll para o topo
  window.scrollTo(0, 0);
}

// Marcar botão respondido
function marcarRespondida(n) {
  document.getElementById("btn-q" + n).classList.add("respondida");
}

// Verificar respostas já marcadas ao carregar
window.addEventListener('load', function() {
  for (let i = 1; i <= totalQuestoes; i++) {
    let radios = document.querySelectorAll(`input[name="respostas[${i}]"]`);
    for (let radio of radios) {
      if (radio.checked) {
        marcarRespondida(i);
        break;
      }
    }
  }
  
  // Marcar primeira questão como atual
  document.getElementById("btn-q1").classList.add("atual");
});


// TIMER
let tempo = <?= $simulado["duracao_minutos"] ?> * 60;
let timerInterval = setInterval(() => {
  let m = Math.floor(tempo / 60);
  let s = tempo % 60;
  document.getElementById("timer").innerText = 
    (m<10?'0':'')+m+":"+(s<10?'0':'')+s;

  document.getElementById("tempoGasto").value = 
    <?= $simulado["duracao_minutos"] ?>*60 - tempo;

  if (tempo <= 0) {
    clearInterval(timerInterval);
    alert("Tempo esgotado! O simulado será enviado automaticamente.");
    document.getElementById("formSimulado").submit();
  }
  tempo--;

}, 1000);

// Alertar se tentar sair da página
window.addEventListener('beforeunload', function (e) {
  e.preventDefault();
  e.returnValue = '';
});

</script>

</body>
</html>