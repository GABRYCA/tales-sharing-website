<?php
// Check if logged in
session_start();
include_once (dirname(__FILE__) . "/../../private/objects/User.php");
include_once (dirname(__FILE__) . "/../../private/objects/Content.php");
include_once (dirname(__FILE__) . "/../common/utility.php");

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("Invalid request method.");
}

// Check contentId is not empty
if (empty($_POST["contentId"])) {
    exit("ContentId is empty");
}

// Load session user.
$user = new User();
$user->setUsername($_SESSION["username"]);
$user->loadUser();

// I receive the contentId from the client
$contentId = validate_input($_POST["contentId"]);
// Create a new Content object.
$content = new Content();
// Set the contentId
$content->setContentId($contentId);
// Get the content from the database
$content->loadContent();

if ($content->getErrorStatus() != "") {
    exit("Error while loading content (an error occurred): " . $content->getErrorStatus());
}

// Check if content is private, if it's, check if the content owner is the same as the user in the session
if ($content->getIsPrivate() && $content->getOwnerId() != $user->getUsername()) {
    exit("You don't have permission to view this content");
}

// Each time you call this manager, it will reverse a like (if liked, will remove it, if not, will add it)
if (!$content->reverseLike($user->getUsername())) {
    exit("Error while reversing like: " . $content->getErrorStatus());
}

// Return the number of likes, just the number
exit('' . $content->getNumberOfLikes());
?>