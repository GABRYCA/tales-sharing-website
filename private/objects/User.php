<?php
include "../dbconnection.php";

// This class, using dbconnection.php, is used to load or create a user like a JavaBean.
class User
{
    private string $username;
    private string $gender;
    private string $email;
    private string $password;
    private string $urlProfilePicture;
    private string $urlCoverPicture;
    private string $description;
    private string $motto;
    private bool $showNSFW;
    private bool $ofAge;
    private bool $isActivated;
    private bool $isMuted;
    private string $activationCode;
    private mixed $joinDate;
    private string $errorStatus;
    private bool $isPremium;
    private string $subscriptionType;
    private mixed $subscriptionDate;
    private mixed $expiryDate;

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
    public function getPremiumData(): bool
    {
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

        // Check if email is already used.
        if ($this->emailExists()) {
            $this->setErrorStatus("Email already used!");
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
     * Function to check if email is already used.
     * @return bool
     */
    public function emailExists(): bool
    {
        $conn = connection();

        $sql = "SELECT * FROM User WHERE email = ?";

        if ($data = $conn->execute_query($sql, [$this->email])){
            if ($data->num_rows > 0) {
                $this->setErrorStatus("Email already taken!");
                return true;
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
    public function reloadData(): bool
    {
        return $this->loadUser();
    }

    /**
     * Function to activate user account.
     * @param $activationCode
     * @return bool
     */
    public function activateAccount($activationCode): bool
    {
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
    public function activatePremium($subscriptionType, $duration): bool
    {
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
        if ($oldPassword == $newPassword) {
            $this->setErrorStatus("New password must be different from old password!");
            return false;
        }

        if ($this->getPassword() == null) {
            $this->setErrorStatus("Password not set!");
            return false;
        }

        // Check if oldPassword is correct.
        if (!password_verify($oldPassword, $this->getPassword())) {
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
        $this->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));

        // Update password in database.
        return $this->updatePasswordToDatabase();
    }

    /**
     * Function to update user password, must update $this->setPassword() with a new password already hashed.
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
     * Function to start a password reset.
     * @param $email
     * @return bool
     * @throws Exception
     */
    public function startPasswordReset($email) : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM User WHERE email = ?";
        $result = $conn->execute_query($sql, [$email]);
        if ($result->num_rows == 0) {
            $this->setErrorStatus("Email not found!");
            return false;
        }

        // Load user data.
        $this->setUsername($result->fetch_assoc()["username"]);
        $this->loadUser();

        // Generate temporary password.
        $tempPassword = bin2hex(random_bytes(8));
        $this->setPassword(password_hash($tempPassword, PASSWORD_DEFAULT));
        $this->updatePasswordToDatabase();

        // Send email.
        $to = $email;
        $subject = "Password reset";
        $message = "Hi " . $this->getUsername() . "!";
        $message .= "<br>Your temporary password is: " . $tempPassword;
        $message .= "<br>Please change your password as soon as possible.";
        $message .= "<br>If you did not request a password reset, someone may have requested it for you and you should now use this one until you change it again.";
        $message .= "<br>Sometimes, we may reset your password for security reasons.";
        $message .= "<br>If you have any questions, please contact us at anonymousgca@tales.anonymousgca.eu";
        $message .= "<br>We care about your security!";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: noreply@tales.anonymousgca.eu\r\n";
        if (@mail($to, $subject, $message, $headers)){
            echo "Email sent successfully";
            $this->setErrorStatus("Email sent successfully");
        } else {
            echo "Email sending failed";
            $this->setErrorStatus("Email sending failed");
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getGender(): string
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getUrlProfilePicture(): string
    {
        return $this->urlProfilePicture;
    }

    /**
     * @param mixed $urlProfilePicture
     */
    public function setUrlProfilePicture(mixed $urlProfilePicture): void
    {
        $this->urlProfilePicture = $urlProfilePicture;
    }

    /**
     * @return string
     */
    public function getUrlCoverPicture(): string
    {
        return $this->urlCoverPicture;
    }

    /**
     * @param mixed $urlCoverPicture
     */
    public function setUrlCoverPicture(mixed $urlCoverPicture): void
    {
        $this->urlCoverPicture = $urlCoverPicture;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getMotto(): string
    {
        return $this->motto;
    }

    /**
     * @param string $motto
     */
    public function setMotto(string $motto): void
    {
        $this->motto = $motto;
    }

    /**
     * @return bool
     */
    public function getShowNSFW(): bool
    {
        return $this->showNSFW;
    }

    /**
     * @param bool $showNSFW
     */
    public function setShowNSFW(bool $showNSFW): void
    {
        $this->showNSFW = $showNSFW;
    }

    /**
     * @return bool
     */
    public function getOfAge(): bool
    {
        return $this->ofAge;
    }

    /**
     * @param bool $ofAge
     */
    public function setOfAge(bool $ofAge): void
    {
        $this->ofAge = $ofAge;
    }

    /**
     * @return bool
     */
    public function getIsActivated(): bool
    {
        return $this->isActivated;
    }

    /**
     * @param bool $isActivated
     */
    public function setIsActivated(bool $isActivated): void
    {
        $this->isActivated = $isActivated;
    }

    /**
     * @return bool
     */
    public function getIsMuted(): bool
    {
        return $this->isMuted;
    }

    /**
     * @param bool $isMuted
     */
    public function setIsMuted(bool $isMuted): void
    {
        $this->isMuted = $isMuted;
    }

    /**
     * @return string
     */
    public function getActivationCode(): string
    {
        return $this->activationCode;
    }

    /**
     * @param string $activationCode
     */
    public function setActivationCode(string $activationCode): void
    {
        $this->activationCode = $activationCode;
    }

    /**
     * @return string
     */
    public function getErrorStatus(): string
    {
        return $this->errorStatus;
    }

    /**
     * @param string $errorStatus
     */
    public function setErrorStatus(string $errorStatus): void
    {
        $this->errorStatus = $errorStatus;
    }

    /**
     * @return bool
     */
    public function getIsPremium(): bool
    {
        return $this->isPremium;
    }

    /**
     * @param bool $isPremium
     */
    public function setIsPremium(bool $isPremium): void
    {
        $this->isPremium = $isPremium;
    }

    /**
     * @return string
     */
    public function getSubscriptionType(): string
    {
        return $this->subscriptionType;
    }

    /**
     * @param string $subscriptionType
     */
    public function setSubscriptionType(string $subscriptionType): void
    {
        $this->subscriptionType = $subscriptionType;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionDate(): mixed
    {
        return $this->subscriptionDate;
    }

    /**
     * @param mixed $subscriptionDate
     */
    public function setSubscriptionDate(mixed $subscriptionDate): void
    {
        $this->subscriptionDate = $subscriptionDate;
    }

    /**
     * @return mixed
     */
    public function getExpiryDate(): mixed
    {
        return $this->expiryDate;
    }

    /**
     * @param mixed $expiryDate
     */
    public function setExpiryDate(mixed $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    /**
     * @return mixed
     */
    public function getJoinDate(): mixed
    {
        return $this->joinDate;
    }

    /**
     * @param mixed $joinDate
     */
    public function setJoinDate(mixed $joinDate): void
    {
        $this->joinDate = $joinDate;
    }
}