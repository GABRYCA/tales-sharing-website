<?php
include_once (dirname(__FILE__) . "/../connection.php");
include_once (dirname(__FILE__) . "/../objects/Gallery.php");
include_once (dirname(__FILE__) . "/../objects/Content.php");
include_once (dirname(__FILE__) . "/../objects/Followers.php");
include_once (dirname(__FILE__) . "/../objects/Friends.php");
include_once (dirname(__FILE__) . "/../objects/Likes.php");

// This class, using connection.php, is used to load or create a user like a JavaBean.
class User implements JsonSerializable
{
    private $username = null;
    private $gender = null;
    private $email = null;
    private $password = null;
    private $urlProfilePicture = null;
    private $urlCoverPicture = null;
    private $description = null;
    private $motto = null;
    private $showNSFW = false;
    private $ofAge = false;
    private $isActivated = false;
    private $isMuted = false;
    private $activationCode = null;
    private $joinDate = null;
    private $errorStatus = null;
    private $isPremium = false;
    private $subscriptionType = null;
    private $subscriptionDate = null;
    private $expiryDate = null;

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
                $this->setErrorStatus("User not found");
                return false;
            }
        } else {
            $this->setErrorStatus("Error: " . $conn->error);
            return false;
        }
        return true;
    }

    /**
     * Function to add a user to the database.
     * It uses default values for some fields.
     * Only sets username, email, password and some other data.
     * @return bool
     */
    public function addUserToDatabase(): bool
    {
        $conn = connection();

        $sql = "INSERT INTO User (username, gender, email, password, urlProfilePicture, urlCoverPicture, description, motto, showNSFW, ofAge, isActivated, isMuted, activationCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($conn->execute_query($sql, [$this->username, $this->gender, $this->email, $this->password, $this->urlProfilePicture, $this->urlCoverPicture, $this->description, $this->motto, $this->showNSFW, $this->ofAge, $this->isActivated, $this->isMuted, $this->activationCode])) {
            $this->setErrorStatus("New User created successfully");
        } else {
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
                $this->setErrorStatus("0 results");
                return false;
            }
        } else {
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
        if ($this->checkIfUserExists()) {
            $this->setErrorStatus("Username already taken!");
            return false;
        }

        $this->gender = "unspecified";
        $this->urlProfilePicture = "assets/img/profile.png";
        $this->urlCoverPicture = "assets/img/cover.jpg";
        $this->description = "I'm a new user!";
        $this->motto = "I'm a new user!";
        $this->showNSFW = 0;
        $this->isActivated = 0;
        $this->isMuted = 0;

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
                $this->setErrorStatus("0 results");
                return false;
            }
        } else {
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
            $this->setErrorStatus("User deleted successfully");
        } else {
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

        if ($conn->execute_query($sql, [$this->getGender(), $this->getEmail(), $this->getUrlProfilePicture(), $this->getUrlCoverPicture(), $this->showNSFW, $this->ofAge, $this->isActivated, $this->isMuted, $this->getActivationCode(), $this->getUsername()])) {
            $this->setErrorStatus("User updated successfully");
        } else {
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
            $this->setErrorStatus("Premium activated successfully");
        } else {
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
            $this->setErrorStatus("Premium updated successfully");
        } else {
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
            $this->setErrorStatus("Password updated successfully");
        } else {
            $this->setErrorStatus("Error: " . $sql . "<br>" . $conn->error);
            return false;
        }

        return true;
    }

    /**
     * Function to start a password reset.
     * @param string $email
     * @return bool
     * @throws Exception
     */
    public function startPasswordReset(string $email) : bool
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
            $this->setErrorStatus("<br>Email sent successfully");
        } else {
            $this->setErrorStatus("Email sending failed");
            return false;
        }

        return true;
    }

    /**
     * Check if user exists (Note that you should set the username first)
     * Return true if user exists, false if not.
     * @return bool
     */
    public function checkIfUserExists() : bool
    {
        $conn = connection();

        $sql = "SELECT * FROM User WHERE username = ?";
        $result = $conn->execute_query($sql, [$this->username]);
        if ($result->num_rows == 0) {
            $this->setErrorStatus("User not found!");
            return false;
        }

        return true;
    }

    /**
     * Get list of Galleries of user.
     * @return Gallery[]
     */
    public function getGalleries() : array
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        return $galleryClass->getGalleriesByOwnerId();
    }

    /**
     * Get gallery by id.
     * @param int $galleryId
     * @return Gallery
     */
    public function getGalleryById(int $galleryId) : Gallery
    {
        $galleryClass = new Gallery();
        $galleryClass->setGalleryId($galleryId);
        $galleryClass->setOwnerId($this->getUsername());
        if (!$galleryClass->loadGalleryInfoByGalleryId()){
            $this->setErrorStatus("Gallery not found!");
        }
        return $galleryClass;
    }

    /**
     * Get gallery by name.
     * @param string $galleryName
     * @return Gallery
     */
    public function getGalleryByName(string $galleryName) : Gallery
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        $galleryClass->setName($galleryName);
        if (!$galleryClass->loadGalleryInfoByOwnerId()){
            $this->setErrorStatus("Gallery not found!");
        }
        return $galleryClass;
    }


    /**
     * Create a new gallery.
     * @param string $galleryName
     * @return bool
     */
    public function createGallery(string $galleryName) : bool
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        $galleryClass->setName($galleryName);
        return $galleryClass->createGallery();
    }

    /**
     * Delete a gallery by id.
     * @param int $galleryId
     * @return bool
     */
    public function deleteGalleryById(int $galleryId): bool
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        $galleryClass->setGalleryId($galleryId);

        if (!$galleryClass->loadGalleryInfoByGalleryId()){
            $this->setErrorStatus("Gallery not found!");
            return false;
        }

        return $galleryClass->deleteGallery();
    }

    /**
     * Delete a gallery by name.
     * @param string $galleryName
     * @return bool
     */
    public function deleteGalleryByName(string $galleryName): bool
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        $galleryClass->setName($galleryName);

        if (!$galleryClass->loadGalleryInfoByOwnerId()){
            $this->setErrorStatus("Gallery not found!");
            return false;
        }

        return $galleryClass->deleteGallery();
    }

    /**
     * Rename a gallery by gallery id.
     * @param int $galleryId
     * @param string $newGalleryName
     * @return bool
     */
    public function renameGalleryById(int $galleryId, string $newGalleryName): bool
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        $galleryClass->setGalleryId($galleryId);
        if (!$galleryClass->loadGalleryInfoByGalleryId()){
            $this->setErrorStatus("Gallery not found!");
            return false;
        }

        return $galleryClass->renameGallery($newGalleryName);
    }

    /**
     * Rename a gallery by gallery name.
     * @param string $galleryName
     * @param string $newGalleryName
     * @return bool
     */
    public function renameGalleryByName(string $galleryName, string $newGalleryName): bool
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        $galleryClass->setName($galleryName);
        if (!$galleryClass->loadGalleryInfoByOwnerId()){
            $this->setErrorStatus("Gallery not found!");
            return false;
        }

        return $galleryClass->renameGallery($newGalleryName);
    }

    /**
     * Function to add content to gallery.
     * @param int $galleryId
     * @param int $contentId
     * @return bool
     */
    public function addContentToGallery(int $galleryId, int $contentId) : bool
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        $galleryClass->setGalleryId($galleryId);
        if (!$galleryClass->loadGalleryInfoByGalleryId()){
            $this->setErrorStatus("Gallery not found!");
            return false;
        }

        $contentClass = new Content();
        $contentClass->setContentId($contentId);
        if (!$contentClass->loadContent()){
            $this->setErrorStatus("Content not found!");
            return false;
        }

        // Check if owner of content is the same as the owner of the gallery.
        if ($contentClass->getOwnerId() != $this->getUsername()){
            $this->setErrorStatus("You do not own this content!");
            return false;
        }

        if ($galleryClass->getOwnerId() != $this->getUsername()){
            $this->setErrorStatus("You do not own this gallery!");
            return false;
        }

        if ($galleryClass->checkIfContentIsInGallery($contentId)){
            $this->setErrorStatus("Content already in gallery!");
            return false;
        }

        // Add content to gallery.
        return $galleryClass->addContentToGallery($contentId);
    }

    /**
     * Function to remove content from gallery.
     * @param int $galleryId
     * @param int $contentId
     * @return bool
     */
    public function removeContentFromGallery(int $galleryId, int $contentId) : bool
    {
        $galleryClass = new Gallery();
        $galleryClass->setOwnerId($this->getUsername());
        $galleryClass->setGalleryId($galleryId);
        if (!$galleryClass->loadGalleryInfoByGalleryId()){
            $this->setErrorStatus("Gallery not found!");
            return false;
        }

        $contentClass = new Content();
        $contentClass->setContentId($contentId);
        if (!$contentClass->loadContent()){
            $this->setErrorStatus("Content not found!");
            return false;
        }

        // Check if owner of content is the same as the owner of the gallery.
        if ($contentClass->getOwnerId() != $this->getUsername()){
            $this->setErrorStatus("You do not own this content!");
            return false;
        }

        if ($galleryClass->getOwnerId() != $this->getUsername()){
            $this->setErrorStatus("You do not own this gallery!");
            return false;
        }

        if (!$galleryClass->checkIfContentIsInGallery($contentId)){
            $this->setErrorStatus("Content not in gallery!");
            return false;
        }

        // Remove content from gallery.
        return $galleryClass->removeContentFromGallery($contentId);
    }

    /**
     * Get content of user by id.
     * @param int $contentId
     * @return Content
     */
    public function getContentById(int $contentId) : Content
    {
        $contentClass = new Content();
        $contentClass->setContentId($contentId);
        $contentClass->loadContent();

        if ($contentClass->getOwnerId() != $this->getUsername()){
            $this->setErrorStatus("You do not own this content!");
            return $contentClass;
        }

        return $contentClass;
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
     * Return user id (essentially the username) since that's the key value.
     * @return string
     */
    public function getId(): string
    {
        return $this->getUsername();
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
     * @param $showNSFW
     */
    public function setShowNSFW($showNSFW): void
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
     * @param $ofAge
     */
    public function setOfAge($ofAge): void
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
     * @param $isActivated
     */
    public function setIsActivated($isActivated): void
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
     * @param $isMuted
     */
    public function setIsMuted($isMuted): void
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
    public function getIsPremium()
    {
        return $this->isPremium;
    }

    /**
     * @param $isPremium
     */
    public function setIsPremium($isPremium): void
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

    // Implements JsonSerializable
    public function jsonSerialize()
    {
        return [
            'username' => $this->username,
            'gender' => $this->gender,
            'email' => $this->email,
            'password' => $this->password,
            'urlProfilePicture' => $this->urlProfilePicture,
            'urlCoverPicture' => $this->urlCoverPicture,
            'description' => $this->description,
            'motto' => $this->motto,
            'showNSFW' => $this->showNSFW,
            'ofAge' => $this->ofAge,
            'isActivated' => $this->isActivated,
            'isMuted' => $this->isMuted,
            'activationCode' => $this->activationCode,
            'errorStatus' => $this->errorStatus,
            'isPremium' => $this->isPremium,
            'subscriptionType' => $this->subscriptionType,
            'subscriptionDate' => $this->subscriptionDate,
            'expiryDate' => $this->expiryDate,
            'joinDate' => $this->joinDate
        ];
    }
}