<?php
include "../dbconnection.php";

class Likes
{
    private $errorStatus;

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
}