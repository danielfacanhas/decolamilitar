<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acesso Negado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="login.css">
  
  <style>
    .acesso-negado {
      text-align: center;
      padding: 80px 20px;
    }
    
    .icone-negado {
      font-size: 8rem;
      color: #dc3545;
      margin-bottom: 30px;
      animation: shake 0.5s;
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }
    
    .btn-voltar {
      background: linear-gradient(135deg, #495846, #6a9762);
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 25px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      margin-top: 30px;
    }
    
    .btn-voltar:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(73, 88, 70, 0.3);
      color: white;
    }
  </style>
</head>
<body>

<div class="bg"></div>
<div class="bg bg2"></div>
<div class="bg bg3"></div>

<div class="container">
  <div class="acesso-negado">
    <div class="icone-negado">🚫</div>
    <h1 class="mb-3" style="color: #dc3545; font-weight: bold;">ACESSO NEGADO</h1>
    <p class="lead text-muted mb-4">
      Você não tem permissão para acessar esta área.<br>
      Esta página é restrita apenas para administradores.
    </p>
    
    <div class="alert alert-warning d-inline-block">
      <strong>⚠️ Atenção:</strong> Se você acredita que deveria ter acesso, entre em contato com o administrador do sistema.
    </div>
    
    <div class="mt-4">
      <a href="pagina1.php" class="btn btn-voltar">
        ← Voltar para Página Inicial
      </a>
    </div>
    
    <?php if (isset($_SESSION['nome'])): ?>
    <div class="mt-4 text-muted">
      <small>Logado como: <strong><?= htmlspecialchars($_SESSION['nome']) ?></strong></small>
    </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>