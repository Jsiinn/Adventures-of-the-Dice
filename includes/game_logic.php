<?php
// game_logic.php — dice rolling, movement, win detection, board rendering

require_once __DIR__ . '/narrator.php';
require_once __DIR__ . '/ai_engine.php';

// ── Main human turn ────────────────────────────────────────────────────
function processHumanTurn(): void {
    $p = 1;

    // Skip check
    if ($_SESSION['skip_next'][$p]) {
        $_SESSION['skip_next'][$p] = false;
        $_SESSION['last_message'] = narratorSkip($p);
        $_SESSION['turn'] = 2;
        $_SESSION['turn_counter']++;
        return;
    }

    $roll = rand(1, 6);
    movePlayer($p, $roll);

    if (!$_SESSION['winner']) {
        // Check extra roll
        if ($_SESSION['extra_roll'][$p]) {
            $_SESSION['extra_roll'][$p] = false;
            // stay on player 1's turn for next POST
        } else {
            $_SESSION['turn'] = 2;
        }
        $_SESSION['turn_counter']++;
    }
}

// ── AI turn (called server-side immediately after human) ───────────────
function processAITurn(): void {
    $p = 2;
    $_SESSION['turn_counter']++;

    if ($_SESSION['skip_next'][$p]) {
        $_SESSION['skip_next'][$p] = false;
        $_SESSION['last_message'] .= ' ' . narratorSkip($p);
        $_SESSION['turn'] = 1;
        return;
    }

    $roll = aiChooseRoll(
        $_SESSION['positions'][$p],
        $_SESSION['snakes'],
        $_SESSION['ladders'],
        $_SESSION['ai_strategy']
    );

    movePlayer($p, $roll);

    if (!$_SESSION['winner']) {
        if ($_SESSION['extra_roll'][$p]) {
            $_SESSION['extra_roll'][$p] = false;
            // AI takes another roll immediately
            $roll2 = aiChooseRoll(
                $_SESSION['positions'][$p],
                $_SESSION['snakes'],
                $_SESSION['ladders'],
                $_SESSION['ai_strategy']
            );
            movePlayer($p, $roll2);
        }
        $_SESSION['turn'] = 1;
    }
}

