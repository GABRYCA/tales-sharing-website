<?php
session_start();

// Check if logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST["action"])) {
        // Error message
        exit("Action is empty. Please select a valid action.");
    }

    $action = validate_input($_POST["action"]);
    $username = validate_input($_POST["username"]);
    $gender = validate_input($_POST["gender"]);
    $motto = validate_input($_POST["motto"]);
    $description = validate_input($_POST["description"]);
    $showNSFW = validate_input($_POST["showNSFW"]);
    $oldPassword = validate_input($_POST["oldPassword"]);
    $newPassword = validate_input($_POST["newPassword"]);
    $email = validate_input($_POST["email"]);

    $user = new User();
    $user->setUsername($_SESSION["username"]);
    if (!$user->loadUser()) {
        // Error message
        exit("Failed to load user. Please try again.");
    }

    switch ($action){

        case "updateProfile": {

            // Check if the username is empty
            if (!$username) {
                // Error message
                exit("Username is empty. Please enter a username.");
            }

            // Check if the username is too short
            if (strlen($username) < 3) {
                // Error message
                exit("Username is too short. Please enter a username with at least 3 characters.");
            }

            // Check if the username is too long
            if (strlen($username) > 255) {
                // Error message
                exit("Username is too long. Please enter a username with at most 255 characters.");
            }

            // Check if the username is already taken
            // TODO: changeUsername() function that also checks if the username is already taken and ignores it if it is the same as the current username.

            // TODO: changeGender() function that changes the gender if it is different than the current one

            // TODO: changeMotto() function that changes the motto if it is different than the current one

            // TODO: changeDescription() function that changes the description if it is different than the current one

            // TODO: changeShowNSFW() function that changes the showNSFW if it is different than the current one

            // TODO: changeEmail() function that changes the email if it is different than the current one, and also checks if the email is already taken, and sends a confirmation email to the old and new email address. If confirmed, the email is changed, if not, the email is not changed.
            // THIS REQUIRES A DB CHANGE TO HANDLE THE EMAIL CONFIRMATION TOKENS AND STATUS.

            break;
        }

        case "updatePassword": {

            // Check if the old password is empty
            if (!$oldPassword) {
                // Error message
                exit("Old password is empty. Please enter your old password.");
            }

            // Check if the new password is empty
            if (!$newPassword) {
                // Error message
                exit("New password is empty. Please enter your new password.");
            }

            // Check if the new password is too short
            if (strlen($newPassword) < 8) {
                // Error message
                exit("New password is too short. Please enter a password with at least 8 characters.");
            }

            // Check if the new password is too long
            if (strlen($newPassword) > 255) {
                // Error message
                exit("New password is too long. Please enter a password with at most 255 characters.");
            }

            // Check if the old password is correct
            if (!$user->changePassword($oldPassword, $newPassword)) {
                // Error message
                exit($user->getErrorStatus());
            }

            // Success message
            exit("Password changed successfully.");
        }

        default: {
            // Error message
            exit("Invalid action. Please select a valid action.");
        }

    }

} else {
    header("Location: ../home.php");
    exit();
}
