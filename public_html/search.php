<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once(dirname(__FILE__) . '/common/common-head.php');
    ?>
    <title>Search - Tales</title>
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

        .user-icon-top {
            transition: 0.2s ease-out !important;
        }

        .user-icon-top:hover {
            background-color: rgba(255, 15, 123, 0.54) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 15, 123, 0.25) !important;
        }

        .img-home {
            max-height: 80vh !important;
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

        #bell:hover {
            cursor: pointer;
        }

        .new-notification {
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
?>

<?php
// Load all necessary includes.
include_once(dirname(__FILE__) . "/../private/connection.php");
include_once(dirname(__FILE__) . '/common/utility.php');
include_once(dirname(__FILE__) . '/../private/objects/User.php');
include_once(dirname(__FILE__) . '/../private/objects/Content.php');
include_once(dirname(__FILE__) . '/../private/objects/Followers.php');
include_once(dirname(__FILE__) . '/../private/objects/Tag.php');

// Get user from session
$user = new User();
$user->setUsername($_SESSION["username"]);
$user->loadUser();

// Empty array of search tags
$searchTags = array();
$originalSearchPrompt = "";

// Check if get request and search is set, if it's get values.
if (isset($_GET["search"])) {
    $search = validate_input($_GET["search"]);
    $originalSearchPrompt = $search;
    // Split the search into an array of tags
    $searchTags = explode(" ", $search);
    // Remove empty elements from the array
    $searchTags = array_filter($searchTags);
    // Remove duplicates from the array
    $searchTags = array_unique($searchTags);
}

?>

<div class="container-fluid">
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
                    <input class="form-control form-control-sm border-0 rounded-3" type="search" placeholder="Search"
                           aria-label="Search"
                           name="search" <?php if ($originalSearchPrompt != "") echo "value='" . $originalSearchPrompt . "'"; ?>>
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

    <div class="row row-horizon border-bottom text-center pt-1 pb-1 pt-lg-3 pb-lg-3 flex-nowrap" id="row-profiles">

        <?php
        // Get followed users and print them out.

        $followers = new Followers($user->getUsername());
        $followers->loadFollowing(); // Load following.
        $followedUsers = $followers->getFollowing(); // Get following.

        // If there are no followed users, print out a message.
        if (count($followedUsers) === 0) {
            echo '<div class="col-12"><h1 class="display-6">You are not following anyone!</h1></div>';
        } else {

            // For each user make an icon to visit his profile
            foreach ($followedUsers as $followedUser) {
                echo '<div class="col-3 col-md-2 col-xl-1">';
                echo '<a href="profile.php?username=' . $followedUser->getUsername() . '" data-bs-toggle="tooltip" title="' . $followedUser->getUsername() . '">';
                echo '<img src="' . $followedUser->getUrlProfilePicture() . '" alt="icon-user" class="img-fluid bg-placeholder bg-opacity-10 rounded-4 user-icon-top" width="50" height="50">';
                echo '</a>';
                echo '</div>';
            }
        }

        ?>
    </div>

    <!-- New Content (New design) -->
    <div class="row p-3 gap-0 justify-content-evenly gy-3">

        <!-- Message that says "search results for..." -->
        <div class="col-12 mt-4">
            <h1 class="display-6 text-center">Search results for: "<?php if ($originalSearchPrompt == "") {
                    echo "🤔";
                } else {
                    echo $originalSearchPrompt;
                } ?>"</h1>
        </div>

        <?php

        // If searchTags is more than 0, then search for tags.
        if (count($searchTags) > 0) {

            $conn = connection();
            $isUserFound = false;

            // Search if there's an activated User matching the search or similar.
            $query = "SELECT username, urlProfilePicture FROM User WHERE isActivated = 1 AND (";
            $params = [];
            foreach ($searchTags as $tag) {
                $query .= "username LIKE ? OR ";
                $params[] = "%$tag%";
            }
            $query = rtrim($query, "OR ") . ")";
            $result = $conn->execute_query($query, $params);

            // User Results
            if ($result && $result->num_rows > 0) {
                // For each row, print the user.
                echo '<hr class="mb-0">';
                echo '<div class="row row-horizon justify-content-center border-bottom text-center pt-3 pb-3 flex-nowrap">';
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="col-3 col-md-2 col-xl-1">';
                    echo '<a href="profile.php?username=' . $row["username"] . '" data-bs-toggle="tooltip" title="' . $row["username"] . '">';
                    echo '<img src="' . $row["urlProfilePicture"] . '" alt="icon-user" class="img-fluid bg-placeholder bg-opacity-10 rounded-4 user-icon-top" width="64" height="64">';
                    echo '</a>';
                    echo '</div>';
                }
                echo '</div>';
                $isUserFound = true;
            }

            // Get all the tags from the database.
            $tag = new Tag();
            $tagArray = $tag->getTagList();

            // Tags search for content
            if (!(count($tagArray) === 0)) {

                // Prepare the SQL query with placeholders for the array parameter
                $query = "SELECT c.contentId, COUNT(ta.tagId) AS matches
                          FROM Content c
                          JOIN TagAssociation ta ON c.contentId = ta.contentId
                          JOIN Tag t ON ta.tagId = t.tagId
                          WHERE t.name IN (?" . str_repeat(", ?", count($searchTags) - 1) . ")
                          GROUP BY c.contentId
                          ORDER BY matches DESC, c.contentId DESC";

                // Create a prepared statement from the query
                $stmt = $conn->prepare($query);

                // Bind the array parameter values to the statement
                // The "s" means that each value is bound as a string
                $stmt->bind_param(str_repeat("s", count($searchTags)), ...$searchTags);

                // Execute the statement
                $stmt->execute();

                // Get the result set from the statement
                $result = $stmt->get_result();

                // Fetch all the rows from the result set as an associative array
                $rows = $result->fetch_all(MYSQLI_ASSOC);

                // Close the statement and free the result set
                $stmt->close();
                $result->free();

                // If there are no rows, print out a message.
                if (count($rows) === 0 && !$isUserFound) {
                    echo '<div class="col-12"><h1 class="display-6 text-center">Woops, nothing found! 🤔</h1></div>';
                } else {
                    // For each row, get the content and print it out.
                    foreach ($rows as $row) {
                        $content = new Content();
                        $content->setContentId($row["contentId"]);
                        $content->loadContent();
                        echo '<div class="col-12 col-lg-4 col-xxl-3 d-flex align-items-stretch px-0 px-lg-2">';
                        echo '<div class="card border-0 bg-placeholder img-home w-100" data-aos="fade-up" onclick="window.location.href = \'/share.php?id=' . $content->getContentId() . '\'">';
                        echo '<div class="card-img-top img-wrapper position-relative text-center w-100 lazy-background" data-background="' . encode_url($content->getUrlImage()) . '" style="background-size: cover; background-position: center; height: 45vh;">';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
            }
        } else {
            // Empty search, show all public content.
            $content = new Content();
            $contentArray = $content->getAllPublicContent();

            if (count($contentArray) === 0) {
                echo '<div class="col-12"><h1 class="display-6 text-center">There is no content to show!</h1></div>';
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
        }


        ?>

    </div>

</div>

<?php
include_once(dirname(__FILE__) . '/common/common-footer.php');
include_once(dirname(__FILE__) . '/common/common-body.php');
?>
<script>
    $(function () {
        // Get the bell element
        var bell = document.getElementById("bell");
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
        // Handle the deletion of all notifications on click of button #delete-notifications.
        $('#delete-notifications').on("click", function () {
            // Send a post request to the server to delete all notifications
            $.ajax({
                type: "POST",
                url: "actions/notifications.php",
                data: {delete: true},
                success: function () {
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
                error: function () {
                    // Show an error message
                    console.log("Error while deleting notifications");
                }
            });
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

    function hideSpinner(image) {
        image.classList.remove("bg-placeholder");
        image.style.opacity = "1";
    }
</script>
</body>
</html>