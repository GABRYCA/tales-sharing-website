<?php
session_start();
include_once (dirname(__FILE__) . "/../../private/connection.php");
include_once (dirname(__FILE__) . "/../common/utility.php");
include_once (dirname(__FILE__) . "/../../private/objects/User.php");
include_once (dirname(__FILE__) . "/../../private/objects/Content.php");
include_once (dirname(__FILE__) . "/../../private/objects/Gallery.php");
include_once (dirname(__FILE__) . "/../../private/objects/Notification.php");

// Check if user logged in.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Domain
    $domain = "https://tales.anonymousgca.eu/";

    // Get user.
    $user = new User();
    $user->setUsername($_SESSION["username"]);
    $user->loadUser();

    // Check if user canUpload
    if (!$user->canUpload()) {
        // Send the error array to the client
        exit("You can't upload anything. Please call for an administrator.");
    }

    // Get the file data from the POST request.
    $file = $_POST["file"] ?? "";
    // Get the name from the POST request.
    $name = validate_input($_POST["name"] ?? "");
    // Get the description from the POST request.
    $description = validate_input($_POST["description"] ?? "");
    // Get the gallery from the POST request.
    $galleryId = validate_input($_POST["gallery"] ?? "");
    // Get the isPrivate from the POST request.
    $isPrivate = validate_input($_POST["isPrivate"] ?? "");
    // Get the isAI from the POST request.
    $isAI = validate_input($_POST["isAI"] ?? "");
    // Action type (upload or edit).
    $action = validate_input($_POST["action"] ?? "");
    // ContentID (if edit).
    $contentId = validate_input($_POST["contentId"] ?? "");
    // Get tags (validate too)
    $tags = $_POST["tags"];

    // Validate each tag if there's any.
    foreach ($tags as $key => $tag) {
        $tags[$key] = validate_input($tag);
    }

    // Action type.
    if ($action == "upload") {

        // Check if the file is empty
        if ($file == "") {
            // Send the error array to the client
            exit("File is empty. Please select a valid file.");
        }

        // remove the prefix if exists
        $data = explode(",", $file);
        if (count($data) > 1) {
            $file = $data[1];
        }

        // Decode the file data
        $file = base64_decode($file);

        // Check if the name is empty or too long
        if ($name == "" || strlen($name) > 255) {
            // Send the error array to the client
            exit("Name is empty or too long. Please enter a valid name.");
        }

        // Check if the description is empty or too long
        if ($description == "" || strlen($description) > 30000) {
            // Send the error array to the client
            exit("Description is empty or too long. Please enter a valid description.");
        }

        // Check if the gallery is empty or invalid
        if ($galleryId != "" && !is_numeric($galleryId)) {
            // Send the error array to the client
            exit("Gallery is empty or invalid. Please select a valid gallery." . $galleryId);
        }

        // Check if the isPrivate is empty or invalid (0 or 1)
        if ($isPrivate == "" || !is_numeric($isPrivate) || ($isPrivate != 0 && $isPrivate != 1)) {
            // Send the error array to the client
            exit("Private field is empty or invalid. Please select a valid isPrivate.");
        }

        // Check if the isAI is empty or invalid (0 or 1)
        if ($isAI == "" || !is_numeric($isAI) || ($isAI != 0 && $isAI != 1)) {
            // Send the error array to the client
            exit("AI field is empty or invalid. Please select a valid isAI.");
        }

        // Check if the file is an image from the base64 data
        if (getimagesizefromstring($file) === false) {
            // Send the error array to the client
            exit("File is not an image. Please select a valid image.");
        }

        // Check if size is too big (50MB)
        if (strlen($file) > 50000000) {
            // Send the error array to the client
            exit("File is too big. Please select a valid image.");
        }

        // Get the user id.
        $user_id = $user->getUsername();

        // Save image (convert it also to webp)
        $path = save_image($file, $user_id, $name);

        // Load image from path (an URL now and also check if it fails)
        $image = imagecreatefromstring(file_get_contents($path));

        // Create content and save it to the database.
        $content = new Content();
        $content->setOwnerId($user_id);
        $content->setType("image");
        $content->setTitle($name);
        $content->setDescription($description);
        $content->setUrlImage($path);
        $content->setIsAI($isAI);
        $content->setPrivate($isPrivate);
        $content->setTextContent("");
        $content->setUploadDate(date("Y-m-d H:i:s"));
        $content->setTags($tags);

        // Save the content to the database.
        if (!$content->addContent()) {
            // Remove domain from path and add ../ to go back to the root folder.
            $path = "../" . str_replace($domain, "", $path);
            unlink($path);
            // Send the error array to the client
            exit("Error saving content to the database.");
        }

        $content->loadContentByPath();

        // Send notification to all friends and followers of user.
        $notification = new Notification();
        $notification->setTitle("New content from " . $user->getUsername());
        $notification->setDescription("New content from " . $user->getUsername() . " was uploaded.
    <br><a href='" . $domain . "share.php?id=" . $content->getContentId() . "'>Click here to see it.</a>");
        $notification->setNotificationType("new_content"); // Types can be new_content, new_comment, new_like, new_friend, new_follow, advertisement
        $notification->setNotificationDate(date("Y-m-d H:i:s"));
        $notification->setViewed(false);
        $notification->insertNotificationToFollowersAndFriends($content->getOwnerId());

        // If gallery is specified, add the content to the gallery in the database.
        if ($galleryId != "" && is_numeric($galleryId)) {

            // Add content to gallery
            if (!$user->addContentToGallery($galleryId, $content->getContentId())) {
                // Remove domain from path and add ../ to go back to the root folder.
                $path = dirname(__FILE__) . "/../" . str_replace($domain, "", $path);
                unlink($path);
                exit("Error adding content to gallery (but content saved with success), direct image here: <a href='" . $path . "'>link</a>");
            }
        }

        exit("Content saved with success, direct image here: <a href='" . $domain . "share.php?id=" . $content->getContentId() . "'>link</a>");
    } else if ($action == "edit"){


        exit("TODO");
    }
}

// Function to save the image to the server and returns the path.
function save_image($image, $user_id, $title) {

    // Domain
    $domain = "https://tales.anonymousgca.eu/";

    // Make sure that title doesn't break paths.
    $title = preg_replace("/([^\w\s\d\-_~,;[\]\(\).])/", "", $title);

    // Create a new image from the decoded image data.
    $image = imagecreatefromstring($image);
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

    // Create directories if there aren't already
    if (!file_exists(dirname(__FILE__) . "/../data/profile/" . $user_id . "/gallery/images/")) {
        mkdir(dirname(__FILE__) . "/../data/profile/" . $user_id . "/gallery/images/", 0777, true);
    }
    // Save the image to the server as a .webp
    imagewebp($new_image, dirname(__FILE__) . "/../data/profile/" . $user_id . "/gallery/images/" . $title . "-" . $uniqueid . ".webp", 80);
    // Get the image path.

    // Return the image path
    return $domain . "data/profile/" . $user_id . "/gallery/images/" . $title . "-" . $uniqueid . ".webp";
}

?>



