<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once (dirname(__FILE__) . '/common/common-head.php');
    ?>
    <script src="data/util/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.3/purify.min.js" integrity="sha512-TBmnYz6kBCpcGbD55K7f4LZ+ykn3owqujFnUiTSHEto6hMA7aV4W7VDPvlqDjQImvZMKxoR0dNY5inyhxfZbmA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
            background: linear-gradient(90deg, rgba(0,97,255,1) 0%, rgb(255, 15, 123) 100%) !important;
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

if (empty($_GET["id"])) {
    header("Location: ../home.php");
    exit();
}

// Includes
include_once (dirname(__FILE__) . '/common/utility.php');
include_once (dirname(__FILE__) . '/../private/objects/User.php');
include_once (dirname(__FILE__) . '/../private/objects/Content.php');

// tinyMCE which's in data/util/tinymce/js/tinymce/tinymce.min.js
    include_once

$id = validate_input($_GET["id"]);

// Get user from session
$user = new User();
$user->setUsername($_SESSION["username"]);
$user->loadUser();

// Get content from id, if not found or it is private, redirect to home.
$content = new Content();
$content->setContentId($id);
if (!$content->loadContent()) {
    header("Location: ../home.php");
    exit();
}

if ($content->getIsPrivate() === true && $content->getOwnerId() !== $user->getUsername()) {
    header("Location: ../home.php");
    exit();
}

// Get owner of content
$owner = new User();
$owner->setUsername($content->getOwnerId());
$owner->loadUser();
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

    <!-- Content -->
    <div class="container-xxl">
        <div class="row mt-4">
            <div class="col">
                <div class="row justify-content-center p-3">
                    <!-- Image -->
                    <div class="col-12 col-xxl-7 rounded-3">
                        <img src="<?php echo $content->getUrlImage(); ?>" class="img-fluid rounded-3 border-gradient" alt="Image" onerror="hideSpinner(this)">
                    </div>
                </div>
            </div>
            <hr>
            <div class="row justify-content-center d-flex align-items-center">
                <!-- Icon of user -->
                <div class="col-3 text-end">
                    <a href="profile.php?username=<?php echo $content->getOwnerId(); ?>" class="m-auto">
                        <img src="<?php echo $owner->getUrlProfilePicture(); ?>" class="img-fluid rounded-circle border-gradient" alt="Profile Picture" width="100" onerror="hideSpinner(this)">
                    </a>
                </div>
                <!-- Title and owner name of content -->
                <div class="col-9 text-start">
                    <h2><?php echo $content->getTitle(); ?></h2>
                    <h6>by <a href="profile.php?username=<?php echo $content->getOwnerId(); ?>"><?php echo $content->getOwnerId(); ?></a></h6>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col" id="description">
                </div>
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