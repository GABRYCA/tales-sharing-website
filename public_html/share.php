<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once(dirname(__FILE__) . '/common/common-head.php');
    ?>
    <script src="data/util/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.3/purify.min.js"
            integrity="sha512-TBmnYz6kBCpcGbD55K7f4LZ+ykn3owqujFnUiTSHEto6hMA7aV4W7VDPvlqDjQImvZMKxoR0dNY5inyhxfZbmA=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <title>Share - Tales</title>
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

        #content-stats {
            background: linear-gradient(90deg, rgb(255, 15, 123, 0.5) 0%, rgba(0, 97, 255, 0.5) 100%) !important;

        }

        .fa-heart:hover {
            cursor: pointer;
        }

        .animate__animated {
            animation-duration: 1s;
            animation-fill-mode: both;
        }

        .animate__heartBeat {
            animation-name: heartBeat;
        }

        @keyframes heartBeat {
            0% {
                transform: scale(1);
            }
            14% {
                transform: scale(1.3);
            }
            28% {
                transform: scale(1);
            }
            42% {
                transform: scale(1.3);
            }
            70% {
                transform: scale(1);
            }
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
include_once(dirname(__FILE__) . '/common/utility.php');
include_once(dirname(__FILE__) . '/../private/objects/User.php');
include_once(dirname(__FILE__) . '/../private/objects/Content.php');
include_once(dirname(__FILE__) . '/../private/objects/Tag.php');
include_once(dirname(__FILE__) . '/../private/objects/Likes.php');
include_once(dirname(__FILE__) . '/../private/objects/Comment.php');

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

// Get IP of user.
$ip = getUserIP();

// Increment views of content (This includes many checks to avoid a user to reload the page and get free views).
try {
    $content->incrementNumberOfViews($user->getUsername(), $ip);
} catch (Exception $e) {
}

// Get owner of content
$owner = new User();
$owner->setUsername($content->getOwnerId());
$owner->loadUser();
?>

<div class="container-fluid">
    <!-- Navbar -->
    <div class="row justify-content-between border-bottom pt-2 pb-2">
        <div class="col">
            <!-- Logo (common/favicon.webp) -->
            <a href="home.php">
                <img src="common/favicon.webp" alt="GCA's Baseline" width="40" height="40">
            </a>
        </div>
        <div class="col">
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
                         style="width: 300px;" data-aos="zoom-in">
                        <h5 class="text-white">Notifications</h5>
                        <hr class="text-white">
                        <?php
                        $notifications = $user->getAllNotifications();
                        if (empty($notifications)) {
                            echo '<p class="text-white">You have no notifications.</p>';
                        } else {
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
                                    echo '<div class="d-flex align-items-start mb-2 rounded-3 new-notification">';
                                } else {
                                    echo '<div class="d-flex align-items-start mb-2 rounded-3">';
                                }
                                echo '<i class="' . $icon . '" style="color: ' . $color . '; font-size: 24px;"></i>';
                                echo '<div class="ms-2">';
                                echo '<h6 class="text-white">' . $title . '</h6>';
                                echo '<p class="text-white mb-1">' . $description . '</p>';
                                echo '<small class="text-white">' . $date . '</small>';
                                echo '</div>';
                                echo '</div>';
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
                                   id="upload-button" data-mdb-toggle="animation" data-mdb-animation-start="onHover"
                                   data-mdb-animation="slide-out-right" href="upload-content.php">Upload</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="container-xxl">
        <div class="row mt-4 justify-content-center">
            <div class="col">
                <div class="row justify-content-center px-xxl-3 pt-2 pb-3">
                    <!-- Image -->
                    <div class="col-12 rounded-3 text-center px-0">
                        <a href="<?= $content->getUrlImage() ?>" target="_blank">
                            <img src="<?= $content->getUrlImage() ?>" class="img-fluid rounded-3" id="image" alt="Image"
                                 onerror="hideSpinner(this)">
                        </a>
                    </div>
                </div>
            </div>
            <hr class="mb-2">
            <div class="row justify-content-center d-flex align-items-center">
                <!-- User's icon, title, owner -->
                <div class="col-12 col-lg-6">
                    <div class="row justify-content-center d-flex align-items-center">
                        <!-- Icon of user -->
                        <div class="col-3 text-end">
                            <a href="profile.php?username=<?= $content->getOwnerId(); ?>" class="m-auto">
                                <img src="<?= $owner->getUrlProfilePicture(); ?>"
                                     class="img-fluid rounded-circle border-gradient" alt="Profile Picture" width="100"
                                     onerror="hideSpinner(this)">
                            </a>
                        </div>
                        <!-- Title and owner name of content -->
                        <div class="col-9 text-center text-lg-start">
                            <h2><?= $content->getTitle() ?></h2>
                            <h6>by
                                <a href="profile.php?username=<?= $content->getOwnerId(); ?>"><?= $content->getOwnerId(); ?></a>
                            </h6>
                        </div>
                    </div>
                </div>
                <!-- Tags -->
                <div class="col-12 col-lg-6 mt-3 mb-2 mt-lg-0 mb-lg-0">
                    <!-- Get all tags of post, print them out, they should be clickable and redirect to search.php?tag=tagname -->
                    <div class="row justify-content-evenly">
                        <?php
                        $tags = $content->getTagsOfContent();
                        foreach ($tags as $tag) {
                            echo '<div class="col-auto text-center p-0">';
                            echo '<a href="search.php?tag=' . $tag->getName() . '" class="btn btn-outline-light m-1">' . $tag->getName() . '</a>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row justify-content-between px-lg-5 pt-2 pb-2 bg-light bg-opacity-10 rounded-3 d-flex align-items-center" id="content-stats">
                <?php
                if ($user->getUsername() == $content->getOwnerId()) {
                    echo '<div class="col-auto">';
                    echo '<div class="row justify-content-center">';
                    echo '<div class="col-auto">';
                    echo '<button class="btn btn-outline-light fs-6" id="delete-button" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-mdb-toggle="animation" data-mdb-animation-start="onHover" data-mdb-animation="slide-out-right"><i class="fas fa-trash"></i> Delete</button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                } else {
                ?>
                <!-- Follow/Unfollow -->
                <div class="col-auto">
                    <div class="row justify-content-center d-flex align-items-center">
                        <div class="col-auto">
                            <div class="row justify-content-center">
                                <div class="col-auto">
                                    <?php
                                    if ($user->isFollowing($content->getOwnerId())) {
                                        echo '<button class="btn btn-outline-light fs-6" id="unfollow-button" data-bs-toggle="tooltip" data-bs-placement="top" title="Unfollow" data-mdb-toggle="animation" data-mdb-animation-start="onHover" data-mdb-animation="slide-out-right"><i class="fas fa-user-minus"></i> Unfollow</button>';
                                    } else {
                                        echo '<button class="btn btn-outline-light fs-6" id="follow-button" data-bs-toggle="tooltip" data-bs-placement="top" title="Follow" data-mdb-toggle="animation" data-mdb-animation-start="onHover" data-mdb-animation="slide-out-right"><i class="fas fa-user-plus"></i> Follow</button>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php }?>
                <!-- Likes -->
                <div class="col-auto">
                    <div class="row justify-content-center">
                        <div class="col-auto">
                            <div class="row justify-content-center d-flex align-items-center">
                                <div class="col-auto pe-0 d-flex align-items-center">
                                    <?php
                                    // If has liked content, use fas fa-heart, else use far fa-heart
                                    if ($user->hasLikedContent($content->getContentId())) {
                                        echo '<i class="fas fa-heart text-danger" style="font-size: 24px;" id="likeButton"></i>';
                                    } else {
                                        echo '<i class="far fa-heart text-danger" style="font-size: 24px;" id="likeButton"></i>';
                                    }
                                    ?>
                                </div>
                                <div class="col-auto pe-0">
                                    <h6 class="d-inline" id="likesCount"><?= $content->getNumberOfLikes() ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Views -->
                <div class="col-auto">
                    <div class="row justify-content-center d-flex align-items-center">
                        <div class="col-auto">
                            <div class="row justify-content-center d-flex align-items-center">
                                <div class="col-auto pe-0 d-flex align-items-center">
                                    <i class="fas fa-eye text-primary-emphasis opacity-75" style="font-size: 24px;"></i>
                                </div>
                                <div class="col-auto pe-0">
                                    <h6 class="d-inline"><?= $content->getNumberOfViews() ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Comments -->
                <div class="col-auto">
                    <div class="row justify-content-center d-flex align-items-center">
                        <div class="col-auto">
                            <div class="row justify-content-center d-flex align-items-center">
                                <div class="col-auto pe-0 d-flex align-items-center">
                                    <i class="fas fa-comment text-light opacity-50" style="font-size: 24px;"></i>
                                </div>
                                <div class="col-auto">
                                    <h6 class="d-inline"><?= $content->getNumberOfComments() ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <p><?= html_entity_decode($content->getDescription()) ?></>
                </div>
            </div>

        </div>
    </div>

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
    })

    $(function (){
        // Like dislike button on click of #likeButton
        $('#likeButton').on("click", function () {

            // Check if far or fas
            var like = !!$('#likeButton').hasClass("far");

            // Send a post request to the server to like or dislike the content
            $.ajax({
                type: "POST",
                url: "actions/likesManager.php",
                data: {contentId: <?= $content->getContentId() ?>},
                success: function (data) {
                    // Set the number of likes
                    $('#likesCount').html(data);
                    // Check if like or dislike
                    if (like) {
                        // Change the icon to fas fa-heart
                        $('#likeButton').removeClass("far");
                        $('#likeButton').addClass("fas");

                        // Animation
                        $('#likeButton').addClass("animate__animated animate__heartBeat");
                        setTimeout(function(){
                            $('#likeButton').removeClass("animate__animated animate__heartBeat");
                        }, 1000);
                    } else {
                        // Change the icon to far fa-heart
                        $('#likeButton').removeClass("fas");
                        $('#likeButton').addClass("far");

                        // Animation
                        $('#likeButton').addClass("animate__animated animate__heartBeat");
                        setTimeout(function(){
                            $('#likeButton').removeClass("animate__animated animate__heartBeat");
                        }, 1000);
                    }
                },
                error: function () {
                    // Show an error message
                    console.log("Error while liking/disliking content");
                },
            });
        })
    })
</script>
</body>
</html>