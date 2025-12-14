<?php
session_start();
if (!isset($_SESSION['nome'])) {
  header("Location: login.php");
  exit;
}

require_once '../config/verificar_admin.php';
include("../config/db_connect.php");

$email_usuario = $_SESSION['email'] ?? '';

// Recebe filtros da URL
$disciplina_filtro = $_GET['disciplina'] ?? '';
$conteudo_filtro = $_GET['conteudo'] ?? '';
$fonte_filtro = $_GET['fonte'] ?? '';

// ID atual
$id_atual = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Monta query com filtros - EXCLUIR questões exclusivas de simulado
$where = ["(tipo_questao = 'banco' OR tipo_questao = 'ambos' OR tipo_questao IS NULL)"];
if (!empty($disciplina_filtro)) {
    $where[] = "disciplina = '".mysqli_real_escape_string($conn, $disciplina_filtro)."'";
}
if (!empty($conteudo_filtro)) {
    $where[] = "conteudo LIKE '%".mysqli_real_escape_string($conn, $conteudo_filtro)."%'";
}
if (!empty($fonte_filtro)) {
    $where[] = "fonte LIKE '%".mysqli_real_escape_string($conn, $fonte_filtro)."%'";
}

$where_sql = " WHERE " . implode(" AND ", $where);

// Se não veio ID: pega primeira questão com filtros
if ($id_atual == 0) {
  $primeira = mysqli_query($conn, "SELECT id FROM questoes $where_sql ORDER BY id ASC LIMIT 1");
  $row = mysqli_fetch_assoc($primeira);
  $id_atual = $row ? $row['id'] : 0;
}

// Questão atual
$sql = "SELECT * FROM questoes WHERE id = $id_atual";
$resultado = mysqli_query($conn, $sql);
$questao = mysqli_fetch_assoc($resultado);

// Próxima questão COM FILTROS
$where_prox = $where_sql . " AND id > $id_atual";
$prox = mysqli_query($conn, "SELECT id FROM questoes $where_prox ORDER BY id ASC LIMIT 1");
$prox_row = mysqli_fetch_assoc($prox);
$proxima_id = $prox_row['id'] ?? null;

// Questão anterior COM FILTROS
$where_ant = $where_sql . " AND id < $id_atual";
$ant = mysqli_query($conn, "SELECT id FROM questoes $where_ant ORDER BY id DESC LIMIT 1");
$ant_row = mysqli_fetch_assoc($ant);
$anterior_id = $ant_row['id'] ?? null;

// Feedback
$feedback = "";

// PROCESSAR RESPOSTA
if (isset($_POST['responder'])) {
  $resposta_usuario = $_POST['resposta'] ?? "";
  $correta = $questao['resposta_correta'];
  $acertou = ($resposta_usuario === $correta) ? 1 : 0;

  // SALVAR RESPOSTA NO BANCO

  if ($acertou) {
    $feedback = "<div class='alert alert-success text-center'><strong>✅ Resposta correta!</strong></div>";
  } else {
    $feedback = "<div class='alert alert-danger text-center'>
                  <strong>❌ Resposta incorreta.</strong><br>
                  O correto é: <b>$correta</b>
                </div>";
  }
}

// PULAR QUESTÃO
if (isset($_POST['pular']) && $proxima_id) {
  $params = http_build_query([
    'id' => $proxima_id,
    'disciplina' => $disciplina_filtro,
    'conteudo' => $conteudo_filtro,
    'fonte' => $fonte_filtro
  ]);
  header("Location: responder_questao.php?$params");
  exit;
}

// Função para construir URL com filtros
function construirUrlQuestao($id) {
  $params = [
    'id' => $id,
    'disciplina' => $_GET['disciplina'] ?? '',
    'conteudo' => $_GET['conteudo'] ?? '',
    'fonte' => $_GET['fonte'] ?? ''
  ];
  return 'responder_questao.php?' . http_build_query($params);
}

