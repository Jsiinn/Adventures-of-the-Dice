<?php
session_start();

if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";
$users_file = "users.txt";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $confirm  = trim($_POST["confirm"]);

    if (empty($username) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = "Username must be 3–20 characters, letters/numbers/underscores only.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $taken = false;
        if (file_exists($users_file)) {
            $lines = file($users_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                [$stored_user] = explode(":", $line, 2);
                if (strtolower($stored_user) === strtolower($username)) {
                    $taken = true;
                    break;
                }
            }
        }

        if ($taken) {
            $error = "That username is already taken.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            file_put_contents($users_file, $username . ":" . $hashed . PHP_EOL, FILE_APPEND | LOCK_EX);
            $success = "Account created! You can now log in.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Snakes and Ladders</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <h2>Register</h2>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <label>Username:</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Confirm Password:</label>
        <input type="password" name="confirm" required>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>

</body>
</html>