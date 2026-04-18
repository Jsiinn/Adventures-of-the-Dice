<?php
// narrator.php — Story-language messages for all game events

function playerName(int $p): string {
    return $p === 1 ? 'You' : 'The AI';
}

function narratorNormal(int $p, int $roll, int $from, int $to): string {
    $name = playerName($p);
    $phrases = [
        "{$name} roll{$p===1?'':'s'} a {$roll} and advance{$p===1?'':'s'} from cell {$from} to cell {$to}.",
        "A {$roll} is cast! {$name} move{$p===1?'':'s'} steadily from {$from} to {$to}.",
        "The dice speaks: {$roll}. {$name} step{$p===1?'':'s'} forward to cell {$to}.",
        "With a roll of {$roll}, {$name} journey{$p===1?'':'s'} from {$from} onward to {$to}.",
    ];
    srand(crc32($p . $roll . $from));
    $result = $phrases[array_rand($phrases)];
    srand();
    return $result;
}

function narratorSnake(int $p, int $roll, int $from, int $head, int $tail): string {
    $name = playerName($p);
    $phrases = [
        "[snake] The serpent hisses! {$name} land{$p===1?'':'s'} on cell {$head} — dragged down to cell {$tail}!",
        "[snake] A great snake coils around {$name}! Down from cell {$head} to the depths of {$tail}!",
        "[snake] Fangs strike at cell {$head}! {$name} slide{$p===1?'':'s'} helplessly to cell {$tail}...",
        "[snake] The serpent king claims its toll — {$name} plummet{$p===1?'':'s'} from {$head} to {$tail}!",
    ];
    srand(crc32($p . $head . $tail));
    $result = $phrases[array_rand($phrases)];
    srand();
    return $result;
}

function narratorLadder(int $p, int $roll, int $from, int $base, int $top): string {
    $name = playerName($p);
    $phrases = [
        "[ladder] Fortune smiles! {$name} find{$p===1?'':'s'} a ladder at cell {$base} — soaring up to cell {$top}!",
        "[ladder] A magical ladder appears at cell {$base}! {$name} rocket{$p===1?'':'s'} up to {$top}!",
        "[ladder] The ladder of destiny beckons at {$base}! {$name} climb{$p===1?'':'s'} swiftly to cell {$top}!",
        "[ladder] At cell {$base}, a shimmering ladder! {$name} ascend{$p===1?'':'s'} triumphantly to {$top}!",
    ];
    srand(crc32($p . $base . $top));
    $result = $phrases[array_rand($phrases)];
    srand();
    return $result;
}

function narratorBonus(int $p, array $tile): string {
    $name = playerName($p);
    return match($tile['type']) {
        'extra_roll' => "[+] {$name} trigger{$p===1?'':'s'} a bonus tile — {$tile['msg']} An extra roll awaits!",
        'skip'       => "[skip] {$tile['msg']} The opposing adventurer must wait a turn...",
        'mystery'    => "[?] {$tile['msg']} The fates whisper something strange...",
        default      => "[*] {$tile['msg']}",
    };
}

function narratorEvent(int $p, array $event, int $new_pos, int $variation): string {
    $name = playerName($p);
    $direction = $event['move'] > 0 ? 'forward' : 'back';
    $abs_move  = abs($event['move']);

    $templates = [
        'bonus' => [
            "{$name} trigger{$p===1?'':'s'} a wondrous event! {$event['msg']} Rushing {$direction} {$abs_move} cells to {$new_pos}!",
            "[*] {$event['msg']} {$name} leap{$p===1?'':'s'} {$direction} to cell {$new_pos}!",
        ],
        'penalty' => [
            "[x] {$event['msg']} {$name} stumble{$p===1?'':'s'} {$direction} to cell {$new_pos}...",
            "{$name} face{$p===1?'':'s'} misfortune! {$event['msg']} Sliding {$direction} to cell {$new_pos}.",
        ],
        'warp' => [
            "[~] {$event['msg']} Reality bends — {$name} warp{$p===1?'':'s'} to cell {$new_pos}!",
            "[~] A rift in the fabric of the board! {$event['msg']} {$name} arrive{$p===1?'':'s'} at cell {$new_pos}!",
        ],
    ];

    $type_templates = $templates[$event['type']] ?? $templates['bonus'];
    return $type_templates[$variation % count($type_templates)];
}

function narratorSkip(int $p): string {
    $name = playerName($p);
    $phrases = [
        "[skip] {$name} must rest this turn — the curse holds firm.",
        "[skip] {$name} cannot act! The hex binds {$p===1?'your':'its'} feet for this round.",
        "[skip] A forced pause — {$name} skip{$p===1?'':'s'} this turn.",
    ];
    srand(crc32((string)$p . (string)time()));
    $result = $phrases[array_rand($phrases)];
    srand();
    return $result;
}

function narratorBounce(int $p, int $roll, int $from): string {
    $name = playerName($p);
    return "[!] {$name} roll{$p===1?'':'s'} a {$roll} from cell {$from}, but that overshoots 100! " .
           "{$name} must wait for the exact number to claim victory.";
}

function narratorWin(int $p): string {
    if ($p === 1) {
        return "[!] VICTORY! You have reached cell 100 and conquered the board! The dice gods bow before you!";
    } else {
        return "[AI] The AI has reached cell 100. Its algorithmic mind claims victory this time... but the board awaits your revenge.";
    }
}