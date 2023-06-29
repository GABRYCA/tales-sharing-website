<?php
function validate_input($data): string
{
    // Trim any whitespace from the data
    $data = trim($data);
    // Strip any slashes from the data
    $data = stripslashes($data);
    // Convert any special characters to HTML entities
    $data = htmlspecialchars($data);
    // Return the sanitized data
    return $data;
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
?>