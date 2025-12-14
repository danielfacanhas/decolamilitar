<?php
session_start();
require_once "./config/db_connect.php";

// Validação dos dados
$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$nova_senha = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
$confirmar_senha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';

if ($token === '') {
    header("Location: esqueci_senha.php?error=Token inválido.");
    exit;
}

if (strlen($nova_senha) < 8) {
    header("Location: redefinir_senha.php?token=$token&error=A senha deve ter no mínimo 8 caracteres.");
    exit;
}

if ($nova_senha !== $confirmar_senha) {
    header("Location: redefinir_senha.php?token=$token&error=As senhas não conferem.");
    exit;
}

// Verifica se o token existe
$query = "SELECT id, token_expira_em FROM usuarios WHERE token_recuperacao = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    $usuario = mysqli_fetch_assoc($resultado);
    $id_usuario = $usuario['id'];
    $token_expira = $usuario['token_expira_em'];

    // Verifica expiração
    if (strtotime($token_expira) < time()) {
        mysqli_stmt_close($stmt);
        header("Location: esqueci_senha.php?error=Link expirado. Solicite um novo.");
        exit;
    }
} else {
    mysqli_stmt_close($stmt);
    header("Location: esqueci_senha.php?error=Link inválido ou expirado.");
    exit;
}

mysqli_stmt_close($stmt);

// Hash da nova senha
$senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

// Atualiza senha + limpa token
$update = "UPDATE usuarios 
           SET senha = ?, token_recuperacao = NULL, token_expira_em = NULL 
           WHERE id = ?";
$stmt = mysqli_prepare($conn, $update);
mysqli_stmt_bind_param($stmt, "si", $senha_hash, $id_usuario);
$sucesso = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Redirecionar corretamente
if ($sucesso) {
    $_SESSION['senha_redefinida'] = true; // mensagem de sucesso via sessão
    header("Location: login.php");
    exit;
} else {
    header("Location: redefinir_senha.php?token=$token&error=Erro ao redefinir senha. Tente novamente.");
    exit;
}
?>
