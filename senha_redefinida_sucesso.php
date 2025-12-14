<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senha Redefinida - Decola Militar</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Seus estilos -->
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/login.css">

    <style>
        /* Estética especial para a página */
        .success-card {
            background: #ffffffcc;
            backdrop-filter: blur(6px);
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0px 8px 20px #00000020;
            text-align: center;
            max-width: 450px;
            width: 100%;
            animation: fadeIn 0.8s ease-out;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            color: #28a745;
            margin-bottom: 20px;
            animation: pop 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pop {
            0% { transform: scale(0.4); }
            70% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

    <!-- Fundo animado -->
    <div class="bg"></div>
    <div class="bg bg2"></div>
    <div class="bg bg3"></div>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <div class="success-card">

            <svg xmlns="http://www.w3.org/2000/svg" 
                 fill="currentColor" 
                 class="bi bi-check-circle-fill success-icon" 
                 viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.03 10.97l-2.47-2.47a.75.75 
                         0 1 1 1.06-1.06l1.41 1.41 4.47-4.47a.75.75 
                         0 0 1 1.06 1.06l-5 5a.75.75 0 0 1-1.06 0z"/>
            </svg>

            <h2 class="titulo mb-3">Senha alterada com sucesso!</h2>

            <p class="mb-4" style="font-size: 1.1rem; color: #444;">
                Sua senha foi redefinida. Agora você já pode acessar sua conta normalmente.
            </p>

            <a href="../index.php" class="btn btn-login w-100">
                Ir para o Login
            </a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
