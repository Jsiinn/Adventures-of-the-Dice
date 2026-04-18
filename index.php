<?php
session_start();

// ── Initialize fresh game ──────────────────────────────────────────────
if (!isset($_SESSION['initialized']) || isset($_GET['new'])) {
    $difficulty = $_GET['difficulty'] ?? $_SESSION['difficulty'] ?? 'standard';
    require_once 'includes/board_config.php';
    $config = getBoardConfig($difficulty);

    $_SESSION = [
        'initialized'   => true,
        'difficulty'    => $difficulty,
        'ai_strategy'   => $_GET['ai_strategy'] ?? 'medium',
        'positions'     => [1 => 1, 2 => 1],         // player 1 & 2 (player 2 = AI)
        'turn'          => 1,                          // whose turn (1 = human)
        'turn_counter'  => 0,
        'dice_history'  => [],                         // ['player'=>1,'roll'=>4,'from'=>5,'to'=>9, ...]
        'events_log'    => [],
        'last_event'    => null,
        'last_roll'     => null,
        'last_message'  => null,
        'skip_next'     => [1 => false, 2 => false],
        'extra_roll'    => [1 => false, 2 => false],
        'winner'        => null,
        'start_time'    => time(),
        'turns_lost_to_snakes'   => [1 => 0, 2 => 0],
        'turns_gained_from_ladders' => [1 => 0, 2 => 0],
        'path_history'  => [1 => [1], 2 => [1]],
        'ai_log'        => [],
        'snakes'        => $config['snakes'],
        'ladders'       => $config['ladders'],
        'bonus_tiles'   => $config['bonus_tiles'],
        'event_cells'   => $config['event_cells'],
    ];
}

require_once 'includes/board_config.php';
require_once 'includes/game_logic.php';
require_once 'includes/ai_engine.php';
require_once 'includes/narrator.php';

// ── Handle POST (human rolls) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$_SESSION['winner']) {
    $action = $_POST['action'] ?? '';

    if ($action === 'roll' && $_SESSION['turn'] === 1) {
        // Human turn
        processHumanTurn();

        // If it's now AI's turn, immediately run AI
        if ($_SESSION['turn'] === 2 && !$_SESSION['winner']) {
            processAITurn();
        }
    }
}

