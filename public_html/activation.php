<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <?php
    include 'common/common-head.php';
    ?>
    <title>Activation</title>
</head>
<body class="font-monospace text-light text-center pt-5 bg-dark">
<?php
include "../private/dbconnection.php";
include "../private/objects/User.php";

// Check if get method and code is set.
if (isset($_GET["code"])){
    // Get code from get method.
    $code = $_GET["code"];
    // DBConnection.
    $conn = connection();
    // Prepare a select statement
    $sql = "SELECT username FROM User WHERE activationCode = ?";
    // Run query
    if ($data = $conn->execute_query($sql, [$code])) {

        // Check if code exists in DB.
        if ($data->num_rows == 0) {
            exit("No account found with that activation code (the activation code may be invalid or you're already activated).");
        }

        // Get from DB the username
        $result = $data->fetch_assoc();
        $username = $result["username"];

        // Create User object and save it in session.
        $user = new User();
        $user->setUsername($username);
        if (!$user->loadUser()){ // Load from DB the User with updated data.
            exit("Error: could not load user (" . $user->getErrorStatus() . ")");
        }

        // Activate user.
        if (!$user->activateAccount($code)){
            exit("Error: could not activate user (" . $user->getErrorStatus() . ")");
        }

        // Update user in DB.
        if (!$user->updateUserToDatabase()){
            exit("Error: could not update user (" . $user->getErrorStatus() . ")");
        }

        // Communicate to user that the account is activated.
        echo "Account activated with success!";

        // Redirect user to login.php after 2 seconds
        header("refresh:2;url=login.php");
    } else {
        // Display an error message if username not found.
        echo "Account not found, maybe the user got deleted or the code is invalid.";
    }
} else {
    exit("Error: missing data");
}
?>

<?php
include "common/common-footer.php";
include "common/common-body.php";
?>
</body>
</html>