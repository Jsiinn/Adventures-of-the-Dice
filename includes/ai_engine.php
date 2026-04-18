<?php
// ai_engine.php — AI opponent logic, probability map, path analyzer

// ── Roll Probability Map ───────────────────────────────────────────────
// Returns [landing_cell => probability] for all 6 dice outcomes from $pos.
function rollProbabilities(int $pos, array $snakes, array $ladders): array {
    $probs = [];
    for ($roll = 1; $roll <= 6; $roll++) {
        $raw = $pos + $roll;
        // Bounce rule: overshoot = stay
        if ($raw > 100) {
            $land = $pos;
        } else {
            $land = $raw;
            // Apply snake/ladder
            if (isset($snakes[$land]))  $land = $snakes[$land];
            elseif (isset($ladders[$land])) $land = $ladders[$land];
        }
        $probs[$land] = ($probs[$land] ?? 0) + (1/6);
    }
    arsort($probs);
    return $probs;
}

// ── Compute best roll by AI strategy ─────────────────────────────────
function aiChooseRoll(int $pos, array $snakes, array $ladders, string $strategy): int {
    switch ($strategy) {
        case 'easy':
            return aiRollEasy();
        case 'medium':
            return aiRollMedium($pos, $snakes, $ladders);
        case 'hard':
            return aiRollHard($pos, $snakes, $ladders);
        default:
            return aiRollMedium($pos, $snakes, $ladders);
    }
}

// ── Easy: pure random ─────────────────────────────────────────────────
function aiRollEasy(): int {
    $roll = rand(1, 6);
    logAI("Easy mode: rolled {$roll} (pure random)");
    return $roll;
}

// ── Medium: greedy avoidance (1-turn look-ahead) ──────────────────────
// Simulates all 6 rolls, picks the one landing on the highest safe cell.
function aiRollMedium(int $pos, array $snakes, array $ladders): int {
    $best_roll   = rand(1, 6); // fallback
    $best_land   = -1;

    for ($roll = 1; $roll <= 6; $roll++) {
        $raw = $pos + $roll;
        if ($raw > 100) continue; // skip overshoots

        $land = $raw;
        if (isset($snakes[$land]))  $land = $snakes[$land];  // snake hit
        elseif (isset($ladders[$land])) $land = $ladders[$land]; // ladder hit

        if ($land > $best_land) {
            $best_land = $land;
            $best_roll = $roll;
        }
    }

    $actual = $pos + $best_roll;
    $note   = "Greedy: from {$pos}, best roll={$best_roll} → lands on {$best_land}";
    if (isset($snakes[$actual]))  $note .= " (avoided snake head at {$actual})";
    if (isset($ladders[$actual])) $note .= " (climbed ladder from {$actual})";
    logAI($note);

    return $best_roll;
}

// ── Hard: 2-turn look-ahead ───────────────────────────────────────────
// For each roll, simulates where AI can get to in 1 more roll after landing.
// Picks the roll that maximises expected position after 2 turns.
function aiRollHard(int $pos, array $snakes, array $ladders): int {
    $best_roll   = rand(1, 6);
    $best_score  = -INF;

    for ($roll = 1; $roll <= 6; $roll++) {
        $raw1 = $pos + $roll;
        if ($raw1 > 100) {
            $score = $pos; // bounce = no progress
        } else {
            $land1 = $raw1;
            if (isset($snakes[$land1]))  $land1 = $snakes[$land1];
            elseif (isset($ladders[$land1])) $land1 = $ladders[$land1];

            // Look-ahead: expected position after next roll from $land1
            $expected_next = 0;
            for ($r2 = 1; $r2 <= 6; $r2++) {
                $raw2 = $land1 + $r2;
                if ($raw2 > 100) {
                    $land2 = $land1;
                } else {
                    $land2 = $raw2;
                    if (isset($snakes[$land2]))  $land2 = $snakes[$land2];
                    elseif (isset($ladders[$land2])) $land2 = $ladders[$land2];
                }
                $expected_next += $land2 / 6;
            }
            $score = $expected_next;
        }

        if ($score > $best_score) {
            $best_score = $score;
            $best_roll  = $roll;
        }
    }

    $land_after = applySnakeLadder($pos + $best_roll, $snakes, $ladders);
    logAI("Hard look-ahead: from {$pos}, best roll={$best_roll}, expected position after 2 turns=" . round($best_score, 1));
    return $best_roll;
}

function applySnakeLadder(int $pos, array $snakes, array $ladders): int {
    if ($pos > 100) return $pos;
    if (isset($snakes[$pos]))  return $snakes[$pos];
    if (isset($ladders[$pos])) return $ladders[$pos];
    return $pos;
}

// ── Optimal path analyzer ─────────────────────────────────────────────
// BFS: finds minimum expected turns to reach 100 from each cell.
// Returns ['min_turns' => int, 'path' => [cells], 'avg_rolls_lost_snakes' => float, ...]
function computeOptimalPath(array $snakes, array $ladders): array {
    // BFS with uniform cost (each roll = 1 turn, 6 possible outcomes)
    $dist = array_fill(1, 100, PHP_INT_MAX);
    $prev = array_fill(1, 100, 0);
    $dist[1] = 0;
    $queue   = [1];

    while (!empty($queue)) {
        $cur = array_shift($queue);
        for ($r = 1; $r <= 6; $r++) {
            $raw  = $cur + $r;
            if ($raw > 100) continue;
            $land = applySnakeLadder($raw, $snakes, $ladders);
            if ($dist[$land] > $dist[$cur] + 1) {
                $dist[$land] = $dist[$cur] + 1;
                $prev[$land] = $cur;
                $queue[] = $land;
            }
        }
    }

    // Trace optimal path
    $path = [];
    $node = 100;
    while ($node && $node !== 1) {
        $path[] = $node;
        $node    = $prev[$node];
    }
    $path[] = 1;
    $path    = array_reverse($path);

    return [
        'min_turns' => $dist[100],
        'path'      => $path,
    ];
}

// ── Path comparison ───────────────────────────────────────────────────
function comparePathToOptimal(
    array $actual_path,
    array $snakes,
    array $ladders,
    array $optimal
): array {
    $actual_turns  = count($actual_path) - 1;
    $optimal_turns = $optimal['min_turns'];
    $extra_turns   = $actual_turns - $optimal_turns;

    // Count snake hits
    $snake_hits = 0;
    $ladder_hits = 0;
    foreach ($actual_path as $i => $cell) {
        if ($i === 0) continue;
        $prev_cell = $actual_path[$i - 1];
        // Check if came from a snake head (prev_cell is a snake tail, and we went backwards)
        foreach ($snakes as $head => $tail) {
            if ($cell === $tail && $prev_cell === $head) {
                $snake_hits++;
            }
        }
        foreach ($ladders as $base => $top) {
            if ($cell === $top && $prev_cell === $base) {
                $ladder_hits++;
            }
        }
    }

    return [
        'actual_turns'  => $actual_turns,
        'optimal_turns' => $optimal_turns,
        'extra_turns'   => $extra_turns,
        'snake_hits'    => $snake_hits,
        'ladder_hits'   => $ladder_hits,
        'efficiency'    => $optimal_turns > 0 ? round($optimal_turns / max($actual_turns, 1) * 100) : 0,
    ];
}

// ── AI internal log ───────────────────────────────────────────────────
function logAI(string $msg): void {
    $_SESSION['ai_log'][] = $msg;
    if (count($_SESSION['ai_log']) > 20) {
        array_shift($_SESSION['ai_log']);
    }
}