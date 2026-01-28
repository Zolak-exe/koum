<?php
/**
 * Mail Manager - NEXT DRIVE IMPORT
 * Handles structured email sending with SMTP support
 */

require_once __DIR__ . '/env.php';

class MailManager
{
    /**
     * Send a devis notification to the admin
     */
    public static function sendDevisNotification($data, $devis_id)
    {
        $admin_email = env('ADMIN_EMAIL', 'nextdriveimport@gmail.com');
        $site_url = env('SITE_URL', 'https://nextdriveimport.fr');

        $subject = "🚗 Nouveau Devis #$devis_id - " . $data['nom'];

        // HTML Template
        $html = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #e63946; border-bottom: 2px solid #e63946; padding-bottom: 10px;'>Nouvelle demande de devis</h2>
                
                <p>Une nouvelle demande vient d'être soumise sur <strong>Next Drive Import</strong>.</p>
                
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 10px; font-weight: bold; width: 150px;'>Client:</td>
                        <td style='padding: 10px;'>" . htmlspecialchars($data['nom']) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; font-weight: bold;'>Email:</td>
                        <td style='padding: 10px;'><a href='mailto:" . htmlspecialchars($data['email']) . "'>" . htmlspecialchars($data['email']) . "</a></td>
                    </tr>
                    <tr style='background-color: #f8f9fa;'>
                        <td style='padding: 10px; font-weight: bold;'>Téléphone:</td>
                        <td style='padding: 10px;'>" . htmlspecialchars($data['telephone']) . "</td>
                    </tr>
                </table>

                <h3 style='margin-top: 20px; color: #457b9d;'>Détails du véhicule</h3>
                <div style='background: #f1faee; padding: 15px; border-radius: 5px;'>
                    <p><strong>Modèle:</strong> " . htmlspecialchars($data['marque']) . " " . htmlspecialchars($data['modele']) . "</p>
                    <p><strong>Budget:</strong> " . number_format($data['budget'], 0, ',', ' ') . " €</p>
                    <p><strong>Année min:</strong> " . ($data['annee_min'] ?? 'Non spécifié') . "</p>
                    <p><strong>KM max:</strong> " . ($data['km_max'] ? number_format($data['km_max'], 0, ',', ' ') . ' km' : 'Non spécifié') . "</p>
                </div>

                <p style='margin-top: 15px;'><strong>Options / Commentaires:</strong><br>
                <span style='font-style: italic;'>" . nl2br(htmlspecialchars($data['commentaires'] ?: 'Aucun')) . "</span></p>

                <div style='margin-top: 30px; text-align: center;'>
                    <a href='$site_url/pages/admin-login.html' style='background-color: #e63946; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Accéder au Dashboard Admin</a>
                </div>
                
                <p style='margin-top: 30px; font-size: 12px; color: #888; text-align: center;'>
                    ID Devis: $devis_id | Date: " . date('d/m/Y H:i') . "
                </p>
            </div>
        </body>
        </html>
        ";

        return self::send($admin_email, $subject, $html, $data['email']);
    }

    /**
     * Core sending function
     */
    private static function send($to, $subject, $message, $replyTo = null)
    {
        $smtp_host = env('SMTP_HOST');
        $smtp_user = env('SMTP_USER');
        $smtp_pass = env('SMTP_PASS');
        $smtp_port = env('SMTP_PORT', 587);

        // Si on a les identifiants SMTP, on tente un envoi direct via sockets
        if ($smtp_host && $smtp_user && $smtp_pass) {
            $smtpSent = self::sendViaSmtp($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $to, $subject, $message, $replyTo);
            if ($smtpSent)
                return true;
        }

        // Sinon on tente l'API Brevo si configurée
        $api_key = env('BREVO_API_KEY');
        if ($api_key && strpos($api_key, 'xkeysib-') === 0) {
            return self::sendViaBrevoApi($to, $subject, $message, $replyTo);
        }

        // En dernier recours : mail() natif (souvent bloqué sur Render)
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Next Drive Import <nextdriveimport@gmail.com>" . "\r\n";
        if ($replyTo)
            $headers .= "Reply-To: $replyTo" . "\r\n";

        $mailSent = @mail($to, $subject, $message, $headers);

        if (!$mailSent) {
            self::logFailedEmail($to, $subject, $message, "Native mail() failed and no SMTP/API working");
        }

        return $mailSent;
    }

