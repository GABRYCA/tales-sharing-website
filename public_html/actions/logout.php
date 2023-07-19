<?php
// Logout user
session_start();

if (isset($_SESSION["token"])) {
    include_once(dirname(__FILE__) . "/../../private/configs/googleConfig.php");
    // Create google config object
    $googleConfig = new googleConfig();
    // Get client.
    $client = $googleConfig->getClient();
    // Set access token
    $client->setAccessToken($_SESSION["token"]);
    // Revoke token
    $client->revokeToken($_SESSION["token"]);
}

session_unset();
session_destroy();
header("Location: ../login.php");
exit();
?>
