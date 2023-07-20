<?php
include_once (dirname(__FILE__) . "/../../private/objects/User.php");
include_once (dirname(__FILE__) . "/../common/utility.php");
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


    $user = new User();
    $user->setUsername($_SESSION["username"]);
    if (!$user->loadUser()) {
        // Error message
        exit("Failed to load user. Please try again.");
    }

    switch ($action){

        case "updateProfile": {

            $username = validate_input($_POST["username"]);
            $gender = validate_input($_POST["gender"]);
            $motto = validate_input($_POST["motto"]);
            $description = validate_input($_POST["description"]);
            $showNSFW = validate_input($_POST["showNSFW"]);
            $email = validate_input($_POST["email"]);

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

            // Change username action. If the username is the same as the current one, it will not be changed.
            if (!$user->changeUsername($username)){
                // Error message
                exit($user->getErrorStatus());
            } else {
                // Update the session username
                $_SESSION["username"] = $username;
                // Reload user in session just to be safe (if something goes wrong, something has broke).
                $user->setUsername($username);
                if (!$user->loadUser()) {
                    // Destroy session.
                    session_destroy();
                    // Send to login page and send error.
                    header("Location: ../login.php");
                    // Error message
                    exit("Failed to load user. Please try again.");
                }
            }

            // Check if gender selected is valid (male, female, unspecified)
            if (!$gender){
                exit("Gender is empty!");
            }

            if ($gender != "male" && $gender != "female" && $gender != "unspecified"){
                exit("Gender not valid: " . $gender);
            }

            // Change gender action.
            if (!$user->changeGender($gender)){
                // Error message
                exit($user->getErrorStatus());
            }

            if (!$motto){
                $motto = "";
            }

            if (strlen($motto) > 255){
                exit("Motto is too long. Please enter a motto with at most 255 characters.");
            }

            // Change motto action.
            if (!$user->changeMotto($motto)){
                // Error message
                exit($user->getErrorStatus());
            }

            if (!$description){
                $description = "";
            }

            if (strlen($description) > 255){
                exit("Description is too long. Please enter a description with at most 255 characters.");
            }

            // Change description action.
            if (!$user->changeDescription($description)){
                // Error message
                exit($user->getErrorStatus());
            }

            if ($showNSFW == null){
                exit("Show NSFW is empty!");
            }

            if ($showNSFW != "true" && $showNSFW != "false"){
                exit("Show NSFW not valid: " . $showNSFW);
            }

            if ($showNSFW == "true"){
                $showNSFW = 1;
            } else {
                $showNSFW = 0;
            }

            // Change showNSFW action.
            if (!$user->changeShowNSFW($showNSFW)){
                // Error message
                exit($user->getErrorStatus());
            }


            // TODO: changeEmail() function that changes the email if it is different than the current one, and also checks if the email is already taken, and sends a confirmation email to the old and new email address. If confirmed, the email is changed, if not, the email is not changed.
            // THIS REQUIRES A DB CHANGE TO HANDLE THE EMAIL CONFIRMATION TOKENS AND STATUS.

            // Success message
            exit("Profile updated successfully.");
        }

        case "updatePassword": {

            $oldPassword = validate_input($_POST["oldPassword"]);
            $newPassword = validate_input($_POST["newPassword"]);

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

        case "checkUsername": {

            // This returns a message if the username is taken or not.
            $username = validate_input($_POST["username"]);

            // Check if the username is empty
            if (!$username) {
                // Error message
                exit("[text-danger]Username is empty!");
            }

            // Check if the username is too short
            if (strlen($username) < 3) {
                // Error message
                exit("[text-warning]Username is too short.");
            }

            // Check if the username is too long
            if (strlen($username) > 255) {
                // Error message
                exit("[text-warning]Username is too long!");
            }

            // Check if current username in session is the same as the one being checked
            if ($username == $_SESSION["username"]) {
                // Error message
                exit("[text-info]This is your current username.");
            }

            // Check if the username is taken
            if (!$user->checkUsername($username)) {
                // Error message
                exit("[text-danger]Username is taken. Please enter a different username.");
            }

            exit("[text-success]Username is available.");
        }

        case "updateProfileImage": {

            //TODO: Implement this.

            exit("Not implemented yet.");
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
