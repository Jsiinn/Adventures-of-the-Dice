<?php
// board_config.php — Snake/ladder/event definitions for all difficulties

function getBoardConfig(string $difficulty): array {
    $configs = [
        // ── BEGINNER: 3 snakes, 3 ladders ─────────────────────────────
        'beginner' => [
            'snakes' => [
                17 => 7,
                54 => 34,
                62 => 19,
            ],
            'ladders' => [
                3  => 22,
                20 => 38,
                57 => 76,
            ],
            'bonus_tiles' => [
                30 => ['type' => 'extra_roll',  'msg' => 'Extra roll! The winds of fate smile upon you.'],
                70 => ['type' => 'skip',         'msg' => 'A heavy fog descends — skip a turn.'],
                85 => ['type' => 'mystery',      'msg' => 'Mystery tile! Something unexpected happens...'],
            ],
            'event_cells' => [
                15 => ['type' => 'bonus',   'move' => +5,  'msg' => 'You discover a hidden shortcut!'],
                42 => ['type' => 'penalty', 'move' => -4,  'msg' => 'Quicksand drags at your boots...'],
                67 => ['type' => 'warp',    'move' => +8,  'msg' => 'A temporal rift whisks you forward!'],
            ],
        ],

        // ── STANDARD: 6 snakes, 5 ladders ─────────────────────────────
        'standard' => [
            'snakes' => [
                17 => 7,
                54 => 34,
                62 => 19,
                64 => 60,
                87 => 24,
                93 => 73,
            ],
            'ladders' => [
                3  => 22,
                8  => 30,
                20 => 38,
                57 => 76,
                78 => 98,
            ],
            'bonus_tiles' => [
                30 => ['type' => 'extra_roll',  'msg' => 'Extra roll granted!'],
                50 => ['type' => 'skip',         'msg' => 'A troll demands tribute — skip a turn.'],
                70 => ['type' => 'skip',         'msg' => 'You stop to rest — skip a turn.'],
                85 => ['type' => 'mystery',      'msg' => 'The Oracle speaks a mystery…'],
            ],
            'event_cells' => [
                15 => ['type' => 'bonus',   'move' => +5,  'msg' => 'You find a hidden path!'],
                27 => ['type' => 'warp',    'move' => +6,  'msg' => 'A gust of wind carries you forward!'],
                42 => ['type' => 'penalty', 'move' => -6,  'msg' => 'The bridge collapses! You fall back.'],
                67 => ['type' => 'bonus',   'move' => +4,  'msg' => 'A friendly wizard gives you a boost!'],
                80 => ['type' => 'penalty', 'move' => -8,  'msg' => 'A rockslide pushes you back!'],
            ],
        ],

        // ── EXPERT: 9 snakes, 4 ladders ───────────────────────────────
        'expert' => [
            'snakes' => [
                17 => 7,
                54 => 34,
                62 => 19,
                64 => 60,
                87 => 24,
                93 => 73,
                49 => 11,
                69 => 33,
                99 => 78,
            ],
            'ladders' => [
                3  => 22,
                8  => 30,
                20 => 38,
                78 => 98,
            ],
            'bonus_tiles' => [
                25 => ['type' => 'extra_roll',  'msg' => 'Fortune favors the bold — extra roll!'],
                50 => ['type' => 'skip',         'msg' => 'A Banshee screams — skip a turn!'],
                75 => ['type' => 'mystery',      'msg' => 'The cursed die appears…'],
            ],
            'event_cells' => [
                10 => ['type' => 'bonus',   'move' => +5,  'msg' => 'Ancient ruins reveal a shortcut!'],
                22 => ['type' => 'penalty', 'move' => -7,  'msg' => 'A swamp pulls you under...'],
                37 => ['type' => 'warp',    'move' => +10, 'msg' => 'A dragon breathes you forward!'],
                55 => ['type' => 'penalty', 'move' => -10, 'msg' => 'Betrayed by a false ally...'],
                72 => ['type' => 'bonus',   'move' => +6,  'msg' => 'Star alignment boosts your quest!'],
                88 => ['type' => 'penalty', 'move' => -12, 'msg' => 'The abyss almost claims you!'],
            ],
        ],
    ];

    return $configs[$difficulty] ?? $configs['standard'];
}

// ── Helper used in templates ───────────────────────────────────────────
function getDieFace(int $roll): string {
    return ['', '1', '2', '3', '4', '5', '6'][$roll] ?? '[dice]';
}

function getEventIcon(string $type): string {
    return match($type) {
        'bonus'      => '[*]',
        'penalty'    => '[x]',
        'warp'       => '[~]',
        'skip'       => '[skip]',
        'extra_roll' => '[+]',
        'mystery'    => '[?]',
        'snake'      => '[snake]',
        'ladder'     => '[ladder]',
        default      => '[.]',
    };
}

function getProbClass(int $cell): string {
    // For coloring the probability bars based on what the cell is
    global $_SESSION;
    if (isset($_SESSION['snakes'][$cell]))  return 'prob-snake';
    if (isset($_SESSION['ladders'][$cell])) return 'prob-ladder';
    if (isset($_SESSION['event_cells'][$cell])) return 'prob-event';
    if ($cell >= 100) return 'prob-win';
    return 'prob-normal';
}