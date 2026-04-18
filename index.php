<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

//Board Setup
$snakes = [
    17 => 7,
    54 => 34,
    62 => 19
];

$ladders = [
    3  => 22,
    20 => 41,
    57 => 76
];

//Player Position
if (!isset($_SESSION['position'])) {
    $_SESSION['position'] = 0;
}

//Dice Roll
$roll = null;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roll'])) {
    $roll = rand(1, 6);
    $pos = $_SESSION['position'] + $roll;

    if ($pos >= 100) {
        $pos = 100;
        $message = "🎉 You reached cell 100 — You Win!";
    } elseif (isset($snakes[$pos])) {
        $message = "🐍 Snake! Sliding down from $pos to {$snakes[$pos]}";
        $pos = $snakes[$pos];
    } elseif (isset($ladders[$pos])) {
        $message = "🪜 Ladder! Climbing up from $pos to {$ladders[$pos]}";
        $pos = $ladders[$pos];
    } else {
        $message = "🎲 You rolled a $roll and moved to cell $pos.";
    }

    $_SESSION['position'] = $pos;
}

//Reset 
if (isset($_POST['reset'])) {
    $_SESSION['position'] = 0;
    $message = "Game reset!";
}

$player_pos = $_SESSION['position'];