// ── Core movement engine ───────────────────────────────────────────────
function movePlayer(int $player, int $roll): void {
    $from     = $_SESSION['positions'][$player];
    $raw_new  = $from + $roll;
    $new_pos  = min($raw_new, 100);   // don't overshoot; bounce rule: must hit exactly
    // Bounce: if overshoot, stay put
    if ($raw_new > 100) {
        $new_pos = $from;
        $hist = [
            'player' => $player, 'roll' => $roll,
            'from' => $from, 'to' => $from,
            'note' => 'bounced',
        ];
        $_SESSION['dice_history'][] = $hist;
        $_SESSION['last_roll'] = $hist;
        $_SESSION['last_message'] = narratorBounce($player, $roll, $from);
        $_SESSION['events_log'][] = ['type' => 'penalty', 'msg' => "P{$player} bounced from {$from}!"];
        return;
    }

    $snake_flag  = false;
    $ladder_flag = false;
    $event_flag  = false;

    // ── Snake? ──────────────────────────────────────────────────
    if (isset($_SESSION['snakes'][$new_pos])) {
        $snake_end = $_SESSION['snakes'][$new_pos];
        $_SESSION['turns_lost_to_snakes'][$player]++;
        $msg = narratorSnake($player, $roll, $from, $new_pos, $snake_end);
        $_SESSION['events_log'][] = ['type' => 'snake', 'msg' => "P{$player}: snake {$new_pos}&rarr;{$snake_end}"];
        $new_pos    = $snake_end;
        $snake_flag = true;
    }
    // ── Ladder? ─────────────────────────────────────────────────
    elseif (isset($_SESSION['ladders'][$new_pos])) {
        $ladder_end = $_SESSION['ladders'][$new_pos];
        $_SESSION['turns_gained_from_ladders'][$player]++;
        $msg = narratorLadder($player, $roll, $from, $new_pos, $ladder_end);
        $_SESSION['events_log'][] = ['type' => 'ladder', 'msg' => "P{$player}: ladder {$new_pos}&rarr;{$ladder_end}"];
        $new_pos     = $ladder_end;
        $ladder_flag = true;
    }
    // ── Bonus tile? ─────────────────────────────────────────────
    elseif (isset($_SESSION['bonus_tiles'][$new_pos])) {
        $tile = $_SESSION['bonus_tiles'][$new_pos];
        $msg = narratorBonus($player, $tile);
        applyBonusTile($player, $tile);
        $_SESSION['events_log'][] = ['type' => $tile['type'], 'msg' => $tile['msg']];
        $event_flag = true;
    }
    // ── Event cell? ─────────────────────────────────────────────
    elseif (isset($_SESSION['event_cells'][$new_pos])) {
        $event = $_SESSION['event_cells'][$new_pos];
        // Seed rand with turn counter for reproducibility
        srand($_SESSION['turn_counter'] * 31 + $player * 7);
        $variation = rand(0, 2);
        srand(); // unseed
        $new_pos = max(1, min(100, $new_pos + $event['move']));
        $msg = narratorEvent($player, $event, $new_pos, $variation);
        $_SESSION['last_event'] = $event;
        $_SESSION['events_log'][] = ['type' => $event['type'], 'msg' => $event['msg']];
        $event_flag = true;
    } else {
        $msg = narratorNormal($player, $roll, $from, $new_pos);
    }

    $_SESSION['positions'][$player] = $new_pos;
    $_SESSION['path_history'][$player][] = $new_pos;

    $hist = [
        'player' => $player, 'roll' => $roll,
        'from' => $from, 'to' => $new_pos,
        'snake' => $snake_flag, 'ladder' => $ladder_flag, 'event' => $event_flag,
    ];
    $_SESSION['dice_history'][] = $hist;
    $_SESSION['last_roll'] = $hist;
    $_SESSION['last_message'] = $msg ?? narratorNormal($player, $roll, $from, $new_pos);

    // ── Win check ────────────────────────────────────────────────
    if ($new_pos >= 100) {
        $_SESSION['positions'][$player] = 100;
        $_SESSION['winner'] = $player;
        $_SESSION['last_message'] = narratorWin($player);
    }
}

function applyBonusTile(int $player, array $tile): void {
    switch ($tile['type']) {
        case 'extra_roll':
            $_SESSION['extra_roll'][$player] = true;
            break;
        case 'skip':
            // skip the OTHER player
            $other = $player === 1 ? 2 : 1;
            $_SESSION['skip_next'][$other] = true;
            break;
        case 'mystery':
            // Random effect
            $effects = ['extra_roll', 'skip', 'nothing'];
            $effect  = $effects[array_rand($effects)];
            if ($effect === 'extra_roll') $_SESSION['extra_roll'][$player] = true;
            if ($effect === 'skip')       $_SESSION['skip_next'][$player]  = true;
            break;
    }
}

