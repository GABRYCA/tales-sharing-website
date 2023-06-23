<?php
// Verifico se loggato
session_start();
include_once (dirname(__FILE__) . "/../../private/objects/Tag.php");

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../login.php");
    exit();
}

// PHP Service, returns tags in JSON format
$query = $_GET["q"];
// Create a new Tag object
$tag = new Tag();
// Get the full list of tags
$tagList = $tag->getTagList();
// Create an empty array for the results
$results = [];
// For each tag in the list
foreach ($tagList as $t) {
    // If the tag name contains the query string
    if (str_contains(strtolower($t->getName()), strtolower($query))) {
        $results[] = $t;
    }
}
// Set the header to JSON
header("Content-Type: application/json");
// Return the results in JSON format
echo json_encode($results);
?>
