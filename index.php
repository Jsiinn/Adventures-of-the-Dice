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

//Narrator
function narrate($player, $cell, $event) {
    switch ($event['type']) {
        case 'bonus':
            return "⭐ $player lands on cell $cell — {$event['msg']} Zooming ahead " . abs($event['move']) . " cells!";
        case 'penalty':
            return "💥 $player hits cell $cell — {$event['msg']} Sliding back " . abs($event['move']) . " cells!";
        case 'skip':
            return "⏸️ $player lands on cell $cell — {$event['msg']} Turn skipped!";
        case 'warp':
            return "🌀 $player lands on cell $cell — {$event['msg']} Warping ahead " . abs($event['move']) . " cells!";
        default:
            return "🎲 $player lands on cell $cell.";
    }
}

//Probability map
function rollProbabilities($pos, $snakes, $ladders) {
    $probs = [];
    for ($r = 1; $r <= 6; $r++) {
        $landing = $pos + $r;
        if ($landing > 100) continue;
        $resolved = $landing;
        if (isset($snakes[$landing]))  $resolved = $snakes[$landing];
        if (isset($ladders[$landing])) $resolved = $ladders[$landing];
        $probs[$resolved] = ($probs[$resolved] ?? 0) + round(1/6, 4);
    }
    return $probs;
}

//AI greedy roll
function aiRoll($pos, $snakes, $ladders) {
    $best_pos  = -1;
    $best_roll = 1;
    for ($r = 1; $r <= 6; $r++) {
        $landing = $pos + $r;
        if ($landing > 100) continue;
        if ($landing === 100) return $r;
        $resolved = $landing;
        if (isset($snakes[$landing]))  $resolved = $snakes[$landing];
        if (isset($ladders[$landing])) $resolved = $ladders[$landing];
        if (!isset($snakes[$landing]) && $resolved > $best_pos) {
            $best_pos  = $resolved;
            $best_roll = $r;
        }
    }
    if ($best_pos === -1) {
        for ($r = 1; $r <= 6; $r++) {
            if ($pos + $r <= 100) { $best_roll = $r; break; }
        }
    }
    return $best_roll;
}

function computeOptimalPath($snakes, $ladders) {
    $pos   = 0;
    $path  = [0];
    $turns = 0;
    $max   = 200; 
    while ($pos < 100 && $turns < $max) {
        $roll = aiRoll($pos, $snakes, $ladders);
        $new  = $pos + $roll;
        if ($new >= 100) { $path[] = 100; break; }
        if (isset($snakes[$new]))  $new = $snakes[$new];
        if (isset($ladders[$new])) $new = $ladders[$new];
        $path[] = $new;
        $pos = $new;
        $turns++;
    }
    return $path;
}

//Leaderboard writer
function recordWin($winner, $difficulty, $turns) {
    $date  = date("Y-m-d H:i");
    $entry = "$winner|$difficulty|$turns|$date" . PHP_EOL;
    file_put_contents("leaderboard.txt", $entry, FILE_APPEND | LOCK_EX);
}

//Process one turn
function processTurn($name, $pos, $roll, $snakes, $ladders, $event_cells) {
    $narration = null;
    $skip      = false;
    $new_pos   = $pos + $roll;

    if ($new_pos >= 100) {
        $new_pos = 100;
        $message = "🎉 $name rolled $roll and reached 100 — Wins!";
        return [$new_pos, $message, $narration, $skip];
    }

    if (isset($snakes[$new_pos])) {
        $message = "🐍 $name rolled $roll — hit a snake! Sliding down from $new_pos to {$snakes[$new_pos]}";
        $new_pos = $snakes[$new_pos];
    } elseif (isset($ladders[$new_pos])) {
        $message = "🪜 $name rolled $roll — hit a ladder! Climbing up from $new_pos to {$ladders[$new_pos]}";
        $new_pos = $ladders[$new_pos];
    } else {
        $message = "🎲 $name rolled $roll → Cell $new_pos";
    }

    if (isset($event_cells[$new_pos])) {
        $event     = $event_cells[$new_pos];
        $narration = narrate($name, $new_pos, $event);
        if ($event['type'] === 'skip') {
            $skip = true;
        } else {
            $after_event = max(1, min(99, $new_pos + $event['move']));
            if (isset($snakes[$after_event]))       $after_event = $snakes[$after_event];
            elseif (isset($ladders[$after_event]))  $after_event = $ladders[$after_event];
            $new_pos = $after_event;
        }
    }

    return [$new_pos, $message, $narration, $skip];
}

