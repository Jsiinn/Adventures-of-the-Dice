<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

//Board Setup
$snakes = [
    17 => 7,
    54 => 34,
    62 => 19
];

$ladders = [
    3  => 22,
    20 => 41,
    57 => 76
];

//Player Position
if (!isset($_SESSION['position'])) {
    $_SESSION['position'] = 0;
}

//Dice Roll
$roll = null;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll'])) {
    $roll = rand(1, 6);
    $pos = $_SESSION['position'] + $roll;

    if ($pos >= 100) {
        $pos = 100;
        $message = "🎉 You reached cell 100 — You Win!";
    } elseif (isset($snakes[$pos])) {
        $message = "🐍 Snake! Sliding down from $pos to {$snakes[$pos]}";
        $pos = $snakes[$pos];
    } elseif (isset($ladders[$pos])) {
        $message = "🪜 Ladder! Climbing up from $pos to {$ladders[$pos]}";
        $pos = $ladders[$pos];
    } else {
        $message = "🎲 You rolled a $roll and moved to cell $pos.";
    }

    $_SESSION['position'] = $pos;
}

//Reset 
if (isset($_POST['reset'])) {
    $_SESSION['position'] = 0;
    $message = "Game reset!";
}

$player_pos = $_SESSION['position'];

//Building Board
function getCellNumber($row, $col) {
    $rowFromBottom = $row; // 0 = bottom row
    $cellBase = $rowFromBottom * 10;
    if ($rowFromBottom % 2 === 0) {
        // left to right
        return $cellBase + $col + 1;
    } else {
        // right to left
        return $cellBase + (10 - $col);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Snakes and Ladders</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="game-wrapper">

    <div class="game-header">
        <h2>Snakes and Ladders</h2>
        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | <a href="logout.php">Logout</a></p>
    </div>

    <?php if ($message): ?>
        <p class="game-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <p>Your position: <strong>Cell <?php echo $player_pos; ?></strong></p>

    <!-- Board -->
    <div class="board">
        <?php
        // Render from top row down to bottom row visually
        for ($row = 9; $row >= 0; $row--):
            for ($col = 0; $col < 10; $col++):
                $cell = getCellNumber($row, $col);
                $classes = "cell";

                if ($cell === $player_pos) $classes .= " player";
                if (isset($snakes[$cell]))  $classes .= " snake-head";
                if (in_array($cell, $snakes)) $classes .= " snake-tail";
                if (isset($ladders[$cell])) $classes .= " ladder-bottom";
                if (in_array($cell, $ladders)) $classes .= " ladder-top";
        ?>
            <div class="<?php echo $classes; ?>">
                <span class="cell-number"><?php echo $cell; ?></span>
                <?php if ($cell === $player_pos): ?>
                    <span class="token">🔵</span>
                <?php endif; ?>
                <?php if (isset($snakes[$cell])): ?>
                    <span class="marker">🐍</span>
                <?php endif; ?>
                <?php if (isset($ladders[$cell])): ?>
                    <span class="marker">🪜</span>
                <?php endif; ?>
            </div>
        <?php endfor; endfor; ?>
    </div>

        <!-- Controls -->
    <div class="controls">
        <form method="POST" action="index.php">
            <?php if ($player_pos < 100): ?>
                <button type="submit" name="roll">🎲 Roll Dice</button>
            <?php endif; ?>
            <button type="submit" name="reset" class="btn-reset">🔄 Reset</button>
        </form>
    </div>

    <!-- Legend -->
    <div class="legend">
        <span>🐍 Snake head (slide down)</span>
        <span>🪜 Ladder base (climb up)</span>
        <span>🔵 Your token</span>
    </div>

</div>

</body>
</html>