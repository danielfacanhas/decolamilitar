<?php
session_start();
require_once "./config/db_connect.php";
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';
require './PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Validação do e-mail
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email === '') {
    header("Location: esqueci_senha.php?error=Digite seu e-mail.");
    exit;
}

// Validação de formato de e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: esqueci_senha.php?error=E-mail inválido.");
    exit;
}

// Verifica se o e-mail existe no banco (usando prepared statement)
$query = "SELECT id, nome, email FROM usuarios WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    $usuario = mysqli_fetch_assoc($resultado);
    $id_usuario = $usuario['id'];
    $nome = $usuario['nome'];
    $encontrou = true;
} else {
    $encontrou = false;
}

mysqli_stmt_close($stmt);

// Por segurança, sempre mostra a mesma mensagem
if (!$encontrou) {
    header("Location: esqueci_senha.php?success=1");
    exit;
}

// Gera um token único e seguro
$token = bin2hex(random_bytes(32));
$expira_em = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token válido por 1 hora

// Salva o token no banco
$update = "UPDATE usuarios SET token_recuperacao = ?, token_expira_em = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $update);
mysqli_stmt_bind_param($stmt, "ssi", $token, $expira_em, $id_usuario);
$update_success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$update_success) {
    header("Location: esqueci_senha.php?error=Erro ao processar solicitação. Tente novamente.");
    exit;
}

// Detecta a URL base automaticamente
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
$base_url = $protocol . "://" . $host . $path;
$base_url = rtrim($base_url, '/\\');

// Link de recuperação
$link = $base_url . "/redefinir_senha.php?token=" . $token;

// Envia o e-mail com PHPMailer
try {
    $mail = new PHPMailer(true);
    
    // Configurações do servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // Use seu servidor SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = 'daniel.2023318294@aluno.iffar.edu.br'; // *** ALTERE AQUI ***
    $mail->Password   = 'jpur gviv yzsg duqa';        // *** ALTERE AQUI *** (senha de app do Gmail)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    
    // Opções SSL
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Remetente e destinatário
    $mail->setFrom('noreply@decolamilitar.com', 'Decola Militar');
    $mail->addAddress($email, $nome);
    
    // Conteúdo do e-mail
    $mail->isHTML(true);
    $mail->Subject = 'Recuperação de Senha - Decola Militar';
    
    $mail->Body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #315a2c; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 12px 30px; background-color: #315a2c; color: white; text-decoration: none; border-radius: 25px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Recuperação de Senha</h2>
            </div>
            <div class='content'>
                <p>Olá, <strong>{$nome}</strong>!</p>
                <p>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Decola Militar</strong>.</p>
                <p>Clique no botão abaixo para criar uma nova senha:</p>
                <p style='text-align: center;'>
                    <a href='{$link}' class='button'>Redefinir Senha</a>
                </p>
                <p>Ou copie e cole o link abaixo no seu navegador:</p>
                <p style='word-break: break-all; color: #315a2c;'>{$link}</p>
                <p><strong>Atenção:</strong> Este link é válido por 1 hora.</p>
                <p>Se você não solicitou esta recuperação, ignore este e-mail.</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " Decola Militar - Todos os direitos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $mail->AltBody = "Olá {$nome},\n\nRecebemos uma solicitação para redefinir sua senha.\n\nClique no link abaixo para criar uma nova senha:\n{$link}\n\nEste link é válido por 1 hora.\n\nSe você não solicitou esta recuperação, ignore este e-mail.\n\nAtenciosamente,\nEquipe Decola Militar";
    
    $mail->send();
    header("Location: esqueci_senha.php?success=1");
    exit;
    
} catch (Exception $e) {
    error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
    header("Location: esqueci_senha.php?error=Erro ao enviar e-mail. Tente novamente mais tarde.");
    exit;
}
?>