<?php
// verificar_admin.php
// Este arquivo deve ser incluído no topo de todas as páginas administrativas

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Verificar se está logado
if (!isset($_SESSION['nome'])) {
  header("Location: ../login.php");
  exit;
}

// Verificar se é admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'colaborador') {
  // Redirecionar para página de acesso negado
  header("Location: acesso_negado.php");
  exit;
}

// Se chegou aqui, é admin e pode continuar
?>