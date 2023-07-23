<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once (dirname(__FILE__) . '/common/common-head.php');
    ?>
    <title>Tales - Register</title>
    <style>
        .btn-google {
            background-color: #4285f4;
            border-color: #4285f4;
            color: #fff;
            font-weight: bold;
            border-radius: 0.5rem;
        }

        .btn-google:hover {
            background-color: #357ae8;
            border-color: #357ae8;
        }
    </style>
</head>
<body class="font-monospace text-light bg-dark">

<?php
session_start();
include_once (dirname(__FILE__) . "/../private/connection.php");
include_once (dirname(__FILE__) . "/../private/configs/googleConfig.php");
include_once (dirname(__FILE__) . "/../private/objects/User.php");
include_once (dirname(__FILE__) . "/common/utility.php");

// If session is active, send user to home.php
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: ../home.php");
    exit();
}

// create googleConfig object and auth URL.
$googleConfig = new googleConfig();
$googleClient = $googleConfig->getClient();
$authUrl = $googleClient->createAuthUrl();

// If there's POST data, then check if the user exists and if the password is correct.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connection();

    // Check if all data in post is set
    if (!isset($_POST["username"]) || !isset($_POST["password"]) || !isset($_POST["confirm_password"]) || !isset($_POST["email"]) || !isset($_POST["ofAge"])) {
        echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
        echo "<p class='text-center mt-2'>Error: missing data</p>";
        exit();
    }

    // Get data from POST
    $username = validate_input($_POST["username"]);
    $password = validate_input($_POST["password"]);
    $confirm_password = validate_input($_POST["confirm_password"]);
    $email = validate_input($_POST["email"]);
    $ofAge = validate_input($_POST["ofAge"]);

    // Check if password and confirm password match
    if ($password != $confirm_password) {
        echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
        exit("<p class='text-center mt-2'>Passwords do not match</p>");
    }

    // Check if user is of age
    if ($ofAge != "on") {
        echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
        exit("<p class='text-center mt-2'>You must be of age to register</p>");
    }

    $ofAge = true;

    // Create user object.
    $user = new User();
    $user->setUsername($username);
    $user->setPassword($password);
    $user->setEmail($email);
    $user->setOfAge($ofAge);

    // Use function to create user in database (Automatically checks email and username if they're already used).
    if ($user->registerUser()){

        // Tell account created with success, please verify email
        echo "<p class='text-center mt-5'><i class='fas fa-envelope fa-3x'></i></p>";
        echo "<p class='text-center mt-2'>Account created with success, please activate it using the activation link sent to your email</p>";

        // If user is created, send to login page after 2 seconds
        header("Refresh: 2; url=login.php");
        exit();
    } else {
        echo "<p class='text-center mt-5'><i class='fas fa-exclamation-triangle fa-3x'></i></p>";
        exit("<p class='text-center mt-2'>Error: user could not be created (" . $user->getErrorStatus() . ")</p>");
    }
} else {
    ?>

    <!-- Register form -->
    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9 col-xxl-6">
                <div class="row mt-4 mb-4">
                    <div class="col">
                        <h1 class="text-center">Register</h1>
                    </div>
                </div>
                <div class="row m-1 mb-5 p-4 border border-light rounded-5">
                    <div class="col">
                        <form class="row g-3 needs-validation" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="username" id="username" required>
                                    <div class="invalid-feedback">
                                        Please choose a username.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" id="email" required>
                                    <div class="invalid-feedback">
                                        Please enter a valid email address.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="password" required>
                                    <div class="invalid-feedback">
                                        Please choose a password.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                    <div class="invalid-feedback">
                                        Please confirm your password.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="on" name="ofAge" id="ofAge" required>
                                    <label class="form-check-label" for="ofAge">
                                        I confirm that I am of legal age.
                                    </label>
                                    <div class="invalid-feedback">
                                        You must be of legal age to register.
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100" type="submit">Register</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-12 mt-2 text-center">
                        <!-- Link with dark theme and Google icon -->
                        <a href="<?= $authUrl ?>" class="btn btn-google w-100" data-bs-theme="dark"><i class="fab fa-google me-3"></i>Register with Google</a>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <p class="text-center">Already have an account? <a href="login.php">Login here</a>.</p>
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
