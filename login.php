<?php
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if ($username === "player1" && $password === "password") {
        $_SESSION["username"] = $username;
        header("Location: index.php");
        exit();
    } else; {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Login to Play!</h2>

<?php if ($error): ?>
    <p style = "color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method = "POST" action = "login.php">
    <label for = "username">Username:</label>
    <br>
    <input type="text" id = "username" name = "username" required>
    <br>
    <br>

    <label for = "password">Password:</label>
    <br>
    <input type="text" id = "password" name = "password" required>
    <br>
    <br>

    <button type = "submit">Login</button>
</form>

</body>
</html>