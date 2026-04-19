<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['difficulty'])) {
    header("Location: lobby.php");
    exit();
}

//Board Setup
$boards = [
    'beginner' => [
        'snakes'  => [17 => 7, 54 => 34, 62 => 19],
        'ladders' => [3 => 22, 20 => 41, 57 => 76]
    ],
    'standard' => [
        'snakes'  => [17 => 7, 54 => 34, 62 => 19, 64 => 40, 87 => 24, 93 => 73],
        'ladders' => [3 => 22, 20 => 41, 57 => 76, 28 => 84, 51 => 67]
    ],
    'expert' => [
        'snakes'  => [17 => 7, 54 => 34, 62 => 19, 64 => 40, 87 => 24, 93 => 73, 46 => 5, 32 => 10, 79 => 43],
        'ladders' => [3 => 22, 20 => 41, 57 => 76, 8 => 52]
    ]
];

$difficulty = $_SESSION['difficulty'] ?? 'beginner';
$snakes     = $boards[$difficulty]['snakes'];
$ladders    = $boards[$difficulty]['ladders'];

//Event Cells
$event_cells = [
    15 => ['type' => 'bonus',   'move' => +5,  'msg' => 'You found a hidden shortcut!'],
    42 => ['type' => 'skip',    'move' => 0,   'msg' => 'You tripped and lost your turn!'],
    67 => ['type' => 'penalty', 'move' => -5,  'msg' => 'A boulder blocks your path!'],
    23 => ['type' => 'warp',    'move' => +10, 'msg' => 'A magic portal appears!'],
    55 => ['type' => 'skip',    'move' => 0,   'msg' => 'Quicksand! You\'re stuck!'],
    33 => ['type' => 'penalty', 'move' => -3,  'msg' => 'You slipped on a banana peel!'],
];

//AI Narrator
function narrate($player, $cell, $event) {
    $name = "Player $player";
    switch ($event['type']) {
        case 'bonus':
            return "⭐ $name lands on cell $cell — {$event['msg']} Zooming ahead " . abs($event['move']) . " cells!";
        case 'penalty':
            return "💥 $name hits cell $cell — {$event['msg']} Sliding back " . abs($event['move']) . " cells!";
        case 'skip':
            return "⏸️ $name lands on cell $cell — {$event['msg']} Turn skipped!";
        case 'warp':
            return "🌀 $name lands on cell $cell — {$event['msg']} Warping ahead " . abs($event['move']) . " cells!";
        default:
            return "🎲 $name lands on cell $cell.";
    }
}

//Player Positions + Turn
if (!isset($_SESSION['positions'])) {
    $_SESSION['positions'] = [0, 0];
}
if (!isset($_SESSION['turn'])) {
    $_SESSION['turn'] = 0;
}
if (!isset($_SESSION['events_log'])) {
    $_SESSION['events_log'] = [];
}
if (!isset($_SESSION['skip_turn'])) {
    $_SESSION['skip_turn'] = [false, false];
}

//Dice Roll
$roll    = null;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll'])) {
    $turn = $_SESSION['turn'];

    //Handle skip turn
    if ($_SESSION['skip_turn'][$turn]) {
        $_SESSION['skip_turn'][$turn] = false;
        $message = "⏸️ Player " . ($turn + 1) . "'s turn was skipped!";
        $_SESSION['turn'] = 1 - $turn;
    } else {
        $roll = rand(1, 6);
        $pos  = $_SESSION['positions'][$turn] + $roll;

        if ($pos >= 100) {
            $pos     = 100;
            $message = "🎉 Player " . ($turn + 1) . " reached 100 — Wins!";
        } elseif (isset($snakes[$pos])) {
            $message = "🐍 Player " . ($turn + 1) . " hit a snake! Sliding down from $pos to {$snakes[$pos]}";
            $pos     = $snakes[$pos];
        } elseif (isset($ladders[$pos])) {
            $message = "🪜 Player " . ($turn + 1) . " hit a ladder! Climbing up from $pos to {$ladders[$pos]}";
            $pos     = $ladders[$pos];
        } else {
            $message = "🎲 Player " . ($turn + 1) . " rolled $roll → Cell $pos";
        }

        //Check for event tile
        if (isset($event_cells[$pos]) && $pos < 100) {
            $event     = $event_cells[$pos];
            $narration = narrate($turn + 1, $pos, $event);
            $_SESSION['last_event']   = $narration;
            $_SESSION['events_log'][] = $narration;

            if ($event['type'] === 'skip') {
                $_SESSION['skip_turn'][$turn] = true;
            } else {
                $pos = max(1, min(99, $pos + $event['move']));
            }
        } else {
            $_SESSION['last_event'] = null;
        }

        $_SESSION['positions'][$turn] = $pos;
        $_SESSION['turn']             = 1 - $turn;
        $_SESSION['roll_history'][]   = "P" . ($turn + 1) . ": $roll → Cell $pos";
    }
}

