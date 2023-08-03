<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once(dirname(__FILE__) . '/common/common-head.php');
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.3/purify.min.js"
            integrity="sha512-TBmnYz6kBCpcGbD55K7f4LZ+ykn3owqujFnUiTSHEto6hMA7aV4W7VDPvlqDjQImvZMKxoR0dNY5inyhxfZbmA=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <title>Gallery - Tales</title>
    <style>
        #upload-button {
            background: rgb(0, 97, 255) !important;
            background: linear-gradient(90deg, rgba(0, 97, 255, 1) 0%, rgba(255, 15, 123, 1) 100%) !important;
            transition: 0.7s ease-out !important;
        }

        #upload-button:hover, #dropdownMenuLink:hover {
            background: #d2186e !important;
            font-weight: bolder !important;
            color: rgb(42, 42, 42) !important;
        }

        #dropdownMenuLink {
            background: rgb(0, 97, 255) !important;
            background: linear-gradient(90deg, rgba(0, 97, 255, 1) 0%, rgb(255, 15, 123) 100%) !important;
            transition: 0.4s ease-out !important;
        }

        #logout-button {
            color: #FF0F7BFF !important;
        }

        #image {
            max-height: 90vh !important;
        }

        #bell:hover {
            cursor: pointer;
        }

        .new-notification {
            background-color: rgba(255, 15, 123, 0.54) !important;
        }

        .img-home {
            max-height: 80vh !important;
            transition: 0.2s ease-out !important;
        }

        .img-home:hover {
            background-color: rgba(255, 15, 123, 0.54) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 15, 123, 0.25) !important;
            filter: brightness(1.1);
            cursor: pointer;
        }

        .fa-eye {
            color: rgb(63, 135, 255) !important;
        }

        .fa-eye-slash {
            color: #FF0F7BFF !important;
        }

        .custom-btn {
            background-color: transparent !important;
            color: #FF0F7BFF !important;
            border-color: #FF0F7BFF !important;
            transition: 0.2s ease-out !important;
        }

        .custom-btn:hover {
            color: #ffffff !important;
            border-color: #FF0F7BFF !important;
            background-color: rgba(255, 15, 123, 0.54) !important;
        }

        .btn-outline-custom {
            border-color: rgba(255, 15, 123, 1) !important;
            color: rgba(255, 15, 123, 1) !important;
        }

        .btn-outline-custom:hover {
            background-color: rgba(255, 15, 123, 0.54) !important;
            color: white !important;
        }
    </style>
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
include_once(dirname(__FILE__) . '/common/utility.php');
include_once(dirname(__FILE__) . '/../private/objects/User.php');
include_once(dirname(__FILE__) . '/../private/objects/Content.php');
include_once(dirname(__FILE__) . '/../private/objects/Gallery.php');

$id = validate_input($_GET["id"]);

// Get gallery from database
$gallery = new Gallery();
$gallery->setGalleryId($id);
if (!$gallery->loadGalleryInfoByGalleryId()) {
    header("Location: ../home.php");
    exit();
}

// Get user from session
$user = new User();
$user->setUsername($_SESSION["username"]);
$user->loadUser();

// Load owner of gallery
$owner = new User();
$owner->setUsername($gallery->getOwnerId());
$owner->loadUser();

?>

