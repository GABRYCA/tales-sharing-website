<?php
session_start();

// If there's already an active session, send user to home.php.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}
?>
<html>
<head>
    <?php
    include_once (dirname(__FILE__) . "/common/common-head.php");
    ?>
    <title>Upload Content</title>
</head>
<body class="bg-dark font-monospace text-light">
<div class="container-fluid">
    <!-- Top bar with on the left a button with an arrow to go back to the previous page and on the right a button to go to the profile -->
    <div class="row bg-dark text-light">
        <div class="col-2">
            <a href="javascript:history.back()" class="btn btn-dark btn-lg"><i class="bi bi-arrow-left"></i></a>
        </div>
        <div class="col-8 text-center">
            <h1 class="display-4">Upload Content</h1>
        </div>
        <div class="col-2">
            <a href="profile.php" class="btn btn-dark btn-lg"><i class="bi bi-person-circle"></i></a>
        </div>
    </div>
</div>
</body>
</html>
