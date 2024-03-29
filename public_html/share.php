<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once(dirname(__FILE__) . '/common/common-head.php');
    ?>
    <link rel="canonical" href="https://tales.anonymousgca.eu/share">
    <script src="data/util/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.3/purify.min.js"
            integrity="sha512-TBmnYz6kBCpcGbD55K7f4LZ+ykn3owqujFnUiTSHEto6hMA7aV4W7VDPvlqDjQImvZMKxoR0dNY5inyhxfZbmA=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <title>Share - Tales</title>
    <style>

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
    <!-- Navbar -->
    <div class="row justify-content-between border-bottom pt-2 pb-2">
        <div class="col-2">
            <!-- Logo (common/favicon.webp) -->
            <a href="home.php">
                <img src="common/favicon.webp" alt="GCA's Baseline" width="40" height="40" data-bs-toggle="tooltip" data-bs-placement="right" title="Homepage">
            </a>
        </div>
        <!-- Search box here -->
        <div class="col-6 col-md-7 col pe-0 d-flex align-items-center">
            <form class="w-100" action="search.php" method="GET">
                <div class="input-group">
                    <input class="form-control form-control-sm border-0 rounded-3" type="search" placeholder="Search"
                           aria-label="Search" name="search">
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
                            echo '<button class="btn btn-outline-danger w-100 btn-sm" id="delete-notifications" data-bs-toggle="tooltip" data-bs-placement="left" title="Click to delete all notifications">Clear all</button>';
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

    <!-- Content -->
    <div class="container-xxl">
        <div class="row mt-4 justify-content-center">
            <div class="col">
                <div class="row justify-content-center px-xxl-3 pt-2 pb-3">
                    <!-- Image -->
                    <div class="col-12 rounded-3 text-center px-0">
                        <a href="<?= $content->getUrlImage() ?>" target="_blank">
                            <img src="<?= $content->getUrlImage() ?>" class="img-fluid rounded-3" id="image" style="max-height: 85vh;"
                                 alt="Image" data-bs-toggle="tooltip" data-bs-placement="top" title="Fullscreen">
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
                                     class="img-fluid rounded-circle border-gradient" alt="Profile Picture" width="100" data-bs-toggle="tooltip" data-bs-placement="top" title="Artist's profile">
                            </a>
                        </div>
                        <!-- Title and owner name of content -->
                        <div class="col-9 text-center text-lg-start">
                            <h2><?= $content->getTitle() ?></h2>
                            <h6>by
                                <a href="profile.php?username=<?= $content->getOwnerId(); ?>"><?= $content->getOwnerId(); ?></a>
                                -
                                <span class="text-white opacity-75 d-inline"><?= $content->getFormattedDate() ?></span>
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
                            echo '<a href="search.php?search=' . preg_replace('/\s+/', '_', $tag->getName()) . '" class="btn btn-outline-light m-1">' . $tag->getName() . '</a>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row justify-content-between px-lg-5 pt-2 pb-2 bg-light bg-opacity-10 rounded-3 d-flex align-items-center"
                 id="content-stats">
                <?php
                if ($user->getUsername() == $content->getOwnerId()) {
                    echo '<div class="col-auto">';
                    echo '<div class="row justify-content-center">';
                    echo '<div class="col-auto">';
                    echo '<a href="edit.php?id=' . $content->getContentId() . '" class="btn btn-outline-light fs-6" id="edit-button" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><i class="fas fa-edit"></i> Edit</a>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    ?>
                    <!-- Follow/Unfollow -->
                    <div class="col-auto ps-0 ps-lg-5">
                        <div class="row justify-content-center d-flex align-items-center">
                            <div class="col-auto">
                                <div class="row justify-content-center">
                                    <div class="col-auto pe-0 pe-lg-2">
                                        <?php
                                        if ($user->isFollowing($content->getOwnerId())) {
                                            echo '<button class="btn btn-outline-light fs-6" id="followButton" data-bs-toggle="tooltip" data-bs-placement="top" title="Unfollow"><i class="fas fa-user-minus"></i> Unfollow</button>';
                                        } else {
                                            echo '<button class="btn btn-outline-light fs-6" id="followButton" data-bs-toggle="tooltip" data-bs-placement="top" title="Follow"><i class="fas fa-user-plus"></i> Follow</button>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <!-- Likes -->
                <div class="col-auto">
                    <div class="row justify-content-center">
                        <div class="col-auto">
                            <div class="row justify-content-center d-flex align-items-center">
                                <div class="col-auto pe-0 d-flex align-items-center">
                                    <?php
                                    // If has liked content, use fas fa-heart, else use far fa-heart
                                    if ($user->hasLikedContent($content->getContentId())) {
                                        echo '<i class="fas fa-heart text-danger" style="font-size: 24px;" id="likeButton" data-bs-toggle="tooltip" data-bs-placement="top" title="Unlike"></i>';
                                    } else {
                                        echo '<i class="far fa-heart text-danger" style="font-size: 24px;" id="likeButton" data-bs-toggle="tooltip" data-bs-placement="top" title="Like"></i>';
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
                                    <i class="fas fa-eye text-primary-emphasis opacity-75" style="font-size: 24px;" data-bs-toggle="tooltip" data-bs-placement="top" title="Views"></i>
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
                                    <i class="fas fa-comment text-light opacity-50" style="font-size: 24px;" data-bs-toggle="tooltip" data-bs-placement="top" title="Comments"></i>
                                </div>
                                <div class="col-auto">
                                    <h6 class="d-inline" id="commentsNumber"><?= $content->getNumberOfComments() ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4 mb-3">
                <div class="col-12">
                    <p><?= html_entity_decode($content->getDescription()) ?></>
                </div>
            </div>

            <hr>

            <!-- Comments area (Add comment, show comments) -->
            <div class="row mt-4">
                <div class="col-12">
                    <h4>Comments</h4>
                </div>

                <!-- Add comment -->
                <div class="col-12 mt-4">
                    <div class="row">
                        <div class="col-12">
                            <form id="commentForm">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a comment here" id="comment"
                                              style="height: 100px" maxlength="255"></textarea>
                                    <label for="comment">Write your comment here</label>
                                </div>
                                <div class="row justify-content-end mt-2">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-outline-custom w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Send comment">Submit comment
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <hr>

                    <!-- Show comments -->
                    <div class="row mt-4" id="comments">
                        <?php
                        $comments = $content->getCommentsOfContent();
                        foreach ($comments as $comment) {
                            $commenter = new User();
                            $commenter->setUsername($comment->getUserId());
                            $commenter->loadUser();
                            ?>
                            <!-- Comment -->
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-auto">
                                        <div class="row justify-content-center">
                                            <div class="col-auto">
                                                <a href="profile.php?username=<?= $commenter->getUsername() ?>"
                                                   class="text-decoration-none" data-bs-toggle="tooltip" data-bs-placement="left" title="User's profile">
                                                    <img src="<?= $commenter->getUrlProfilePicture() ?>"
                                                         class="rounded-circle" style="width: 50px; height: 50px;">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h6 class="d-inline"><?= $commenter->getUsername() ?></h6>
                                                        <p class="d-inline opacity-50"><?= $comment->getCommentDateWithoutSeconds() ?></p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <p><?= $comment->getCommentText() ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    if ($comment->getUserId() == $user->getUsername()) {
                                        ?>
                                        <!-- Button to delete/edit comment, it also contains the comment ID -->
                                        <div class="col-auto">
                                            <div class="dropdown">
                                                <button class="btn btn-outline-custom dropdown-toggle" type="button"
                                                        id="commentOptionsButton" data-bs-toggle="dropdown"
                                                        aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="commentOptionsButton">
                                                    <li>
                                                        <button class="dropdown-item btn btn-outline-danger text-danger"
                                                                id="deleteCommentButtonModal"
                                                                data-comment-id="<?= $comment->getCommentId() ?>"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteCommentModal"><i
                                                                    class="fas fa-times"></i> Delete
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn btn-outline-primary"
                                                                id="editCommentButton"
                                                                data-comment-id="<?= $comment->getCommentId() ?>"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editCommentModal"><i
                                                                    class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals section -->

    <!-- Edit comment modal -->
    <div class="modal fade" id="editCommentModal" tabindex="-1" aria-labelledby="editCommentModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCommentModalLabel">Edit comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCommentForm">
                        <input type="hidden" id="editCommentId" name="commentId">
                        <div class="mb-3">
                            <label for="editCommentText" class="form-label">Comment</label>
                            <textarea class="form-control" id="editCommentText" name="commentText" maxlength="255"
                                      minlength="1" rows="3" required></textarea>
                        </div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-outline-custom">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete comment modal -->
    <div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-labelledby="deleteCommentModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCommentModalLabel">Delete Comment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="h6 pt-3">Are you sure you want to delete this comment?</p>
                    <p class="fs-6 opacity-50">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-custom" id="deleteCommentButton">Delete</button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
include_once(dirname(__FILE__) . '/common/common-footer.php');
include_once(dirname(__FILE__) . '/common/common-body.php');
?>
<script src="data/util/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
<script>

    function Comment(comment) {
        // Assign the comment properties to the instance
        this.commentId = comment.commentId;
        this.commentUsername = comment.commentUsername;
        this.commentUserIconUrl = comment.commentUserIconUrl;
        this.commentDate = comment.commentDate;
        this.commentText = comment.commentText;

        Comment.prototype.render = function () {
            return `
    <div class="col-12">
      <div class="row">
        <div class="col-auto">
          <div class="row justify-content-center">
            <div class="col-auto">
              <a href="profile.php?username=${this.commentUsername}" class="text-decoration-none">
                <img src="${this.commentUserIconUrl}" class="rounded-circle" style="width: 50px; height: 50px;">
              </a>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="row">
            <div class="col-12">
              <div class="row">
                <div class="col-12">
                  <h6 class="d-inline">${this.commentUsername}</h6>
                  <p class="d-inline opacity-50"> ${this.commentDate}</p>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <p>${this.commentText}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-auto">
          <div class="dropdown">
            <button class="btn btn-outline-custom dropdown-toggle" type="button" id="commentOptionsButton" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-ellipsis-v me-2"></i>
            </button>
            <ul class="dropdown-menu" aria-labelledby="commentOptionsButton">
              <li><button class="dropdown-item btn btn-outline-danger text-danger" id="deleteCommentButtonModal" data-comment-id="${this.commentId}" data-bs-toggle="modal" data-bs-target="#deleteCommentModal"><i class="fas fa-times"></i> Delete</button></li>
              <li><button class="dropdown-item btn btn-outline-primary" id="editCommentButton" data-comment-id="${this.commentId}"><i class="fas fa-edit"></i> Edit</button></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  `;
        };

        Comment.prototype.renderAlt = function () {
            return `
    <div class="col-12">
      <div class="row">
        <div class="col-auto">
          <div class="row justify-content-center">
            <div class="col-auto">
              <a href="profile.php?username=${this.commentUsername}" class="text-decoration-none">
                <img src="${this.commentUserIconUrl}" class="rounded-circle" style="width: 50px; height: 50px;">
              </a>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="row">
            <div class="col-12">
              <div class="row">
                <div class="col-12">
                  <h6 class="d-inline">${this.commentUsername}</h6>
                  <p class="d-inline opacity-50"> ${this.commentDate}</p>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <p>${this.commentText}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
        };
    }

    // Util function to get, set, increase and decrease the comments number
    function CommentsNumber() {
        // Use const to declare a constant variable
        const commentsNumberElement = $("#commentsNumber");

        // Use arrow function for prototype method
        CommentsNumber.prototype.get = () => parseInt(commentsNumberElement.text());

        // Use arrow function for prototype method
        CommentsNumber.prototype.set = (number) => commentsNumberElement.text(number);

        // Use arrow function for prototype method
        CommentsNumber.prototype.increase = () => commentsNumberElement.text(parseInt(commentsNumberElement.text()) + 1);

        // Use arrow function for prototype method
        CommentsNumber.prototype.decrease = () => commentsNumberElement.text(parseInt(commentsNumberElement.text()) - 1);
    }


    $(function () {
        // Handle comment submit
        $('#commentForm').on("submit", function (e) {
            e.preventDefault();
            // Get the comment text
            var commentText = $('#comment').val();
            // Check length, if more than 255 or minor = 0, send jquery toast and stop
            if (commentText.length > 255 || commentText.length === 0) {
                $.toast({
                    heading: 'Error',
                    text: 'Comment must be between 1 and 255 characters',
                    showHideTransition: 'slide',
                    icon: 'error',
                    position: 'top-right',
                    loaderBg: '#ff0f7b'
                });
                return;
            }
            // Get the content id
            let contentId = <?= $content->getContentId() ?>;
            // Send a post request to the server to add the comment
            $.ajax({
                type: "POST",
                url: "actions/comments.php",
                data: {
                    commentText: commentText,
                    contentId: contentId,
                    action: "addComment"
                },
                success: function (response) {
                    // If the comment was added successfully
                    console.log("Comment added successfully");
                    // Delete input text
                    $('#comment').val("");

                    // Init Comment.
                    let commentElement = new Comment(JSON.parse(response));
                    let commentHtml = commentElement.render();

                    // Create a new comment element and prepend it to the comments section, with also an animation (slideDown)
                    $(commentHtml).prependTo('#comments').hide().slideDown(300);

                    // Toast
                    $.toast({
                        heading: 'Success',
                        text: 'Comment added successfully',
                        showHideTransition: 'slide',
                        icon: 'success',
                        position: 'top-right',
                        hideAfter: 1700,
                        bgColor: '#6600e1',
                        textColor: '#fff',
                        loaderBg: '#ff0f7b'
                    });

                    // Update #commentsNumber
                    let commentsNumber = new CommentsNumber();
                    commentsNumber.increase();
                }
            });
        });

        var commentId;

        // Handle comment edit
        $('#comments').on("click", "#deleteCommentButtonModal", function () {
            // Get the comment id
            commentId = $(this).data("comment-id");
            console.log("Comment id: " + commentId);
        });

        // Handle comment delete
        $('#deleteCommentModal').on("click", "#deleteCommentButton", function () {

            // Check if commentId is set
            if (commentId === undefined) {
                console.log("Comment id not set");
                // Error toast
                $.toast({
                    heading: 'Error',
                    text: 'Comment id not set',
                    showHideTransition: 'slide',
                    icon: 'error',
                    position: 'top-right',
                    loaderBg: '#ff0f7b'
                });
                return;
            }

            // Send a post request to the server to delete the comment
            $.ajax({
                type: "POST",
                url: "actions/comments.php",
                data: {
                    contentId: <?= $content->getContentId() ?>,
                    commentId: commentId,
                    action: "deleteComment"
                },
                success: function (response) {

                    // Log for debug purposes.
                    console.log("Comment deleted successfully, status: " + response);

                    // Close modal
                    $('#deleteCommentModal').modal('hide');

                    // If comment deleted with success, remove it from the comments section, with an animation effect (the opposite of addComment)
                    $('#comments').find("[data-comment-id='" + commentId + "']").parent().parent().parent().parent().parent().slideUp(300, function () {
                        $(this).remove();
                    });

                    // Toast
                    $.toast({
                        heading: 'Success',
                        text: 'Comment deleted successfully',
                        showHideTransition: 'slide',
                        icon: 'success',
                        position: 'top-right',
                        hideAfter: 1700,
                        bgColor: '#6600e1',
                        textColor: '#fff',
                        loaderBg: '#ff0f7b'
                    });

                    // Update #commentsNumber
                    let commentsNumber = new CommentsNumber();
                    commentsNumber.decrease();
                }
            });
        });

        // Handle comment edit
        $('#comments').on("click", "#editCommentButton", function () {
            // Get the comment id
            commentId = $(this).data("comment-id");
            // Get the comment text with ajax
            $.ajax({
                type: "POST",
                url: "actions/comments.php",
                data: {
                    contentId: <?= $content->getContentId() ?>,
                    commentId: commentId,
                    action: "getCommentText"
                },
                success: function (response) {
                    // If the comment text was retrieved successfully
                    console.log("Comment text retrieved successfully");
                    // Create a modal with the comment text
                    $('#editCommentModal').find("textarea").val(response);
                    // Show the modal
                    $('#editCommentModal').modal('show');
                }
            });
        });

        // Handle modal submit
        $('#editCommentModal').off("submit").on("submit", "#editCommentForm", function (e) {
            e.preventDefault();
            // Get the comment text
            var commentText = $('#editCommentModal').find("textarea").val();
            // Check length, if more than 255 or minor = 0, send jquery toast and stop
            if (commentText.length > 255 || commentText.length === 0) {
                $.toast({
                    heading: 'Error',
                    text: 'Comment must be between 1 and 255 characters',
                    showHideTransition: 'slide',
                    icon: 'error',
                    position: 'top-right',
                    loaderBg: '#ff0f7b'
                });
                return;
            }
            // Send a post request to the server to edit the comment
            $.ajax({
                type: "POST",
                url: "actions/comments.php",
                data: {
                    contentId: <?= $content->getContentId() ?>,
                    commentId: commentId,
                    commentText: commentText,
                    action: "editComment"
                },
                success: function (response) {
                    // If the comment was edited successfully
                    console.log("Comment edited successfully");
                    // Hide the modal
                    $('#editCommentModal').modal('hide');
                    // Delete and rebuild the comment with animation
                    $('#comments').find("[data-comment-id='" + commentId + "']").parent().parent().parent().parent().parent().slideUp(300, function () {
                        $(this).remove();
                    });

                    // Init comment element
                    let commentElement = new Comment(JSON.parse(response));
                    var commentHtml = commentElement.render();

                    // Add the comment in the same position where it got removed
                    $('#comments').find("[data-comment-id='" + commentId + "']").parent().parent().parent().parent().parent().after(commentHtml);

                    // Send toast
                    $.toast({
                        heading: 'Success',
                        text: 'Comment edited successfully',
                        showHideTransition: 'slide',
                        icon: 'success',
                        position: 'top-right',
                        bgColor: '#6600e1',
                        textColor: '#fff',
                        loaderBg: '#ff0f7b'
                    });
                }
            });
        });
    });

    // Return string with comment text
    function getCommentTextAjax(commentId) {
        // Send a post request to the server to get the comment text
        $.ajax({
            type: "POST",
            url: "actions/comments.php",
            data: {
                contentId: <?= $content->getContentId() ?>,
                commentId: commentId,
                action: "getCommentText"
            },
            success: function (response) {
                // If the comment text was retrieved successfully
                console.log("Comment text retrieved successfully ");
                // Return the comment text
                return response;
            }
        });
    }

    // Method to reload comments of page.
    function reloadComments() {
        // Send a post request to the server to get the comments
        $.ajax({
            type: "GET",
            url: "actions/comments.php",
            data: {
                contentId: <?= $content->getContentId() ?>,
            },
            success: function (response) {
                // If the comments were retrieved successfully
                console.log("Comments retrieved successfully");
                // Get the comments array
                var commentsArray = JSON.parse(response);
                // Get the comments div
                var commentsDiv = $('#comments');
                // Empty the comments div
                commentsDiv.empty();
                // For each comment in the array
                for (var i = 0; i < commentsArray.length; i++) {
                    // Get the comment
                    var comment = commentsArray[i];
                    // InnerHTML each comment, also check isOwner, if true, add button to delete comment.
                    if (comment.isOwner) {

                        // Init comment.
                        let commentElement = new Comment(comment);
                        let commentHtml = commentElement.render();

                        commentsDiv.append(commentHtml);
                    } else {

                        // Init comment.
                        let commentElement = new Comment(comment);
                        let commentHtml = commentElement.renderAlt();

                        commentsDiv.append(commentHtml);
                    }
                }
            }
        });
    }

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

    $(function () {
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
                        setTimeout(function () {
                            $('#likeButton').removeClass("animate__animated animate__heartBeat");
                        }, 1000);
                    } else {
                        // Change the icon to far fa-heart
                        $('#likeButton').removeClass("fas");
                        $('#likeButton').addClass("far");

                        // Animation
                        $('#likeButton').addClass("animate__animated animate__heartBeat");
                        setTimeout(function () {
                            $('#likeButton').removeClass("animate__animated animate__heartBeat");
                        }, 1000);
                    }
                },
                error: function () {
                    // Show an error message
                    console.log("Error while liking/disliking content");
                },
            });
        });
    });

    $(function () {
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
                data: {userId: "<?= $content->getOwnerId() ?>"},
                success: function () {
                    // Check if unfollow
                    if (unfollow) {
                        // Change the icon to fa-user-plus
                        $('#followButton').children().removeClass("fa-user-minus");
                        $('#followButton').children().addClass("fa-user-plus");
                        // Change the button text (without thouching the children <i> to Follow
                        contents[contents.length - 1].nodeValue = " Follow";
                        // Change button title
                        $('#followButton').attr("title", "Follow");
                    } else {
                        // Change the icon to fa-user-minus
                        $('#followButton').children().removeClass("fa-user-plus");
                        $('#followButton').children().addClass("fa-user-minus");
                        // Change the text to Unfollow
                        contents[contents.length - 1].nodeValue = " Unfollow";
                        // Change button title
                        $('#followButton').attr("title", "Unfollow");
                    }
                },
                error: function (data) {
                    // Show an error message
                    console.log("Error while following/unfollowing user, error: " + data);
                },
            });
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

    // Change title of the document to the name of the content
    document.title = "Share - <?= $content->getTitle() ?> - Tales";

    function hideSpinner(image) {
        image.classList.remove("bg-placeholder");
        image.style.opacity = "1";
    }
</script>
</body>
</html>