<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$events       = $_SESSION['events_log']   ?? [];
$history      = $_SESSION['roll_history'] ?? [];
$turns        = $_SESSION['turn_count']   ?? 0;
$diff         = ucfirst($_SESSION['difficulty'] ?? 'beginner');
$winner       = $_SESSION['recap_winner'] ?? 'Unknown';
$mode         = $_SESSION['game_mode']    ?? '2player';
$p1_path      = $_SESSION['p1_path']      ?? [0];
$optimal_path = $_SESSION['optimal_path'] ?? [];
$snakes_hit   = $_SESSION['snakes_hit']   ?? 0;
$ladders_hit  = $_SESSION['ladders_hit']  ?? 0;
$p1_name      = $_SESSION['username'];

// Count P1 actual turns (only P1 entries in history)
$p1_turns = 0;
foreach ($history as $entry) {
    if (strpos($entry, 'P1:') === 0) $p1_turns++;
}

$optimal_turns = max(0, count($optimal_path) - 1);

$turns_lost   = max(0, $p1_turns - $optimal_turns);
$efficiency   = $optimal_turns > 0 ? min(100, round(($optimal_turns / max(1, $p1_turns)) * 100)) : 100;
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

    <!-- Summary -->
    <div class="recap-summary">
        <p>🏆 <strong><?php echo htmlspecialchars($winner); ?></strong> won on <strong><?php echo htmlspecialchars($diff); ?></strong> difficulty in <strong><?php echo $turns; ?></strong> total turns
        (<?php echo $mode === 'ai' ? 'vs 🤖 AI' : '👥 2 Player'; ?> mode).</p>
    </div>

    <!-- Path Analysis -->
    <?php if (!empty($optimal_path) && $p1_turns > 0): ?>
    <h3>📊 Path Analysis — <?php echo htmlspecialchars($p1_name); ?></h3>
    <div class="path-analysis">

        <div class="path-stat-grid">
            <div class="path-stat">
                <span class="path-stat-value"><?php echo $p1_turns; ?></span>
                <span class="path-stat-label">Your turns</span>
            </div>
            <div class="path-stat">
                <span class="path-stat-value"><?php echo $optimal_turns; ?></span>
                <span class="path-stat-label">Optimal turns</span>
            </div>
            <div class="path-stat <?php echo $turns_lost > 0 ? 'stat-bad' : 'stat-good'; ?>">
                <span class="path-stat-value"><?php echo $turns_lost > 0 ? "+$turns_lost" : '0'; ?></span>
                <span class="path-stat-label">Extra turns</span>
            </div>
            <div class="path-stat">
                <span class="path-stat-value"><?php echo $efficiency; ?>%</span>
                <span class="path-stat-label">Efficiency</span>
            </div>
            <div class="path-stat stat-bad">
                <span class="path-stat-value"><?php echo $snakes_hit; ?></span>
                <span class="path-stat-label">🐍 Snakes hit</span>
            </div>
            <div class="path-stat stat-good">
                <span class="path-stat-value"><?php echo $ladders_hit; ?></span>
                <span class="path-stat-label">🪜 Ladders used</span>
            </div>
        </div>

        <!-- Efficiency bar -->
        <div class="efficiency-bar-wrap">
            <div class="efficiency-bar" style="width: <?php echo $efficiency; ?>%"></div>
        </div>
        <p class="efficiency-label">
            <?php if ($efficiency >= 90): ?>
                🌟 Outstanding — nearly perfect run!
            <?php elseif ($efficiency >= 70): ?>
                👍 Good effort — only a few turns wasted.
            <?php elseif ($efficiency >= 50): ?>
                😬 Rough game — the snakes had their way with you.
            <?php else: ?>
                💀 The board destroyed you this time. Better luck next game!
            <?php endif; ?>
        </p>

        <!-- Path comparison -->
        <div class="path-compare">
            <div class="path-col">
                <h4>🔵 Your Path (<?php echo $p1_turns; ?> turns)</h4>
                <div class="path-cells">
                    <?php foreach ($p1_path as $i => $cell): ?>
                        <span class="path-cell <?php echo $i === 0 ? 'path-start' : ($cell === 100 ? 'path-end' : ''); ?>">
                            <?php echo $cell; ?>
                        </span>
                        <?php if ($i < count($p1_path) - 1): ?><span class="path-arrow">→</span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="path-col">
                <h4>⚡ Optimal Path (<?php echo $optimal_turns; ?> turns)</h4>
                <div class="path-cells">
                    <?php foreach ($optimal_path as $i => $cell): ?>
                        <span class="path-cell <?php echo $i === 0 ? 'path-start' : ($cell === 100 ? 'path-end' : ''); ?>">
                            <?php echo $cell; ?>
                        </span>
                        <?php if ($i < count($optimal_path) - 1): ?><span class="path-arrow">→</span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>

    <!-- Events Log -->
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

    <!-- Full Roll History -->
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