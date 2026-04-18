<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$users_file = "users.txt";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        $error = "Both fields are required.";
    } elseif (!file_exists($users_file)) {
        $error = "No accounts found. Please register first.";
    } else {
        $found = false;
        $lines = file($users_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            [$stored_user, $stored_hash] = explode(":", $line, 2);
            if (strtolower($stored_user) === strtolower($username)) {
                $found = true;
                if (password_verify($password, $stored_hash)) {
                    $_SESSION['username'] = $stored_user;
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Incorrect password.";
                }
                break;
            }
        }
        if (!$found) {
            $error = "No account found with that username.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Snakes and Ladders</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <h2>Snakes and Ladders — Login</h2>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label>Username:</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>

</body>
</html>