//Session init
if (!isset($_SESSION['positions']))    $_SESSION['positions']    = [0, 0];
if (!isset($_SESSION['turn']))         $_SESSION['turn']         = 0;
if (!isset($_SESSION['events_log']))   $_SESSION['events_log']   = [];
if (!isset($_SESSION['skip_turn']))    $_SESSION['skip_turn']    = [false, false];
if (!isset($_SESSION['turn_count']))   $_SESSION['turn_count']   = 0;
if (!isset($_SESSION['winner_saved'])) $_SESSION['winner_saved'] = false;
if (!isset($_SESSION['ai_message']))   $_SESSION['ai_message']   = null;
if (!isset($_SESSION['show_probmap'])) $_SESSION['show_probmap'] = false;
if (!isset($_SESSION['last_event']))   $_SESSION['last_event']   = null;
if (!isset($_SESSION['p1_path']))      $_SESSION['p1_path']      = [0];
if (!isset($_SESSION['snakes_hit']))   $_SESSION['snakes_hit']   = 0;
if (!isset($_SESSION['ladders_hit']))  $_SESSION['ladders_hit']  = 0;

$message = "";
$p1_name = $_SESSION['username'];
$p2_name = $game_mode === 'ai' ? '🤖 AI' : 'Player 2';

//Toggle probability map
if (isset($_POST['toggle_probmap'])) {
    $_SESSION['show_probmap'] = !$_SESSION['show_probmap'];
}

//Main roll handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll'])) {
    $turn = $_SESSION['turn'];
    $_SESSION['ai_message'] = null;
    $_SESSION['last_event'] = null;

    $current_name = $turn === 0 ? $p1_name : $p2_name;

    if ($_SESSION['skip_turn'][$turn]) {
        $_SESSION['skip_turn'][$turn] = false;
        $message = "⏸️ {$current_name}'s turn was skipped!";
        $_SESSION['turn'] = 1 - $turn;
    } else {
        $roll = rand(1, 6);
        $_SESSION['turn_count']++;
        $pos = $_SESSION['positions'][$turn];

        $raw_landing = $pos + $roll;
        if ($turn === 0 && $raw_landing <= 100) {
            if (isset($snakes[$raw_landing]))  $_SESSION['snakes_hit']++;
            if (isset($ladders[$raw_landing])) $_SESSION['ladders_hit']++;
        }

        [$new_pos, $message, $narration, $skip] = processTurn(
            $turn === 0 ? $p1_name : $p2_name,
            $pos, $roll, $snakes, $ladders, $event_cells
        );

        if ($narration) {
            $_SESSION['last_event']   = $narration;
            $_SESSION['events_log'][] = $narration;
        }
        if ($skip) $_SESSION['skip_turn'][$turn] = true;

        $_SESSION['positions'][$turn] = $new_pos;

        if ($turn === 0) {
            $_SESSION['p1_path'][] = $new_pos;
        }

        $label = $turn === 0 ? 'P1' : 'P2';
        $_SESSION['roll_history'][] = "$label: $roll → Cell $new_pos";

        if ($new_pos >= 100) {
            if (!$_SESSION['winner_saved']) {
                $winner_name = $turn === 0 ? $p1_name : $p2_name;
                recordWin($winner_name, $difficulty, $_SESSION['turn_count']);
                $_SESSION['recap_winner'] = $winner_name;
                $_SESSION['winner_saved'] = true;
                $_SESSION['optimal_path'] = computeOptimalPath($snakes, $ladders);
            }
        } else {
            if ($game_mode === 'ai' && $turn === 0) {
                $ai_pos  = $_SESSION['positions'][1];
                $ai_roll = aiRoll($ai_pos, $snakes, $ladders);
                $_SESSION['turn_count']++;

                [$ai_new_pos, $ai_msg, $ai_narration, $ai_skip] = processTurn(
                    '🤖 AI', $ai_pos, $ai_roll, $snakes, $ladders, $event_cells
                );

                if ($ai_narration) $_SESSION['events_log'][] = $ai_narration;
                if ($ai_skip)      $_SESSION['skip_turn'][1] = true;

                $_SESSION['positions'][1]   = $ai_new_pos;
                $_SESSION['roll_history'][] = "AI: $ai_roll → Cell $ai_new_pos";
                $_SESSION['ai_message']     = $ai_msg;

                if ($ai_new_pos >= 100 && !$_SESSION['winner_saved']) {
                    recordWin('AI', $difficulty, $_SESSION['turn_count']);
                    $_SESSION['recap_winner'] = '🤖 AI';
                    $_SESSION['winner_saved'] = true;
                    $_SESSION['optimal_path'] = computeOptimalPath($snakes, $ladders);
                }
            } else {
                $_SESSION['turn'] = 1 - $turn;
            }
        }
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
    $_SESSION['turn_count']   = 0;
    $_SESSION['winner_saved'] = false;
    $_SESSION['recap_winner'] = null;
    $_SESSION['ai_message']   = null;
    $_SESSION['p1_path']      = [0];
    $_SESSION['snakes_hit']   = 0;
    $_SESSION['ladders_hit']  = 0;
    $_SESSION['optimal_path'] = null;
    $message = "Game reset!";
}

