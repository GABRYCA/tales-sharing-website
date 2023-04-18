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
    private $errorStatus;

    // Load a user from the database.
    public function loadUserFromDatabase() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM User WHERE username = ?";
        
        if ($data = $conn->execute_query($sql, [$this->username])){
            if ($data->num_rows > 0) {
                $row = $data->fetch_row();
                $this->setUsername($row[0]);
                $this->setGender($row[1]);
                $this->setEmail($row[2]);
                $this->setPassword($row[3]);
                $this->setUrlProfilePicture($row[4]);
                $this->setUrlCoverPicture($row[5]);
                $this->setDescription($row[6]);
                $this->setMotto($row[7]);
                $this->setShowNSFW($row[8]);
                $this->setOfAge($row[9]);
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
     * Function to register a user with input data (and all the checks necessaries) and sends email too, the password will be encrypted.
     * @return bool
     */
    public function registerUser(): bool
    {
        if ($this->loadUserFromDatabase()) {
            $this->setErrorStatus("Username already taken!");
            return false;
        }

        $this->setPassword(password_hash($this->password, PASSWORD_DEFAULT));

        $this->setUrlProfilePicture("assets/img/profile.png");
        $this->setUrlCoverPicture("assets/img/cover.jpg");
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
            https://anonymousgca.eu/tales-website/activation.php?code=" . $this->getActivationCode();
            $headers = "From: noreply@anonymousgca.eu";

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
}