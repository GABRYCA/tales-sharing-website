<?php
include_once (dirname(__FILE__) . "/../connection.php");

class Likes implements JsonSerializable
{
    private $userId;
    private $contentId;
    private $errorStatus;
    
    /**
     * Function to load likes info from database following and $contentId.
     * Please set contentId before calling this function.
     * @return array
     */
    public function getLikesInternal() : array
    {
        $conn = connection();

        $sql = "SELECT * FROM Liked WHERE contentId = ?";

        if ($data = $conn->execute_query($sql, [$this->contentId])){
            $likes = [];
            while ($row = $data->fetch_assoc()) {
                $like = new Likes();
                $like->setUserId($row["userId"]);
                $like->setContentId($row["contentId"]);
                $likes[] = $like;
            }
        } else {
            $this->setErrorStatus("Error while loading likes");
            return [];
        }

        return $likes;
    }
    
    /**
     * Function to add a like to a content using set userId and contentId.
     * Please set userId and contentId before calling this function.
     * @return bool
     */
    public function addLikeInternal() : bool
    {
        $conn = connection();

        $sql = "INSERT INTO Liked (userId, contentId) VALUES (?, ?)";

        if ($conn->execute_query($sql, [$this->userId, $this->contentId])) {
            return true;
        } else {
            $this->setErrorStatus("Error while adding like");
            return false;
        }
    }

    /**
     * Function to remove a like to a content using set userId and contentId.
     * Please set userId and contentId before calling this function.
     * @return bool
     */
    public function removeLikeInternal() : bool
    {
        $conn = connection();

        $sql = "DELETE FROM Liked WHERE userId = ? AND contentId = ?";

        if ($conn->execute_query($sql, [$this->userId, $this->contentId])) {
            return true;
        } else {
            $this->setErrorStatus("Error while removing like");
            return false;
        }
    }

    /**
     * Function to reverse a like or dislike to a content depending on what is already present if there is.
     * Please set userId and contentId before calling this function.
     * @return bool
     */
    public function reverseLikeInternal() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Liked WHERE userId = ? AND contentId = ?";

        if ($data = $conn->execute_query($sql, [$this->userId, $this->contentId])) {
            if ($data->num_rows > 0) {
                return $this->removeLikeInternal();
            } else {
                return $this->addLikeInternal();
            }
        } else {
            $this->setErrorStatus("Error while reversing like");
            return false;
        }
    }

    /**
     * Function that returns if content is liked by user.
     * Please set userId and contentId before calling this function.
     * @return bool
     */
    public function isLikedInternal() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Liked WHERE userId = ? AND contentId = ?";

        if ($data = $conn->execute_query($sql, [$this->userId, $this->contentId])) {
            if ($data->num_rows > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            $this->setErrorStatus("Error while checking like");
            return false;
        }
    }

    /**
     * Function to add a like to a content.
     * @param $userId
     * @param $contentId
     * @return bool
     */
    public function addLike($userId, $contentId) : bool
    {
        $conn = connection();

        $sql = "INSERT INTO Liked (userId, contentId) VALUES (?, ?)";

        if ($conn->execute_query($sql, [$userId, $contentId])) {
            return true;
        } else {
            $this->setErrorStatus("Error while adding like");
            return false;
        }
    }

    /**
     * Function to remove a like to a content.
     * @param $userId
     * @param $contentId
     * @return bool
     */
    public function removeLike($userId, $contentId) : bool
    {
        $conn = connection();

        $sql = "DELETE FROM Liked WHERE userId = ? AND contentId = ?";

        if ($conn->execute_query($sql, [$userId, $contentId])) {
            return true;
        } else {
            $this->setErrorStatus("Error while removing like");
            return false;
        }
    }

    /**
     * Function to reverse a like or dislike to a content depending on what is already present if there is.
     * @param $userId
     * @param $contentId
     * @return bool
     */
    public function reverseLike($userId, $contentId) : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Liked WHERE userId = ? AND contentId = ?";

        if ($data = $conn->execute_query($sql, [$userId, $contentId])) {
            if ($data->num_rows > 0) {
                return $this->removeLike($userId, $contentId);
            } else {
                return $this->addLike($userId, $contentId);
            }
        } else {
            $this->setErrorStatus("Error while reversing like");
            return false;
        }
    }

    /**
     * Function to check if a user has liked a content.
     * @param $userId
     * @param $contentId
     * @return bool
     */
    public function hasLiked($userId, $contentId) : bool {
        $conn = connection();

        $sql = "SELECT * FROM Liked WHERE userId = ? AND contentId = ?";

        if ($data = $conn->execute_query($sql, [$userId, $contentId])) {
            if ($data->num_rows > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            $this->setErrorStatus("Error while checking if user has liked");
            return false;
        }
    }

    /**
     * Function to get the number of likes of a content.
     * @param $contentId
     * @return int
     */
    public function getLikes($contentId) : int {
        $conn = connection();

        $sql = "SELECT * FROM Liked WHERE contentId = ?";

        if ($data = $conn->execute_query($sql, [$contentId])) {
            return $data->num_rows;
        } else {
            $this->setErrorStatus("Error while getting likes");
            return 0;
        }
    }

    /**
     * Function to get the Users that liked a content.
     * @param $contentId
     * @return array
     */
    public function getLikedUsers($contentId) : array {
        $conn = connection();

        $sql = "SELECT userId FROM Liked WHERE contentId = ?";

        if ($data = $conn->execute_query($sql, [$contentId])) {
            $users = array();
            while ($row = $data->fetch_assoc()) {
                // Load user from database and add it to array
                $user = new User();
                $user->setUsername($row['userId']);
                if ($user->loadUser()){
                    $users[] = $user;
                }
            }
            return $users;
        } else {
            $this->setErrorStatus("Error while getting liked users");
            return array();
        }
    }
    
    // Getters and setters
    
    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }
    
    /**
     * @param mixed $userId
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }
    
    /**
     * @return mixed
     */
    public function getContentId()
    {
        return $this->contentId;
    }
    
    /**
     * @param mixed $contentId
     */
    public function setContentId($contentId): void
    {
        $this->contentId = $contentId;
    }

    /**
     * @return mixed
     */
    public function getErrorStatus()
    {
        return $this->errorStatus;
    }

    /**
     * @param mixed $errorStatus
     */
    public function setErrorStatus($errorStatus): void
    {
        $this->errorStatus = $errorStatus;
    }

    // Implement JsonSerializable
    public function jsonSerialize()
    {
        return [
            'userId' => $this->userId,
            'contentId' => $this->contentId,
            'errorStatus' => $this->errorStatus
        ];
    }
}