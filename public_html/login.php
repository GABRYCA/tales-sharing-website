<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include 'common/common-head.php';
    ?>
    <title>Login</title>
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

    $username = $_POST["username"];

    $sql = "SELECT password FROM User WHERE username = ?";

    if ($data = $conn->execute_query($sql, [$username])) {

        if ($data->num_rows == 0) {
            exit("No account found with that username.");
        }

        $result = $data->fetch_assoc();
        if (password_verify($_POST['password'], $result['password'])) {
            // If password is correct, start a new session.
            session_start();

            // Store data in session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["username"] = $username;

            // Create User object
            $user = new User();
            $user->setUsername($username);
            $user->loadUser();
            $_SESSION["user"] = $user;

            // Redirect user to home.php
            header("Location: ../home.php");
        } else {
            // Display an error message if password is not valid
             echo "Wrong password, please try again.";
        }
    } else {
        // Display an error message if username doesn't exist
        echo "No account found with that username.";
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
                                <label for="username" class="form-label">Username*</label>
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
    include "common/common-footer.php";
    ?>

<?php
}
include "common/common-body.php";
?>
</body>
</html>
