<?php
session_start();
if (!isset($_SESSION['nome'])) {
  header("Location: login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Simulados - Decola Militar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../stylesheet/navbar.css">
    <link rel="stylesheet" href="../stylesheet/footer.css">
    <link rel="stylesheet" href="../stylesheet/global.css">
    <link rel="stylesheet" href="../stylesheet/simulados_main.css">
</head>

<body>

<?php include("navbar.php") ?>

<!-- Fundo animado -->
<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="container mt-5 mb-5">

    <div class="titulo text-center">
        <h1>SIMULADOS</h1>
        <p class="subtitulo mt-3">
            Treine com simulados realistas para os principais vestibulares militares.
        </p>
    </div>

    <div class="row justify-content-center g-4 mt-4">

        <!-- CARD 1 -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="sim-card shadow-lg">
                <img src="./imgs/2.png" class="sim-img" alt="Simulado IME">
                <h5 class="sim-title">SIMULADO IME 2025.1.2</h5>
                <p class="sim-txt">Simulado completo estilo IME, com nível avançado e tempo controlado.</p>
                <a href="#" class="btn btn-simulado">COMEÇAR</a>
            </div>
        </div>

        <!-- CARD 2 -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="sim-card shadow-lg">
                <img src="./imgs/1.png" class="sim-img" alt="Simulado ITA">
                <h5 class="sim-title">SIMULADO ITA 2025.1.1</h5>
                <p class="sim-txt">Questões de alta dificuldade no estilo tradicional do ITA.</p>
                <a href="#" class="btn btn-simulado">COMEÇAR</a>
            </div>
        </div>

        <!-- CARD 3 -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="sim-card shadow-lg">
                <img src="./imgs/3.png" class="sim-img" alt="Simulado EN">
                <h5 class="sim-title">SIMULADO EN 2025.2.1</h5>
                <p class="sim-txt">Simulado atualizado do concurso da Escola Naval.</p>
                <a href="#" class="btn btn-simulado">COMEÇAR</a>
            </div>
        </div>

        <!-- CARD 4 -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="sim-card shadow-lg">
                <img src="./imgs/4.png" class="sim-img" alt="Simulado AFA">
                <h5 class="sim-title">SIMULADO AFA 2025.2.2</h5>
                <p class="sim-txt">Questões no estilo AFA com abordagem rápida e objetiva.</p>
                <a href="#" class="btn btn-simulado">COMEÇAR</a>
            </div>
        </div>

    </div>

</div>

<?php include("footer.php") ?>
</body>
</html>