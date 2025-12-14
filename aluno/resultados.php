<?php
session_start();
if (!isset($_SESSION['nome'])) {
    header("Location: ../login.php");
    exit;
}

include("../config/db_connect.php");

$email_usuario = $_SESSION['email'] ?? '';

// ESTATÍSTICAS GERAIS
$sql_total = "SELECT COUNT(*) as total FROM respostas_usuarios WHERE email_usuario = '$email_usuario'";
$res_total = mysqli_query($conn, $sql_total);
$total_respondidas = mysqli_fetch_assoc($res_total)['total'];

$sql_corretas = "SELECT COUNT(*) as corretas FROM respostas_usuarios WHERE email_usuario = '$email_usuario' AND correta = 1";
$res_corretas = mysqli_query($conn, $sql_corretas);
$total_corretas = mysqli_fetch_assoc($res_corretas)['corretas'];

$sql_incorretas = "SELECT COUNT(*) as incorretas FROM respostas_usuarios WHERE email_usuario = '$email_usuario' AND correta = 0";
$res_incorretas = mysqli_query($conn, $sql_incorretas);
$total_incorretas = mysqli_fetch_assoc($res_incorretas)['incorretas'];

$taxa_acerto = $total_respondidas > 0 ? round(($total_corretas / $total_respondidas) * 100, 1) : 0;

// ESTATÍSTICAS POR DISCIPLINA
$sql_disciplinas = "
  SELECT 
    q.disciplina,
    COUNT(*) as total,
    SUM(r.correta) as corretas,
    ROUND((SUM(r.correta) / COUNT(*)) * 100, 1) as taxa
  FROM respostas_usuarios r
  JOIN questoes q ON r.id_questao = q.id
  WHERE r.email_usuario = '$email_usuario'
  GROUP BY q.disciplina
  ORDER BY taxa DESC
";
$res_disciplinas = mysqli_query($conn, $sql_disciplinas);

// ÚLTIMAS QUESTÕES RESPONDIDAS
$sql_ultimas = "
  SELECT 
    r.*,
    q.disciplina,
    q.conteudo,
    q.enunciado,
    q.resposta_correta
  FROM respostas_usuarios r
  JOIN questoes q ON r.id_questao = q.id
  WHERE r.email_usuario = '$email_usuario'
  ORDER BY r.data_resposta DESC
  LIMIT 10
";
$res_ultimas = mysqli_query($conn, $sql_ultimas);

// QUESTÕES MAIS ERRADAS
$sql_erros = "
  SELECT 
    q.id,
    q.disciplina,
    q.conteudo,
    q.enunciado,
    COUNT(*) as vezes_erradas
  FROM respostas_usuarios r
  JOIN questoes q ON r.id_questao = q.id
  WHERE r.email_usuario = '$email_usuario' AND r.correta = 0
  GROUP BY q.id
  ORDER BY vezes_erradas DESC
  LIMIT 5
