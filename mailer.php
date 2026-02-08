<?php
function sendSMTPEmail($to, $subject, $message) {
    $smtpHost = 'smtp.gmail.com';
    $smtpPort = 587;
    $smtpUsername = 'wenbusale383@gmail.com';
    $smtpPassword = 'gllr irpd caku blzy';
    $from = 'wenbusale383@gmail.com';

    try {
        $fp = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
        if (!$fp) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }

        // Read greeting
        fgets($fp, 515);

        // Send EHLO
        fputs($fp, "EHLO $smtpHost\r\n");
        while ($line = fgets($fp, 515)) {
            if (substr($line, 3, 1) == ' ') break;
        }

        // Start TLS
        fputs($fp, "STARTTLS\r\n");
        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("STARTTLS failed: $response");
        }

        // Enable crypto
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        // Send EHLO again
        fputs($fp, "EHLO $smtpHost\r\n");
        while ($line = fgets($fp, 515)) {
            if (substr($line, 3, 1) == ' ') break;
        }

        // Authenticate
        fputs($fp, "AUTH LOGIN\r\n");
        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '334') {
            throw new Exception("AUTH LOGIN failed: $response");
        }

        fputs($fp, base64_encode($smtpUsername) . "\r\n");
        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '334') {
            throw new Exception("Username failed: $response");
        }

        fputs($fp, base64_encode($smtpPassword) . "\r\n");
        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '235') {
            throw new Exception("Password failed: $response");
        }

        // Send mail
        fputs($fp, "MAIL FROM: <$from>\r\n");
        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("MAIL FROM failed: $response");
        }

        fputs($fp, "RCPT TO: <$to>\r\n");
        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("RCPT TO failed: $response");
        }

        fputs($fp, "DATA\r\n");
        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '354') {
            throw new Exception("DATA failed: $response");
        }

        // Send headers and message
        fputs($fp, "Subject: $subject\r\n");
        fputs($fp, "To: $to\r\n");
        fputs($fp, "From: $from\r\n");
        fputs($fp, "MIME-Version: 1.0\r\n");
        fputs($fp, "Content-type: text/html; charset=UTF-8\r\n");
        fputs($fp, "\r\n");
        fputs($fp, $message);
        fputs($fp, "\r\n.\r\n");

        $response = fgets($fp, 515);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("Message send failed: $response");
        }

        fputs($fp, "QUIT\r\n");
        fgets($fp, 515);

        fclose($fp);
        return true;

    } catch (Exception $e) {
        // Log the error
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}
?>