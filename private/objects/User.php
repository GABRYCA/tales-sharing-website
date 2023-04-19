<?php
include "../dbconnection.php";

// This class, using dbconnection.php, is used to load or create a user like a JavaBean.
class User
{
    private $username;
    private $gender;
    private $email;
    private $password;
    private $urlProfilePicture;
    private $urlCoverPicture;
    private $description;
    private $motto;
    private $showNSFW;
    private $ofAge;
    private $isActivated;
    private $isMuted;
    private $activationCode;
    private $joinDate;
    private $errorStatus;
    private $isPremium;
    private $subscriptionType;
    private $subscriptionDate;
    private $expiryDate;

    /**
     * Load user by username from database.
     * @return bool
     */
    public function loadUser() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM User WHERE username = ?";
        
        if ($data = $conn->execute_query($sql, [$this->username])){
            if ($data->num_rows > 0) {
                $row = $data->fetch_assoc();
                $this->setUsername($row["username"]);
                $this->setGender($row["gender"]);
                $this->setEmail($row["email"]);
                $this->setPassword($row["password"]);
                $this->setUrlProfilePicture($row["urlProfilePicture"]);
                $this->setUrlCoverPicture($row["urlCoverPicture"]);
                $this->setDescription($row["description"]);
                $this->setMotto($row["motto"]);
                $this->setShowNSFW($row["showNSFW"]);
                $this->setOfAge($row["ofAge"]);
                $this->setIsActivated($row["isActivated"]);
                $this->setIsMuted($row["isMuted"]);
                $this->setActivationCode($row["activationCode"]);
                $this->setJoinDate($row["joinDate"]);
            } else {
                echo "User not found";
                $this->setErrorStatus("User not found");
                return false;
            }
        } else {
            echo "Error: " . $conn->error;
            $this->setErrorStatus("Error: " . $conn->error);
            return false;
        }
        return true;
    }

    /**
     * Function to add a user to the database.
     * @return bool
     */
    public function addUserToDatabase(): bool
    {
        $conn = connection();

        $sql = "INSERT INTO User (username, gender, email, password, urlProfilePicture, urlCoverPicture, description, motto, showNSFW, ofAge, isActivated, isMuted, activationCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($conn->execute_query($sql, [$this->username, $this->gender, $this->email, $this->password, $this->urlProfilePicture, $this->urlCoverPicture, $this->description, $this->motto, $this->showNSFW, $this->ofAge, $this->isActivated, $this->isMuted, $this->activationCode])){
            echo "New User created successfully";
            $this->setErrorStatus("New User created successfully");
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            $this->setErrorStatus("Error: " . $sql . "<br>" . $conn->error);
            return false;
        }

        return true;
    }

    /**
     * Function to get object data about Premium of the user.
     * @return bool
     */
    public function getPremiumData(): bool {
        $conn = connection();

        $sql = "SELECT * FROM Premium WHERE userid = ?";

        if ($data = $conn->execute_query($sql, [$this->username])){
            if ($data->num_rows > 0) {
                $row = $data->fetch_assoc();
                $this->setIsPremium($row["isPremium"]);
                $this->setSubscriptionType("subscriptionType");
                $this->setSubscriptionDate("subscriptionDate");
                $this->setExpiryDate("expiryDate");
                
                // Check if expiration date is expired.
                if ($this->getExpiryDate() < date("Y-m-d")) {
                    $this->setIsPremium(0);
                    $this->setSubscriptionType(0);
                    $this->setSubscriptionDate(null);
                    $this->setExpiryDate(null);
                    $this->updatePasswordToDatabase();
                }
                
            } else {
                echo "0 results";
                $this->setErrorStatus("0 results");
                return false;
            }
        } else {
            echo "Error: " . $conn->error;
            $this->setErrorStatus("Error: " . $conn->error);
            return false;
        }
        return true;
    }

    /**
     * Function to register a user with input data (and all the checks necessaries) and sends email too, the password will be encrypted.
     * @return bool
     */
    public function registerUser(): bool
    {
        if ($this->loadUser()) {
            $this->setErrorStatus("Username already taken!");
            return false;
        }

        $this->setUrlProfilePicture("assets/img/profile.png");
        $this->setUrlCoverPicture("assets/img/cover.jpg");
        $this->setDescription("I'm a new user!");
        $this->setMotto("I'm a new user!");
        $this->setShowNSFW(0);
        $this->setOfAge(0);
        $this->setIsActivated(0);
        $this->setIsMuted(0);

        // Check if password is strong enough.
        if (strlen($this->getPassword()) < 8) {
            $this->setErrorStatus("Password too short!");
            return false;
        }

        if (!preg_match("#[0-9]+#", $this->getPassword())) {
            $this->setErrorStatus("Password must include at least one number!");
            return false;
        }

        if (!preg_match("#[a-zA-Z]+#", $this->getPassword())) {
            $this->setErrorStatus("Password must include at least one letter!");
            return false;
        }

        // Check if email is valid.
        if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $this->setErrorStatus("Invalid email!");
            return false;
        }

        // Encrypt password.
        $debugPassword = $this->getPassword();
        $this->setPassword(password_hash($this->getPassword(), PASSWORD_DEFAULT));

        // Check using password.
        if (!password_verify($debugPassword, $this->getPassword())) {
            $this->setErrorStatus("Password encryption failed!");
            return false;
        }

        // Generate random activation code.
        try {
            $this->setActivationCode(bin2hex(random_bytes(16)));
        } catch (Exception $e) {
            $this->setErrorStatus("Error: " . $e->getMessage());
            return false;
        }

        if ($this->addUserToDatabase()) {
            $to = $this->getEmail();
            $subject = "Account Activation";
            $message = "Hi " . $this->getUsername() . ",
            Thank you for registering! Please Click on the link below to activate your account.
            https://tales.anonymousgca.eu/activation.php?code=" . $this->getActivationCode();
            $headers = "From: noreply@tales.anonymousgca.eu";

            if (@mail($to, $subject, $message, $headers)) {
                $this->setErrorStatus("Email sent successfully!");
            } else {
                $this->setErrorStatus("Email sending failed!");
                // Delete User from database.
                $this->deleteUserFromDatabase();
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Function to delete user account from database.
     * @return bool
    */
    public function deleteUserFromDatabase(): bool
    {
        $conn = connection();

        $sql = "DELETE FROM User WHERE username = ?";

        if ($conn->execute_query($sql, [$this->username])){
            echo "User deleted successfully";
            $this->setErrorStatus("User deleted successfully");
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            $this->setErrorStatus("Error: " . $sql . "<br>" . $conn->error);
            return false;
        }

        return true;
    }

    /**
     * Function to reload object retrieving data from database.
     * @return bool
     */
    public function reloadData(): bool {
        return $this->loadUser();
    }

    /**
     * Function to activate user account.
     * @param $activationCode
     * @return bool
     */
    public function activateAccount($activationCode): bool {
        $conn = connection();
        
        $sql = "SELECT * FROM User WHERE username = ? AND activationCode = ?";
        
        if ($data = $conn->execute_query($sql, [$this->username, $activationCode])) {
            if ($data->num_rows > 0) {
                $this->setIsActivated(1);
                $this->setActivationCode("");
                if ($this->updateUserToDatabase()) {
                    return true;
                } else {
                    return false;
                }
            } else {
                $this->setErrorStatus("0 results");
                return false;
            }
        } else {
            $this->setErrorStatus("Error: " . $conn->error);
            return false;
        }
    }

    /**
     * Function to update user data to database.
     * @return bool
     */
    public function updateUserToDatabase() : bool
    {
        $conn = connection();

        // Query with the update of all the user data (except password and username).
        $sql = "UPDATE User SET gender = ?, email = ?, urlProfilePicture = ?, urlCoverPicture = ?, showNSFW = ?, ofAge = ?, isActivated = ?, isMuted = ?, activationCode = ? WHERE username = ?";

        if ($conn->execute_query($sql, [$this->getGender(), $this->getEmail(), $this->getUrlProfilePicture(), $this->getUrlCoverPicture(), $this->getShowNSFW(), $this->getOfAge(), $this->getIsActivated(), $this->getIsMuted(), $this->getActivationCode(), $this->getUsername()])) {
            echo "User updated successfully";
            $this->setErrorStatus("User updated successfully");
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            $this->setErrorStatus("Error: " . $sql . "<br>" . $conn->error);
            return false;
        }
        return true;
    }

    /**
     * Activate premium account.
     * @param $subscriptionType
     * @param $duration
     * @return bool
     */
    public function activatePremium($subscriptionType, $duration): bool {
        $conn = connection();

        $sql = "INSERT INTO Premium (userid, subscriptionType, expiryDate) VALUES (?, ?, ?)";

        // Check if duration is positive.
        if ($duration <= 0) {
            $this->setErrorStatus("Duration must be positive!");
            return false;
        }

        // Check if subscriptionType is valid (plus, pro, premium).
        if ($subscriptionType != "plus" && $subscriptionType != "pro" && $subscriptionType != "premium") {
            $this->setErrorStatus("Invalid subscription type!");
            return false;
        }

        // Get current MariaDB date and add duration to it.
        $expiryDate = $conn->execute_query("SELECT DATE_ADD(CURRENT_DATE(), INTERVAL ? MONTH)", [$duration])->fetch_row()[0];
        if ($conn->execute_query($sql, [$this->username, $subscriptionType, $expiryDate])) {
            echo "Premium activated successfully";
            $this->setErrorStatus("Premium activated successfully");
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            $this->setErrorStatus("Error: " . $sql . "<br>" . $conn->error);
            return false;
        }

        return true;
    }

    /**
     * Function to update premium status.
     * @return bool
     */
    public function updatePremiumToDatabase() : bool
    {
        $conn = connection();

        $sql = "UPDATE Premium SET subscriptionType = ?, subscriptionDate = ?, expiryDate = ? WHERE userid = ?";
        if ($conn->execute_query($sql, [$this->getSubscriptionType(), $this->getSubscriptionDate(), $this->getExpiryDate(), $this->getUsername()])) {
            echo "Premium updated successfully";
            $this->setErrorStatus("Premium updated successfully");
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            $this->setErrorStatus("Error: " . $sql . "<br>" . $conn->error);
            return false;
        }
        return true;
    }

    /**
     * Function to change user password.
     * @param $oldPassword
     * @param $newPassword
     * @return bool
     */
    public function changePassword($oldPassword, $newPassword) : bool
    {
        $conn = connection();

        // Convert oldPassword into an encrypted password to compare with $this->password.
        $oldPassword = password_hash($oldPassword, PASSWORD_DEFAULT);

        // Check if oldPassword is correct.
        if ($oldPassword != $this->password) {
            $this->setErrorStatus("Old password is incorrect!");
            return false;
        }

        // Check if newPassword is valid.
        if (strlen($newPassword) < 8) {
            $this->setErrorStatus("New password must be at least 8 characters long!");
            return false;
        }

        if (!preg_match("#[0-9]+#", $this->getPassword())) {
            $this->setErrorStatus("Password must include at least one number!");
            return false;
        }

        if (!preg_match("#[a-zA-Z]+#", $this->getPassword())) {
            $this->setErrorStatus("Password must include at least one letter!");
            return false;
        }

        // Convert newPassword into an encrypted password.
        $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->setPassword($newPassword);

        // Update password in database.
        return $this->updatePasswordToDatabase();
    }

    /**
     * Function to update user password.
     * @return bool
     */
    public function updatePasswordToDatabase() : bool
    {
        $conn = connection();

        $sql = "UPDATE User SET password = ? WHERE username = ?";
        if ($conn->execute_query($sql, [$this->getPassword(), $this->getUsername()])) {
            echo "Password updated successfully";
            $this->setErrorStatus("Password updated successfully");
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            $this->setErrorStatus("Error: " . $sql . "<br>" . $conn->error);
            return false;
        }

        return true;
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
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param mixed $gender
     */
    public function setGender($gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getUrlProfilePicture()
    {
        return $this->urlProfilePicture;
    }

    /**
     * @param mixed $urlProfilePicture
     */
    public function setUrlProfilePicture($urlProfilePicture): void
    {
        $this->urlProfilePicture = $urlProfilePicture;
    }

    /**
     * @return mixed
     */
    public function getUrlCoverPicture()
    {
        return $this->urlCoverPicture;
    }

    /**
     * @param mixed $urlCoverPicture
     */
    public function setUrlCoverPicture($urlCoverPicture): void
    {
        $this->urlCoverPicture = $urlCoverPicture;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getMotto()
    {
        return $this->motto;
    }

    /**
     * @param mixed $motto
     */
    public function setMotto($motto): void
    {
        $this->motto = $motto;
    }

    /**
     * @return mixed
     */
    public function getShowNSFW()
    {
        return $this->showNSFW;
    }

    /**
     * @param mixed $showNSFW
     */
    public function setShowNSFW($showNSFW): void
    {
        $this->showNSFW = $showNSFW;
    }

    /**
     * @return mixed
     */
    public function getOfAge()
    {
        return $this->ofAge;
    }

    /**
     * @param mixed $ofAge
     */
    public function setOfAge($ofAge): void
    {
        $this->ofAge = $ofAge;
    }

    /**
     * @return mixed
     */
    public function getIsActivated()
    {
        return $this->isActivated;
    }

    /**
     * @param mixed $isActivated
     */
    public function setIsActivated($isActivated): void
    {
        $this->isActivated = $isActivated;
    }

    /**
     * @return mixed
     */
    public function getIsMuted()
    {
        return $this->isMuted;
    }

    /**
     * @param mixed $isMuted
     */
    public function setIsMuted($isMuted): void
    {
        $this->isMuted = $isMuted;
    }

    /**
     * @return mixed
     */
    public function getActivationCode()
    {
        return $this->activationCode;
    }

    /**
     * @param mixed $activationCode
     */
    public function setActivationCode($activationCode): void
    {
        $this->activationCode = $activationCode;
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

    /**
     * @return mixed
     */
    public function getIsPremium()
    {
        return $this->isPremium;
    }

    /**
     * @param mixed $isPremium
     */
    public function setIsPremium($isPremium): void
    {
        $this->isPremium = $isPremium;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionType()
    {
        return $this->subscriptionType;
    }

    /**
     * @param mixed $subscriptionType
     */
    public function setSubscriptionType($subscriptionType): void
    {
        $this->subscriptionType = $subscriptionType;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionDate()
    {
        return $this->subscriptionDate;
    }

    /**
     * @param mixed $subscriptionDate
     */
    public function setSubscriptionDate($subscriptionDate): void
    {
        $this->subscriptionDate = $subscriptionDate;
    }

    /**
     * @return mixed
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * @param mixed $expiryDate
     */
    public function setExpiryDate($expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    /**
     * @return mixed
     */
    public function getJoinDate()
    {
        return $this->joinDate;
    }

    /**
     * @param mixed $joinDate
     */
    public function setJoinDate($joinDate): void
    {
        $this->joinDate = $joinDate;
    }
}