<div class="container-fluid">
    <!-- Navbar -->
    <!-- Navbar -->
    <div class="row justify-content-between border-bottom pt-2 pb-2">
        <div class="col-2">
            <!-- Logo (common/favicon.webp) -->
            <a href="home.php">
                <img src="common/favicon.webp" alt="GCA's Baseline" width="40" height="40">
            </a>
        </div>
        <!-- Search box here -->
        <div class="col-6 col-md-7 col pe-0 d-flex align-items-center">
            <form class="w-100" action="search.php" method="GET">
                <div class="input-group">
                    <input class="form-control form-control-sm border-0 rounded-3" type="search" placeholder="Search" aria-label="Search" name="search">
                    <button class="btn btn-sm btn-outline-custom" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <div class="col-4 col-md-3 ps-0">
            <div class="row justify-content-end text-end">
                <div class="col-auto mt-2 pe-0">
                    <!-- Notification -->
                    <span class="fa-layers fa-fw" id="bell" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php
                        if (count($user->getUnreadNotifications()) > 0) {
                            echo '<span class="fa-layers-counter rounded-5 p-1 px-2" style="background: rgba(255, 15, 123, 0.54);" id="notification-count">' . count($user->getUnreadNotifications()) . '</span>';
                        }
                        ?>
                    </span>
                    <!-- Dropdown menu -->
                    <div class="dropdown-menu dropdown-menu-end p-3 bg-dark-subtle notifications-dropdown"
                         style="width: 300px; max-height: 80vh; overflow-y: auto;" data-aos="zoom-in">
                        <h5 class="text-white">Notifications</h5>
                        <hr class="text-white">
                        <?php
                        $notifications = $user->getAllNotifications();
                        if (empty($notifications)) {
                            echo '<p class="text-white">You have no notifications.</p>';
                        } else {
                            // Button to delete all notification that appears only on hover of the dropdown.
                            echo '<button class="btn btn-outline-danger w-100 btn-sm" id="delete-notifications" title="Click to delete all notifications">Clear all</button>';
                            echo '<hr class="text-white notification-divider">';
                            foreach ($notifications as $notification) {
                                $title = $notification->getTitle();
                                $description = $notification->getDescription();
                                $type = $notification->getNotificationType();
                                $date = $notification->getNotificationDate();
                                $viewed = $notification->getViewed();
                                switch ($type) {
                                    case 'new_content':
                                        $color = 'rgba(0, 97, 255, 1)';
                                        $icon = 'fas fa-bell';
                                        break;
                                    case 'new_comment':
                                        $color = 'rgba(255, 15, 123, 1)';
                                        $icon = 'fas fa-comment';
                                        break;
                                    case 'new_like':
                                        $color = 'rgba(255, 0, 0, 1)';
                                        $icon = 'fas fa-heart';
                                        break;
                                    case 'new_friend':
                                        $color = 'rgba(0, 255, 0, 1)';
                                        $icon = 'fas fa-user-friends';
                                        break;
                                    case 'new_follow':
                                        $color = 'rgba(255, 255, 0, 1)';
                                        $icon = 'fas fa-user-plus';
                                        break;
                                    case 'advertisement':
                                        $color = 'rgba(255, 255, 255, 1)';
                                        $icon = 'fas fa-ad';
                                        break;
                                    default:
                                        $color = 'rgba(255, 255, 255, 1)';
                                        $icon = 'fas fa-bell';
                                        break;
                                }
                                $date = date('d/m/y', strtotime($date));
                                // If not viewed, add class new-notification
                                if ($viewed == 0) {
                                    echo '<div class="d-flex align-items-start mb-2 rounded-3 notification new-notification pt-2 pb-2">';
                                } else {
                                    echo '<div class="d-flex align-items-start mb-2 notification rounded-3">';
                                }
                                echo '<i class="' . $icon . '" style="color: ' . $color . '; font-size: 24px;"></i>';
                                echo '<div class="ms-2">';
                                echo '<h6 class="text-white">' . $title . '</h6>';
                                echo '<p class="text-white mb-1">' . $description . '</p>';
                                echo '<small class="text-white">' . $date . '</small>';
                                echo '</div>';
                                echo '</div>';
                                echo '<hr class="text-white notification-divider">';
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="col-auto">
                    <!-- Profile icon that when clicked opens a dropdown menu, aligned to end -->
                    <div class="dropdown float-end">
                        <a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink"
                            data-aos="fade-in">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><a class="dropdown-item" href="help.php">Help</a></li>
                            <li><a class="dropdown-item" id="logout-button" href="actions/logout.php">Logout</a></li>
                            <!-- Upload button -->
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-center text-light border-top border-bottom pt-2 pb-2 rounded-4 bg-gradient"
                                   id="upload-button" href="upload-content.php">Upload</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    if ($gallery->isHiddenGallery() && ($user->getUsername() !== $gallery->getOwnerId())) {
        echo '<div class="row mt-3">';
        echo '<div class="col-12">';
        echo '<h1 class="display-6 text-center">This gallery is hidden!</h1>';
        echo '</div>';
        echo '</div>';
    } else {
        ?>

        <!-- Title of gallery -->
        <div class="row mt-3">
            <div class="col-12">
                <h1 class="display-6 text-center" id="galleryTitle"><?php echo $gallery->getName(); ?></h1>
            </div>
        </div>

        <!-- Info of (Owner and if hidden) -->
        <div class="row">
            <div class="col-12">
                <p class="text-center mb-1" id="hideOrShow">
                    <?php
                    if ($gallery->isHiddenGallery()) {
                        echo '<i class="fas fa-eye-slash"></i> Hidden gallery';
                    } else {
                        echo '<i class="fas fa-eye"></i> Public gallery';
                    }
                    ?>
                </p>
            </div>

            <?php
            if ($user->getUsername() === $gallery->getOwnerId()) {
                echo '<div class="col-12">';
                echo '<p class="text-center">';
                echo '<i class="fas fa-user"></i> You are the owner of this gallery';
                echo '</p>';
                echo '</div>';
            } else {
                echo '<div class="col-12">';
                echo '<p class="text-center">';
                echo '<i class="fas fa-user"></i> Owner: <a href="profile.php?username=' . $gallery->getOwnerId() . '">' . $gallery->getOwnerId() . '</a>';
                echo '</p>';
                echo '</div>';
            }
            ?>
        </div>

        <hr class="mt-1 mb-2">

        <!-- If it's the owner, add buttons to edit title of the gallery and manage it -->
        <?php
        if ($user->getUsername() === $gallery->getOwnerId()) { ?>

            <!-- Edit title button that opens a modal on click to edit it and also a delete button that opens a modal to confirm the deletion of the gallery -->
            <div class="row bg-light bg-opacity-10 p-2 px-0 px-lg-4 mx-0 mx-lg-1 rounded-4 justify-content-center text-center align-items-center">
                <div class="col-4">
                    <button type="button" class="btn btn-outline-primary custom-btn" data-bs-toggle="modal" data-bs-target="#editGalleryModal">
                        <i class="fas fa-edit"></i> Rename
                    </button>
                </div>
                <div class="col-4">
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteGalleryModal">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                <div class="col-4">
                    <!-- Use a dropdown button with an icon and a label -->
                    <div class="dropdown">
                        <button class="btn btn-outline-custom dropdown-toggle" type="button" id="galleryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i>
                            Options
                        </button>
                        <!-- Use a dropdown menu with the buttons as items -->
                        <ul class="dropdown-menu" aria-labelledby="galleryDropdown">
                            <!-- The item for hiding the gallery -->
                            <li>
                                <button type="button" class="dropdown-item btn btn-outline-danger" id="hideGalleryButton" <?php if ($gallery->isHiddenGallery()) echo "disabled"; ?>>
                                    <i class="fas fa-eye-slash"></i> Hide
                                </button>
                            </li>
                            <!-- The item for showing the gallery -->
                            <li>
                                <button type="button" class="dropdown-item btn btn-outline-light" id="showGalleryButton" <?php if (!$gallery->isHiddenGallery()) echo "disabled"; ?>>
                                    <i class="fas fa-eye"></i> Show
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>



            <hr class="mt-2">

        <?php } ?>

        <!-- Content of gallery -->
        <div class="row p-3 gap-0 justify-content-evenly gy-3">

            <?php
            $contentArray = $gallery->getContent();
            if (count($contentArray) === 0) {
                echo '<div class="col-12"><h1 class="display-6 text-center">This gallery is empty!</h1></div>';
            } else {
                foreach ($contentArray as $content) {
                    echo '<div class="col-12 col-lg-4 col-xxl-3 d-flex align-items-stretch px-0 px-lg-2">';
                    echo '<div class="card border-0 bg-placeholder img-home w-100" data-aos="fade-up" onclick="window.location.href = \'/share.php?id=' . $content->getContentId() . '\'">';
                    echo '<div class="card-img-top img-wrapper position-relative text-center w-100 lazy-background" data-background="' . encode_url($content->getUrlImage()) . '" style="background-size: cover; background-position: center; height: 45vh;">';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>

        </div>

        <?php
        if ($user->getUsername() === $gallery->getOwnerId()) {?>

            <!-- Edit gallery modal -->
            <div class="modal fade" id="editGalleryModal" tabindex="-1" aria-labelledby="editGalleryModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editGalleryModalLabel">Edit gallery title</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="galleryTitleInput" class="form-label">New title</label>
                                    <input type="text" class="form-control" id="galleryTitleInput" placeholder="Enter a new title for your gallery">
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary custom-btn" id="renameGallery">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete gallery modal -->
            <div class="modal fade" id="deleteGalleryModal" tabindex="-1" aria-labelledby="deleteGalleryModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title text-danger" id="deleteGalleryModalLabel">Delete gallery</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <p>Are you sure you want to delete this gallery?</p>
                            <p>The content won't be deleted and will still be visible on your profile page. Only the gallery will.</p>
                            <p class="mb-0">This action cannot be undone!</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="deleteGallery">Delete</button>
                        </div>
                    </div>
                </div>
            </div>


        <?php } ?>

    <?php } ?>

</div>

<?php
include_once(dirname(__FILE__) . '/common/common-footer.php');
include_once(dirname(__FILE__) . '/common/common-body.php');
?>
<script>
    $(function () {
        // Get the bell element
        //var bell = document.getElementById("bell");
        // Add a click event listener to the bell
        $('#bell').on("click", function () {
            // Get the dropdown menu element
            var dropdown = document.querySelector(".notifications-dropdown");
            // Set a timeout of 1 second after the dropdown is opened
            setTimeout(function () {
                // Check if the dropdown is still open
                if (dropdown.classList.contains("show")) {
                    // Get the new-notification elements
                    var newNotifications = document.querySelectorAll(".new-notification");
                    // Remove the new-notification class from the newNotifications
                    for (var i = 0; i < newNotifications.length; i++) {
                        newNotifications[i].classList.remove("new-notification");
                    }
                    // Send a post request to the server to mark all notifications as read
                    $.ajax({
                        type: "POST",
                        url: "actions/notifications.php",
                        data: {read: true},
                        success: function () {
                            // Remove the element notification-count
                            $('#notification-count').remove();
                        },
                        error: function () {
                            // Show an error message
                            console.log("Error while marking notifications as read");
                        }
                    });
                }
            }, 1000);
        });
    });

    $(function () {
        // Get all the elements with class lazy-background
        const lazyBackgrounds = $('.lazy-background');

        // Create a new IntersectionObserver instance
        const observer = new IntersectionObserver((entries, observer) => {
            // Loop through each entry
            entries.forEach(entry => {
                // If the entry is intersecting (visible)
                if (entry.isIntersecting) {
                    // Get the element from the entry
                    const el = $(entry.target);
                    // Get the data-background value from the element
                    const background = el.data('background');
                    console.log(background);
                    // Set the background-image style property to load the image
                    el.css('background-image', `url(${background})`);
                    // Unobserve the element (no need to watch it anymore)
                    observer.unobserve(el[0]);
                }
            });
        });

        // Loop through each element and observe it
        lazyBackgrounds.each(function () {
            observer.observe(this);
        });

    });

    <?php if ($user->getUsername() == $gallery->getOwnerId()) { ?>

    $(function (){
       // Function that handles the click of the delete gallery button.
        // Sends ajax request to delete the gallery to galleryManager.php with action = "delete" and the galleryId
        // On click #deleteGallery
        $('#deleteGallery').on("click", function () {
            const galleryId = <?php echo $gallery->getGalleryId(); ?>;
            if (galleryId === null) return;
            $.ajax({
                type: "POST",
                url: "actions/galleryManager.php",
                data: {action: "delete", galleryId: galleryId},
                success: function (data) {
                    // Toast message.
                    $.toast({
                        heading: 'Success',
                        text: 'Gallery deleted with success: ' + data,
                        icon: 'success',
                        position: 'top-right',
                        hideAfter: 2000
                    });
                    // Redirect to profile page after 2 seconds.
                    setTimeout(function () {
                        window.location.href = "profile.php";
                    }, 2000);
                },
                error: function (data) {
                    // Send toast message with error.
                    $.toast({
                        heading: 'Error',
                        text: 'Error while deleting gallery: ' + data,
                        icon: 'error',
                        position: 'top-right'
                    });
                }
            });
        });

        $('#renameGallery').on("click", function () {
            const newGalleryName = $('#galleryTitleInput').val();
            if (newGalleryName === null || newGalleryName === "") return;
            $.ajax({
                type: "POST",
                url: "actions/galleryManager.php",
                data: {
                    action: "rename",
                    galleryId: <?php echo $gallery->getGalleryId(); ?>,
                    newGalleryName: newGalleryName},
                success: function (data) {
                    // Toast message.
                    $.toast({
                        heading: 'Success',
                        text: 'Gallery renamed with success: ' + data,
                        icon: 'success',
                        position: 'top-right',
                        hideAfter: 1500
                    });
                    // Close modal
                    $('#editGalleryModal').modal('hide');
                    // Update galleryTitle
                    $('#galleryTitle').text(newGalleryName);
                },
                error: function (data) {
                    // Send toast message with error.
                    $.toast({
                        heading: 'Error',
                        text: 'Error while renaming gallery: ' + data,
                        icon: 'error',
                        position: 'top-right'
                    });
                }
            });
        });
    });

    // Get the buttons
    var hideButton = $("#hideGalleryButton");
    var showButton = $("#showGalleryButton");
    // Add an event listener to the hide button
    hideButton.on("click", function() {
        // Send an ajax request to the server with the value true
        $.ajax({
            url: "actions/galleryManager.php",
            type: "POST",
            data: {
                action: "hide",
                galleryId: <?php echo $gallery->getGalleryId(); ?>
            },
            success: function(response) {
                // Disable the hide button and enable the show button
                hideButton.prop("disabled", true);
                showButton.prop("disabled", false);
                // Edit content.
                $("#hideOrShow").html("<i class='fas fa-eye-slash'></i> Hidden gallery");
                // Toast
                $.toast({
                    heading: 'Success',
                    text: 'Gallery is now hidden.',
                    icon: 'success',
                    position: 'top-right',
                    hideAfter: 2000
                });
                console.log(response);
            },
            error: function(error) {
                console.log(error);
            }
        });
    });
    // Add an event listener to the show button
    showButton.on("click", function() {
        // Send an ajax request to the server with the value false
        $.ajax({
            url: "actions/galleryManager.php",
            type: "POST",
            data: {
                action: "show",
                galleryId: <?php echo $gallery->getGalleryId(); ?>
            },
            success: function(response) {
                // Disable the show button and enable the hide button
                showButton.prop("disabled", true);
                hideButton.prop("disabled", false);
                // Edit content.
                $("#hideOrShow").html("<i class='fas fa-eye'></i> Public gallery");
                // Toast
                $.toast({
                    heading: 'Success',
                    text: 'Gallery is now visible to everyone.',
                    icon: 'success',
                    position: 'top-right',
                    hideAfter: 2000
                });
                console.log(response);
            },
            error: function(error) {
                console.log(error);
            }
        });
    });

    <?php } ?>

    // Change title of the document to the name of the gallery.
    document.title = "Gallery - <?= $gallery->getName() ?> - Tales";

    function hideSpinner(image) {
        image.classList.remove("bg-placeholder");
        image.style.opacity = "1";
    }
</script>
</body>
</html>