// Redirect to leaderboard on win
if ($_SESSION['winner']) {
    // Store summary before redirect
    $_SESSION['game_summary'] = buildGameSummary();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adventures of the Dice [dice]</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Lora:ital,wght@0,400;0,600;1,400&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="page-wrapper">

  <!-- ── HEADER ── -->
  <header class="game-header">
    <div class="header-inner">
      <div class="logo-group">
        <span class="dice-icon">[dice]</span>
        <h1 class="game-title">Adventures of the Dice</h1>
      </div>
      <nav class="header-nav">
        <span class="difficulty-badge diff-<?= htmlspecialchars($_SESSION['difficulty']) ?>">
          <?= strtoupper($_SESSION['difficulty']) ?>
        </span>
        <span class="ai-badge">AI: <?= strtoupper($_SESSION['ai_strategy']) ?></span>
        <a href="?new=1" class="btn btn-ghost">New Game</a>
        <?php if ($_SESSION['winner']): ?>
          <a href="leaderboard.php" class="btn btn-gold">[trophy] Leaderboard</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <!-- ── NARRATOR BOX ── -->
  <?php if ($_SESSION['last_message']): ?>
  <div class="narrator-wrap">
    <div class="narrator-box">
      <span class="narrator-icon">[log]</span>
      <p class="narrator-text"><?= $_SESSION['last_message'] ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── WIN BANNER ── -->
  <?php if ($_SESSION['winner']): ?>
  <div class="win-banner">
    <div class="win-inner">
      <?php if ($_SESSION['winner'] === 1): ?>
        <h2>[!] Victory! You conquered the board!</h2>
        <p>You reached cell 100 in <?= $_SESSION['turn_counter'] ?> turns!</p>
      <?php else: ?>
        <h2>[AI] The AI wins this round…</h2>
        <p>The AI reached cell 100 in <?= $_SESSION['turn_counter'] ?> turns.</p>
      <?php endif; ?>
      <div class="win-actions">
        <a href="leaderboard.php" class="btn btn-gold">View Analysis &amp; Leaderboard &rarr;</a>
        <a href="?new=1" class="btn btn-ghost">Play Again</a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <main class="game-layout">

    <!-- ── LEFT PANEL: Controls + History ── -->
    <aside class="side-panel left-panel">

      <!-- Player Status Cards -->
      <div class="player-cards">
        <?php foreach ([1, 2] as $p): ?>
        <div class="player-card <?= $_SESSION['turn'] === $p && !$_SESSION['winner'] ? 'active' : '' ?> <?= $_SESSION['winner'] === $p ? 'winner' : '' ?>">
          <div class="pc-header">
            <span class="pc-icon"><?= $p === 1 ? '[P1]' : '[AI]' ?></span>
            <span class="pc-name"><?= $p === 1 ? 'You' : 'AI Opponent' ?></span>
            <?php if ($_SESSION['turn'] === $p && !$_SESSION['winner']): ?>
              <span class="turn-indicator">YOUR TURN</span>
            <?php endif; ?>
          </div>
          <div class="pc-cell">
            Cell <strong><?= $_SESSION['positions'][$p] ?></strong> / 100
          </div>
          <div class="pc-progress">
            <div class="pc-bar" style="width: <?= ($_SESSION['positions'][$p] / 100 * 100) ?>%"></div>
          </div>
          <?php if ($_SESSION['skip_next'][$p]): ?>
            <div class="pc-status skip">[skip] Skip next turn</div>
          <?php elseif ($_SESSION['extra_roll'][$p]): ?>
            <div class="pc-status extra">[+] Extra roll banked</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Roll Control -->
      <?php if (!$_SESSION['winner']): ?>
      <div class="roll-panel">
        <?php if ($_SESSION['turn'] === 1): ?>
          <form method="POST" id="roll-form">
            <input type="hidden" name="action" value="roll">
            <button type="submit" class="btn-roll" id="roll-btn">
              <span class="roll-dice">[dice]</span>
              <span>Roll the Dice</span>
            </button>
          </form>
        <?php else: ?>
          <div class="ai-thinking">
            <span>[AI] AI is plotting…</span>
          </div>
        <?php endif; ?>

        <?php if ($_SESSION['last_roll']): ?>
        <div class="last-roll-display">
          <div class="die-face">
            <?= getDieFace($_SESSION['last_roll']['roll']) ?>
          </div>
          <div class="roll-info">
            <span class="roll-number"><?= $_SESSION['last_roll']['roll'] ?></span>
            <span class="roll-label">
              <?= $_SESSION['last_roll']['player'] === 1 ? 'Your roll' : 'AI roll' ?>
            </span>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Dice History -->
      <div class="dice-history">
        <h3 class="panel-title">Dice History</h3>
        <div class="history-list">
          <?php foreach (array_reverse(array_slice($_SESSION['dice_history'], -10)) as $h): ?>
          <div class="history-item p<?= $h['player'] ?>">
            <span class="hi-icon"><?= $h['player'] === 1 ? '[P1]' : '[AI]' ?></span>
            <span class="hi-roll"><?= $h['roll'] ?></span>
            <span class="hi-path"><?= $h['from'] ?>&rarr;<?= $h['to'] ?></span>
            <?php if ($h['snake'] ?? false): ?>
              <span class="hi-event snake">[snake]</span>
            <?php elseif ($h['ladder'] ?? false): ?>
              <span class="hi-event ladder">[ladder]</span>
            <?php elseif ($h['event'] ?? false): ?>
              <span class="hi-event bonus">[+]</span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php if (empty($_SESSION['dice_history'])): ?>
            <p class="empty-history">No rolls yet. Begin your adventure!</p>
          <?php endif; ?>
        </div>
      </div>

    </aside>

    <!-- ── BOARD ── -->
    <div class="board-container">
      <?php renderBoard(); ?>
    </div>

    <!-- ── RIGHT PANEL: Prob Map + Events ── -->
    <aside class="side-panel right-panel">

      <!-- Probability Map -->
      <div class="prob-map-panel">
        <h3 class="panel-title">Move Probability Map</h3>
        <p class="panel-subtitle">Where you'll land from cell <?= $_SESSION['positions'][1] ?></p>
        <?php
        require_once 'includes/ai_engine.php';
        $probs = rollProbabilities(
            $_SESSION['positions'][1],
            $_SESSION['snakes'],
            $_SESSION['ladders']
        );
        ?>
        <div class="prob-bars">
          <?php foreach ($probs as $cell => $prob): ?>
          <div class="prob-row">
            <span class="prob-cell">Cell <?= $cell ?></span>
            <div class="prob-bar-wrap">
              <div class="prob-bar-fill <?= getProbClass($cell) ?>" style="width:<?= round($prob * 100) ?>%"></div>
            </div>
            <span class="prob-pct"><?= round($prob * 100) ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- AI Strategy -->
      <?php if (!empty($_SESSION['ai_log'])): ?>
      <div class="ai-log-panel">
        <h3 class="panel-title">AI Reasoning</h3>
        <div class="ai-log-list">
          <?php foreach (array_reverse(array_slice($_SESSION['ai_log'], -5)) as $log): ?>
          <div class="ai-log-item">
            <span class="al-icon">[AI]</span>
            <span class="al-text"><?= htmlspecialchars($log) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Event Log -->
      <?php if (!empty($_SESSION['events_log'])): ?>
      <div class="events-panel">
        <h3 class="panel-title">Adventure Log</h3>
        <div class="events-list">
          <?php foreach (array_reverse(array_slice($_SESSION['events_log'], -8)) as $ev): ?>
          <div class="event-item <?= $ev['type'] ?>">
            <span class="ev-icon"><?= getEventIcon($ev['type']) ?></span>
            <span class="ev-text"><?= htmlspecialchars($ev['msg']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </aside>

  </main>

  <!-- Legend -->
  <footer class="legend-bar">
    <div class="legend-inner">
      <span class="leg-item"><span class="leg-swatch snake-swatch"></span> Snake (slide down)</span>
      <span class="leg-item"><span class="leg-swatch ladder-swatch"></span> Ladder (climb up)</span>
      <span class="leg-item"><span class="leg-swatch bonus-swatch"></span> Bonus tile</span>
      <span class="leg-item"><span class="leg-swatch event-swatch"></span> Event cell</span>
      <span class="leg-item">[P1] You &nbsp;|&nbsp; [AI] AI</span>
      <span class="leg-item">Turn <?= $_SESSION['turn_counter'] ?></span>
    </div>
  </footer>

</div>

<script src="js/board.js"></script>
</body>
</html>