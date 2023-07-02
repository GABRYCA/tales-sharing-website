<?php
session_start();
include_once(dirname(__FILE__) . "/../../private/objects/User.php");
include_once(dirname(__FILE__) . "/../../private/objects/Notification.php");
include_once (dirname(__FILE__) . "/../common/utility.php");

// Check if user logged in.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("Invalid request method.");
}

if (!empty($_POST["delete"])){

    if (validate_input($_POST["delete"]) != "true") {
        exit("Is this false or invalid?");
    }

    $notification = new Notification();
    $notification->setUserId($_SESSION["username"]);
    if ($notification->deleteAllNotificationsOfUser()){
        exit("success");
    } else {
        exit("error");
    }
}

if (empty($_POST["read"])) {
    exit("Read boolean value is empty..");
}

$read = validate_input($_POST["read"]);

if ($read != "true" && $read != "false") {
    exit("Read boolean value is invalid..");
}

$notification = new Notification();
$notification->setViewed($read);
$notification->setUserId($_SESSION["username"]);

if ($notification->setAllNotificationsAsViewed()) {
    exit("success");
} else {
    exit("error");
}

?>