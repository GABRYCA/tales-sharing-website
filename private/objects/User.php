<?php
include_once (dirname(__FILE__) . "/../connection.php");
include_once (dirname(__FILE__) . "/../objects/Gallery.php");
include_once (dirname(__FILE__) . "/../objects/Content.php");
include_once (dirname(__FILE__) . "/../objects/Followers.php");
include_once (dirname(__FILE__) . "/../objects/Friends.php");
include_once (dirname(__FILE__) . "/../objects/Likes.php");
include_once (dirname(__FILE__) . "/../objects/Notification.php");
include_once (dirname(__FILE__) . "/../objects/VariablesConfig.php");
include_once (dirname(__FILE__) . "/../objects/UniqueImage.php");

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
    private $canUpload = null;

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
                $this->setCanUpload($row["canUpload"]);
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
        $this->urlProfilePicture = "common/assets/profile.webp";
        $this->urlCoverPicture = "common/assets/cover.webp";
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
            $subject = "Account Activation - Tales";
            $headers = "From: " . VariablesConfig::$emailNoreply;
            $message = "Hi " . $this->getUsername() . ",\\n";
            $message .= "Thank you for registering! Please click on the link below to activate your account.\\n";
            $message .= "https://tales.anonymousgca.eu/activation.php?code=" . $this->getActivationCode();

            if (@mail($to, $subject, $message, $headers)) {
                $this->setErrorStatus("Email sent successfully!");
            } else {
                $this->setErrorStatus("Email sending failed!");
                // Delete User from database.
                $this->deleteUserFromDatabase();
                return false;
            }

            // TODO: TEST IF IT ACTUALLY WORKS ON USER REGISTRATION.
            if ($this->generateAndSetUniqueProfileImage()){
                // $this->setErrorStatus("Profile picture generated successfully!");
            } else {
                $this->setErrorStatus("Profile picture generation failed!");
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
     * Function to generate random unique image
     * Please set name and load user before calling this function.
     * @return bool
     */
    public function generateAndSetUniqueProfileImage() : bool
    {
        $uniqueImageObject = new UniqueImage($this->username);

        if ($uniqueImageObject->createUniqueProfileImage()) {
            // Reload user
            $this->reloadData();
            return true;
        } else {
            return false;
        }
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
        $subject = "Password reset - Tales";
        $message = "Hi " . $this->getUsername() . "!";
        $message .= "<br>Your temporary password is: " . $tempPassword;
        $message .= "<br>Please change your password as soon as possible.";
        $message .= "<br>If you did not request a password reset, someone may have requested it for you and you should now use this one until you change it again.";
        $message .= "<br>Sometimes, we may reset your password for security reasons.";
        $message .= "<br>If you have any questions, please contact us at anonymousgca@tales.anonymousgca.eu";
        $message .= "<br>We care about your security!";
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . VariablesConfig::$emailNoreply . "\r\n";
        if (@mail($to, $subject, $message, $headers)){
            $this->setErrorStatus("<br>Email sent successfully");
        } else {
            $this->setErrorStatus("Email sending failed");
            return false;
        }

        return true;
    }

    /**
     * Function to change username, please load user by setting original username before.
     * @param string $username
     * @return bool
     */
    public function changeUsername(string $username) : bool
    {
        if ($username == null) {
            $this->setErrorStatus("Username not set!");
            return true;
        }

        if ($this->getUsername() == $username) {
            $this->setErrorStatus("New username must be different from old username!");
            return true;
        }

        // Check if username is valid.
        if (strlen($username) < 3) {
            $this->setErrorStatus("Username must be at least 3 characters long!");
            return false;
        }

        if (!preg_match("#[a-zA-Z0-9]+#", $username)) {
            $this->setErrorStatus("Username must include only letters and numbers!");
            return false;
        }

        // Check if username is already taken.
        $conn = connection();

        // Check if user already exists.
        $sql = "SELECT * FROM User WHERE username = ?";
        $result = $conn->execute_query($sql, [$username]);
        if ($result->num_rows > 0) {
            $this->setErrorStatus("Username already taken!");
            return false;
        }

        // Update username in database, using a query, with a where condition with the old username
        $sql = "UPDATE User SET username = ? WHERE username = ?";
        if ($conn->execute_query($sql, [$username, $this->getUsername()])) {
            $this->setErrorStatus("Username updated successfully");
            
            // Reload user.
            $this->setUsername($username);
            $this->loadUser();
            
            return true;
        } else {
            $this->setErrorStatus("Error: something went during DB query for changeUsername() action.");
            return false;
        }
    }

    /**
     * Function to change user gender, please set username before and load user.
     * @param string $gender
     * @return bool
     */
    public function changeGender(string $gender) : bool
    {
        if ($gender == null) {
            $this->setErrorStatus("Username not set!");
            return true;
        }

        if ($this->gender == $gender){
            $this->setErrorStatus("New gender must be different than the previous one!");
            return true;
        }

        // Connection to database and query.
        $conn = connection();
        
        $sql = "UPDATE User SET gender = ? WHERE username = ?";
        
        if ($conn->execute_query($sql, [$gender, $this->getUsername()])){
            $this->setErrorStatus("Gender updated with success.");

            // Reload user.
            $this->loadUser();

            return true;
        } else {
            $this->setErrorStatus("Error: something went during DB query for changeGender() action.");
            return false;
        }
    }

    /**
     * Function to change user motto, please set username and load user before.
     * @param string $motto
     * @return bool
     */
    public function changeMotto(string $motto) : bool
    {
        if ($motto == null) {
            $this->setErrorStatus("Motto not set!");
            return true;
        }

        if ($this->motto == $motto){
            $this->setErrorStatus("New motto must be different than the previous one!");
            return true;
        }

        // Connection to database and query.
        $conn = connection();

        $sql = "UPDATE User SET motto = ? WHERE username = ?";

        if ($conn->execute_query($sql, [$motto, $this->getUsername()])){
            $this->setErrorStatus("Motto updated with success.");

            // Reload user.
            $this->loadUser();

            return true;
        } else {
            $this->setErrorStatus("Error: something went during DB query for changeMotto() action.");
            return false;
        }
    }

    /**
     * Function to change user description, please set username before and load user.
     * @param string $description
     * @return bool
     */
    public function changeDescription(string $description) : bool
    {
        if ($description == null) {
            $this->setErrorStatus("Description not set!");
            return true;
        }

        if ($this->description == $description){
            $this->setErrorStatus("New description must be different than the previous one!");
            return true;
        }

        // Connection to database and query.
        $conn = connection();

        $sql = "UPDATE User SET description = ? WHERE username = ?";

        if ($conn->execute_query($sql, [$description, $this->getUsername()])){
            $this->setErrorStatus("Description updated with success.");

            // Reload user.
            $this->loadUser();

            return true;
        } else {
            $this->setErrorStatus("Error: something went during DB query for changeDescription() action.");
            return false;
        }
    }

    /**
     * Function to update showNSFW, please set username before and load user.
     * @param bool $showNSFW
     * @return bool
     */
    public function changeShowNSFW(bool $showNSFW) : bool
    {
        if ($showNSFW == null) {
            $showNSFW = 0;
        } else {
            $showNSFW = 1;
        }

        if ($this->showNSFW == $showNSFW){
            $this->setErrorStatus("New showNSFW must be different than the previous one! " . $this->showNSFW . " " . $showNSFW);
            return true;
        }

        // Connection to database and query.
        $conn = connection();

        $sql = "UPDATE User SET showNSFW = ? WHERE username = ?";

        if ($conn->execute_query($sql, [$showNSFW, $this->getUsername()])){
            $this->setErrorStatus("ShowNSFW updated with success.");

            // Reload user.
            $this->loadUser();

            return true;
        } else {
            $this->setErrorStatus("Error: something went wrong during DB query for changeShowNSFW() action.");
            return false;
        }
    }

    /**
     * Function to change user profile picture given URL of picture.
     * @param string $urlProfilePicture
     * @return bool
     */
    public function changeProfilePicture(string $urlProfilePicture) : bool
    {
        if ($urlProfilePicture == null) {
            $this->setErrorStatus("URL of profile picture not set!");
            return true;
        }

        if ($this->urlProfilePicture == $urlProfilePicture){
            $this->setErrorStatus("New URL of profile picture must be different than the previous one!");
            return true;
        }

        // Connection to database and query.
        $conn = connection();

        $sql = "UPDATE User SET urlProfilePicture = ? WHERE username = ?";

        if ($conn->execute_query($sql, [$urlProfilePicture, $this->getUsername()])){
            $this->setErrorStatus("URL of profile picture updated with success.");

            // Reload user.
            $this->loadUser();

            return true;
        } else {
            $this->setErrorStatus("Error: something went wrong during DB query for changeProfilePicture() action.");
            return false;
        }
    }

    /**
     * Function to change user profile cover given URL of cover image.
     * @param string $urlProfileCover
     * @return bool
     */
    public function changeProfileCover(string $urlProfileCover) : bool
    {
        if ($urlProfileCover == null) {
            $this->setErrorStatus("URL of profile cover not set!");
            return true;
        }

        if ($this->urlCoverPicture == $urlProfileCover){
            $this->setErrorStatus("New URL of profile cover must be different than the previous one!");
            return true;
        }

        // Connection to database and query.
        $conn = connection();

        $sql = "UPDATE User SET urlCoverPicture = ? WHERE username = ?";

        if ($conn->execute_query($sql, [$urlProfileCover, $this->getUsername()])){
            $this->setErrorStatus("URL of profile cover updated with success.");

            // Reload user.
            $this->loadUser();

            return true;
        } else {
            $this->setErrorStatus("Error: something went wrong during DB query for changeProfileCover() action.");
            return false;
        }
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
     * Function that check if username is already taken.
     * Returns true if username is available, false if not.
     * @param string $username
     * @return bool
     */
    public function checkUsername(string $username)
    {
        $conn = connection();

        $sql = "SELECT * FROM User WHERE username = ?";
        $result = $conn->execute_query($sql, [$username]);
        if ($result->num_rows == 0) {
            return true;
        }

        return false;
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

        // Check if content is already in gallery
        if ($galleryClass->isContentInGallery($contentId)){
            $this->setErrorStatus("Content is already in gallery!");
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

        if (!$galleryClass->isContentInGallery($contentId)){
            $this->setErrorStatus("Content not in gallery!");
            return false;
        }

        // Remove content from gallery.
        return $galleryClass->removeContentFromGallery($contentId);
    }

    /**
     * Function to get all notifications of user.
     * @return array
     */
    public function getAllNotifications() : array
    {
        $notificationClass = new Notification();
        $notificationClass->setUserId($this->getUsername());
        return $notificationClass->loadNotificationsByUser();
    }

    /**
     * Function to get the unread notifications of user.
     * @return array
     */
    public function getUnreadNotifications() : array
    {
        $notificationClass = new Notification();
        $notificationClass->setUserId($this->getUsername());
        return $notificationClass->loadNotificationsByUserNotViewed();
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
     * Get Followers of user.
     * @return User[]
     */
    public function getFollowers() : array
    {
        $followersClass = new Followers($this->getUsername());
        return $followersClass->getFollowers();
    }

    /**
     * Get Following of user.
     * @return User[]
     */
    public function getFollowing() : array
    {
        $followingClass = new Followers($this->getUsername());
        return $followingClass->getFollowing();
    }

    /**
     * Get number of followers of user.
     * @return int
     */
    public function getNumberOfFollowers() : int
    {
        $followersClass = new Followers($this->getUsername());
        return count($followersClass->getFollowers());
    }

    /**
     * Get number of following of user.
     * @return int
     */
    public function getNumberOfFollowing() : int
    {
        $followingClass = new Followers($this->getUsername());
        return count($followingClass->getFollowing());
    }

    /**
     * Follow a user by given $userId.
     * @param string $userId
     * @return bool
     */
    public function followUser(string $userId) : bool
    {
        $followersClass = new Followers($this->getUsername());
        return $followersClass->follow($userId);
    }

    /**
     * Unfollow a user by given $userId.
     * @param string $userId
     * @return bool
     */
    public function unfollowUser(string $userId) : bool
    {
        $followersClass = new Followers($this->getUsername());
        return $followersClass->unfollow($userId);
    }

    /**
     * Check if user is following given $userId.
     * @param string $userId
     * @return bool
     */
    public function isFollowing(string $userId) : bool
    {
        $followersClass = new Followers($this->getUsername());
        // Get followed users.
        $following = $followersClass->getFollowing();
        // Check if given $userId is in the list.
        foreach ($following as $follow){
            if ($follow->getUsername() == $userId){
                return true;
            }
        }
        return false;
    }

    /**
     * Function that returns if a user has likes a contentId.
     * Please set $username before calling this function.
     * @param int $contentId
     * @return bool
     */
    public function hasLikedContent(int $contentId) : bool
    {
        $likes = new Likes();
        return $likes->hasLiked($this->username, $contentId);
    }

    /**
     * Function to get total number of likes received by user contents.
     * @return int
     */
    public function getTotalLikesReceived() : int
    {
        // Get all contents of user and do a sum of the likes.
        $contents = new Content();
        $contents = $contents->getAllContentOfUser($this->getUsername());
        $totalLikes = 0;
        foreach ($contents as $content){
            $totalLikes += $content->getNumberOfLikes();
        }

        return $totalLikes;
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
     * Returns the date the user joined in a human readable format.
     * @return string
     */
    public function getJoinDateHuman(): string
    {
        return date('F jS, Y', strtotime($this->joinDate));
    }

    /**
     * Returns the registering date year and month only, month as a name.
     * @return string
     */
    public function getJoinDateYearMonth(): string
    {
        return date('F Y', strtotime($this->joinDate));
    }

    /**
     * @param mixed $joinDate
     */
    public function setJoinDate(mixed $joinDate): void
    {
        $this->joinDate = $joinDate;
    }

    /**
     * @return bool
     */
    public function canUpload(): bool
    {
        return $this->canUpload;
    }

     /**
     * @param bool $canUpload
     */
    public function setCanUpload(bool $canUpload): void
    {
        $this->canUpload = $canUpload;
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
            'joinDate' => $this->joinDate,
            'canUpload' => $this->canUpload
        ];
    }
}