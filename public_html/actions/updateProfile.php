<?php
include_once (dirname(__FILE__) . "/../../private/objects/User.php");
include_once (dirname(__FILE__) . "/../common/utility.php");
include_once (dirname(__FILE__) . "/../../private/objects/VariablesConfig.php");

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

            // Check if user canUpload
            if (!$user->canUpload()) {
                // Send the error array to the client
                exit("You can't upload anything. Please call for an administrator.");
            }

            // Check if the file is empty
            $file = $_POST["profileImage"] ?? "";

            if ($file == "" || !$file) {
                // Error message
                exit("File is empty. Please select a file.");
            }

            // remove the prefix if exists
            $data = explode(",", $file);
            if (count($data) > 1) {
                $file = $data[1];
            }

            // Decode the file data
            $file = base64_decode($file);

            // Check if the file is an image from the base64 data
            if (getimagesizefromstring($file) === false) {
                // Send the error array to the client
                exit("File is not an image. Please select a valid image.");
            }

            // Check if size is too big (15MB)
            if (strlen($file) > 15000000) {
                // Send the error array to the client
                exit("File is too big. Please select a valid image.");
            }

            // Save image (convert it also to webp)
            $path = save_image($file, $user->getUsername(), VariablesConfig::$profileImage);

            // Load image from path (an URL now and also check if it fails)
            $image = imagecreatefromstring(file_get_contents($path));

            // Save new URL profile picture
            if (!$user->changeProfilePicture($path)) {
                // Error message
                exit($user->getErrorStatus());
            }

            exit("Profile image updated successfully.");
        }

        case "updateProfileCover": {

            // Check if user canUpload
            if (!$user->canUpload()) {
                // Send the error array to the client
                exit("You can't upload anything. Please call for an administrator.");
            }

            // Check if the file is empty
            $file = $_POST["profileCover"] ?? "";

            if ($file == "" || !$file) {
                // Error message
                exit("File is empty. Please select a file.");
            }

            // remove the prefix if exists
            $data = explode(",", $file);
            if (count($data) > 1) {
                $file = $data[1];
            }

            // Decode the file data
            $file = base64_decode($file);

            // Check if the file is an image from the base64 data
            if (getimagesizefromstring($file) === false) {
                // Send the error array to the client
                exit("File is not an image. Please select a valid image.");
            }

            // Check if size is too big (15MB)
            if (strlen($file) > 15000000) {
                // Send the error array to the client
                exit("File is too big. Please select a valid image.");
            }

            // Save image (convert it also to webp)
            $path = save_image($file, $user->getUsername(), VariablesConfig::$profileCover);

            // Load image from path (an URL now and also check if it fails)
            $image = imagecreatefromstring(file_get_contents($path));

            // Save new URL profile picture
            if (!$user->changeProfileCover($path)) {
                // Error message
                exit($user->getErrorStatus());
            }

            exit("Profile cover updated successfully.");

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

// Function to save the image to the server and returns the path.
function save_image($image, $user_id, $title) {
    // Make sure that title doesn't break paths.
    $title = preg_replace("/([^\w\s\d\-_~,;[\]\(\).])/", "", $title);

    // Create a new image from the decoded image data.
    $image = imagecreatefromstring($image);
    // Get the image width.
    $image_width = imagesx($image);
    // Get the image height.
    $image_height = imagesy($image);
    // Create a new image with the same width and height, keeping transparent background if there's.
    $new_image = imagecreatetruecolor($image_width, $image_height);
    // Set the flag to save full alpha channel information.
    imagesavealpha($new_image, true);
    // Copy the image to the new image.
    imagecopy($new_image, $image, 0, 0, 0, 0, $image_width, $image_height);
    // The uniqueid
    $uniqueid = uniqid();

    // Create directories if there aren't already
    if (!file_exists(dirname(__FILE__) . "/../data/profile/" . $user_id . "/gallery/images/")) {
        mkdir(dirname(__FILE__) . "/../data/profile/" . $user_id . "/gallery/images/", 0777, true);
    }
    // Save the image to the server as a .webp
    imagewebp($new_image, dirname(__FILE__) . "/../data/profile/" . $user_id . "/gallery/images/" . $title . "-" . $uniqueid . ".webp", 80);
    // Get the image path.

    // Return the image path
    return VariablesConfig::$domain . "data/profile/" . $user_id . "/gallery/images/" . $title . "-" . $uniqueid . ".webp";
}
?>
