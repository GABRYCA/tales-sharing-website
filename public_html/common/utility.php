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
?>