    /**
     * Simple SMTP Client implementation using sockets
     * Designed to work on Render without PHPMailer
     */
    private static function sendViaSmtp($host, $port, $user, $pass, $to, $subject, $htmlContent, $replyTo = null)
    {
        try {
            $socket = fsockopen($host, $port, $errno, $errstr, 10);
            if (!$socket)
                throw new Exception("Connexion impossible: $errstr ($errno)");

            $getResponse = function ($socket) {
                $response = "";
                while ($line = fgets($socket, 515)) {
                    $response .= $line;
                    if (substr($line, 3, 1) == " ")
                        break;
                }
                return $response;
            };

            $sendCommand = function ($socket, $cmd) use ($getResponse) {
                fputs($socket, $cmd . "\r\n");
                return $getResponse($socket);
            };

            $getResponse($socket); // Banner
            $sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $sendCommand($socket, "STARTTLS");

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Échec de la négociation TLS");
            }

            $sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $auth = $sendCommand($socket, "AUTH LOGIN");
            $sendCommand($socket, base64_encode($user));
            $passResult = $sendCommand($socket, base64_encode($pass));

            if (strpos($passResult, '235') === false) {
                throw new Exception("Échec de l'authentification SMTP: " . $passResult);
            }

            $sendCommand($socket, "MAIL FROM:<nextdriveimport@gmail.com>");
            $sendCommand($socket, "RCPT TO:<$to>");
            $sendCommand($socket, "DATA");

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Next Drive Import <nextdriveimport@gmail.com>\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            if ($replyTo)
                $headers .= "Reply-To: <$replyTo>\r\n";
            $headers .= "\r\n";

            $sendCommand($socket, $headers . $htmlContent . "\r\n.");
            $sendCommand($socket, "QUIT");
            fclose($socket);

            return true;
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            self::logFailedEmail($to, $subject, $htmlContent, $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using Brevo REST API
     */
    private static function sendViaBrevoApi($to, $subject, $htmlContent, $replyTo = null)
    {
        $api_url = 'https://api.brevo.com/v3/smtp/email';
        $api_key = env('BREVO_API_KEY');

        $data = [
            'sender' => ['name' => 'Next Drive Import', 'email' => 'nextdriveimport@gmail.com'],
            'to' => [['email' => $to]],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        if ($replyTo) {
            $data['replyTo'] = ['email' => $replyTo];
        }

        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n" .
                    "api-key: $api_key\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($api_url, false, $context);

        if ($result === FALSE) {
            $error = "Brevo API Connection Error";
            error_log($error);
            self::logFailedEmail($to, $subject, $htmlContent, $error);
            return false;
        }

        $responseCode = $http_response_header[0] ?? '';
        if (strpos($responseCode, '201') === false && strpos($responseCode, '200') === false) {
            $error = "Brevo API Error ($responseCode): " . $result;
            error_log($error);
            self::logFailedEmail($to, $subject, $htmlContent, $error);
            return false;
        }

        return true;
    }

    /**
     * Log failed email for manual recovery
     */
    private static function logFailedEmail($to, $subject, $message, $errorDetail = null)
    {
        $logDir = __DIR__;
        $emailFile = $logDir . '/pending_emails_' . date('Y-m-d') . '.txt';

        $logContent = "\n" . str_repeat('=', 60) . "\n";
        $logContent .= "[" . date('Y-m-d H:i:s') . "] FAILED EMAIL NOTIFICATION\n";
        if ($errorDetail) {
            $logContent .= "Error: $errorDetail\n";
        }
        $logContent .= "To: $to\n";
        $logContent .= "Subject: $subject\n";
        $logContent .= str_repeat('-', 60) . "\n";
        $logContent .= strip_tags($message) . "\n";
        $logContent .= str_repeat('=', 60) . "\n";

        @file_put_contents($emailFile, $logContent, FILE_APPEND | LOCK_EX);
    }
}
