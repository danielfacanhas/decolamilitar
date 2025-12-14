<?php
session_start();
if (!isset($_SESSION['nome'])) {
    header("Location: ../login.php");
    exit;
}

include("../config/db_connect.php");

$email_usuario = $_SESSION['email'] ?? '';

// Buscar todos os simulados ativos
$sql_simulados = "SELECT * FROM simulados WHERE ativo = 1 ORDER BY data_criacao DESC";
$res_simulados = mysqli_query($conn, $sql_simulados);

// Verificar simulados já realizados pelo usuário
$sql_realizados = "SELECT simulado_id FROM respostas_simulado WHERE email_usuario = '$email_usuario'";
$res_realizados = mysqli_query($conn, $sql_realizados);
$simulados_realizados = [];
while($row = mysqli_fetch_assoc($res_realizados)) {
  $simulados_realizados[] = $row['simulado_id'];
}

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
    <title>Simulados - Decola Militar</title>    
    <link rel="stylesheet" href="../stylesheet/navbar.css">
    <link rel="stylesheet" href="../stylesheet/footer.css">
    <link rel="stylesheet" href="../stylesheet/global.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
      .logo-vestibular {
        width: 80px;
        height: 80px;
        object-fit: contain;
        border-radius: 10px;
        background: white;
        padding: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      
      .simulado-card {
        border: 3px;
        border-radius: 15px;
        background: white;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        transition: all 0.3s ease;
        width: 75%;
        margin: 20px auto;
        font-family: "Montserrat", sans-serif;
      }
      
      .simulado-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 12px rgba(142, 209, 129, 0.33);
      }
      
      .simulado-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
        font-family: "Montserrat", sans-serif;
      }
      
      .simulado-titulo {
        font-size: 1.8rem;
        font-weight: bold;
        color: #2e3d2f;
      }

      

      .badge-status {
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
      }

      .badge-realizado {
        background: linear-gradient(135deg, #28a745, #5cb85c);
        color: white;
      }

      .badge-disponivel {
        background: linear-gradient(135deg, #17a2b8, #48b9db);
        color: white;
      }

      .simulado-info {
        display: flex;
        gap: 30px;
        margin: 20px 0;
        flex-wrap: wrap;
      }
      
      .info-item {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      
      .info-icon {
        font-size: 1.5rem;
      }
      
      .info-text {
        font-size: 0.95rem;
        color: #666;
      }
      
      .info-value {
        font-weight: bold;
        color: #2e3d2f;
      }

      .btn-actions {
        display: flex;
        gap: 15px;
        margin-top: 20px;
        flex-wrap: wrap;
      }

      .btn-comecar {
        background: linear-gradient(135deg, #495846, #6a9762) !important;
        color: white !important;
        border: none !important;
        padding: 12px 30px !important;
        border-radius: 25px !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
        flex: 1 !important;
      }

      .btn-comecar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(73, 88, 70, 0.3);
        color: white;
      }

      .btn-resultado {
        background: linear-gradient(135deg, #ffc107, #ffca2c);
        color: #000000ff !important;
        border: none !important;
        padding: 12px 30px !important;
        border-radius: 25px !important;
        font-weight: 600 !important;
        transition: all 0.3s ease !important;
      }

      .btn-resultado:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(255, 193, 7, 0.3);
        color: #333;
      }

      .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #999;
      }

      .empty-icon {
        font-size: 5rem;
        margin-bottom: 20px;
      }

      
    .info-table {
      background: white;
      border: 3px;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 4px 8px 0 rgba(119, 119, 119, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
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
    
    .badge-role {
      padding: 5px 12px;
      border-radius: 15px;
      font-weight: 600;
      font-size: 0.85rem;
    }
    
    .badge-admin {
      background: linear-gradient(135deg, #dc3545, #e57373);
      color: white;
    }
    
    .badge-aluno {
      background: linear-gradient(135deg, #17a2b8, #48b9db);
      color: white;
    }
    
    .badge-vestibular {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      background: linear-gradient(135deg, #6c757d, #868e96);
      color: white;
    }
    
    .log-item {
      padding: 12px;
      border-left: 3px solid #495846;
      background: #f8f9fa;
      border-radius: 5px;
      margin-bottom: 10px;
      transition: all 0.3s ease;
    }
    
    .log-item:hover {
      background: #e9ecef;
      transform: translateX(5px);
    }
    
    .log-acao {
      font-weight: bold;
      color: #495846;
    }
    
    .log-data {
      font-size: 0.85rem;
      color: #666;
    }
    
    </style>
</head>

<body>
<?php include("navbar.php"); ?>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="container">
  <div class="paragrafo">
    <div class="titulo">
      <h1>SIMULADOS</h1>
    </div>
    <h5 class="text-center mb-5">
      Teste seus conhecimentos com simulados completos dos principais vestibulares militares. 
      Baixe o PDF, responda no seu tempo e registre suas respostas no sistema!
    </h5>

    
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
  </div>
</div>


<div class="container mb-5">

  <?php if (mysqli_num_rows($res_simulados) > 0): ?>
    
    <?php while($simulado = mysqli_fetch_assoc($res_simulados)): ?>
      <?php $ja_realizou = in_array($simulado['id'], $simulados_realizados); ?>
      
      <div class="simulado-card">

        <div class="simulado-header">
          <div class="d-flex align-items-center gap-3">

            <?php if (!empty($simulado['logo_vestibular'])): ?>
              <img 
                src="../imgs/vestibulares/<?= htmlspecialchars($simulado['logo_vestibular']) ?>"
                alt="Logo <?= htmlspecialchars($simulado['vestibular']) ?>"
                class="logo-vestibular"
              >
            <?php endif; ?>

            <div>
              <div class="simulado-titulo"><?= htmlspecialchars($simulado['titulo']) ?></div>
              <?php if (!empty($simulado['vestibular'])): ?>
                <small class="text-muted">📚 <?= htmlspecialchars($simulado['vestibular']) ?></small>
              <?php endif; ?>
            </div>
          </div>

          <span class="badge-status <?= $ja_realizou ? 'badge-realizado' : 'badge-disponivel' ?>">
            <?= $ja_realizou ? '✅ Realizado' : '📝 Disponível' ?>
          </span>
        </div>

        <p class="text-muted"><?= htmlspecialchars($simulado['descricao']) ?></p>

        <div class="simulado-info">

          <div class="info-item">
            <span class="info-icon">⏱️</span>
            <div>
              <div class="info-text">Duração</div>
              <div class="info-value"><?= $simulado['duracao_minutos'] ?> minutos</div>
            </div>
          </div>

          <div class="info-item">
            <span class="info-icon">📝</span>
            <div>
              <div class="info-text">Questões</div>
              <div class="info-value"><?= $simulado['total_questoes'] ?> questões</div>
            </div>
          </div>

          <div class="info-item">
            <span class="info-icon">📅</span>
            <div>
              <div class="info-text">Criado em</div>
              <div class="info-value"><?= date('d/m/Y', strtotime($simulado['data_criacao'])) ?></div>
            </div>
          </div>

        </div>

        <div class="btn-actions">

          <?php if (!$ja_realizou): ?>
            <a href="fazer_simulado.php?id=<?= $simulado['id'] ?>" class="btn btn-comecar">
              COMEÇAR SIMULADO
            </a>
          <?php else: ?>
            <a href="resultado_simulado.php?id=<?= $simulado['id'] ?>" class="btn btn-resultado">
              Ver Resultado
            </a>
          <?php endif; ?>

        </div>

      </div>

    <?php endwhile; ?>

  <?php else: ?>

    <div class="empty-state">
      <div class="empty-icon">🎯</div>
      <h3>Nenhum simulado disponível no momento</h3>
      <p class="text-muted">Novos simulados serão adicionados em breve!</p>
    </div>

  <?php endif; ?>

</div>

<?php include("footer.php"); ?>

</body>
</html>