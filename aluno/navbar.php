<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nome = isset($_SESSION['nome']) ? $_SESSION['nome'] : "Usuário";
$foto = "";

if (isset($_SESSION['foto']) && $_SESSION['foto'] !== "") {
    $foto = $_SESSION['foto'];
}

$padrao = "padrao.webp";

if ($foto == "") {
    $foto_web = "../perfil/" . $padrao;
} else {
    $foto_web = "../perfil/" . $foto;
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.navbar .dropdown-menu {
  z-index: 3000 !important;
  min-width: 10rem;
}

.navbar .dropdown-toggle {
  cursor: pointer;
}

.navbar .dropdown-menu.show {
  display: block !important;
}

.navbar .profile-img {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 50%;
  border: 2px solid rgba(0,0,0,0.08);
}

.aluno-badge {
  background: linear-gradient(135deg, #85d366ff, #2f6126ff);
  color: white;
  padding: 4px 12px;
  border-radius: 15px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 8px;
}
</style>

<!-- PRELOADER (cole no topo do navbar.php, logo após <body>) -->
<div id="site-preloader" aria-hidden="true">
  <div class="preloader-inner" role="status" aria-label="Carregando">
    <!-- opcional: sua logo -->
    <img src="/imgs/logo-pequena.png" alt="" class="preloader-logo" onerror="this.style.display='none'">
    <div class="preloader-spinner" aria-hidden="true"></div>
    <div class="preloader-label">Carregando...</div>
  </div>
</div>
<!-- Fim preloader -->
 
<nav class="navbar navbar-expand-lg navbar-light bg-light px-3">
  <div class="container-fluid">

    <a class="navbar-brand" href="pagina1.php">
      <img src="../imgs/logo.png" width="90" height="90" alt="Logo">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText"
      aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarText">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="bancomain.php">Banco de Questões</a></li>
        <li class="nav-item"><a class="nav-link" href="simulados_main.php">Simulados</a></li>
        <li class="nav-item"><a class="nav-link" href="historico_aluno.php">Meu Desempenho</a></li>
      </ul>

      <div class="d-flex align-items-center">
        <span class="navbar-text me-2">
          Seja bem-vindo, <strong><?php echo htmlspecialchars($nome); ?></strong>
          <span class="aluno-badge">Aluno</span>
        </span>

        <div class="dropdown">
          <a id="perfilDropdown" class="nav-link dropdown-toggle d-flex align-items-center gap-2"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
             
            <img src="<?php echo htmlspecialchars($foto_web); ?>" 
                 alt="Foto de perfil"
                 class="profile-img"
                 onerror="this.src='../perfil/padrao.webp'">

          </a>

          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="perfilDropdown">
            <li><a class="dropdown-item" href="configuracoes.php">Configurações</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../config/logout.php">Sair</a></li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>