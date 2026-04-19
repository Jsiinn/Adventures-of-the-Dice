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
$game_mode  = $_SESSION['game_mode']  ?? '2player';
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
    $name = $player;
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

//Apply snake/ladder to a position
function resolvePosition($pos, $snakes, $ladders) {
    if (isset($snakes[$pos]))  return $snakes[$pos];
    if (isset($ladders[$pos])) return $ladders[$pos];
    return $pos;
}

//AI greedy roll 
function aiRoll($current_pos, $snakes, $ladders) {
    $best_pos  = -1;
    $best_roll = 1;

    for ($r = 1; $r <= 6; $r++) {
        $landing = $current_pos + $r;
        if ($landing > 100) continue;
        if ($landing === 100) return $r; 
        $resolved = resolvePosition($landing, $snakes, $ladders);
        if (!isset($snakes[$landing]) && $resolved > $best_pos) {
            $best_pos  = $resolved;
            $best_roll = $r;
        }
    }

    if ($best_pos === -1) {
        for ($r = 1; $r <= 6; $r++) {
            $landing = $current_pos + $r;
            if ($landing <= 100) {
                $best_roll = $r;
                break;
            }
        }
    }

    return $best_roll;
}

//Leaderboard writer
function recordWin($winner, $difficulty, $turns) {
    $file  = "leaderboard.txt";
    $date  = date("Y-m-d H:i");
    $entry = "$winner|$difficulty|$turns|$date" . PHP_EOL;
    file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
}

//Process a single player/AI turn
function processTurn($name, $pos, $roll, $snakes, $ladders, $event_cells) {
    $message   = "";
    $narration = null;
    $new_pos   = $pos + $roll;

    if ($new_pos >= 100) {
        $new_pos = 100;
        $message = "🎉 $name rolled $roll and reached 100 — Wins!";
    } elseif (isset($snakes[$new_pos])) {
        $message = "🐍 $name rolled $roll — hit a snake! Sliding down from $new_pos to {$snakes[$new_pos]}";
        $new_pos = $snakes[$new_pos];
    } elseif (isset($ladders[$new_pos])) {
        $message = "🪜 $name rolled $roll — hit a ladder! Climbing up from $new_pos to {$ladders[$new_pos]}";
        $new_pos = $ladders[$new_pos];
    } else {
        $message = "🎲 $name rolled $roll → Cell $new_pos";
    }

    if (isset($event_cells[$new_pos]) && $new_pos < 100) {
        $event     = $event_cells[$new_pos];
        $narration = narrate($name, $new_pos, $event);
        if ($event['type'] !== 'skip') {
            $new_pos = max(1, min(99, $new_pos + $event['move']));
        }
    }

    return [$new_pos, $message, $narration];
}

//Session init
if (!isset($_SESSION['positions']))    $_SESSION['positions']    = [0, 0];
if (!isset($_SESSION['turn']))         $_SESSION['turn']         = 0;
if (!isset($_SESSION['events_log']))   $_SESSION['events_log']   = [];
if (!isset($_SESSION['skip_turn']))    $_SESSION['skip_turn']    = [false, false];
if (!isset($_SESSION['turn_count']))   $_SESSION['turn_count']   = 0;
if (!isset($_SESSION['winner_saved'])) $_SESSION['winner_saved'] = false;
if (!isset($_SESSION['ai_message']))   $_SESSION['ai_message']   = null;

$roll      = null;
$message   = "";
$p1_name   = $_SESSION['username'];
$p2_name   = $game_mode === 'ai' ? '🤖 AI' : 'Player 2';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll'])) {
    $turn = $_SESSION['turn'];
    $_SESSION['ai_message']  = null;
    $_SESSION['last_event']  = null;

    //Handle skip turn
    if ($_SESSION['skip_turn'][$turn]) {
        $_SESSION['skip_turn'][$turn] = false;
        $message = "⏸️ " . ($turn === 0 ? $p1_name : $p2_name) . "'s turn was skipped!";
        $_SESSION['turn'] = 1 - $turn;
    } else {
        //Human roll
        $roll = rand(1, 6);
        $_SESSION['turn_count']++;
        $pos  = $_SESSION['positions'][0];

        [$new_pos, $message, $narration] = processTurn($p1_name, $pos, $roll, $snakes, $ladders, $event_cells);

        if ($narration) {
            $_SESSION['last_event']   = $narration;
            $_SESSION['events_log'][] = $narration;
            if (isset($event_cells[$_SESSION['positions'][0] + $roll]) && $event_cells[$_SESSION['positions'][0] + $roll]['type'] === 'skip') {
                $_SESSION['skip_turn'][0] = true;
            }
        }

        $_SESSION['positions'][0]   = $new_pos;
        $_SESSION['roll_history'][] = "P1: $roll → Cell $new_pos";

        if ($new_pos >= 100) {
            if (!$_SESSION['winner_saved']) {
                recordWin($p1_name, $difficulty, $_SESSION['turn_count']);
                $_SESSION['recap_winner'] = $p1_name;
                $_SESSION['winner_saved'] = true;
            }
        } elseif ($game_mode === 'ai') {
            $ai_pos  = $_SESSION['positions'][1];
            $ai_roll = aiRoll($ai_pos, $snakes, $ladders);
            $_SESSION['turn_count']++;

            [$ai_new_pos, $ai_msg, $ai_narration] = processTurn('🤖 AI', $ai_pos, $ai_roll, $snakes, $ladders, $event_cells);

            if ($ai_narration) {
                $_SESSION['events_log'][] = $ai_narration;
            }

            $_SESSION['positions'][1]   = $ai_new_pos;
            $_SESSION['roll_history'][] = "AI: $ai_roll → Cell $ai_new_pos";
            $_SESSION['ai_message']     = $ai_msg;

            if ($ai_new_pos >= 100 && !$_SESSION['winner_saved']) {
                recordWin('AI', $difficulty, $_SESSION['turn_count']);
                $_SESSION['recap_winner'] = '🤖 AI';
                $_SESSION['winner_saved'] = true;
            }
        } else {
            //2 player — switch turn
            $_SESSION['turn'] = 1 - $turn;
        }
    }

    //2 player turn switch after skip
    if ($game_mode === '2player' && isset($_POST['roll']) && $_SESSION['skip_turn'][$turn] === false && $turn !== $_SESSION['turn']) {

    }
}

