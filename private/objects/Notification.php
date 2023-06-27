<?php

include_once (dirname(__FILE__) . "/../connection.php");
include_once (dirname(__FILE__) . "/../objects/Content.php");
include_once (dirname(__FILE__) . "/../objects/User.php");
include_once (dirname(__FILE__) . "/../objects/Followers.php");
include_once (dirname(__FILE__) . "/../objects/Friends.php");

class Notification implements JsonSerializable
{

    private $notificationId;
    private $userId;
    private $title;
    private $description;
    private $notificationType;
    private $notificationDate;
    private $viewed;
    private $errorStatus;

    /**
     * Function to load notification info from database following $notificationId.
     * Please set $notificationId before calling this function.
     * @return bool
     */
    public function loadNotification() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM Notification WHERE notificationId = ?";

        if ($data = $conn->execute_query($sql, [$this->notificationId])){
            if ($data->num_rows == 0) {
                $this->setErrorStatus("Notification not found");
                return false;
            }
            $row = $data->fetch_assoc();
            $this->userId = $row["userId"];
            $this->title = $row["title"];
            $this->description = $row["description"];
            $this->notificationType = $row["notificationType"];
            $this->notificationDate = $row["notificationDate"];
            $this->viewed = $row["viewed"];
        } else {
            $this->setErrorStatus("Error while loading notification");
            return false;
        }

