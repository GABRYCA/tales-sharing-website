<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include_once (dirname(__FILE__) . '/common/common-head.php');
    ?>
    <title>Activation</title>
</head>
<body class="font-monospace text-light text-center pt-5 bg-dark">
<?php
include_once (dirname(__FILE__) . "/../private/connection.php");
include_once (dirname(__FILE__) . "/../private/objects/User.php");
include_once (dirname(__FILE__) . "/common/utility.php");

// Check if get method and code is set.
if (!empty($_GET["code"])){
    // Get code from get method.
    $code = validate_input($_GET["code"]);
    // DBConnection.
    $conn = connection();
    // Prepare a select statement.
    $sql = "SELECT username FROM User WHERE activationCode = ?";
    // Run query
    if ($data = $conn->execute_query($sql, [$code])) {

        // Check if code exists in DB.
        if ($data->num_rows == 0) {
            exit("<p class='text-center'>No account found with that activation code (the activation code may be invalid or you're already activated).</p>");
        }

        // Get from DB the username
        $result = $data->fetch_assoc();
        $username = $result["username"];

        // Create User object and save it in session.
        $user = new User();
        $user->setUsername($username);
        if (!$user->loadUser()){ // Load from DB the User with updated data.
            exit("<p class='text-center'>Error: could not load user (" . $user->getErrorStatus() . ")</p>");
        }

        // Activate user.
        if (!$user->activateAccount($code)){
            exit("<p class='text-center'>Error: could not activate user (" . $user->getErrorStatus() . ")</p>");
        }

        // Update user in DB.
        if (!$user->updateUserToDatabase()){
            exit("<p class='text-center'>Error: could not update user (" . $user->getErrorStatus() . ")</p>");
        }

        // Communicate to user that the account is activated.
        echo "<p class='text-center'>Account activated with success!</p>";

        // Redirect user to login.php after 2 seconds.
        header("refresh:2;url=login.php");
    } else {
        // Display an error message if username not found.
        echo "<p class='text-center'>Account not found, maybe the user got deleted or the code is invalid.</p>";
    }
} else {
    exit("<p class='text-center'>Error: missing data</p>");
}
?>

<?php
include_once (dirname(__FILE__) . "/common/common-footer.php");
include_once (dirname(__FILE__) . "/common/common-body.php");
?>
</body>
</html>