<?php
include_once(dirname(__FILE__) . "/../../private/connection.php");
include_once(dirname(__FILE__) . "/../../private/objects/Gallery.php");
include_once(dirname(__FILE__) . "/../../private/objects/User.php");
include_once(dirname(__FILE__) . "/../../private/objects/Content.php");
include_once(dirname(__FILE__) . "/../common/utility.php");
session_start();

// Check if logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}

// Check if the request method is POST
// If it's POST, check the action type (create, delete, rename) and the parameters.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = validate_input($_POST["action"] ?? "");
    $galleryId = validate_input($_POST["galleryId"] ?? "");
    $galleryName = validate_input($_POST["galleryName"] ?? "");
    $newGalleryName = validate_input($_POST["newGalleryName"] ?? "");
    $contentId = validate_input($_POST["contentId"] ?? "");

    // Check if the action is empty
    if (!$action) {
        // Error
        exit("Action is empty. Please select a valid action.");
    }

    // Check if the action is not valid
    if ($action != "create" && $action != "delete" && $action != "rename" && $action != "list" && $action != "load" && $action != "addContent" && $action != "removeContent" && $action != "hide" && $action != "show") {
        // Error
        exit("Invalid action. Please select a valid action.");
    }

    $user = new User();
    $user->setUsername($_SESSION["username"]);
    if (!$user->loadUser()) {
        // Error
        exit("Failed to load user. Please try again.");
    }

    switch ($action) {

        case "create":
        {

            // Check if the gallery name is empty or too long
            if (!$galleryName || strlen($galleryName) > 255) {
                // Error
                exit("Gallery name is empty or too long. Please enter a valid gallery name.");
            }

            $galleries = $user->getGalleries();

            foreach ($galleries as $gallery) {
                if ($gallery->getName() == $galleryName) {
                    exit("Gallery name already exists. Please enter a different gallery name.");
                }
            }

            if (!$user->createGallery($galleryName)) {
                // Send the error array to the client
                exit("Failed to create gallery. Please try again.");
            }

            // Load gallery by name
            if (!$gallery = $user->getGalleryByName($galleryName)) {
                // Send the error array to the client
                exit("Failed to load gallery. Please try again.");
            }

            // Send the gallery id to the client
            exit("Created gallery: " . $gallery->getName());
        }

        case "delete":
        {

            // Check if the gallery id is empty
            if (!$galleryId) {
                // Send the error array to the client
                exit("Gallery id is empty. Please select a valid gallery.");
            }

            // Load gallery by id
            if (!$gallery = $user->getGalleryById($galleryId)) {
                // Send the error array to the client
                exit("Failed to load gallery. Please try again.");
            }

            // Delete gallery
            if (!$user->deleteGalleryById($gallery->getGalleryId())) {
                // Send the error array to the client
                exit("Failed to delete gallery. Please try again.");
            }

            // Send the gallery id to the client
            exit($gallery->getName());
        }

        case "rename":
        {

            // Check if the gallery id is empty
            if (!$galleryId) {
                // Send the error array to the client
                exit("Gallery id is empty. Please select a valid gallery.");
            }

            // Check if the gallery name is empty or too long
            if (!$newGalleryName || strlen($newGalleryName) > 255) {
                // Send error message to the client
                exit("Gallery name is empty or too long. Please enter a valid gallery name.");
            }

            // Load gallery by id
            if (!$gallery = $user->getGalleryById($galleryId)) {
                // Send the error array to the client
                exit("Failed to load gallery. Please try again.");
            }

            // Rename gallery
            if (!$user->renameGalleryById($gallery->getGalleryId(), $newGalleryName)) {
                // Send the error array to the client
                exit("Failed to rename gallery. Please try again.");
            }

            // Send the gallery id to the client
            exit($gallery->getName());
        }

        case "list":
        {

            // Load gallery by id
            if (!$galleries = $user->getGalleries()) {
                // Send the error array to the client
                exit("Failed to load gallery. Please try again.");
            }

            // Send the gallery array to the client
            exit(json_encode($galleries));
        }

        case "load":
        {

            // Check if the gallery id is empty
            if (!$galleryId) {
                // Send the error array to the client
                exit("Gallery id is empty. Please select a valid gallery.");
            }

            // Load gallery by id
            if (!$gallery = $user->getGalleryById($galleryId)) {
                // Send the error array to the client
                exit("Failed to load gallery. Please try again.");
            }

            // Send gallery to the client
            exit(json_encode($gallery));
        }

        case "addContent":
        {

            // Check if the gallery id is empty
            if (!$galleryId) {
                // Send the error array to the client
                exit("Gallery id is empty. Please select a valid gallery.");
            }

            // Load gallery by id
            if (!$gallery = $user->getGalleryById($galleryId)) {
                // Send the error array to the client
                exit("Failed to load gallery. Please try again.");
            }

            if (!$content = $user->getContentById($contentId)) {
                // Send the error array to the client
                exit("Failed to load content. Please try again.");
            }

            // Check if the user owns the content
            if ($content->getOwnerId() != $user->getUsername()) {
                exit("You do not own this content!");
            }

            // Add content to gallery
            if (!$user->addContentToGallery($galleryId, $contentId)) {
                // Send the error array to the client
                exit("Failed to add content to gallery. Please try again.");
            }

            // Send the gallery name to the client
            exit("Added content " . $content->getTitle() . " to gallery: " . $gallery->getName());
        }

        case "removeContent":
        {

            // Check if the gallery id is empty
            if (!$galleryId) {
                // Send the error array to the client
                exit("Gallery id is empty. Please select a valid gallery.");
            }

            if (!$gallery = $user->getGalleryById($galleryId)) {
                exit("Failed to load gallery. Please try again.");
            }

            if ($gallery->getOwnerId() != $user->getUsername()) {
                exit("You do not own this gallery!");
            }

            // Load gallery by id
            if (!$gallery = $user->getGalleryById($galleryId)) {
                exit("Failed to load gallery. Please try again.");
            }

            if (!$content = $user->getContentById($contentId)) {
                exit("Failed to load content. Please try again.");
            }

            // Check if the user owns the content
            if ($content->getOwnerId() != $user->getUsername()) {
                exit("You do not own this content!");
            }

            // Remove content from gallery
            if (!$user->removeContentFromGallery($galleryId, $contentId)) {
                exit("Failed to remove content from gallery. Please try again.");
            }

            // Send the gallery name to the client
            exit("Removed content " . $content->getTitle() . " from gallery: " . $gallery->getName());
        }

        case "hide":
        {

            // Check if the gallery id is empty
            if (!$galleryId) {
                // Send the error array to the client
                exit("Gallery id is empty. Please select a valid gallery.");
            }

            // Load gallery by id
            if (!$gallery = $user->getGalleryById($galleryId)) {
                // Send the error array to the client
                exit("Failed to load gallery. Please try again.");
            }

            // Check if user in session is owner.
            if ($gallery->getOwnerId() != $user->getUsername()) {
                // Send the error array to the client
                exit("You do not own this gallery!");
            }

            // Hide gallery
            if (!$gallery->hideGallery()) {
                // Send the error array to the client
                exit("Failed to hide gallery. Please try again.");
            }

            // Send the gallery id to the client
            exit("success");
        }

        case "show":
        {

            // Check if the gallery id is empty
            if (!$galleryId) {
                // Send the error array to the client
                exit("Gallery id is empty. Please select a valid gallery.");
            }

            // Load gallery by id
            if (!$gallery = $user->getGalleryById($galleryId)) {
                // Send the error array to the client
                exit("Failed to load gallery. Please try again.");
            }

            // Check if user in session is owner.
            if ($gallery->getOwnerId() != $user->getUsername()) {
                // Send the error array to the client
                exit("You do not own this gallery!");
            }

            // Show gallery
            if (!$gallery->showGallery()) {
                // Send the error array to the client
                exit("Failed to show gallery. Please try again.");
            }

            // Send the gallery id to the client
            exit("success");
        }

        default:
        {

            // Send the error array to the client
            exit("Invalid action. Please select a valid action.");
        }
    }
}