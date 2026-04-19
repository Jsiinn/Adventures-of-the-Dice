<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['difficulty'])) {
    $_SESSION['difficulty'] = $_POST['difficulty'];
    $_SESSION['positions'] = [0, 0];
    $_SESSION['turn'] = 0;
    $_SESSION['roll_history'] = [];
    header("Location: index.php");
    exit();
}

$current = $_SESSION['difficulty'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Lobby</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="game-wrapper">

    <div class="game-header">
        <h2>🎲 Snakes and Ladders</h2>
        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | <a href="logout.php">Logout</a></p>
    </div>

    <h3>Select a Difficulty</h3>

    <div class="difficulty-options">
        <form method="POST">

            <div class="difficulty-card <?php echo $current === 'beginner' ? 'active' : ''; ?>">
                <h4>🟢 Beginner</h4>
                <p>3 snakes &amp; 3 ladders — great for learning the ropes</p>
                <button type="submit" name="difficulty" value="beginner">Select</button>
            </div>

            <div class="difficulty-card <?php echo $current === 'standard' ? 'active' : ''; ?>">
                <h4>🟡 Standard</h4>
                <p>6 snakes &amp; 5 ladders — the classic experience</p>
                <button type="submit" name="difficulty" value="standard">Select</button>
            </div>

            <div class="difficulty-card <?php echo $current === 'expert' ? 'active' : ''; ?>">
                <h4>🔴 Expert</h4>
                <p>9 snakes &amp; 4 ladders — only the brave survive</p>
                <button type="submit" name="difficulty" value="expert">Select</button>
            </div>

        </form>
    </div>

</div>

</body>
</html>