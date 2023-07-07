<?php
include_once (dirname(__FILE__) . "/../connection.php");
include_once (dirname(__FILE__) . "/../objects/Notification.php");


/**
 * Class Followers
 * Contains followed and following users by specified $username.
 * Needs $username to be set.
 */
class Followers
{
    private $username;
    private $followers;
    private $following;
    private $errorStatus;
    private $domain = "https://tales.anonymousgca.eu/";

    // Constructor with username
    public function __construct($username)
    {
        $this->username = $username;
        $this->loadFollowers();
        $this->loadFollowing();
    }

    /**
     * Please if empty constructor is used, before set $username with setUsername().
     * Function to load followers from database following $username.
     * @return bool
     */
    public function loadFollowers() : bool
    {
        $conn = connection();

        $sql = "SELECT followerId FROM Follower WHERE userId = ?";

        if ($data = $conn->execute_query($sql, [$this->username])) {

            // Array of User using followerId
            $this->followers = array();
            while ($row = $data->fetch_assoc()){
                $user = new User();
                $user->setUsername($row["followerId"]);
                // Load user from database
                if ($user->loadUser()) {
                    $this->followers[] = $user;
                }
            }
            return true;
        } else {
            $this->setErrorStatus("Error while loading followers");
            return false;
        }
    }

    /**
     * Please if empty constructor is used, before set $username with setUsername().
     * Function to load following Users from database Follower by $username.
     * @return bool
     */
    public function loadFollowing() : bool {
        $conn = connection();

        // Query to get all users following $username and ordered by their last upload (using necessary inner join)
        $sql = "SELECT userId FROM Follower LEFT JOIN Content ON Follower.userId = Content.ownerId WHERE followerId = ? GROUP BY userId ORDER BY MAX(Content.uploadDate) DESC";

        if ($data = $conn->execute_query($sql, [$this->username])) {

            // Array of User following $username
            $this->following = array();
            while ($row = $data->fetch_assoc()){
                $user = new User();
                $user->setUsername($row["userId"]);
                // Load user from database
                if ($user->loadUser()) {
                    $this->following[] = $user;
                }
            }
            return true;
        } else {
            $this->setErrorStatus("Error while loading following");
            return false;
        }
    }

    /**
     * Function to follow a user.
     * @param $usernameToFollow
     * @return bool
     */
    public function follow($usernameToFollow) : bool
    {
        $conn = connection();

        $sql = "INSERT INTO Follower (userId, followerId) VALUES (?, ?)";

        if ($conn->execute_query($sql, [$usernameToFollow, $this->username])) {
            $this->reloadFollowers();

            // Send notification to the guy who received a new follower.
            $notification = new Notification();
            $notification->setUserId($usernameToFollow);
            $notification->setNotificationType("new_follow");
            $notification->setNotificationDate(date("Y-m-d H:i:s"));
            $notification->setTitle("New follower");
            $notification->setDescription("<a href='" . $this->domain . "profile.php?username=" . $this->username . "'>" .  $this->username . "</a> is now following you!");
            // Send notification
            $notification->insertNotification();

            return true;
        } else {
            $this->setErrorStatus("Error while following");
            return false;
        }
    }

    /**
     * Function to unfollow a user.
     * @param $usernameToUnfollow
     * @return bool
     */
    public function unfollow($usernameToUnfollow) : bool
    {
        $conn = connection();

        $sql = "DELETE FROM Follower WHERE userId = ? AND followerId = ?";

        if ($conn->execute_query($sql, [$usernameToUnfollow, $this->username])) {
            $this->reloadFollowers();
            return true;
        } else {
            $this->setErrorStatus("Error while unfollowing");
            return false;
        }
    }

    /**
     * Function to add follower to $username.
     * @param $usernameToAdd
     * @return bool
     */
    public function addFollower($usernameToAdd) : bool
    {
        $conn = connection();

        $sql = "INSERT INTO Follower (userId, followerId) VALUES (?, ?)";

        if ($conn->execute_query($sql, [$this->username, $usernameToAdd])) {
            $this->reloadFollowers();
            return true;
        } else {
            $this->setErrorStatus("Error while adding follower");
            return false;
        }
    }

    /**
     * Function to remove follower from $username.
     * @param $usernameToRemove
     * @return bool
     */
    public function removeFollower($usernameToRemove) : bool
    {
        $conn = connection();

        $sql = "DELETE FROM Follower WHERE userId = ? AND followerId = ?";

        if ($conn->execute_query($sql, [$this->username, $usernameToRemove])) {
            $this->reloadFollowers();
            return true;
        } else {
            $this->setErrorStatus("Error while removing follower");
            return false;
        }
    }

    /**
     * Function to reload followers and following.
     * @return bool
     */
    public function reloadFollowers() : bool
    {
        if ($this->username == null) {
            $this->setErrorStatus("Username not set");
            return false;
        }

        if (!$this->loadFollowers() || !$this->loadFollowing()) {
            $this->setErrorStatus("Error while reloading followers");
            return false;
        }

        return true;
    }

    /**
     * IF called with empty constructor.
     * Before please call loadFollowers() to load followers from database.
     * @return User[]
     */
    public function getFollowers() : array
    {
        return $this->followers;
    }

    /**
     * IF Called with empty constructor.
     * Before please call loadFollowing() to load following from database.
     * @return User[]
     */
    public function getFollowing() : array
    {
        return $this->following;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
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