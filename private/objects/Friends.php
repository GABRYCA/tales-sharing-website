<?php
include "../dbconnection.php";

class Friends
{
    private $username;
    private $friends;
    private $errorStatus;

    // Constructor with username
    public function __construct($username)
    {
        $this->username = $username;
        $this->loadFriends();
    }

    /**
     * Please if empty constructor is used, before set $username with setUsername().
     * Function to load friends from database following $username.
     * @return bool
     */
    public function loadFriends() : bool
    {
        $conn = connection();

        $sql = "SELECT friendId FROM Friend WHERE userId1 = ?";

        if ($data = $conn->execute_query($sql, [$this->username])) {
            // Array of User using followerId
            $this->friends = array();
            foreach ($data as $row) {
                // Check if userId2 is a friend of $username
                $sql = "SELECT userId1 FROM Friend WHERE userId1 = ? AND friendId = ?";
                if ($conn->execute_query($sql, [$row[1], $this->username])) {
                    $user = new User();
                    $user->setUsername($row[1]);
                    // Load user from database
                    if ($user->loadUser()) {
                        $this->friends[] = $user;
                    }
                }
            }
            return true;
        } else {
            $this->setErrorStatus("Error while loading friends");
            return false;
        }
    }

    /**
     * Function to get friends.
     * @return array
     */
    public function getFriends() : array
    {
        return $this->friends;
    }

    /**
     * Function to get error status.
     * @return string
     */
    public function getErrorStatus() : string
    {
        return $this->errorStatus;
    }

    /**
     * Function to set error status.
     * @param string $errorStatus
     */
    public function setErrorStatus(string $errorStatus) : void
    {
        $this->errorStatus = $errorStatus;
    }

    /**
     * Function to set username.
     * @param string $username
     */
    public function setUsername(string $username) : void
    {
        $this->username = $username;
    }
}