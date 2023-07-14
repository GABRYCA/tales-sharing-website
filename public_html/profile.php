<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once(dirname(__FILE__) . '/common/common-head.php');
    ?>
    <title>User Profile</title>
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
            background: linear-gradient(90deg, rgba(0, 97, 255, 1) 0%, rgba(255, 15, 123, 1) 100%) !important;
            transition: 0.4s ease-out !important;
        }

        #logout-button {
            color: #FF0F7BFF !important;
        }

        #bell:hover {
            cursor: pointer;
        }

        .new-notification {
            background-color: rgba(255, 15, 123, 0.54) !important;
        }

        #profile-stats {
            background: linear-gradient(90deg, rgb(255, 15, 123, 0.5) 0%, rgba(0, 97, 255, 0.5) 100%) !important;
        }

        .fa-user {
            transition: 0.2s ease-out !important;
        }

        .fa-user:hover {
            color: #FF0F7BFF !important;
        }

        .fa-heart {
            transition: 0.2s ease-out !important;
        }

        .fa-heart:hover {
            color: #FF0F7BFF !important;
        }

        .fa-calendar-alt {
            transition: 0.2s ease-out !important;
        }

        .fa-calendar-alt:hover {
            color: #FF0F7BFF !important;
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

        .gallery {
            transition: 0.2s ease-out !important;
        }

        .gallery:hover {
            background-color: rgba(255, 15, 123, 0.54) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 15, 123, 0.25) !important;
            filter: brightness(2);
            cursor: pointer;
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

// Load all necessary includes.
include_once(dirname(__FILE__) . '/../private/objects/User.php');
include_once(dirname(__FILE__) . '/../private/objects/Content.php');
include_once(dirname(__FILE__) . '/../private/objects/Gallery.php');
include_once(dirname(__FILE__) . '/common/utility.php');

// Get user from session
$user = new User();
$user->setUsername($_SESSION["username"]);
$user->loadUser();

$userProfile = new User();

if (!empty($_GET['username'])){
    $userProfile->setUsername(validate_input($_GET['username']));
    if (!$userProfile->loadUser()){
        // Say user not found and after 3 seconds redirect to home.
        echo '<div class="container-fluid text-center mt-5">';
        echo '<h1 class="display-1 text-danger">User not found ' . $userProfile->getUsername() . '</h1>';
        echo '<p class="text-white">Redirecting to home in 3 seconds...</p>';
        echo '</div>';
        header("refresh:3;url=home.php");
        exit();
    }
} else {
    $userProfile = $user;
}

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

    <!-- Main content -->
    <div class="row justify-content-center">
        <!-- Profile icon and name -->
        <div class="col">
            <!-- Cover image as background -->
            <div class="bg-image rounded-bottom-5" style="background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0)), url('<?php echo $user->getUrlCoverPicture(); ?>'), linear-gradient(to top, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0)); height: 300px; background-repeat: no-repeat; background-position: center; background-size: cover;">
                <!-- Profile icon in the center and name under id -->
                <div class="row justify-content-center align-items-end" style="height: 100%;">
                    <div class="col-auto">
                        <img src="<?php echo $userProfile->getUrlProfilePicture(); ?>" class="rounded-circle bg-dark shadow" width="150px"
                             height="150px">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Username and follow or edit -->
    <div class="row justify-content-center mt-4 mt-lg-3">
        <div class="col-auto d-flex justify-content-center">
            <h1 class="text-white mt-2"><?= $userProfile->getUsername(); ?></h1>
        </div>

        <!-- Buttons -->
        <?php
        if ($user->getUsername() != $userProfile->getUsername()) {
            // Follow or unfollow button.
            if ($user->isFollowing($userProfile->getUsername())) {
                echo '<div class="col-auto d-flex justify-content-center">';
                echo '<button class="btn btn-outline-light fs-6" id="followButton" data-bs-toggle="tooltip" data-bs-placement="top" title="Unfollow"><i class="fas fa-user-minus"></i> Unfollow</button>';
                echo '</div>';
            } else {
                echo '<div class="col-auto d-flex justify-content-center">';
                echo '<button class="btn btn-outline-light fs-6" id="followButton" data-bs-toggle="tooltip" data-bs-placement="top" title="Follow"><i class="fas fa-user-plus"></i> Follow</button>';
                echo '</div>';
            }
        } else {
            // Edit profile
            echo '<div class="col-auto d-flex justify-content-center">';
            // On click open the edit-profile.php page
            echo '<button class="btn btn-outline-light fs-6" id="editButton" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Profile" onclick="window.location.href=\'/edit-profile.php\'"><i class="fa fa-edit"></i> Edit profile</button>';
            echo '</div>';
        }
        ?>

    </div>

    <!-- User info (number of followers, registering date, etc.) -->
    <div class="row justify-content-evenly mt-3 mb-3 mx-1 pt-3 pb-3 bg-light bg-opacity-10 rounded-4 d-flex align-items-center" id="profile-stats">
        <!-- Number of followers -->
        <div class="col-auto" data-bs-toggle="tooltip" data-bs-placement="top" title="Followers - Click to see!" style="cursor: pointer">
            <div class="row justify-content-center d-flex align-items-center">
                <div class="col-auto pe-0 d-flex align-items-center">
                    <i class="fas fa-user text-light opacity-75" style="font-size: 24px;" data-bs-toggle="dropdown"></i>
                    <!-- Create a dropdown menu with the list of followers -->
                    <ul class="dropdown-menu">
                        <!-- Loop through the array of followers and display their icons and usernames -->
                        <?php
                        $followers = $userProfile->getFollowers();
                        if (count($followers) == 0) { ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <span>No followers yet!</span>
                                </a>
                            </li>
                        <?php } else {
                            foreach ($followers as $follower){ ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="profile.php?username=<?= $follower->getUsername() ?>">
                                        <!-- Display the follower's icon -->
                                        <img src="<?= $follower->getUrlProfilePicture() ?>" alt="<?= $follower->getUsername() ?>" class="rounded-circle me-2" width="32" height="32">
                                        <!-- Display the follower's username -->
                                        <span><?= $follower->getUsername() ?></span>
                                    </a>
                                </li>
                            <?php }
                        }?>
                    </ul>
                </div>
                <div class="col-auto pe-0">
                    <h6 class="d-inline" id="followersNumber"><?= $userProfile->getNumberOfFollowers() ?></h6>
                </div>
            </div>
        </div>


        <!-- Total likes received by user in all of its contents -->
        <div class="col-auto" data-bs-toggle="tooltip" data-bs-placement="top" title="Total likes received" style="cursor: help">
            <div class="row justify-content-center d-flex align-items-center">
                <div class="col-auto pe-0 d-flex align-items-center">
                    <i class="fas fa-heart text-light opacity-75" style="font-size: 24px;"></i>
                </div>
                <div class="col-auto pe-0">
                    <h6 class="d-inline"><?= $userProfile->getTotalLikesReceived() ?></h6>
                </div>
            </div>
        </div>
        <!-- Registering date -->
        <div class="col-auto" data-bs-toggle="tooltip" data-bs-placement="top" title="Join date" style="cursor: help">
            <div class="row justify-content-center d-flex align-items-center">
                <div class="col-auto pe-0 d-flex align-items-center">
                    <i class="fas fa-calendar-alt text-light opacity-75" style="font-size: 24px;"></i>
                </div>
                <div class="col-auto pe-0">
                    <h6 class="d-inline"><?= $userProfile->getJoinDateYearMonth() ?></h6>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <hr>

        <!-- Toggle to show content or galleries -->
        <div class="row justify-content-center">
            <div class="col-auto bg-light bg-opacity-10 pt-2 pb-2 rounded-3">
                <div class="form-check form-switch">
                    <input id="toggle-btn" class="form-check-input" type="checkbox" checked>
                    <label id="toggle-label" class="form-check-label" for="toggle-btn">Show Content</label>
                </div>
            </div>
        </div>

        <hr>

        <!-- Content of user -->
        <div class="row p-3 gap-0 justify-content-evenly gy-3" id="content">

            <?php
            $content = new Content();
            if ($user->getUsername() === $userProfile->getUsername()) {
                // If the user is visiting his own profile, show all his content (public and private)
                $contentArray = $content->getAllContentOfUser($userProfile->getUsername());
            } else {
                // If the user is visiting another profile, show only his public content (not private
                $contentArray = $content->getAllPublicContentOfUser($userProfile->getUsername());
            }

            // For each Content, print it out.
            // If there are no content, print out a message.
            if (count($contentArray) === 0) {
                echo '<div class="col-12"><h1 class="display-6 text-center">There is no content to show!</h1></div>';
            } else {

                // For each content make an icon to visit his profile
                foreach ($contentArray as $content) {
                    echo '<div class="col-12 col-lg-4 col-xxl-3">';
                    echo '<div class="img-wrapper position-relative text-center">';
                    echo '<img src="' . $content->getUrlImage() . '" alt="image" class="img-fluid rounded-4 img-thumbnail img-home" loading="lazy" onclick="window.location.href = \'/share.php?id=' . $content->getContentId() . '\'" data-aos="fade-up">';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        <!-- Galleries of user -->
        <div class="row p-3 gap-0 justify-content-evenly gy-3 d-none" id="galleries">

            <?php
            $gallery = new Gallery();
            $gallery->setOwnerId($userProfile->getUsername());
            if ($user->getUsername() === $userProfile->getUsername()) {
                // If the user is visiting his own profile, show all his content (public and private)
                $galleryArray = $gallery->getGalleriesByOwnerId();
            } else {
                // If the user is visiting another profile, show only his public content (not private
                $galleryArray = $gallery->getGalleriesByOwnerIdNotHidden();
            }

            // For each Content, print it out.
            // If there are no content, print out a message.
            if (count($galleryArray) === 0) {
                echo '<div class="col-12"><h1 class="display-6 text-center">There are not galleries to show!</h1></div>';
            } else {
                // Show galleries and also their name.
                foreach ($galleryArray as $gallery) {
                    echo '<div class="col-12 col-lg-4 col-xxl-3" onclick="window.location.href = \'gallery.php?id=' . $gallery->getGalleryId() . '\'">';
                    echo '<div class="img-wrapper position-relative text-center gallery">';
                    echo '<img src="common/assets/cover.webp" alt="image" class="img-fluid rounded-4 img-thumbnail img-home" loading="lazy" data-aos="fade-up">';
                    echo '<div class="img-overlay position-absolute top-0 start-0 w-100 h-100 rounded-4" style="background-color: rgba(0, 0, 0, 0.5);"></div>';
                    echo '<div class="img-text position-absolute top-50 start-50 translate-middle text-light">';
                    echo '<h1 class="display-6">' . $gallery->getName() . '</h1>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>


</div>

<?php
include_once(dirname(__FILE__) . '/common/common-footer.php');
include_once(dirname(__FILE__) . '/common/common-body.php');
?>
<script>
    $(function (){
        // Get the bell element
        var bell = document.getElementById("bell");
        // Add a click event listener to the bell
        $('#bell').on("click", function() {
            // Get the dropdown menu element
            var dropdown = document.querySelector(".notifications-dropdown");
            // Set a timeout of 1 second after the dropdown is opened
            setTimeout(function() {
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
                        success: function() {
                            // Remove the element notification-count
                            $('#notification-count').remove();
                        },
                        error: function() {
                            // Show an error message
                            console.log("Error while marking notifications as read");
                        }
                    });
                }
            }, 1000);
        });
    });

    $(function(){
        // Handle the deletion of all notifications on click of button #delete-notifications.
        $('#delete-notifications').on("click", function() {
            // Send a post request to the server to delete all notifications
            $.ajax({
                type: "POST",
                url: "actions/notifications.php",
                data: {delete: true},
                success: function() {
                    // Remove all the elements with class .notification
                    $('.notification').remove();
                    // Remove the element notification-count
                    $('#notification-count').remove();
                    // Remove notification-divider
                    $('.notification-divider').remove();
                    // Remove button #delete-notifications
                    $('#delete-notifications').remove();
                    // And replace it with text: You have no notifications.
                    $('.notifications-dropdown').append('<p class="text-white">You have no notifications.</p>');
                },
                error: function() {
                    // Show an error message
                    console.log("Error while deleting notifications");
                }
            });
        });
    });

    // Follow and unfollow.
    $(function(){
        // Handle follow and unfollow
        // If it has child <i> with class fa-user-minus, it means it is already following, so handle unfollow onClick, else
        // if it has fa-user-plus, it means it is not following, so handle follow onClick
        $('#followButton').on("click", function () {
            // Check if it has fa-user-minus
            var unfollow = !!$('#followButton').children().hasClass("fa-user-minus");
            var contents = $("#followButton").contents();

            // Send a post request to the server to follow or unfollow the user
            $.ajax({
                type: "POST",
                url: "actions/followManager.php",
                data: {userId: "<?= $userProfile->getUsername() ?>"},
                success: function (data) {
                    // Check if unfollow
                    if (unfollow) {
                        // Change the icon to fa-user-plus
                        $('#followButton').children().removeClass("fa-user-minus");
                        $('#followButton').children().addClass("fa-user-plus");
                        // Change the button text (without thouching the children <i> to Follow
                        contents[contents.length - 1].nodeValue = " Follow";
                        // Change button title
                        $('#followButton').attr("title", "Follow");
                        // Update the followersNumber element using data from the server
                        $('#followersNumber').text(data);
                    } else {
                        // Change the icon to fa-user-minus
                        $('#followButton').children().removeClass("fa-user-plus");
                        $('#followButton').children().addClass("fa-user-minus");
                        // Change the text to Unfollow
                        contents[contents.length - 1].nodeValue = " Unfollow";
                        // Change button title
                        $('#followButton').attr("title", "Unfollow");
                        // Update the followersNumber element using data from the server
                        $('#followersNumber').text(data);
                    }
                },
                error: function (data) {
                    // Show an error message
                    console.log("Error while following/unfollowing user, error: " + data);
                },
            });
        });
    });

    // Set a flag to indicate the current mode
    var showAll = true;

    // Show all content or galleries handler.
    $(function(){
        // Get the toggle button, the toggle label and the container elements
        var toggleBtn = $("#toggle-btn");
        var toggleLabel = $("#toggle-label");
        var container = $("#container");
        // Add a change event listener to the toggle button
        toggleBtn.on('change', function () {
            // Hide or show content and galleries based on the current mode
            if (showAll) {
                $('#content').addClass('d-none');
                $('#galleries').removeClass('d-none');
                toggleLabel.text("Show Gallery");
                showAll = false;
            } else {
                $('#content').removeClass('d-none');
                $('#galleries').addClass('d-none');
                toggleLabel.text("Show Content");
                showAll = true;
            }
        });
    });

    // Change title of the document to the name of the user
    document.title = "User Profile - <?= $userProfile->getUsername() ?>";

    function hideSpinner(image) {
        image.classList.remove("bg-placeholder");
        image.style.opacity = "1";
    }
</script>
</body>
</html>