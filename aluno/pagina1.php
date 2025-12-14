<?php
session_start();
if (!isset($_SESSION['nome'])) {
    header("Location: ../login.php");
    exit;
}

include("../config/db_connect.php");

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Decola Militar — Plataforma de Aprovação Militar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Estilos do projeto -->
  <link rel="stylesheet" href="../stylesheet/footer.css">
  <link rel="stylesheet" href="../stylesheet/global.css">
  <link rel="stylesheet" href="../stylesheet/navbar.css">

  <style>
    /* ===== CARDS SECUNDÁRIOS ===== */
    .secao-cards {
      padding: 60px 20px;
    }

    .card-concurso {
      border-radius: 20px;
      overflow: hidden;
      transition: 0.3s;
      border: 2px solid #495846;
    }

    .card-concurso:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    .card-concurso img {
      height: 220px;
      object-fit: cover;
    }

    .secao-titulo {
      text-align: center;
      margin-bottom: 40px;
      font-size: 2.2rem;
      color: #2e3d2f;
      font-weight: bold;
    }

    /* ===== VANTAGENS ===== */
    .vantagem-box {
      background: #f7f7f7;
      border-radius: 15px;
      padding: 25px;
      border-left: 6px solid #495846;
      transition: 0.3s;
    }

    .vantagem-box:hover {
      transform: translateX(5px);
      background: #eef0ee;
    }

    /* ===== DEPOIMENTOS ===== */
    .depoimento {
      background: white;
      padding: 25px;
      border-radius: 15px;
      text-align: center;
      border: 2px solid #cdd3c9;
    }

    .depoimento img {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      margin-bottom: 15px;
      border: 3px solid #495846;
    }

    /* ===== CTA FINAL ===== */
    .cta-final {
      background: linear-gradient(135deg, #2d3a30, #1b241d);
      color: white;
      padding: 70px 20px;
      text-align: center;
    }

    .cta-final .btn {
      padding: 15px 35px;
      background: white;
      color: #1e2a21;
      font-size: 1.2rem;
      border-radius: 40px;
    }
  </style>
</head>
<body>
<?php include("navbar.php"); ?>
<!-- CARROSSEL -->
<div id="carouselExampleDark" class="carousel carousel-dark slide" data-bs-ride="carousel">
  <div class="carousel-inner">

    <div class="carousel-item active" data-bs-interval="3000">
      <img src="../imgs/slide1.png" class="d-block w-100" alt="slide 1">
      <div class="carousel-caption d-none d-md-block text-light">
        <h5>Instituto Tecnológico de Aeronáutica</h5>
        <p>Formação de excelência para engenheiros do futuro.</p>
      </div>
    </div>

    <div class="carousel-item" data-bs-interval="3000">
      <img src="../imgs/slide2.png" class="d-block w-100" alt="slide 2">
      <div class="carousel-caption d-none d-md-block text-light">
        <h5>Instituto Militar de Engenharia</h5>
        <p>Conhecimento que transforma o Exército e o Brasil.</p>
      </div>
    </div>

    <div class="carousel-item">
      <img src="../imgs/slide3.png" class="d-block w-100" alt="slide 3">
      <div class="carousel-caption d-none d-md-block text-light">
        <h5>Escola Naval</h5>
        <p>Tradição e disciplina no preparo dos oficiais da Marinha.</p>
      </div>
    </div>

  </div>

  <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleDark" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleDark" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div>
<!-- SEÇÃO DE CARDS -->
<section class="secao-cards container">
  <div class="row g-4">

    <div class="col-md-4">
      <div class="card card-concurso">
        <img src="../imgs/vestibulares/ita.png" class="card-img-top">
        <div class="card-body">
          <h5 class="card-title">ITA — Aeronáutica</h5>
          <p class="card-text">O vestibular mais desafiador do Brasil, agora ao seu alcance.</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-concurso">
        <img src="../imgs/vestibulares/ime.png" class="card-img-top">
        <div class="card-body">
          <h5 class="card-title">IME — Engenharia Militar</h5>
          <p class="card-text">Excelência acadêmica unida à formação militar.</p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-concurso">
        <img src="../imgs/vestibulares/en.png" class="card-img-top">
        <div class="card-body">
          <h5 class="card-title">Escola Naval</h5>
          <p class="card-text">A entrada para uma carreira brilhante na Marinha do Brasil.</p>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- VANTAGENS -->
<section class="container my-5">
  <h2 class="secao-titulo">Por que estudar com a gente?</h2>

  <div class="row g-4">

    <div class="col-md-6">
      <div class="vantagem-box">
        <h5>📚 Banco de Questões Completo</h5>
        <p>Milhares de questões de provas anteriores atualizadas constantemente.</p>
      </div>
    </div>

    <div class="col-md-6">
      <div class="vantagem-box">
        <h5>⏳ Simulados com Timer</h5>
        <p>Prepare-se no mesmo ritmo e pressão da prova real.</p>
      </div>
    </div>

    <div class="col-md-6">
      <div class="vantagem-box">
        <h5>📈 Estatísticas Detalhadas</h5>
        <p>Acompanhe seu desempenho, acertos por disciplina e muito mais.</p>
      </div>
    </div>

    <div class="col-md-6">
      <div class="vantagem-box">
        <h5>🎧 Suporte Premium</h5>
        <p>Equipe disponível para ajudar a qualquer momento.</p>
      </div>
    </div>

  </div>
</section>


<!-- DEPOIMENTOS -->
<section class="container my-5">
  <h2 class="secao-titulo">O que nossos alunos dizem</h2>

  <div class="row g-4">

    <div class="col-md-4">
      <div class="depoimento">
        <img src="imgs/user1.jpg">
        <p>"A plataforma mudou completamente minha preparação. Hoje estou aprovado no IME!"</p>
        <strong>— João Henrique</strong>
      </div>
    </div>

    <div class="col-md-4">
      <div class="depoimento">
        <img src="imgs/user2.jpg">
        <p>"Melhor banco de questões do Brasil. Simulados idênticos à prova real!"</p>
        <strong>— Ana Vitória</strong>
      </div>
    </div>

    <div class="col-md-4">
      <div class="depoimento">
        <img src="imgs/user3.jpg">
        <p>"As estatísticas foram essenciais para eu identificar meus pontos fracos."</p>
        <strong>— Matheus Silva</strong>
      </div>
    </div>

  </div>
</section>


<!-- CTA FINAL -->
<section class="cta-final">
  <h2>Pronto para iniciar sua aprovação?</h2>
  <p style="margin-top:10px; font-size:1.2rem;">Comece agora mesmo gratuitamente!</p>
  <a href="bancomain.php" class="btn btn-light mt-3">Acessar Plataforma</a>
</section>

<?php include("footer.php"); ?>
</body>
</html>