//Reset
if (isset($_POST['reset'])) {
    $_SESSION['positions']    = [0, 0];
    $_SESSION['turn']         = 0;
    $_SESSION['roll_history'] = [];
    $_SESSION['events_log']   = [];
    $_SESSION['skip_turn']    = [false, false];
    $_SESSION['last_event']   = null;
    $message = "Game reset!";
}

$p1 = $_SESSION['positions'][0];
$p2 = $_SESSION['positions'][1];

//Building Board
function getCellNumber($row, $col) {
    $rowFromBottom = $row;
    $cellBase      = $rowFromBottom * 10;
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

    <?php if (!empty($_SESSION['last_event'])): ?>
        <div class="narrator-box">
            <p><?php echo htmlspecialchars($_SESSION['last_event']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Turn Display -->
    <p>Current Turn: <strong>Player <?php echo $_SESSION['turn'] + 1; ?></strong>
        &nbsp;|&nbsp; Difficulty: <strong><?php echo ucfirst($difficulty); ?></strong>
    </p>

    <!-- Both positions -->
    <p>🔵 Player 1: <strong>Cell <?php echo $p1; ?></strong> &nbsp;|&nbsp; 🔴 Player 2: <strong>Cell <?php echo $p2; ?></strong></p>

    <!-- Board -->
    <div class="board">
        <?php
        for ($row = 9; $row >= 0; $row--):
            for ($col = 0; $col < 10; $col++):
                $cell    = getCellNumber($row, $col);
                $classes = "cell";

                if (isset($snakes[$cell]))       $classes .= " snake-head";
                if (in_array($cell, $snakes))    $classes .= " snake-tail";
                if (isset($ladders[$cell]))      $classes .= " ladder-bottom";
                if (in_array($cell, $ladders))   $classes .= " ladder-top";

                if (isset($event_cells[$cell])) {
                    $type = $event_cells[$cell]['type'];
                    if ($type === 'bonus' || $type === 'warp') $classes .= " event-bonus";
                    if ($type === 'penalty')                   $classes .= " event-penalty";
                    if ($type === 'skip')                      $classes .= " event-skip";
                }
        ?>
            <div class="<?php echo $classes; ?>">
                <span class="cell-number"><?php echo $cell; ?></span>

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

                <?php if (isset($event_cells[$cell])): ?>
                    <span class="marker">
                        <?php
                        $t = $event_cells[$cell]['type'];
                        echo $t === 'bonus'   ? '⭐' :
                            ($t === 'penalty' ? '💥' :
                            ($t === 'skip'    ? '⏸️' :
                            ($t === 'warp'    ? '🌀' : '')));
                        ?>
                    </span>
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
        <a href="lobby.php" class="btn-reset">⚙️ Change Difficulty</a>
    </div>

    <!-- Legend -->
    <div class="legend">
        <span>🐍 Snake head</span>
        <span>🪜 Ladder base</span>
        <span>⭐ Bonus</span>
        <span>💥 Penalty</span>
        <span>⏸️ Skip turn</span>
        <span>🌀 Warp</span>
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