";
$res_erros = mysqli_query($conn, $sql_erros);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meus Resultados - Decola Militar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../stylesheet/navbar.css">
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/configuracoes.css">
  <link rel="stylesheet" href="../stylesheet/global.css">
  <style>
    .stats-card {
      border: 3px;
      border-radius: 15px;
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      padding: 25px;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
    }

    .stats-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 12px rgba(0,0,0,0.15);
    }

    .stats-number {
      font-size: 3rem;
      font-weight: bold;
      color: #495846;
      margin: 10px 0;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }

    .stats-label {
      font-size: 1.1rem;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .progress-custom {
      height: 30px;
      border-radius: 15px;
      background-color: #e9ecef;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }

    .progress-bar-custom {
      background: linear-gradient(90deg, #495846 0%, #6a9762 100%);
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 1rem;
      color: white;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }

    .disciplina-card {
      border: 2px solid #d0d0d0;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 15px;
      background: white;
      transition: all 0.3s ease;
    }

    .disciplina-card:hover {
      border-color: #4956;
      transform: translateX(5px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .badge-custom {
      font-size: 0.9rem;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 600;
    }

    .historico-item {
      border-left: 4px solid #495846;
      padding: 15px;
      margin-bottom: 15px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }

    .historico-item:hover {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      transform: translateX(5px);
    }

    .historico-item.correto {
      border-left-color: #28a745;
      background: #f0fff4;
    }

    .historico-item.incorreto {
      border-left-color: #dc3545;
      background: #fff5f5;
    }

    .icon-circle {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin: 0 auto 15px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .icon-success {
      background: linear-gradient(135deg, #28a745, #5cb85c);
      color: white;
    }

    .icon-danger {
      background: linear-gradient(135deg, #dc3545, #e57373);
      color: white;
    }

    .icon-primary {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }

    .empty-state img {
      width: 150px;
      opacity: 0.5;
      margin-bottom: 20px;
    }
  </style>
</head>

<body>

<?php include("navbar.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="titulo">
  <h1>MEUS RESULTADOS</h1>
</div>

<div class="container mt-4 mb-5">

<?php if ($total_respondidas > 0): ?>

  <!-- ESTATÍSTICAS GERAIS -->
  <div class="row mb-5">
    <div class="col-md-4 mb-3">
      <div class="stats-card">
        <div class="icon-circle icon-primary">📝</div>
        <div class="stats-number"><?= $total_respondidas ?></div>
        <div class="stats-label">Questões Respondidas</div>
      </div>
    </div>

    <div class="col-md-4 mb-3">
      <div class="stats-card">
        <div class="icon-circle icon-success">✓</div>
        <div class="stats-number"><?= $total_corretas ?></div>
        <div class="stats-label">Respostas Corretas</div>
      </div>
    </div>

    <div class="col-md-4 mb-3">
      <div class="stats-card">
        <div class="icon-circle icon-danger">✗</div>
        <div class="stats-number"><?= $total_incorretas ?></div>
        <div class="stats-label">Respostas Incorretas</div>
      </div>
    </div>
  </div>

  <!-- TAXA DE ACERTO -->
  <div class="row mb-5">
    <div class="col-12">
      <div class="stats-card">
        <h4 class="mb-3" style="color: #2e3d2f;">🎯 Taxa de Acerto Geral</h4>
        <div class="progress-custom">
          <div class="progress-bar-custom" style="height: 100%; width: <?= $taxa_acerto ?>%">
            <?= $taxa_acerto ?>%
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- DESEMPENHO POR DISCIPLINA -->
  <div class="row mb-5">
    <div class="col-12">
      <div class="stats-card">
        <h4 class="mb-4" style="color: #2e3d2f;">📚 Desempenho por Disciplina</h4>

        <?php while ($disc = mysqli_fetch_assoc($res_disciplinas)): ?>
          <div class="disciplina-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0"><?= htmlspecialchars($disc['disciplina']) ?></h5>
              <span class="badge-custom <?= $disc['taxa'] >= 70 ? 'bg-success' : ($disc['taxa'] >= 50 ? 'bg-warning' : 'bg-danger') ?>">
                <?= $disc['taxa'] ?>%
              </span>
            </div>
            <div class="progress-custom" style="height: 20px;">
              <div class="progress-bar-custom" style="width: <?= $disc['taxa'] ?>%; font-size: 0.85rem;">
                <?= $disc['corretas'] ?>/<?= $disc['total'] ?> corretas
              </div>
            </div>
          </div>
        <?php endwhile; ?>

      </div>
    </div>
  </div>

  <!-- QUESTÕES MAIS ERRADAS -->
  <div class="row mb-5">
    <div class="col-12">
      <div class="stats-card">
        <h4 class="mb-4" style="color: #2e3d2f;">⚠️ Questões que Você Mais Errou</h4>

        <?php while ($erro = mysqli_fetch_assoc($res_erros)): ?>
          <div class="disciplina-card">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1">
                  <span class="badge bg-danger me-2"><?= $erro['vezes_erradas'] ?>x</span>
                  <?= htmlspecialchars($erro['disciplina']) ?> - <?= htmlspecialchars($erro['conteudo']) ?>
                </h6>
                <!-- Enunciado removido -->
              </div>

              <a href="responder_questao.php?id=<?= $erro['id'] ?>" class="btn btn-sm btn-outline-primary">
                Revisar
              </a>
            </div>
          </div>
        <?php endwhile; ?>

      </div>
    </div>
  </div>

  <!-- HISTÓRICO RECENTE -->
  <div class="row mb-5">
    <div class="col-12">
      <div class="stats-card">
        <h4 class="mb-4" style="color: #2e3d2f;">🕐 Histórico Recente (Últimas 10 Questões)</h4>

        <?php while ($resp = mysqli_fetch_assoc($res_ultimas)): ?>
          <div class="historico-item <?= $resp['correta'] ? 'correto' : 'incorreto' ?>">
            <div class="d-flex justify-content-between align-items-start">
              <div class="flex-grow-1">

                <h6 class="mb-1">
                  <?= $resp['correta'] ? '✅' : '❌' ?>
                  <?= htmlspecialchars($resp['disciplina']) ?> - <?= htmlspecialchars($resp['conteudo']) ?>
                </h6>

                <!-- Enunciado removido -->

                <small class="text-muted">
                  Sua resposta: <strong><?= htmlspecialchars($resp['resposta_usuario']) ?></strong>
                  <?php if (!$resp['correta']): ?>
                    | Correta: <strong class="text-success"><?= htmlspecialchars($resp['resposta_correta']) ?></strong>
                  <?php endif; ?>
                </small><br>

                <small class="text-muted">
                  <?= date('d/m/Y H:i', strtotime($resp['data_resposta'])) ?>
                </small>

              </div>

              <a href="responder_questao.php?id=<?= $resp['id_questao'] ?>" 
                 class="btn btn-sm btn-outline-secondary ms-3">
                Ver
              </a>
            </div>
          </div>
        <?php endwhile; ?>                    

        <div class="text-center mt-4">
  <a href="historico_questoes_aluno.php" 
     class="btn btn-lg"
     style="
       background: linear-gradient(135deg, #495846, #6a9762);
       color: white;
       border-radius: 12px;
       padding: 12px 25px;
       font-weight: 600;
       box-shadow: 0 4px 8px rgba(0,0,0,0.15);
       transition: 0.3s ease;
       border: none;
     "
     onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.2)'"
     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)'">
      📄 Acessar Histórico Completo
  </a>
</div>

      </div>
    </div>
  </div>

<?php else: ?>

  <!-- ESTADO VAZIO -->
  <div class="empty-state">
    <div class="icon-circle icon-primary" style="width: 100px; height: 100px; font-size: 3rem;">
      📊
    </div>
    <h3 class="mt-4 mb-3">Você ainda não respondeu nenhuma questão!</h3>
    <p class="mb-4">Comece a responder questões para ver suas estatísticas e acompanhar seu progresso.</p>
    <a href="bancomain.php" class="btn btn-primary btn-lg" style="background-color: #495846; border-color: #495846;">
      Ir para o Banco de Questões
    </a>
  </div>

<?php endif; ?>

</div>

<?php include("footer.php"); ?>
</body>
</html>
