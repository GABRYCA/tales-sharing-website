<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include 'common/common-head.php';
    ?>
    <title>GCA's Baseline</title>
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

        .user-icon-top {
            transition: 0.2s ease-out !important;
        }

        .user-icon-top:hover {
            background-color: rgba(255, 15, 123, 0.54) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 15, 123, 0.25) !important;
        }

        .img-home {
            transition: 0.2s ease-out !important;
        }

        .row-horizon {
            overflow-x: auto;
            white-space: nowrap;
        }

        .img-home:hover {
            background-color: rgba(255, 15, 123, 0.54) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 15, 123, 0.25) !important;
            filter: brightness(1.1);
            cursor: pointer;
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
                    <li><a class="dropdown-item" id="logout-button" href="logout.php">Logout</a></li>
                    <!-- Upload button -->
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center text-light border-top border-bottom pt-2 pb-2 rounded-4 bg-gradient" id="upload-button" data-mdb-toggle="animation" data-mdb-animation-start="onHover" data-mdb-animation="slide-out-right" href="upload.php">Upload</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row row-horizon border-bottom text-center pt-3 pb-3 flex-nowrap" id="row-profiles">
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
        <div class="col-3 col-md-2 col-xl-1">
            <a href="/profile/name" data-bs-toggle="tooltip" title="click to open">
                <img src="common/favicon.webp" alt="icon-user" class="img-fluid p-1 bg-light bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">
            </a>
        </div>
    </div>

    <!-- Content and images will be here in an array, some will be square, other rectangular, there shouldn't be empty spaces -->
    <div class="row p-3 gap-0 justify-content-evenly gy-3">
        <div class="col-12 col-lg-4 col-xxl-3">
            <div class="img-wrapper position-relative">
                <img src="data/profile/anonymousgca/gallery/images/Blaze.webp" alt="image" class="img-fluid rounded-4 img-thumbnail bg-placeholder img-home" loading="lazy" onclick="window.location.href = '/share.php?id=1'" onload="hideSpinner(this)" style="opacity: 0;" data-aos="fade-up">
            </div>
        </div>
        <div class="col-12 col-lg-4 col-xxl-3">
            <div class="img-wrapper position-relative">
                <img src="data/profile/anonymousgca/gallery/images/Dylan.webp" alt="image" class="img-fluid rounded-4 img-thumbnail bg-placeholder img-home" loading="lazy" onclick="window.location.href = '/share.php?id=1'" onload="hideSpinner(this)" style="opacity: 0;" data-aos="fade-up">
            </div>
        </div>
    </div>

</div>

<?php
include 'common/common-body.php';
?>
</body>
</html>