// ── Board renderer ─────────────────────────────────────────────────────
function renderBoard(): void {
    $snakes    = $_SESSION['snakes'];
    $ladders   = $_SESSION['ladders'];
    $bonuses   = $_SESSION['bonus_tiles'];
    $events    = $_SESSION['event_cells'];
    $p1_pos    = $_SESSION['positions'][1];
    $p2_pos    = $_SESSION['positions'][2];
    $winner    = $_SESSION['winner'];

    // Build 10×10 grid. Row 10 = top (cells 91-100), Row 1 = bottom (1-10).
    // Odd rows (from bottom): left-to-right. Even rows: right-to-left.
    echo '<div class="board" id="game-board">';

    for ($row = 10; $row >= 1; $row--) {
        $base = ($row - 1) * 10;
        $cells = range($base + 1, $base + 10);
        if ($row % 2 === 0) $cells = array_reverse($cells);

        echo '<div class="board-row">';
        foreach ($cells as $cell) {
            $classes = ['cell'];

            // Special cell types
            if (isset($snakes[$cell]))    $classes[] = 'cell-snake-head';
            if (in_array($cell, $snakes)) $classes[] = 'cell-snake-tail';
            if (isset($ladders[$cell]))   $classes[] = 'cell-ladder-base';
            if (in_array($cell, $ladders))$classes[] = 'cell-ladder-top';
            if (isset($bonuses[$cell]))   $classes[] = 'cell-bonus';
            if (isset($events[$cell]))    $classes[] = 'cell-event';
            if ($cell === 1)              $classes[] = 'cell-start';
            if ($cell === 100)            $classes[] = 'cell-end';

            // Player positions
            $here = [];
            if ($p1_pos === $cell) $here[] = 1;
            if ($p2_pos === $cell) $here[] = 2;
            if (!empty($here))    $classes[] = 'cell-occupied';

            // Active cell highlight
            $active_cell = $_SESSION['last_roll']['to'] ?? null;
            if ($active_cell === $cell) $classes[] = 'cell-active';

            echo '<div class="' . implode(' ', $classes) . '" data-cell="' . $cell . '">';

            // Cell number
            echo '<span class="cell-num">' . $cell . '</span>';

            // Cell icon overlays
            if (isset($snakes[$cell])) {
                echo '<span class="cell-overlay snake-overlay" title="Snake to ' . $snakes[$cell] . '">[snake]</span>';
            }
            if (isset($ladders[$cell])) {
                echo '<span class="cell-overlay ladder-overlay" title="Ladder to ' . $ladders[$cell] . '">[ladder]</span>';
            }
            if (isset($bonuses[$cell])) {
                $btype = $bonuses[$cell]['type'];
                $icons = ['extra_roll' => '[+]', 'skip' => '[skip]', 'mystery' => '[?]'];
                echo '<span class="cell-overlay bonus-overlay">' . ($icons[$btype] ?? '[*]') . '</span>';
            }
            if (isset($events[$cell])) {
                $etype = $events[$cell]['type'];
                $icons = ['bonus' => '[*]', 'penalty' => '[x]', 'warp' => '[~]'];
                echo '<span class="cell-overlay event-overlay">' . ($icons[$etype] ?? '[.]') . '</span>';
            }

            // Show snake destination
            if (isset($snakes[$cell])) {
                echo '<span class="cell-dest">&rarr;' . $snakes[$cell] . '</span>';
            }
            if (isset($ladders[$cell])) {
                echo '<span class="cell-dest up">&rarr;' . $ladders[$cell] . '</span>';
            }

            // Player tokens
            if (!empty($here)) {
                echo '<div class="token-group">';
                foreach ($here as $pn) {
                    $winner_class = ($winner === $pn) ? ' token-winner' : '';
                    echo '<span class="token player-' . $pn . $winner_class . '">';
                    echo $pn === 1 ? '[P1]' : '[AI]';
                    echo '</span>';
                }
                echo '</div>';
            }

            echo '</div>'; // .cell
        }
        echo '</div>'; // .board-row
    }

    echo '</div>'; // .board
}

// ── Game summary builder ───────────────────────────────────────────────
function buildGameSummary(): array {
    return [
        'winner'        => $_SESSION['winner'],
        'turns'         => $_SESSION['turn_counter'],
        'time_played'   => time() - $_SESSION['start_time'],
        'path_p1'       => $_SESSION['path_history'][1],
        'path_p2'       => $_SESSION['path_history'][2],
        'snakes_p1'     => $_SESSION['turns_lost_to_snakes'][1],
        'snakes_p2'     => $_SESSION['turns_lost_to_snakes'][2],
        'ladders_p1'    => $_SESSION['turns_gained_from_ladders'][1],
        'ladders_p2'    => $_SESSION['turns_gained_from_ladders'][2],
        'difficulty'    => $_SESSION['difficulty'],
        'ai_strategy'   => $_SESSION['ai_strategy'],
        'events_log'    => $_SESSION['events_log'],
        'dice_history'  => $_SESSION['dice_history'],
    ];
}