// URL de voltar com filtros
$params_voltar = http_build_query([
  'disciplina' => $disciplina_filtro,
  'conteudo' => $conteudo_filtro,
  'fonte' => $fonte_filtro
]);
$url_voltar = "exibir_questoes.php?" . $params_voltar;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Responder Questão - Decola Militar</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/global.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">

  <style>
    .questao-card {
      border: 3px;
      border-radius: 15px;
      background: #fff;
      padding: 30px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      animation: slideDown 0.3s ease;
      white-space: normal !important;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alternativas-linha {
      display: flex;
      justify-content: center;
      gap: 15px;
      flex-wrap: wrap;
      margin-top: 20px;
      white-space: normal !important;
      overflow-wrap: break-word !important;
    }

    .alternativa-inline {
      border: 2px solid #d0d0d0;
      border-radius: 10px;
      padding: 10px 15px;
      cursor: pointer;
      transition: 0.25s;
      display: flex;
      align-items: center;
      white-space: normal !important;
      overflow-wrap: break-word !important;
      word-break: break-word !important;
      max-width: 100%;
    }

    .alternativa-inline:hover {
      border-color: #495846;
      background: #f4f6f4;
      transform: translateY(-2px);
    }

    .questao-card p {
      white-space: normal !important;
      overflow-wrap: break-word !important;
      word-break: break-word !important;
    }

    /* CORREÇÃO DO MATHJAX */

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

  .btn-voltar {
  background-color: #495846 !important; /* verde escuro */
  color: white !important;
  border: 2px solid #495846 !important;
  padding: 10px 18px;
  font-weight: 600;
  border-radius: 10px;
  transition: 0.25s ease-in-out;
}

.btn-voltar:hover {
  background-color: #3b4638 !important; /* verde mais escuro */
  border-color: #3b4638 !important;
  color: #fff !important;
  transform: translateY(-2px);
}
  </style>

  <!-- MATHJAX 3 - CHTML -->
  <script>
    window.MathJax = {
      tex: {
        inlineMath: [['$', '$'], ['\\(', '\\)']],
        displayMath: [['$$', '$$'], ['\\[', '\\]']]
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
    <h1>RESPONDER QUESTÃO</h1>
  </div>

  <div class="container mt-4 mb-5">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">

        <!-- BOTÃO VOLTAR -->
        <a href="<?= $url_voltar ?>" class="btn btn-voltar btn-custom mb-3">
          ← Voltar para Lista de Questões
        </a>

        <?php if ($questao): ?>
          <form method="POST" class="questao-card">

            <h4 class="mb-3 text-center" style="color:#2e3d2f;">
              <?= htmlspecialchars($questao['disciplina']) ?> — <?= htmlspecialchars($questao['conteudo']) ?>
            </h4>

            <p class="mb-3" style="font-size:1.15rem;"><?= nl2br($questao['enunciado']) ?></p>

            <?php if (!empty($questao['imagem'])): ?>
              <div class="text-center mb-3">
                <img src="../uploads/<?= $questao['imagem'] ?>" class="img-fluid rounded shadow" style="max-width:80%;">
              </div>
            <?php endif; ?>

            <?php if (empty($feedback)): ?>

              <div class="alternativas-linha">

                <label class="alternativa-inline">
                  <input type="radio" name="resposta" value="A" required> A) <?= $questao['alternativa_a'] ?>
                </label>

                <label class="alternativa-inline">
                  <input type="radio" name="resposta" value="B" required> B) <?= $questao['alternativa_b'] ?>
                </label>

                <label class="alternativa-inline">
                  <input type="radio" name="resposta" value="C" required> C) <?= $questao['alternativa_c'] ?>
                </label>

                <label class="alternativa-inline">
                  <input type="radio" name="resposta" value="D" required> D) <?= $questao['alternativa_d'] ?>
                </label>

                <?php if (!empty($questao['alternativa_e'])): ?>
                  <label class="alternativa-inline">
                    <input type="radio" name="resposta" value="E" required> E) <?= $questao['alternativa_e'] ?>
                  </label>
                <?php endif; ?>

              </div>

              <div class="d-flex justify-content-center gap-3 mt-4 flex-wrap">
                <?php if ($anterior_id): ?>
                  <a href="<?= construirUrlQuestao($anterior_id) ?>" class="btn btn-danger btn-custom">
                    ← Anterior
                  </a>
                <?php endif; ?>

                <button type="submit" name="responder" class="btn btn-success btn-custom">
                  ✓ Responder
                </button>

                <?php if ($proxima_id): ?>
                  <button type="submit" name="pular" class="btn btn-secondary btn-custom">
                    Pular →
                  </button>
                <?php endif; ?>
              </div>

            <?php endif; ?>

            <?= $feedback ?>

            <?php if (!empty($feedback)): ?>
              <div class="text-center mt-4">


                <?php if ($proxima_id): ?>
                  <a href="<?= construirUrlQuestao($proxima_id) ?>" class="btn btn-primary btn-custom">
                    Próxima Questão →
                  </a>
                <?php else: ?>
                  <div class="alert alert-info mt-3">
                    🎉 Você chegou à última questão dos filtros aplicados!
                    <br>
                    <a href="<?= $url_voltar ?>" class="btn btn-sm btn-outline-primary mt-2">
                      Voltar para Lista
                    </a>
                  </div>
                <?php endif; ?>

              </div>
            <?php endif; ?>

          </form>

        <?php else: ?>
          <div class="alert alert-warning text-center">Nenhuma questão encontrada.</div>
        <?php endif; ?>

      </div>
    </div>
  </div>

<?php include("footer.php"); ?>
</body>
</html>
