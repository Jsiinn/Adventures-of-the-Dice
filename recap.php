<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$events  = $_SESSION['events_log']   ?? [];
$history = $_SESSION['roll_history'] ?? [];
$turns   = $_SESSION['turn_count']   ?? 0;
$diff    = ucfirst($_SESSION['difficulty'] ?? 'beginner');
$winner  = $_SESSION['recap_winner'] ?? 'Unknown';
$mode    = $_SESSION['game_mode']    ?? '2player';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adventure Recap - Snakes and Ladders</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="game-wrapper">

    <div class="game-header">
        <h2>📜 Adventure Recap</h2>
        <p><a href="index.php">← Back to Game</a> | <a href="logout.php">Logout</a></p>
    </div>

    <div class="recap-summary">
        <p>🏆 <strong><?php echo htmlspecialchars($winner); ?></strong> won on <strong><?php echo htmlspecialchars($diff); ?></strong> difficulty in <strong><?php echo $turns; ?></strong> turns
        (<?php echo $mode === 'ai' ? 'vs 🤖 AI' : '👥 2 Player'; ?> mode).</p>
    </div>

    <h3>⚡ Events That Occurred</h3>
    <?php if (empty($events)): ?>
        <p class="recap-empty">No special events occurred this game — a clean run!</p>
    <?php else: ?>
        <div class="recap-events">
            <?php foreach ($events as $i => $event): ?>
                <div class="recap-event-entry">
                    <span class="recap-num"><?php echo $i + 1; ?></span>
                    <span><?php echo htmlspecialchars($event); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3>🎲 Full Roll History</h3>
    <?php if (empty($history)): ?>
        <p class="recap-empty">No rolls recorded.</p>
    <?php else: ?>
        <div class="recap-history">
            <?php foreach ($history as $i => $entry): ?>
                <div class="recap-roll-entry">
                    <span class="recap-num"><?php echo $i + 1; ?></span>
                    <span><?php echo htmlspecialchars($entry); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="recap-actions">
        <a href="lobby.php" class="btn-reset">🎮 Play Again</a>
        <a href="leaderboard.php" class="btn-reset">🏆 Leaderboard</a>
    </div>

</div>

</body>
</html>