//2-player manual turn 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll']) && $game_mode === '2player') {

}

//Reset
if (isset($_POST['reset'])) {
    $_SESSION['positions']    = [0, 0];
    $_SESSION['turn']         = 0;
    $_SESSION['roll_history'] = [];
    $_SESSION['events_log']   = [];
    $_SESSION['skip_turn']    = [false, false];
    $_SESSION['last_event']   = null;
    $_SESSION['turn_count']   = 0;
    $_SESSION['winner_saved'] = false;
    $_SESSION['recap_winner'] = null;
    $_SESSION['ai_message']   = null;
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

    <?php if (!empty($_SESSION['ai_message'])): ?>
        <p class="game-message ai-message"><?php echo htmlspecialchars($_SESSION['ai_message']); ?></p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['last_event'])): ?>
        <div class="narrator-box">
            <p><?php echo htmlspecialchars($_SESSION['last_event']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Win Screen -->
    <?php if ($p1 >= 100 || $p2 >= 100): ?>
        <div class="win-box">
            <h3>🎉 <?php echo htmlspecialchars($_SESSION['recap_winner'] ?? 'Someone'); ?> wins!</h3>
            <p>Completed in <strong><?php echo $_SESSION['turn_count']; ?></strong> turns on <strong><?php echo ucfirst($difficulty); ?></strong> difficulty.</p>
            <div class="win-actions">
                <a href="recap.php" class="btn-leaderboard">📜 Adventure Recap</a>
                <a href="leaderboard.php" class="btn-leaderboard">🏆 Leaderboard</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Turn / Mode Display -->
    <p>
        Mode: <strong><?php echo $game_mode === 'ai' ? 'vs 🤖 AI' : '👥 2 Player'; ?></strong>
        &nbsp;|&nbsp;
        <?php if ($game_mode === '2player'): ?>
            Current Turn: <strong><?php echo $_SESSION['turn'] === 0 ? htmlspecialchars($p1_name) : $p2_name; ?></strong>
        <?php else: ?>
            Your turn — roll when ready!
        <?php endif; ?>
        &nbsp;|&nbsp; Difficulty: <strong><?php echo ucfirst($difficulty); ?></strong>
    </p>

    <!-- Positions -->
    <p>
        🔵 <?php echo htmlspecialchars($p1_name); ?>: <strong>Cell <?php echo $p1; ?></strong>
        &nbsp;|&nbsp;
        <?php echo $game_mode === 'ai' ? '🤖 AI' : '🔴 Player 2'; ?>: <strong>Cell <?php echo $p2; ?></strong>
    </p>

    <!-- Board -->
    <div class="board">
        <?php
        for ($row = 9; $row >= 0; $row--):
            for ($col = 0; $col < 10; $col++):
                $cell    = getCellNumber($row, $col);
                $classes = "cell";

                if (isset($snakes[$cell]))     $classes .= " snake-head";
                if (in_array($cell, $snakes))  $classes .= " snake-tail";
                if (isset($ladders[$cell]))    $classes .= " ladder-bottom";
                if (in_array($cell, $ladders)) $classes .= " ladder-top";

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
                    <span class="token"><?php echo $game_mode === 'ai' ? '🤖' : '🔴'; ?></span>
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
        <a href="leaderboard.php" class="btn-reset">🏆 Leaderboard</a>
    </div>

    <!-- Legend -->
    <div class="legend">
        <span>🐍 Snake head</span>
        <span>🪜 Ladder base</span>
        <span>⭐ Bonus</span>
        <span>💥 Penalty</span>
        <span>⏸️ Skip turn</span>
        <span>🌀 Warp</span>
        <span>🔵 <?php echo htmlspecialchars($p1_name); ?></span>
        <span><?php echo $game_mode === 'ai' ? '🤖 AI' : '🔴 Player 2'; ?></span>
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