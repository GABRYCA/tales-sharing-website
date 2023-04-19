<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include 'common/common-head.php';
    ?>
    <title>Register</title>
</head>
<body class="font-monospace text-light bg-dark">

<?php
session_start();
include "../private/dbconnection.php";
include "../private/objects/User.php";

// If session is active, send user to home.php
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: ../home.php");
    exit();
}

// If there's POST data, then check if the user exists and if the password is correct.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connection();

    // Check if all data in post is set
    if (!isset($_POST["username"]) || !isset($_POST["password"]) || !isset($_POST["confirm_password"]) || !isset($_POST["email"]) || !isset($_POST["ofAge"])) {
        echo "Error: missing data";
        exit();
    }

    $username = $_POST["username"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $email = $_POST["email"];
    $ofAge = $_POST["ofAge"];

    // Check if username is already taken
    $sql = "SELECT username FROM User WHERE username = ?";
    if ($data = $conn->execute_query($sql, [$username])) {
        $result = $data->fetch_assoc();
        if ($result["username"] == $username) {
            // If username is taken, display error message
            exit("Username is already taken");
        }
    }

    // Check if email is already taken
    $sql = "SELECT email FROM User WHERE email = ?";
    if ($data = $conn->execute_query($sql, [$email])) {
        $result = $data->fetch_assoc();
        if ($result["email"] == $email) {
            // If email is taken, display error message
            exit("There is already an account with this email");
        }
    }

    // Check if password and confirm password match
    if ($password != $confirm_password) {
        exit("Passwords do not match");
    }

    // Check if user is of age
    if ($ofAge != "on") {
        exit("You must be of age to register");
    }

    // Create user object.
    $user = new User();
    $user->setUsername($username);
    $user->setPassword($password);
    $user->setEmail($email);
    $user->setOfAge($ofAge);

    // Use function to create user in database
    if ($user->registerUser()){
        // If user is created, send to login page

        // For Debug reasons, output all post DATA and user object
        echo "POST DATA: <br>";
        var_dump($_POST);
        echo "<br><br>USER OBJECT: <br>";
        var_dump($user);
        echo "<br><br>";

        //header("Location: login.php");
    } else {
        exit("Error: user could not be created (" . $user->getErrorStatus() . ")");
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
                        <form class="row g-3 needs-validation" novalidate method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
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
    include "common/common-footer.php";
    ?>

    <?php
}
include "common/common-body.php";
?>
</body>
</html>