$p1 = $_SESSION['positions'][0];
$p2 = $_SESSION['positions'][1];

$prob_pos = $game_mode === 'ai' ? $p1 : $_SESSION['positions'][$_SESSION['turn']];
$prob_map = rollProbabilities($prob_pos, $snakes, $ladders);
$max_prob = !empty($prob_map) ? max($prob_map) : 1;

function getCellNumber($row, $col) {
    $base = $row * 10;
    return $row % 2 === 0 ? $base + $col + 1 : $base + (10 - $col);
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

    <p>
        Mode: <strong><?php echo $game_mode === 'ai' ? 'vs 🤖 AI' : '👥 2 Player'; ?></strong>
        &nbsp;|&nbsp;
        <?php if ($game_mode === '2player'): ?>
            Turn: <strong><?php echo $_SESSION['turn'] === 0 ? htmlspecialchars($p1_name) : $p2_name; ?></strong>
        <?php else: ?>
            Your turn — roll when ready!
        <?php endif; ?>
        &nbsp;|&nbsp; Difficulty: <strong><?php echo ucfirst($difficulty); ?></strong>
    </p>

    <p>
        🔵 <?php echo htmlspecialchars($p1_name); ?>: <strong>Cell <?php echo $p1; ?></strong>
        &nbsp;|&nbsp;
        <?php echo $game_mode === 'ai' ? '🤖 AI' : '🔴 Player 2'; ?>: <strong>Cell <?php echo $p2; ?></strong>
    </p>

    <?php if ($p1 < 100 && $p2 < 100): ?>
    <form method="POST" action="index.php" style="margin-bottom: 8px;">
        <button type="submit" name="toggle_probmap" class="btn-probmap">
            <?php echo $_SESSION['show_probmap'] ? '🗺️ Hide Probability Map' : '🗺️ Show Probability Map'; ?>
        </button>
    </form>
    <?php if ($_SESSION['show_probmap']): ?>
        <p class="probmap-legend">Landing probabilities from <strong>Cell <?php echo $prob_pos; ?></strong>. Darker blue = higher chance.</p>
    <?php endif; ?>
    <?php endif; ?>

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
                    $t = $event_cells[$cell]['type'];
                    if ($t === 'bonus' || $t === 'warp') $classes .= " event-bonus";
                    if ($t === 'penalty')                $classes .= " event-penalty";
                    if ($t === 'skip')                   $classes .= " event-skip";
                }

                $prob_style = "";
                if ($_SESSION['show_probmap'] && isset($prob_map[$cell]) && $p1 < 100 && $p2 < 100) {
                    $intensity  = $prob_map[$cell] / $max_prob;
                    $alpha      = round(0.15 + ($intensity * 0.65), 2);
                    $prob_style = "background: rgba(30, 100, 220, $alpha);";
                }
        ?>
            <div class="<?php echo $classes; ?>"<?php echo $prob_style ? " style=\"$prob_style\"" : ''; ?>>
                <span class="cell-number"><?php echo $cell; ?></span>

                <?php if ($_SESSION['show_probmap'] && isset($prob_map[$cell]) && $p1 < 100 && $p2 < 100): ?>
                    <span class="prob-label"><?php echo round($prob_map[$cell] * 100); ?>%</span>
                <?php endif; ?>

                <?php if ($cell === $p1): ?><span class="token">🔵</span><?php endif; ?>
                <?php if ($cell === $p2): ?><span class="token"><?php echo $game_mode === 'ai' ? '🤖' : '🔴'; ?></span><?php endif; ?>
                <?php if (isset($snakes[$cell])): ?><span class="marker">🐍</span><?php endif; ?>
                <?php if (isset($ladders[$cell])): ?><span class="marker">🪜</span><?php endif; ?>
                <?php if (isset($event_cells[$cell])): ?>
                    <span class="marker">
                        <?php
                        $t = $event_cells[$cell]['type'];
                        echo $t === 'bonus' ? '⭐' : ($t === 'penalty' ? '💥' : ($t === 'skip' ? '⏸️' : ($t === 'warp' ? '🌀' : '')));
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endfor; endfor; ?>
    </div>

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