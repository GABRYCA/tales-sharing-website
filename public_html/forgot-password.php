<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include 'common/common-head.php';
    ?>
    <title>Forgot Password</title>
</head>
<body class="font-monospace text-light text-center pt-5 bg-dark">

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
    if (!isset($_POST["email"])) {
        echo "<p class='text-center'>Error: missing data</p>";
        exit();
    }

    // Get data from POST
    $email = $_POST["email"];

    // Check if an account with that email actually exists, if not stop (not existing accounts can't be reset)
    $sql = "SELECT email FROM User WHERE email = ?";
    if ($data = $conn->execute_query($sql, [$email])) {
        $result = $data->fetch_assoc();
        if ($result["email"] != $email) {
            // If email is not taken, display error message
            exit("Can't find an account with this email (" . $email . "), go back and try again <a href='forgot-password.php'>(Go Back)</a>. If you don't have an account, you can register one <a href='register.php'>here</a>.");
        }
    }

    $user = new User();

    try {
        if ($user->startPasswordReset($email)) {
            exit("<br>Success: password reset with success, please check your email for instructions and close this page.</p>");
        } else {
            exit("<p class='text-center'>Error: could not start password reset (error: " . $user->getErrorStatus() . ")</p>");
        }
    } catch (Exception $e) {
        exit("<p class='text-center'>Error: could not start password reset (error: " . $e->getMessage() . ")</p>");
    }

} else {
    ?>

    <!-- Password reset form -->
    <div class="container">
        <div class="row mt-4">
            <div class="col">
                <p class="h1 text-center">Password recovery</p>
            </div>
        </div>
        <div class="row justify-content-center mx-1">
            <div class="col-12 col-lg-8 col-xxl-6 border border-light rounded-4 pt-5 pb-5 px-5 mt-3 mb-3">
                <!-- Form asking for email to reset password -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="row g-3 needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="email" placeholder="Account email" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <button class="btn btn-outline-danger w-100" type="submit">Reset password</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col">
                <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
        <div class="row mb-5">
            <div class="col">
                <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
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
