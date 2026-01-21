<?php

 
 
 

function send_simple_email($to, $subject, $htmlMessage) {
    $to = trim($to);
    if (empty($to)) return false;

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Grand Hostel <no-reply@grandhostel.local>\r\n";

    $ok = false;
    try {
        $ok = @mail($to, $subject, $htmlMessage, $headers);
    } catch (Exception $e) {
        $ok = false;
    }

    if ($ok) return true;

     
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/emails.log';
    $line = "----\nTO: " . $to . "\nSUBJECT: " . $subject . "\nDATE: " . date('Y-m-d H:i:s') . "\nMESSAGE:\n" . strip_tags($htmlMessage) . "\n";
    @file_put_contents($logFile, $line, FILE_APPEND);
    return false;
}

?>

