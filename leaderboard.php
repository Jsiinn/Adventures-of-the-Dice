<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$leaderboard_file = "leaderboard.txt";
$entries = [];

if (file_exists($leaderboard_file)) {
    $lines = file($leaderboard_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode("|", $line);
        if (count($parts) === 4) {
            $entries[] = [
                'winner'     => $parts[0],
                'difficulty' => $parts[1],
                'turns'      => (int)$parts[2],
                'date'       => $parts[3]
            ];
        }
    }
    $entries = array_reverse($entries);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leaderboard - Snakes and Ladders</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="game-wrapper">

    <div class="game-header">
        <h2>🏆 Leaderboard</h2>
        <p><a href="index.php">← Back to Game</a> | <a href="logout.php">Logout</a></p>
    </div>

    <?php if (empty($entries)): ?>
        <p>No games completed yet. Go win one!</p>
    <?php else: ?>
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Winner</th>
                    <th>Difficulty</th>
                    <th>Turns</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $i => $entry): ?>
                    <tr class="<?php echo $entry['winner'] === $_SESSION['username'] ? 'highlight' : ''; ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($entry['winner']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($entry['difficulty'])); ?></td>
                        <td><?php echo $entry['turns']; ?></td>
                        <td><?php echo htmlspecialchars($entry['date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

</body>
</html>