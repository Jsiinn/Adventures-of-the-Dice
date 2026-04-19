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

//Player Positions + Turn
if (!isset($_SESSION['positions'])) {
    $_SESSION['positions'] = [0, 0];
}

if (!isset($_SESSION['turn'])) {
    $_SESSION['turn'] = 0; 
}

//Dice Roll
$roll = null;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll'])) {
    $roll = rand(1, 6);

    $turn = $_SESSION['turn'];
    $pos = $_SESSION['positions'][$turn] + $roll;

    if ($pos >= 100) {
        $pos = 100;
        $message = "🎉 Player " . ($turn + 1) . " reached 100 — Wins!";
    } elseif (isset($snakes[$pos])) {
        $message = "🐍 Player " . ($turn + 1) . " slid down!";
        $pos = $snakes[$pos];
    } elseif (isset($ladders[$pos])) {
        $message = "🪜 Player " . ($turn + 1) . " climbed up!";
        $pos = $ladders[$pos];
    } else {
        $message = "🎲 Player " . ($turn + 1) . " rolled $roll → $pos";
    }

    $_SESSION['positions'][$turn] = $pos;

    //Switch turn
    $_SESSION['turn'] = 1 - $turn;

    $_SESSION['roll_history'][] = "P" . ($turn + 1) . ": $roll → Cell $pos";
}

//Reset 
if (isset($_POST['reset'])) {
    $_SESSION['positions'] = [0, 0];
    $_SESSION['turn'] = 0;
    $_SESSION['roll_history'] = [];
    $message = "Game reset!";
}

$p1 = $_SESSION['positions'][0];
$p2 = $_SESSION['positions'][1];

//Building Board
function getCellNumber($row, $col) {
    $rowFromBottom = $row;
    $cellBase = $rowFromBottom * 10;

    if ($rowFromBottom % 2 === 0) {
        return $cellBase + $col + 1;
    } else {
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

    <!-- Turn Display -->
    <p>Current Turn: <strong>Player <?php echo $_SESSION['turn'] + 1; ?></strong></p>

    <!-- Both positions -->
    <p>Player 1: <strong>Cell <?php echo $p1; ?></strong> | Player 2: <strong>Cell <?php echo $p2; ?></strong></p>

    <!-- Board -->
    <div class="board">
        <?php
        for ($row = 9; $row >= 0; $row--):
            for ($col = 0; $col < 10; $col++):
                $cell = getCellNumber($row, $col);
                $classes = "cell";

                if (isset($snakes[$cell]))  $classes .= " snake-head";
                if (in_array($cell, $snakes)) $classes .= " snake-tail";
                if (isset($ladders[$cell])) $classes .= " ladder-bottom";
                if (in_array($cell, $ladders)) $classes .= " ladder-top";
        ?>
            <div class="<?php echo $classes; ?>">
                <span class="cell-number"><?php echo $cell; ?></span>

                <!-- ✅ Player Tokens -->
                <?php if ($cell === $p1): ?>
                    <span class="token">🔵</span>
                <?php endif; ?>

                <?php if ($cell === $p2): ?>
                    <span class="token">🔴</span>
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
            <?php if ($p1 < 100 && $p2 < 100): ?>
                <button type="submit" name="roll">🎲 Roll Dice</button>
            <?php endif; ?>
            <button type="submit" name="reset" class="btn-reset">🔄 Reset</button>
        </form>
    </div>

    <!-- Legend -->
    <div class="legend">
        <span>🐍 Snake head (slide down)</span>
        <span>🪜 Ladder base (climb up)</span>
        <span>🔵 Player 1</span>
        <span>🔴 Player 2</span>
    </div>

    <!-- History -->
    <?php if (!empty($_SESSION['roll_history'])): ?>
    <div class="roll-history">
        <h4>Roll History</h4>
        <?php foreach (array_reverse($_SESSION['roll_history']) as $entry): ?>
            <p><?php echo htmlspecialchars($entry); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

</body>
</html>