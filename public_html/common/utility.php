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

function convert_url_to_bgimage_css($url) {
    // Use rawurldecode to preserve the plus sign
    $decoded_url = rawurldecode($url);
    // Use single quotes to avoid escaping characters
    $css_url = "background-image: url('$decoded_url')";
    return $css_url;
}

function convert_url_to_data_background_css($url) {
    // Use rawurldecode to preserve the plus sign
    $decoded_url = rawurldecode($url);
    // Use single quotes to avoid escaping characters
    $css_url = "data-background='$decoded_url'";
    return $css_url;
}

function encode_url($url) {
    // Use str_replace to replace the invalid characters with their conversions
    $encoded_url = str_replace([' ', '(', ')'], ['%20', '%28', '%29'], $url);
    // Return the encoded URL
    return $encoded_url;
}
?>