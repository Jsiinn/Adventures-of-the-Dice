<?php
// new_game.php — Game setup screen
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adventures of the Dice — New Game</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700;900&family=Lora:ital,wght@0,400;0,600;1,400&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
.setup-page {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; min-height: 100vh; padding: 2rem;
}
.setup-hero {
  text-align: center; margin-bottom: 3rem;
}
.setup-hero .big-dice { font-size: 4rem; display: block; margin-bottom: 1rem; }
.setup-hero h1 {
  font-family: 'Cinzel Decorative', serif;
  font-size: clamp(1.4rem, 4vw, 2.4rem); color: var(--gold-lt);
  letter-spacing: 0.03em; margin-bottom: 0.5rem;
}
.setup-hero p { color: var(--text-dim); font-style: italic; }

.setup-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem; width: 100%; max-width: 1000px;
}
.setup-card {
  background: var(--navy-mid); border: 1px solid var(--border);
  border-radius: 16px; padding: 1.75rem;
  text-decoration: none; transition: all 0.25s;
  display: flex; flex-direction: column; gap: 1rem;
}
.setup-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.4); }
.setup-card.beginner:hover { border-color: var(--teal);  box-shadow: 0 16px 40px rgba(42,184,160,0.15); }
.setup-card.standard:hover { border-color: var(--gold);  box-shadow: 0 16px 40px rgba(201,168,76,0.15); }
.setup-card.expert:hover   { border-color: var(--ember); box-shadow: 0 16px 40px rgba(224,92,42,0.15); }

.sc-header { display: flex; align-items: center; gap: 0.75rem; }
.sc-icon { font-size: 1.5rem; }
.sc-title { font-family: 'Cinzel Decorative', serif; font-size: 1rem; color: var(--cream); }
.sc-difficulty { font-size: 0.72rem; padding: 0.15rem 0.5rem; border-radius: 4px; }
.sc-ai-row { display: flex; flex-direction: column; gap: 0.5rem; }
.sc-ai-option {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 0.75rem; background: rgba(255,255,255,0.04);
  border: 1px solid var(--border); border-radius: 8px;
  text-decoration: none; transition: all 0.2s;
}
.sc-ai-option:hover { background: rgba(255,255,255,0.08); border-color: var(--gold); }
.sc-ai-label { color: var(--cream); font-size: 0.85rem; font-weight: 600; flex: 1; }
.sc-ai-desc  { color: var(--text-dim); font-size: 0.72rem; font-style: italic; }
.sc-ai-icon  { font-size: 0.8rem; color: var(--gold); }
.sc-stats {
  font-size: 0.75rem; color: var(--text-dim);
  padding-top: 0.75rem; border-top: 1px solid var(--border);
  display: flex; gap: 1rem; flex-wrap: wrap;
}
</style>
</head>
<body>
<div class="setup-page">
  <div class="setup-hero">
    <span class="big-dice">[dice]</span>
    <h1>Adventures of the Dice</h1>
    <p>Choose your difficulty and AI opponent strategy</p>
  </div>

  <div class="setup-grid">

    <?php
    $difficulties = [
      'beginner' => ['[beginner]', 'Beginner', '3 snakes · 3 ladders', 'The gentle introduction — perfect for learning the board.'],
      'standard' => ['[standard]', 'Standard', '6 snakes · 5 ladders', 'A balanced challenge with plenty of dramatic reversals.'],
      'expert'   => ['[expert]', 'Expert',   '9 snakes · 4 ladders', 'Brutal and unforgiving. One wrong step and you plummet.'],
    ];
    $ai_strategies = [
      'easy'   => ['Random',     'Rolls dice blindly — pure chance'],
      'medium' => ['Greedy',     'Avoids snakes, picks best 1-step outcome'],
      'hard'   => ['Look-ahead', 'Simulates 2 turns and picks optimal path'],
    ];
    foreach ($difficulties as $diff => [$icon, $label, $stats, $desc]):
    ?>
    <div class="setup-card <?= $diff ?>">
      <div class="sc-header">
        <span class="sc-icon"><?= $icon ?></span>
        <div>
          <div class="sc-title"><?= $label ?></div>
          <p style="font-size:0.75rem;color:var(--text-dim);margin-top:0.15rem;font-style:italic"><?= $desc ?></p>
        </div>
      </div>

      <div class="sc-ai-row">
        <?php foreach ($ai_strategies as $ai => [$ai_label, $ai_desc]): ?>
        <a href="index.php?new=1&difficulty=<?= $diff ?>&ai_strategy=<?= $ai ?>" class="sc-ai-option">
          <span class="sc-ai-label">vs <?= $ai_label ?> AI</span>
          <span class="sc-ai-desc"><?= $ai_desc ?></span>
          <span class="sc-ai-icon">&rarr;</span>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="sc-stats">
        <span><?= $stats ?></span>
        <span>PHP session-driven</span>
        <span>Grad AI + path analysis</span>
      </div>
    </div>
    <?php endforeach; ?>

  </div>

  <p style="margin-top:2rem;color:var(--text-dim);font-size:0.8rem;text-align:center">
    CSC 4370/6370 · Spring 2026 · Server-Side Web Development with Scrum
  </p>
</div>
</body>
</html>