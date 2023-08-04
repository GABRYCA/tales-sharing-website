<?php
// Check if logged in
session_start();
include_once(dirname(__FILE__) . "/../../private/objects/User.php");
include_once(dirname(__FILE__) . "/../common/utility.php");

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: ../login.php");
    exit();
}

// If POST, handle the post with data, if GET, handle the get with data
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get action from the client
    if (empty($_POST["action"])) {
        exit("Action is empty");
    }

    $action = validate_input($_POST["action"]);

    switch ($action) {
        case "addComment":
        {

            // Gets contentId and commentText from the client, check if they're empty
            if (empty($_POST["contentId"])) {
                exit("ContentId is empty");
            }

            if (empty($_POST["commentText"])) {
                exit("CommentText is empty");
            }

            $contentId = validate_input($_POST["contentId"]);
            $commentText = validate_input($_POST["commentText"]);

            // Load session user.
            $user = new User();
            $user->setUsername($_SESSION["username"]);
            $user->loadUser();

            // Check if content is private, if it's, only the owner can post comments.
            $content = new Content();
            $content->setContentId($contentId);
            $content->loadContent();

            if ($content->getIsPrivate()) {
                if ($content->getOwnerId() != $user->getUsername()) {
                    exit("Content is private");
                }
            }

            // Check content length, if more than 255 characters or if 0, exit
            if (strlen($commentText) > 255 || strlen($commentText) == 0) {
                exit("Comment length is invalid");
            }

            // Use Content functions to add comment
            if (!$content->addCommentToContent($user->getUsername(), $commentText)) {
                exit("Error while adding comment: " . $content->getErrorStatus());
            }

            // Get comment just added and return it to the client
            $commentsArray = getCommentsArray($content);
            // Get the first element
            $comment = $commentsArray[0];
            exit(json_encode($comment));
        }
        case "deleteComment":
        {

            // Gets contentId and commentId from the client, check if they're empty
            if (empty($_POST["contentId"])) {
                exit("ContentId is empty");
            }

            if (empty($_POST["commentId"])) {
                exit("CommentId is empty");
            }

            $contentId = validate_input($_POST["contentId"]);
            $commentId = validate_input($_POST["commentId"]);

            // Load session user.
            $user = new User();
            $user->setUsername($_SESSION["username"]);
            $user->loadUser();

            // Check if content is private, if it's, only the owner can delete comments.
            $content = new Content();
            $content->setContentId($contentId);
            $content->loadContent();

            if ($content->getIsPrivate()) {
                if ($content->getOwnerId() != $user->getUsername()) {
                    exit("Content is private");
                }
            }

            // Check if the user is owner of the comment.
            $comment = new Comment();
            $comment->setCommentId($commentId);
            $comment->loadComment();

            if ($comment->getUserId() != $user->getUsername()) {
                exit("You can't delete this comment");
            }

            // Use Content functions to delete comment
            if (!$content->deleteCommentFromContent($commentId)) {
                exit("Error while deleting comment: " . $content->getErrorStatus());
            }

            // Return success
            exit("success");
        }

        case "editComment":
        {
            if (empty($_POST["contentId"])) {
                exit("ContentId is empty");
            }

            if (empty($_POST["commentId"])) {
                exit("CommentId is empty");
            }

            if (empty($_POST["commentText"])) {
                exit("CommentText is empty");
            }

            $contentId = validate_input($_POST["contentId"]);
            $commentId = validate_input($_POST["commentId"]);
            $commentText = validate_input($_POST["commentText"]);

            // Load session user.
            $user = new User();
            $user->setUsername($_SESSION["username"]);
            $user->loadUser();

            // Check if content is private, if it's, only the owner can edit comments.
            $content = new Content();
            $content->setContentId($contentId);
            $content->loadContent();

            if ($content->getIsPrivate()) {
                if ($content->getOwnerId() != $user->getUsername()) {
                    exit("Content is private");
                }
            }

            // Check if the user is owner of the comment.
            $comment = new Comment();
            $comment->setCommentId($commentId);
            $comment->loadComment();

            if ($comment->getUserId() != $user->getUsername()) {
                exit("You can't edit this comment");
            }

            // Check content length, if more than 255 characters or if 0, exit
            if (strlen($commentText) > 255 || strlen($commentText) == 0) {
                exit("Comment length is invalid");
            }

            // Use Content functions to edit comment
            if (!$content->editCommentOfContent($commentId, $commentText, $user->getUsername())) {
                exit("Error while editing comment: " . $content->getErrorStatus());
            }

            // Return updated comment.
            $commentsArray = getCommentsArray($content);
            // Get the element with commentId = $commentId
            foreach ($commentsArray as $comment) {
                if ($comment["commentId"] == $commentId) {
                    exit(json_encode($comment));
                }
            }

            exit("Error while editing comment: " . $content->getErrorStatus());
        }

        default:
        {
            exit("Invalid action");
        }
    }


} else if ($_SERVER["REQUEST_METHOD"] == "GET") {

    // Check contentId is not empty
    if (empty($_GET["contentId"])) {
        exit("ContentId is empty");
    }

    // Load session user.
    $user = new User();
    $user->setUsername($_SESSION["username"]);
    $user->loadUser();

    // I receive the contentId from the client
    $contentId = validate_input($_GET["contentId"]);

    // Create a new User object.
    $content = new Content();
    // Set the contentId
    $content->setContentId($contentId);
    // Get the content from the database
    $content->loadContent();

    // Check if content is private
    if ($content->getIsPrivate()) {
        // Check if user is owner
        if ($content->getOwnerId() != $user->getUsername()) {
            exit("Content is private");
        }
    }
    $commentsArray = getCommentsArray($content);

    // Return the comments array as json
    exit(json_encode($commentsArray));
} else {
    exit("Invalid request method.");
}

/**
 * @param Content $content
 * @return array
 */
function getCommentsArray(Content $content): array
{
    // Get the comments, with also User data of each comment (like Url, Username, etc)
    $comments = $content->getCommentsOfContent();

    // Custom array to store comment text, date, and load user data (profile url icon and name)
    $commentsArray = array();
    foreach ($comments as $comment) {
        $commentArray = array();
        $commentArray["commentText"] = $comment->getCommentText();
        $commentArray["commentDate"] = $comment->getCommentDate();
        $commentArray["commentUsername"] = $comment->getUserId();
        $commentArray["commentId"] = $comment->getCommentId();
        // If the current session user is the owner of the comment, add a flag to the array
        if ($comment->getUserId() == $_SESSION["username"]) {
            $commentArray["isOwner"] = true;
        } else {
            $commentArray["isOwner"] = false;
        }
        // Load User.
        $userComment = new User();
        $userComment->setUsername($comment->getUserId());
        $userComment->loadUser();
        // Add data to array
        $commentArray["commentUserIconUrl"] = $userComment->getUrlProfilePicture();
        $commentsArray[] = $commentArray;
    }
    return $commentsArray;
}