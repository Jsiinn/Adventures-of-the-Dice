<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['difficulty']) && isset($_POST['mode'])) {
    $_SESSION['difficulty']    = $_POST['difficulty'];
    $_SESSION['game_mode']     = $_POST['mode'];
    $_SESSION['positions']     = [0, 0];
    $_SESSION['turn']          = 0;
    $_SESSION['roll_history']  = [];
    $_SESSION['events_log']    = [];
    $_SESSION['skip_turn']     = [false, false];
    $_SESSION['turn_count']    = 0;
    $_SESSION['winner_saved']  = false;
    $_SESSION['recap_winner']  = null;
    $_SESSION['last_event']    = null;
    header("Location: index.php");
    exit();
}

$current_diff = $_SESSION['difficulty'] ?? null;
$current_mode = $_SESSION['game_mode']  ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Lobby - Snakes and Ladders</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="game-wrapper">

    <div class="game-header">
        <h2>🎲 Snakes and Ladders</h2>
        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | <a href="logout.php">Logout</a></p>
    </div>

    <form method="POST" action="lobby.php">

        <h3>Select Game Mode</h3>
        <div class="lobby-options">
            <div class="lobby-card <?php echo $current_mode === '2player' ? 'active' : ''; ?>">
                <h4>👥 2 Player</h4>
                <p>Take turns on the same screen</p>
                <label>
                    <input type="radio" name="mode" value="2player" <?php echo $current_mode !== 'ai' ? 'checked' : ''; ?>> Select
                </label>
            </div>
            <div class="lobby-card <?php echo $current_mode === 'ai' ? 'active' : ''; ?>">
                <h4>🤖 vs AI</h4>
                <p>Challenge the computer</p>
                <label>
                    <input type="radio" name="mode" value="ai" <?php echo $current_mode === 'ai' ? 'checked' : ''; ?>> Select
                </label>
            </div>
        </div>

        <h3>Select Difficulty</h3>
        <div class="difficulty-options">
            <div class="difficulty-card <?php echo $current_diff === 'beginner' ? 'active' : ''; ?>">
                <h4>🟢 Beginner</h4>
                <p>3 snakes &amp; 3 ladders</p>
                <label>
                    <input type="radio" name="difficulty" value="beginner" <?php echo $current_diff !== 'standard' && $current_diff !== 'expert' ? 'checked' : ''; ?>> Select
                </label>
            </div>
            <div class="difficulty-card <?php echo $current_diff === 'standard' ? 'active' : ''; ?>">
                <h4>🟡 Standard</h4>
                <p>6 snakes &amp; 5 ladders</p>
                <label>
                    <input type="radio" name="difficulty" value="standard" <?php echo $current_diff === 'standard' ? 'checked' : ''; ?>> Select
                </label>
            </div>
            <div class="difficulty-card <?php echo $current_diff === 'expert' ? 'active' : ''; ?>">
                <h4>🔴 Expert</h4>
                <p>9 snakes &amp; 4 ladders</p>
                <label>
                    <input type="radio" name="difficulty" value="expert" <?php echo $current_diff === 'expert' ? 'checked' : ''; ?>> Select
                </label>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit">🎮 Start Game</button>
        </div>

    </form>

</div>

</body>
</html>