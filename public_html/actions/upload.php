<?php
session_start();
include_once (dirname(__FILE__) . "/../../private/connection.php");
include_once (dirname(__FILE__) . "/../../private/objects/User.php");
include_once (dirname(__FILE__) . "/../common/utility.php");

// If there's already an active session, send user to home.php.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Initialize an empty array for errors
    $errors = array();

    // Validate and sanitize the post values
    $title = validate_input($_POST["title"] ?? "");
    $description = validate_input($_POST["description"] ?? "");
    $public = validate_input($_POST["public"] ?? false);
    $ai_generated = validate_input($_POST["ai_generated"] ?? false);
    $image = validate_input($_POST["image"] ?? "");

    // Check if the title is empty or too long
    if (!$title || strlen($title) > 255) {
        // Add an error message to the errors array
        $errors[] = "Title is empty or too long. Please enter a valid title.";
    }

    // Check if the description is too long
    if (strlen($description) > 255) {
        // Add an error message to the errors array
        $errors[] = "Description is too long. Please enter a shorter description.";
    }

    // Check if the public value is not a boolean
    if (!is_bool($public)) {
        // Add an error message to the errors array
        $errors[] = "Invalid public value. Please select a valid option.";
    }

    // Check if the ai_generated value is not a boolean
    if (!is_bool($ai_generated)) {
        // Add an error message to the errors array
        $errors[] = "Invalid AI generated value. Please select a valid option.";
    }

    // Check if the image value is not a valid data URL
    if (!preg_match("/^data:image\/(jpg|png|gif|webp);base64,/", $image)) {
        // Add an error message to the errors array
        $errors[] = "Invalid image data. Please upload a valid image.";
    }

    // Check if the errors array is empty
    if (empty($errors)) {
        // Get user from session
        $user = $_SESSION["user"];
        // Get the user id from the user object
        $user_id = $user->getUserId();
        // Check if the user id is not null
        if ($user_id != null) {
            // Create a new content object with the user id and image type
            $content = new Content();
            $content->setOwnerId($user_id);
            $content->setType("image");
            // Set the content properties with the post values
            $content->setTitle($title);
            $content->setDescription($description);
            $content->setPrivate($public);
            $content->setIsAI($ai_generated);
            $content->setUrlImage($image);
            // Set the current date as the upload date
            $content->setUploadDate(date("Y-m-d"));
            // Insert the content into the database using the content object method
            $result = $content->addContent();
            // Check if the result is true
            if ($result) {
                // Send a success response with a message to the client
                echo json_encode(array("success" => true, "message" => "Your image has been posted successfully."));
            } else {
                // Send an error response with a message to the client
                echo json_encode(array("success" => false, "message" => "There was an error in posting your image. Please try again later."));
            }
        } else {
            // Send an error response with a message to the client
            echo json_encode(array("success" => false, "message" => "Invalid user id. Please log in again."));
        }

    } else {
        // Send an error response with all the error messages to the client
        echo json_encode(array("success" => false, "message" => implode("\n", $errors)));
    }

}

// Function to save the image to the server and right path using Config.php and the config.ini defaults->save_path
function save_image($image, $user_id, $title) {

    // Check if image is URL or direct file upload.
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        // Return the image URL
        return $image;
    }

    // If it isn't an URL but a valid image data uploaded, save it to the server, but before, convert it into a .webp if it isn't already.

    // Get the image data from the UPLOAD.
    $image_data = explode(',', $image)[1];
    // Decode the image data.
    $image_data = base64_decode($image_data);
    // Create a new image from the decoded image data.
    $image = imagecreatefromstring($image_data);
    // Get the image width.
    $image_width = imagesx($image);
    // Get the image height.
    $image_height = imagesy($image);
    // Create a new image with the same width and height.
    $new_image = imagecreatetruecolor($image_width, $image_height);
    // Copy the image to the new image.
    imagecopy($new_image, $image, 0, 0, 0, 0, $image_width, $image_height);
    // The uniqueid
    $uniqueid = uniqid();
    // Save the image to the server as a .webp
    imagewebp($new_image, dirname(__FILE__) . "/../data/profile/" . $user_id . "/gallery/images/" . $uniqueid . ".webp", 80);
    // Get the image path.
    $image_path = dirname(__FILE__) . "/../data/profile/" . $user_id . "/gallery/images/" . $uniqueid . ".webp";

    // Return the image path
    return $image_path;
}

?>



