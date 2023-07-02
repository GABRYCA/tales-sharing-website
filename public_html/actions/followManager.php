<?php
// Check if logged in
session_start();
include_once (dirname(__FILE__) . "/../../private/objects/User.php");
include_once (dirname(__FILE__) . "/../common/utility.php");

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    exit("Invalid request method.");
}

// Check userId is not empty
if (empty($_POST["userId"])) {
    exit("UserId is empty");
}

// Load session user.
$user = new User();
$user->setUsername($_SESSION["username"]);
$user->loadUser();

// I receive the userId from the client
$userId = validate_input($_POST["userId"]);

// Create a new User object.
$followedUser = new User();
// Set the userId
$followedUser->setUsername($userId);
// Get the user from the database
$followedUser->loadUser();

// Check that followedUser is not the same as the user in the session
if ($followedUser->getUsername() == $user->getUsername()) {
    exit("You can't follow yourself");
}

if ($user->isFollowing($followedUser->getUsername())){
    // unfollow
    if (!$user->unfollowUser($followedUser->getUsername())) {
        exit("Error while unfollowing: " . $user->getErrorStatus());
    }
} else {
    // follow
    if (!$user->followUser($followedUser->getUsername())) {
        exit("Error while following: " . $user->getErrorStatus());
    }
}

// Return the number of followers, just the number
exit('' . $followedUser->getNumberOfFollowers());
?>