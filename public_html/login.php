<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once (dirname(__FILE__) . '/common/common-head.php');
    ?>
    <link rel="canonical" href="https://tales.anonymousgca.eu/login">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <title>Tales - Login</title>
</head>
<body class="font-monospace text-light bg-dark">

<?php
session_start();
include_once (dirname(__FILE__) . "/../private/configs/googleConfig.php");
include_once (dirname(__FILE__) . "/../private/configs/cloudflareConfig.php");
include_once (dirname(__FILE__) . "/../private/connection.php");
include_once (dirname(__FILE__) . "/../private/objects/User.php");
include_once (dirname(__FILE__) . "/common/utility.php");

// If there's already an active session, send user to home.php.
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: ../home.php");
    exit();
}

// create googleConfig object and auth URL.
$googleConfig = new googleConfig();
$googleClient = $googleConfig->getClient();
$authUrl = $googleClient->createAuthUrl();

$cloudflareConfig = new cloudflareConfig();

// If there's POST data, then check if the user exists and if the password is correct.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['cf-turnstile-response'])){
        echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
        echo "<p class='text-center mt-5'>Error: missing captcha</p>";
        header("refresh:2;url=../login.php");
        exit();
    }

    // Check if all data in post is set.
    if (!isset($_POST["username"]) || !isset($_POST["password"])) {
        echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
        echo "<p class='text-center mt-5'>Error: missing data</p>";
        header("refresh:2;url=../login.php");
        exit();
    }

    // DBConnection.
    $conn = connection();

    // Get username from POST and save it temporarily.
    $usernameOrMail = validate_input($_POST["username"]);
    $password = validate_input($_POST["password"]);

    // Check if captcha is valid.
    $captcha = validate_input($_POST['cf-turnstile-response']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $data = $data = array('secret' => $cloudflareConfig->getSecretKey(), 'response' => $captcha, 'remoteip' => $ip);
    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => http_build_query($data))
    );
    $stream = stream_context_create($options);
    $result = file_get_contents(
        $cloudflareConfig->getUrlPath(), false, $stream);
    $response =  $result;
    $responseKeys = json_decode($response,true);
    if(intval($responseKeys["success"]) !== 1) {
        echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
        echo "<p class='text-center mt-5'>Error: captcha not valid</p>";
        header("refresh:2;url=../login.php");
        exit();
    }

    $isEmail = false;

    // Check if username is an email.
    if (filter_var($usernameOrMail, FILTER_VALIDATE_EMAIL)) {
        // Prepare a select statement by email.
        $sql = "SELECT username, password FROM User WHERE email = ?";
        $isEmail = true;
    } else {
        // Prepare a select statement by username.
        $sql = "SELECT password FROM User WHERE username = ?";
    }

    // Run query
    if ($data = $conn->execute_query($sql, [$usernameOrMail])) {

        // Check if email/username exists in DB by running query.
        if ($data->num_rows == 0) {
            if ($isEmail) {
                echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
                echo "<p class='text-center mt-5'>Email not found, please check your email and try again.</p>";
            } else {
                echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
                echo "<p class='text-center mt-5'>User not found, please check your username and try again.</p>";
            }
            header("refresh:2;url=../login.php");
            exit();
        }


        // Get from DB the hashed password
        $result = $data->fetch_assoc();
        if ($isEmail){
            // Set username instead of email from DB (So I can keep common code afterward.
            $usernameOrMail = $result['username'];
        }
        if (password_verify($password, $result['password'])) {
            // If password is correct, start a new session.
            session_start();

            // Create User object and save it in session.
            $user = new User();
            $user->setUsername($usernameOrMail);
            if (!$user->loadUser()){ // Load from DB the User with updated data.
                echo "<p class='text-center mt-5'>Error: could not load user (" . $user->getErrorStatus() . ")</p>";
                header("refresh:2;url=../login.php");
                exit();
            }

            // Check if user is activated.
            if (!$user->getIsActivated()){
                echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
                echo "<p class='text-center mt-2'>Error: account not activated, please check your email or contact anonymousgca@anonymousgca.eu</p>";
                echo "<p class='text-center mt-5'>Redirecting...</p>";

                // Redirect user to login.php after 2 seconds.
                header("refresh:2;url=../login.php");
                exit();
            }

            // Store data in session variables.
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $usernameOrMail;
            $_SESSION["user"] = $user;

            // Tell the user that the login was successful.
            echo "<p class='text-center mt-5'><i class='fas fa-check-circle fa-3x'></i></p>";
            echo "<p class='text-center mt-2'>Login successful, redirecting...</p>";

            // Redirect user to home.php after 2 seconds.
            header("refresh:2;url=../home.php");
        } else {
            // Display an error message if password is not valid.
            echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
             echo "<p class='text-center mt-2'>Wrong password, please try again.</p>";
             header("refresh:2;url=../login.php");
        }
    } else {
        // Display an error message if username not found.
        echo "<p class='text-center mt-5'>Account not found, wrong username or password.</p>";
        header("refresh:2;url=../login.php");
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["code"])) {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET["code"]);
    if(isset($token['error'])){
        // Error.
        echo "<p class='text-center mt-5'>Error: " . $token['error'] . "</p>";
        header("refresh:2;url=../login.php");
        exit;
    }
    $_SESSION["token"] = $token;

    // Add user info to DB.
    $googleClient->setAccessToken($token['access_token']);
    $google_oauth = new Google_Service_Oauth2($googleClient);
    $google_account_info = $google_oauth->userinfo->get();

    // Check if user exists in DB.
    $conn = connection();
    $sql = "SELECT username FROM User WHERE email = ?";

    // Run query
    if ($data = $conn->execute_query($sql, [$email = trim($google_account_info['email'])])) {
        if ($data->num_rows > 0) {
            // User exists, login.

            // Create User object and save it in session.
            $user = new User();
            $user->setUsername($data->fetch_assoc()['username']);
            if (!$user->loadUser()) { // Load from DB the User with updated data.
                echo "<p class='text-center mt-5'>Error: could not load user (" . $user->getErrorStatus() . ")</p>";
                header("refresh:2;url=../login.php");
                exit();
            }

            // Check if user is activated.
            if (!$user->getIsActivated()) {
                echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
                echo "<p class='text-center mt-2'>Error: account not activated, please check your email or contact anonymousgca@tales.anonymousgca.eu</p>";
                header("refresh:2;url=../login.php");
                exit();
            }

            // Store data in session variables.
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $user->getUsername();
            $_SESSION["user"] = $user;

            // Tell the user that the login was successful.
            echo "<p class='text-center mt-5'><i class='fas fa-check-circle fa-3x'></i></p>";
            echo "<p class='text-center mt-2'>Login successful, redirecting...</p>";

            // Redirect user to home.php after 2 seconds.
            header("refresh:2;url=../home.php");
            exit();
        } else {
            // Create new user.
            $user = new User();

            // Set user data.
            $user->setUsername($google_account_info['given_name'] . $google_account_info['family_name'] . rand(0, 1000));

            // Check if username already exists.
            while ($user->loadUser()) {
                $user->setUsername($google_account_info['given_name'] . $google_account_info['family_name'] . rand(0, 1000));
            }

            $user->setEmail($google_account_info['email']);
            $user->setPassword(password_hash($google_account_info['sub'], PASSWORD_DEFAULT));
            $user->setOfAge(true);

            if ($user->registerUser()){
                // Account created with success. Need to verify email.
                echo "<p class='text-center mt-5'><i class='fas fa-check-circle fa-3x'></i></p>";
                echo "<p class='text-center'>Account created with success, please activate it using the activation link sent to your email</p>";

                // If user is created, send to login page after 2 seconds
                header("Refresh: 2; url=login.php");
                exit();
            } else {
                echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
                exit("<p class='text-center'>Error: user could not be created (" . $user->getErrorStatus() . ")</p>");
            }
        }
    } else {
        echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
        exit("<p class='text-center'>Error: could not connect to DB</p>");
    }
} else {
?>

    <!-- Login form -->
    <div class="container mb-3">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9 col-xxl-6">
                <div class="row mt-4 mb-4">
                    <div class="col">
                        <h1 class="text-center">Login - Tales</h1>
                    </div>
                </div>
                <div class="row m-1 mb-5 p-4 border border-light rounded-5">
                    <div class="col">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="row g-3 needs-validation">
                            <div class="col-md-12">
                                <label for="username" class="form-label">Email/Username*</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <div class="invalid-feedback">
                                        Please enter your username.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label for="password" class="form-label">Password*</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="invalid-feedback">
                                        Please enter your password.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mt-3 pt-2 mb-0 cf-turnstile" data-sitekey="<?= $cloudflareConfig->getClientKey() ?>"></div>
                            <div class="col-md-12">
                                <a href="forgot-password.php" class="link-danger">Forgot password?</a>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-12 mt-2 text-center">
                        <!-- Link with dark theme and Google icon -->
                        <a href="<?= $authUrl ?>" class="btn btn-google w-100" data-bs-theme="dark"><i class="fab fa-google me-3"></i>Login with Google</a>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <p class="text-center">Don't have an account? <a href="register.php">Register here</a>.</p>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <p class="text-center text-muted">By logging in, you agree to our <a href="faq/tos.php" target="_blank" class="link-primary">Terms of Service</a> and <a href="faq/privacy-policy.php" target="_blank" class="link-primary">Privacy Policy</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include_once (dirname(__FILE__) . "/common/common-footer.php");
    ?>

<?php
}
include_once (dirname(__FILE__) . "/common/common-body.php");
?>
</body>
</html>
