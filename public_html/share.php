<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once (dirname(__FILE__) . '/common/common-head.php');
    ?>
    <title>Share - Tales</title>
    <style>
        #upload-button {
            background: rgb(0,97,255) !important;
            background: linear-gradient(90deg, rgba(0,97,255,1) 0%, rgba(255,15,123,1) 100%) !important;
            transition: 0.7s ease-out !important;
        }

        #upload-button:hover, #dropdownMenuLink:hover {
            background: #d2186e !important;
            font-weight: bolder !important;
            color: rgb(42, 42, 42) !important;
        }

        #dropdownMenuLink {
            background: rgb(0,97,255) !important;
            background: linear-gradient(90deg, rgba(0,97,255,1) 0%, rgba(255,15,123,1) 100%) !important;
            transition: 0.4s ease-out !important;
        }

        #logout-button {
            color: #FF0F7BFF !important;
        }
    </style>

    <script>
        function hideSpinner(image) {
            image.classList.remove("bg-placeholder");
            image.style.opacity = "1";
        }
    </script>
</head>
<body class="font-monospace text-light bg-dark">

<?php
session_start();
// Check if logged in.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: ../home.php");
    exit();
}

?>

<?php
// Load all necessary includes.
include_once (dirname(__FILE__) . '/../private/objects/User.php');
include_once (dirname(__FILE__) . '/../private/objects/Content.php');

// Get user from session
$user = new User();
$user->setUsername($_SESSION["username"]);
$user->loadUser();

// Get content from id, if not found or it is private, redirect to home.
$content = new Content();
$content->setContentId($_GET["id"]);
if (!$content->loadContent()) {
    header("Location: ../home.php");
    exit();
}

if ($content->getIsPrivate() === true) {
    header("Location: ../home.php");
    exit();
}
?>

<div class="container-fluid">
    <!-- Navbar -->
    <div class="row justify-content-between border-bottom pt-2 pb-2">
        <div class="col-3">
            <!-- Logo (common/favicon.webp) -->
            <a href="index.php">
                <img src="common/favicon.webp" alt="GCA's Baseline" width="40" height="40">
            </a>
        </div>
        <div class="col-3">
            <!-- Profile icon that when clicked opens a dropdown menu, aligned to end -->
            <div class="dropdown float-end">
                <a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink" data-aos="fade-in">
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                    <li><a class="dropdown-item" href="help.php">Help</a></li>
                    <li><a class="dropdown-item" id="logout-button" href="actions/logout.php">Logout</a></li>
                    <!-- Upload button -->
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center text-light border-top border-bottom pt-2 pb-2 rounded-4 bg-gradient" id="upload-button" data-mdb-toggle="animation" data-mdb-animation-start="onHover" data-mdb-animation="slide-out-right" href="upload-content.php">Upload</a></li>
                </ul>
            </div>
        </div>
    </div>



</div>

<?php
include_once (dirname(__FILE__) . '/common/common-footer.php');
include_once (dirname(__FILE__) . '/common/common-body.php');
?>
</body>
</html>