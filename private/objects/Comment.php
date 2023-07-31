<?php
include_once (dirname(__FILE__) . "/../connection.php");

class Comment implements JsonSerializable
{
    private $commentId;
    private $userId;
    private $contentId;
    private $commentText;
    private $commentDate;
    private $errorStatus;

    /**
     * Function to load comment info from database following $commentId.
     * @return bool
     */
    public function loadComment() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Comment WHERE commentId = ?";

        if ($data = $conn->execute_query($sql, [$this->commentId])){
            $row = $data->fetch_assoc();
            $this->userId = $row["userId"];
            $this->contentId = $row["contentId"];
            $this->commentText = $row["commentText"];
            $this->commentDate = $row["commentDate"];
        } else {
            $this->setErrorStatus("Error while loading comment");
            return false;
        }

        return true;
    }

    /**
     * Function to add comment to a content using by userId, contentId and commentText.
     * Please set userId, contentId and commentText before calling this function.
     * @return bool
     */
    public function addComment() : bool
    {
        $conn = connection();

        $sql = "INSERT INTO Comment (userId, contentId, commentText) VALUES (?, ?, ?)";

        if ($conn->execute_query($sql, [$this->userId, $this->contentId, $this->commentText])) {
            return true;
        } else {
            $this->setErrorStatus("Error while adding comment");
            return false;
        }
    }

    /**
     * Function to delete comment by commentId.
     * Please set commentId before calling this function.
     * @return bool
     */
    public function deleteComment() : bool
    {
        $conn = connection();

        $sql = "DELETE FROM Comment WHERE commentId = ?";

        if ($conn->execute_query($sql, [$this->commentId])) {
            return true;
        } else {
            $this->setErrorStatus("Error while deleting comment");
            return false;
        }
    }

    /**
     * Function to update comment by commentId.
     * Please set commentId and new commentText before calling this function.
     * @return bool
     */
    public function updateComment() : bool
    {
        $conn = connection();

        $sql = "UPDATE Comment SET commentText = ? WHERE commentId = ?";

        if ($conn->execute_query($sql, [$this->commentText, $this->commentId])) {
            return true;
        } else {
            $this->setErrorStatus("Error while updating comment");
            return false;
        }
    }

    /**
     * Function to load all comments from database following $contentId.
     * @return Comment[]
     */
    public function getComments() : array
    {
        $conn = connection();

        $sql = "SELECT * FROM Comment WHERE contentId = ? ORDER BY commentDate DESC";

        if ($data = $conn->execute_query($sql, [$this->contentId])){
            $comments = [];
            while ($row = $data->fetch_assoc()) {
                $comment = new Comment();
                $comment->setCommentId($row["commentId"]);
                $comment->setUserId($row["userId"]);
                $comment->setContentId($row["contentId"]);
                $comment->setCommentText($row["commentText"]);
                $comment->setCommentDate($row["commentDate"]);
                $comments[] = $comment;
            }
        } else {
            $this->setErrorStatus("Error while loading comments");
            return [];
        }

        return $comments;
    }


    // Getters and setters
    public function getCommentId()
    {
        return $this->commentId;
    }

    public function setCommentId($commentId): void
    {
        $this->commentId = $commentId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }

    public function getContentId()
    {
        return $this->contentId;
    }

    public function setContentId($contentId): void
    {
        $this->contentId = $contentId;
    }

    public function getCommentText()
    {
        return $this->commentText;
    }

    public function setCommentText($commentText): void
    {
        $this->commentText = $commentText;
    }

    public function getCommentDate()
    {
        return $this->commentDate;
    }

    public function setCommentDate($commentDate): void
    {
        $this->commentDate = $commentDate;
    }

    public function getErrorStatus()
    {
        return $this->errorStatus;
    }

    public function setErrorStatus($errorStatus): void
    {
        $this->errorStatus = $errorStatus;
    }

    // Implement JsonSerializable
    public function jsonSerialize()
    {
        return [
            'commentId' => $this->commentId,
            'userId' => $this->userId,
            'contentId' => $this->contentId,
            'commentText' => $this->commentText,
            'commentDate' => $this->commentDate,
            'errorStatus' => $this->errorStatus
        ];
    }
}