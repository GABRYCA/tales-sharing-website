<?php
include_once (dirname(__FILE__) . "/../connection.php");

class Friends
{
    private $username;
    private $friends;
    private $pendingInFriends;
    private $pendingOutFriends;
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

        $sql = "SELECT * FROM Friend WHERE senderId = ?";

        if ($data = $conn->execute_query($sql, [$this->username])){
            // If accepted is true, then the friend is accepted and add it to friends array, if not, add it to pendingOutFriends array.
            $this->friends = array();
            $this->pendingOutFriends = array();
            foreach ($data as $row) {
                $user = new User();
                if ($row[2] == 1) {
                    $user->setUsername($row[1]);
                    // Load user from database
                    if ($user->loadUser()) {
                        $this->friends[] = $user;
                    }
                } else {
                    $user->setUsername($row[1]);
                    // Load user from database
                    if ($user->loadUser()) {
                        $this->pendingOutFriends[] = $user;
                    }
                }
            }
        } else {
            $this->setErrorStatus("Error while loading friends");
            return false;
        }

        return true;
    }

    /**
     * Function to load friends that accepted the request.
     * @return bool
     */
    public function loadAcceptedFriends() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Friend WHERE senderId = ? AND accepted = 1";

        if ($data = $conn->execute_query($sql, [$this->username])){
            // If accepted is true, then the friend is accepted and add it to friends array, if not, add it to pendingOutFriends array.
            $this->friends = array();
            foreach ($data as $row) {
                $user = new User();
                $user->setUsername($row[1]);
                // Load user from database
                if ($user->loadUser()) {
                    $this->friends[] = $user;
                }
            }
        } else {
            $this->setErrorStatus("Error while loading accepted friends");
            return false;
        }

        return true;
    }

    /**
     * Function to get incoming friends requests.
     * @return bool
     */
    public function loadPendingInFriends() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Friend WHERE receiverId = ? AND accepted = 0";

        if ($data = $conn->execute_query($sql, [$this->username])){
            // If accepted is true, then the friend is accepted and add it to friends array, if not, add it to pendingOutFriends array.
            $this->pendingInFriends = array();
            foreach ($data as $row) {
                $user = new User();
                $user->setUsername($row[0]);
                // Load user from database
                if ($user->loadUser()) {
                    $this->pendingInFriends[] = $user;
                }
            }
        } else {
            $this->setErrorStatus("Error while loading pending in friends");
            return false;
        }

        return true;
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

    /**
     * @return mixed
     */
    public function getPendingInFriends()
    {
        return $this->pendingInFriends;
    }

    /**
     * @param mixed $pendingInFriends
     */
    public function setPendingInFriends($pendingInFriends): void
    {
        $this->pendingInFriends = $pendingInFriends;
    }

    /**
     * @return mixed
     */
    public function getPendingOutFriends()
    {
        return $this->pendingOutFriends;
    }

    /**
     * @param mixed $pendingOutFriends
     */
    public function setPendingOutFriends($pendingOutFriends): void
    {
        $this->pendingOutFriends = $pendingOutFriends;
    }
}