<?php
session_start();
require_once 'includes/board_config.php';
require_once 'includes/ai_engine.php';

// Persist result to a simple JSON leaderboard file
$lb_file = __DIR__ . '/leaderboard.json';
$entries = [];
if (file_exists($lb_file)) {
    $entries = json_decode(file_get_contents($lb_file), true) ?? [];
}

$summary = $_SESSION['game_summary'] ?? null;

if ($summary && !isset($_SESSION['lb_saved'])) {
    $winner_name = $summary['winner'] === 1 ? 'Human' : 'AI';
    $entries[] = [
        'winner'     => $winner_name,
        'turns'      => $summary['turns'],
        'time'       => $summary['time_played'],
        'difficulty' => $summary['difficulty'],
        'ai_strategy'=> $summary['ai_strategy'],
        'date'       => date('Y-m-d H:i'),
    ];
    // Keep last 20 entries
    $entries = array_slice($entries, -20);
    file_put_contents($lb_file, json_encode($entries, JSON_PRETTY_PRINT));
    $_SESSION['lb_saved'] = true;
}

// Compute path analysis
$analysis_p1 = null;
$analysis_p2 = null;
$optimal     = null;
if ($summary) {
    $optimal     = computeOptimalPath($summary['snakes'] ?? $_SESSION['snakes'], $summary['ladders'] ?? $_SESSION['ladders']);
    $analysis_p1 = comparePathToOptimal(
        $summary['path_p1'],
        $_SESSION['snakes'],
        $_SESSION['ladders'],
        $optimal
    );
    $analysis_p2 = comparePathToOptimal(
        $summary['path_p2'],
        $_SESSION['snakes'],
        $_SESSION['ladders'],
        $optimal
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard & Analysis — Adventures of the Dice</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Lora:ital,wght@0,400;0,600;1,400&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="page-wrapper lb-page">

  <header class="game-header">
    <div class="header-inner">
      <div class="logo-group">
        <span class="dice-icon">[dice]</span>
        <h1 class="game-title">Adventures of the Dice</h1>
      </div>
      <nav class="header-nav">
        <a href="index.php?new=1" class="btn btn-ghost">New Game</a>
        <a href="index.php?new=1&difficulty=beginner" class="btn btn-ghost">Beginner</a>
        <a href="index.php?new=1&difficulty=standard" class="btn btn-ghost">Standard</a>
        <a href="index.php?new=1&difficulty=expert" class="btn btn-ghost">Expert</a>
      </nav>
    </div>
  </header>

  <main class="lb-main">

    <!-- ── POST-GAME ANALYSIS ── -->
    <?php if ($summary && $analysis_p1): ?>
    <section class="analysis-section">
      <h2 class="section-title">[analysis] AI Path Analysis</h2>

      <!-- Winner Banner -->
      <div class="result-banner <?= $summary['winner'] === 1 ? 'human-wins' : 'ai-wins' ?>">
        <?php if ($summary['winner'] === 1): ?>
          <span>[!]</span>
          <div>
            <strong>Human Victory!</strong>
            <p>You won in <?= $summary['turns'] ?> turns on <?= ucfirst($summary['difficulty']) ?> difficulty.</p>
          </div>
        <?php else: ?>
          <span>[AI]</span>
          <div>
            <strong>AI Victory (<?= ucfirst($summary['ai_strategy']) ?> strategy)</strong>
            <p>The AI won in <?= $summary['turns'] ?> turns on <?= ucfirst($summary['difficulty']) ?> difficulty.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Stats Comparison -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">Optimal Path</div>
          <div class="stat-value"><?= $optimal['min_turns'] ?> turns</div>
          <div class="stat-sub">Theoretical minimum</div>
        </div>
        <div class="stat-card <?= $summary['winner'] === 1 ? 'highlight' : '' ?>">
          <div class="stat-label">Your Turns</div>
          <div class="stat-value"><?= $analysis_p1['actual_turns'] ?></div>
          <div class="stat-sub"><?= $analysis_p1['efficiency'] ?>% efficient</div>
        </div>
        <div class="stat-card <?= $summary['winner'] === 2 ? 'highlight' : '' ?>">
          <div class="stat-label">AI Turns</div>
          <div class="stat-value"><?= $analysis_p2['actual_turns'] ?></div>
          <div class="stat-sub"><?= $analysis_p2['efficiency'] ?>% efficient</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Time Played</div>
          <div class="stat-value"><?= gmdate('i:s', $summary['time_played']) ?></div>
          <div class="stat-sub">minutes : seconds</div>
        </div>
      </div>

      <!-- Path Comparison -->
      <div class="path-comparison">
        <div class="path-col">
          <h3>[P1] Your Path</h3>
          <div class="path-meta">
            <span class="pm-badge snake">[snake] <?= $analysis_p1['snake_hits'] ?> snakes</span>
            <span class="pm-badge ladder">[ladder] <?= $analysis_p1['ladder_hits'] ?> ladders</span>
            <span class="pm-badge extra">+<?= $analysis_p1['extra_turns'] ?> extra turns</span>
          </div>
          <div class="path-cells">
            <?php foreach ($summary['path_p1'] as $i => $cell): ?>
              <span class="path-cell
                <?= isset($_SESSION['snakes'][$cell]) ? 'path-snake' : '' ?>
                <?= isset($_SESSION['ladders'][$cell]) ? 'path-ladder' : '' ?>
                <?= $cell === 100 ? 'path-end' : '' ?>"
                title="Step <?= $i ?>">
                <?= $cell ?>
              </span>
              <?php if ($i < count($summary['path_p1'])-1): ?>&rarr;<?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="path-col">
          <h3>[AI] AI Path (<?= ucfirst($summary['ai_strategy']) ?>)</h3>
          <div class="path-meta">
            <span class="pm-badge snake">[snake] <?= $analysis_p2['snake_hits'] ?> snakes</span>
            <span class="pm-badge ladder">[ladder] <?= $analysis_p2['ladder_hits'] ?> ladders</span>
            <span class="pm-badge extra">+<?= $analysis_p2['extra_turns'] ?> extra turns</span>
          </div>
          <div class="path-cells">
            <?php foreach ($summary['path_p2'] as $i => $cell): ?>
              <span class="path-cell
                <?= isset($_SESSION['snakes'][$cell]) ? 'path-snake' : '' ?>
                <?= isset($_SESSION['ladders'][$cell]) ? 'path-ladder' : '' ?>
                <?= $cell === 100 ? 'path-end' : '' ?>"
                title="Step <?= $i ?>">
                <?= $cell ?>
              </span>
              <?php if ($i < count($summary['path_p2'])-1): ?>&rarr;<?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="path-col optimal-col">
          <h3>[*] Optimal Path (BFS)</h3>
          <div class="path-meta">
            <span class="pm-badge optimal"><?= $optimal['min_turns'] ?> turns minimum</span>
          </div>
          <div class="path-cells">
            <?php foreach ($optimal['path'] as $i => $cell): ?>
              <span class="path-cell path-optimal" title="Optimal step <?= $i ?>"><?= $cell ?></span>
              <?php if ($i < count($optimal['path'])-1): ?>&rarr;<?php endif; ?>
            <?php endforeach; ?>
          </div>
          <p class="optimal-note">
            BFS finds the guaranteed shortest theoretical route — actual rolls are random,
            so this path may be impossible in one playthrough, but represents the board's
            ideal traversal if dice cooperated perfectly.
          </p>
        </div>
      </div>

      <!-- Adventure Recap -->
      <?php if (!empty($summary['events_log'])): ?>
      <div class="recap-panel">
        <h3>[log] Adventure Recap — All Events</h3>
        <div class="recap-list">
          <?php foreach ($summary['events_log'] as $i => $ev): ?>
          <div class="recap-item <?= $ev['type'] ?>">
            <span class="ri-num"><?= $i+1 ?></span>
            <span class="ri-icon"><?= getEventIcon($ev['type']) ?></span>
            <span class="ri-msg"><?= htmlspecialchars($ev['msg']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </section>
    <?php endif; ?>

    <!-- ── LEADERBOARD ── -->
    <section class="lb-section">
      <h2 class="section-title">[trophy] Leaderboard</h2>
      <?php if (!empty($entries)): ?>
      <table class="lb-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Winner</th>
            <th>Turns</th>
            <th>Time</th>
            <th>Difficulty</th>
            <th>AI Mode</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sorted = array_reverse($entries);
          usort($sorted, fn($a,$b) => $a['turns'] - $b['turns']);
          foreach ($sorted as $rank => $e): ?>
          <tr class="<?= $e['winner'] === 'Human' ? 'lb-human' : 'lb-ai' ?>">
            <td><?= $rank + 1 ?></td>
            <td><?= $e['winner'] === 'Human' ? '[P1] Human' : '[AI] AI' ?></td>
            <td><?= $e['turns'] ?></td>
            <td><?= gmdate('i:s', $e['time']) ?></td>
            <td class="diff-<?= $e['difficulty'] ?>"><?= ucfirst($e['difficulty']) ?></td>
            <td><?= ucfirst($e['ai_strategy'] ?? 'medium') ?></td>
            <td><?= $e['date'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="empty-lb">No games recorded yet. Play your first game!</p>
      <?php endif; ?>
    </section>

    <!-- ── NEW GAME SELECTOR ── -->
    <section class="new-game-section">
      <h2 class="section-title">Start a New Adventure</h2>
      <div class="new-game-grid">
        <?php
        $modes = [
          ['beginner', 'easy',   '[beginner] Beginner',  'Easy AI',   '3 snakes · 3 ladders · Random AI'],
          ['standard', 'medium', '[standard] Standard',  'Medium AI', '6 snakes · 5 ladders · Greedy AI'],
          ['expert',   'hard',   '[expert] Expert',    'Hard AI',   '9 snakes · 4 ladders · Look-ahead AI'],
        ];
        foreach ($modes as [$diff, $ai, $label, $ai_label, $desc]):
        ?>
        <a href="index.php?new=1&difficulty=<?= $diff ?>&ai_strategy=<?= $ai ?>" class="new-game-card diff-<?= $diff ?>-card">
          <span class="ngc-label"><?= $label ?></span>
          <span class="ngc-ai"><?= $ai_label ?></span>
          <span class="ngc-desc"><?= $desc ?></span>
          <span class="ngc-btn">Play &rarr;</span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

  </main>
</div>
</body>
</html>