        return true;
    }

    /**
     * Notification that loads all notifications of $userId.
     * Please set $userId before calling this function.
     * @return Notification[]
     */
    public function loadNotificationsByUser() : array
    {
        $conn = connection();

        $sql = "SELECT * FROM Notification WHERE userId = ? ORDER BY notificationDate DESC";

        $notifications = [];

        if ($data = $conn->execute_query($sql, [$this->userId])){
            while ($row = $data->fetch_assoc()) {
                $notification = new Notification();
                $notification->setNotificationId($row["notificationId"]);
                $notification->setUserId($row["userId"]);
                $notification->setTitle($row["title"]);
                $notification->setDescription($row["description"]);
                $notification->setNotificationType($row["notificationType"]);
                $notification->setNotificationDate($row["notificationDate"]);
                $notification->setViewed($row["viewed"]);
                $notifications[] = $notification;
            }
        } else {
            $this->setErrorStatus("Error while loading notifications");
            return [];
        }

        return $notifications;
    }

    /**
     * Function that insert the notification into the database.
     * Please set $userId, $title, $description, $notificationType, $notificationDate, $viewed before calling this function.
     * @return bool
     */
    public function insertNotification() : bool
    {
        $conn = connection();

        $sql = "INSERT INTO Notification (userId, title, description, notificationType, notificationDate, viewed) VALUES (?, ?, ?, ?, ?, ?)";

        if (!$conn->execute_query($sql, [$this->userId, $this->title, $this->description, $this->notificationType, $this->notificationDate, $this->viewed])){
            $this->setErrorStatus("Error while inserting notification");
            return false;
        }

        return true;
    }

    /**
     * Function to insert a notification to all user followers (of param $username which is essentially the userId).
     * Please set $userId, $title, $description, $notificationType, $notificationDate, $viewed before calling this function.
     * @param $username
     * @return bool
     */
    public function insertNotificationToFollowers($username) : bool
    {
        $followers = new Followers($username);
        $followers = $followers->getFollowers();
        foreach ($followers as $follower) {
            $notification = new Notification();
            $notification->setUserId($follower->getFollowerId());
            $notification->setTitle($this->title);
            $notification->setDescription($this->description);
            $notification->setNotificationType($this->notificationType);
            $notification->setNotificationDate($this->notificationDate);
            $notification->setViewed($this->viewed);
            if (!$notification->insertNotification()){
                $this->setErrorStatus("Error while inserting notification to follower");
                return false;
            }
        }
        return true;
    }

    /**
     * Function to insert a notification to all user friends (of param $username which is essentially the userId).
     * Please set $userId, $title, $description, $notificationType, $notificationDate, $viewed before calling this function.
     * @param $username
     * @return bool
     */
    public function insertNotificationToFriends($username) : bool
    {
        $friends = new Friends($username);
        $friends->loadAcceptedFriends();
        $friends = $friends->getFriends();
        foreach ($friends as $friend) {
            $notification = new Notification();
            $notification->setUserId($friend->getFriendId());
            $notification->setTitle($this->title);
            $notification->setDescription($this->description);
            $notification->setNotificationType($this->notificationType);
            $notification->setNotificationDate($this->notificationDate);
            $notification->setViewed($this->viewed);
            if (!$notification->insertNotification()){
                $this->setErrorStatus("Error while inserting notification to friend");
                return false;
            }
        }
        return true;
    }

    /**
     * Function to insert a notification to all user followers and friends (of param $username which is essentially the userId).
     * Please set $userId, $title, $description, $notificationType, $notificationDate, $viewed before calling this function.
     * @param $username
     * @return bool
     */
    public function insertNotificationToFollowersAndFriends($username) : bool
    {

        // To avoid double notifications for friends who are also followes, I save them in an array and check if they are already in the array before inserting the notification.
        $friendsAndFollowers = [];

        $friends = new Friends($username);
        $friends->loadAcceptedFriends();
        $friends = $friends->getFriends();
        foreach ($friends as $friend) {
            $notification = new Notification();
            $notification->setUserId($friend->getFriendId());
            $notification->setTitle($this->title);
            $notification->setDescription($this->description);
            $notification->setNotificationType($this->notificationType);
            $notification->setNotificationDate($this->notificationDate);
            $notification->setViewed($this->viewed);
            if (!$notification->insertNotification()){
                $this->setErrorStatus("Error while inserting notification to friend");
                return false;
            }
            $friendsAndFollowers[] = $friend->getFriendId();
        }

        $followers = new Followers($username);
        $followers = $followers->getFollowers();
        foreach ($followers as $follower) {

            // Check if follower is already a friend (and should've already received the notification)
            if (in_array($follower->getFollowerId(), $friendsAndFollowers)) {
                continue;
            }

            $notification = new Notification();
            $notification->setUserId($follower->getFollowerId());
            $notification->setTitle($this->title);
            $notification->setDescription($this->description);
            $notification->setNotificationType($this->notificationType);
            $notification->setNotificationDate($this->notificationDate);
            $notification->setViewed($this->viewed);
            if (!$notification->insertNotification()){
                $this->setErrorStatus("Error while inserting notification to follower");
                return false;
            }
        }

        return true;
    }

    /**
     * Function that update the notification into the database.
     * Please set $notificationId, $userId, $title, $description, $notificationType, $notificationDate, $viewed before calling this function.
     * @return bool
     */
    public function updateNotification() : bool
    {
        $conn = connection();

        $sql = "UPDATE Notification SET userId = ?, title = ?, description = ?, notificationType = ?, notificationDate = ?, viewed = ? WHERE notificationId = ?";

        if (!$conn->execute_query($sql, [$this->userId, $this->title, $this->description, $this->notificationType, $this->notificationDate, $this->viewed, $this->notificationId])){
            $this->setErrorStatus("Error while updating notification");
            return false;
        }

        return true;
    }

    /**
     * Function that delete the notification from the database.
     * Please set $notificationId before calling this function.
     * @return bool
     */
    public function deleteNotification() : bool
    {
        $conn = connection();

        $sql = "DELETE FROM Notification WHERE notificationId = ?";

        if (!$conn->execute_query($sql, [$this->notificationId])){
            $this->setErrorStatus("Error while deleting notification");
            return false;
        }

        return true;
    }

    /**
     * Get all notifications.
     * @return Notification[]
     */
    public function getAllNotifications() : array
    {
        $conn = connection();

        $sql = "SELECT * FROM Notification ORDER BY notificationDate DESC";

        $notifications = [];

        if ($data = $conn->execute_query($sql, [])){
            while ($row = $data->fetch_assoc()) {
                $notification = new Notification();
                $notification->setNotificationId($row["notificationId"]);
                $notification->setUserId($row["userId"]);
                $notification->setTitle($row["title"]);
                $notification->setDescription($row["description"]);
                $notification->setNotificationType($row["notificationType"]);
                $notification->setNotificationDate($row["notificationDate"]);
                $notification->setViewed($row["viewed"]);
                $notifications[] = $notification;
            }
        } else {
            $this->setErrorStatus("Error while loading notifications");
            return [];
        }

        return $notifications;
    }

    /**
     * Function to set all notification of $userId as viewed.
     * Please set $userId before calling this function.
     * @return bool
     */
    public function setAllNotificationsAsViewed() : bool
    {
        $conn = connection();

        $sql = "UPDATE Notification SET viewed = 1 WHERE userId = ?";

        if (!$conn->execute_query($sql, [$this->userId])){
            $this->setErrorStatus("Error while setting all notifications as viewed");
            return false;
        }

        return true;
    }

    /**
     * Shortcut function to set a single notification as viewed.
     * Please set $notificationId before calling this function.
     * @return bool
     */
    public function setNotificationAsViewed() : bool
    {
        // Load notification
        if (!$this->loadNotification()){
            $this->setErrorStatus("Error while loading notification");
            return false;
        }

        // Set as viewed.
        $this->setViewed(1);
        return $this->updateNotification();
    }

    /**
     * Set the notificationId.
     * @param $notificationId
     * @return bool
     */
    public function setNotificationId($notificationId) : bool
    {
        $this->notificationId = $notificationId;
        return true;
    }

    /**
     * Get the notificationId.
     * @return mixed
     */
    public function getNotificationId()
    {
        return $this->notificationId;
    }

    /**
     * Set the userId.
     * @param $userId
     * @return bool
     */
    public function setUserId($userId) : bool
    {
        $this->userId = $userId;
        return true;
    }

    /**
     * Get the userId.
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the title.
     * @param $title
     * @return bool
     */
    public function setTitle($title) : bool
    {
        $this->title = $title;
        return true;
    }

    /**
     * Get the title.
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the description.
     * @param $description
     * @return bool
     */
    public function setDescription($description) : bool
    {
        $this->description = $description;
        return true;
    }

    /**
     * Get the description.
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the notificationType.
     * @param $notificationType
     * @return bool
     */
    public function setNotificationType($notificationType) : bool
    {
        $this->notificationType = $notificationType;
        return true;
    }

    /**
     * Get the notificationType.
     * @return mixed
     */
    public function getNotificationType()
    {
        return $this->notificationType;
    }

    /**
     * Set the notificationDate.
     * @param $notificationDate
     * @return bool
     */
    public function setNotificationDate($notificationDate) : bool
    {
        $this->notificationDate = $notificationDate;
        return true;
    }

    /**
     * Get the notificationDate.
     * @return mixed
     */
    public function getNotificationDate()
    {
        return $this->notificationDate;
    }

    /**
     * Set the viewed.
     * @param $viewed
     * @return bool
     */
    public function setViewed($viewed) : bool
    {
        $this->viewed = $viewed;
        return true;
    }

    /**
     * Get the viewed.
     * @return mixed
     */
    public function getViewed()
    {
        return $this->viewed;
    }

    /**
     * Set the errorStatus.
     * @param $errorStatus
     * @return bool
     */
    public function setErrorStatus($errorStatus) : bool
    {
        $this->errorStatus = $errorStatus;
        return true;
    }

    /**
     * Get the errorStatus.
     * @return mixed
     */
    public function getErrorStatus()
    {
        return $this->errorStatus;
    }

    /**
     * Serialize the object to JSON.
     * @return array
     */
    public function jsonSerialize() : array
    {
        return [
            "notificationId" => $this->notificationId,
            "userId" => $this->userId,
            "title" => $this->title,
            "description" => $this->description,
            "notificationType" => $this->notificationType,
            "notificationDate" => $this->notificationDate,
            "viewed" => $this->viewed
        ];
    }

}