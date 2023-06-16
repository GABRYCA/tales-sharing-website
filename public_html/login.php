<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once (dirname(__FILE__) . '/common/common-head.php');
    ?>
    <title>Login</title>
</head>
<body class="font-monospace text-light bg-dark">

<?php
session_start();
include_once (dirname(__FILE__) . "/../private/connection.php");
include_once (dirname(__FILE__) . "/../private/objects/User.php");
include_once (dirname(__FILE__) . "/common/utility.php");

// If there's already an active session, send user to home.php.
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: ../home.php");
    exit();
}

// If there's POST data, then check if the user exists and if the password is correct.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if all data in post is set.
    if (!isset($_POST["username"]) || !isset($_POST["password"])) {
        echo "<p class='text-center mt-5'>Error: missing data</p>";
        header("refresh:2;url=../login.php");
        exit();
    }

    // DBConnection.
    $conn = connection();

    // Get username from POST and save it temporarily.
    $usernameOrMail = validate_input($_POST["username"]);
    $password = validate_input($_POST["password"]);

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
                echo "<p class='text-center mt-5'>Email not found, please check your email and try again.</p>";
            } else {
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
                echo "<p class='text-center mt-5'>Error: account not activated, please check your email or contact anonymousgca@tales.anonymousgca.eu</p>";

                // Redirect user to login.php after 2 seconds.
                header("refresh:2;url=../login.php");
                exit();
            }

            // Store data in session variables.
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $usernameOrMail;
            $_SESSION["user"] = $user;

            // Tell the user that the login was successful.
            echo "<p class='text-center mt-5'>Login successful, redirecting...</p>";

            // Redirect user to home.php after 2 seconds.
            header("refresh:2;url=../home.php");
        } else {
            // Display an error message if password is not valid.
             echo "<p class='text-center mt-5'>Wrong password, please try again.</p>";
             header("refresh:2;url=../login.php");
        }
    } else {
        // Display an error message if username not found.
        echo "<p class='text-center mt-5'>Account not found, wrong username or password.</p>";
        header("refresh:2;url=../login.php");
    }
} else {
?>

    <!-- Login form -->
    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9 col-xxl-6">
                <div class="row mt-4 mb-4">
                    <div class="col">
                        <h1 class="text-center">Login</h1>
                    </div>
                </div>
                <div class="row m-1 mb-5 p-4 border border-light rounded-5">
                    <div class="col">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="row g-3 needs-validation" novalidate>
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
                            <div class="col-md-12">
                                <a href="forgot-password.php" class="link-danger">Forgot password?</a>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <p class="text-center">Don't have an account? <a href="register.php">Register here</a>.</p>
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
