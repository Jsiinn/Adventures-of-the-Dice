# Snakes and Ladders

A fully playable digital version of Snakes and Ladders built with PHP, HTML, and CSS. No JavaScript. No database. All game logic, authentication, and data storage is handled entirely server-side.

Built by **John Gomez & Jason Lopez**.

---

## What It Is

This is a browser-based Snakes and Ladders game with two game modes, three difficulty levels, an AI opponent, special event tiles, a probability map, a leaderboard, and a post-game recap. Everything runs on PHP sessions and flat file storage.

---

## Features

- User registration and login with bcrypt password hashing
- Session-based authentication with protected pages
- Game lobby with difficulty and game mode selection
- 100-cell board generated dynamically with classic alternating row numbering
- Server-side dice roll engine using PHP rand()
- Three difficulty levels with different snake and ladder layouts
- Two-player mode and versus AI mode
- Special event tiles: bonus, penalty, skip turn, and warp
- AI narrator with story-driven messages on event tile landings
- AI opponent using a greedy strategy
- Probability map overlay showing landing chances from current position
- Persistent leaderboard stored in a flat text file
- Adventure Recap showing all events that occurred during the game
- Post-game path analysis comparing your actual path to the optimal path
- Vintage board game CSS theme

---

## File Structure

```
/snakes-and-ladders/
  index.php          # Main game board and logic
  login.php          # Login page
  register.php       # Registration page
  logout.php         # Destroys session and redirects to login
  lobby.php          # Difficulty and game mode selection
  leaderboard.php    # Leaderboard display
  recap.php          # Adventure recap and path analysis
  users.txt          # Flat file user storage (auto created on first register)
  leaderboard.txt    # Flat file leaderboard storage (auto created on first win)
  css/
    styles.css       # All styles
```

---

## Requirements

- PHP 7.4 or higher
- A local server such as XAMPP, MAMP, or WAMP
- A modern browser
- Internet connection for Google Fonts (degrades gracefully without it)

---

## Setup

1. Clone or download the project into your server's web root directory. For XAMPP this would be `htdocs`, for MAMP it would be `htdocs` as well.

2. Create two empty files in the project root if they do not already exist:
   ```
   users.txt
   leaderboard.txt
   ```

3. Make sure both files are writable by the server. On Linux or Mac you can run:
   ```
   chmod 664 users.txt leaderboard.txt
   ```

4. Start your local server and visit:
   ```
   http://localhost/snakes-and-ladders/
   ```

5. Register an account and start playing.

---

## How to Play

1. Register and log in
2. On the lobby screen, choose a game mode and difficulty level
3. Click Start Game
4. Click Roll Dice to take your turn
5. Land on a snake head and you slide down, land on a ladder base and you climb up
6. Land on a special event tile for bonus moves, penalties, or a skipped turn
7. First player to reach cell 100 wins
8. After the game, view the Adventure Recap and Path Analysis to see how you did

---

## Difficulty Levels

| Difficulty | Snakes | Ladders |
|------------|--------|---------|
| Beginner   | 3      | 3       |
| Standard   | 6      | 5       |
| Expert     | 9      | 4       |

---

## Game Modes

**2 Player** — Both players share the same screen and take turns rolling.

**vs AI** — You play against a PHP-driven AI opponent that uses a greedy strategy, always picking the roll that lands on the highest safe cell while avoiding snake heads.

---

## Event Tiles

| Symbol | Type    | Effect                  |
|--------|---------|-------------------------|
| star   | Bonus   | Move forward extra cells |
| burst  | Penalty | Move back a few cells   |
| pause  | Skip    | Lose your next turn     |
| swirl  | Warp    | Jump ahead further      |

---

## Data Storage

No database is used. All data is stored in plain text files.

- `users.txt` stores registered users as `username:hashed_password` on each line
- `leaderboard.txt` stores completed games as `winner|difficulty|turns|date` on each line

Both files are created automatically the first time a user registers or a game is completed, as long as the directory is writable.

---

## Tech Stack

- **PHP** for all game logic, authentication, session management, AI, and file storage
- **HTML** for page structure and forms
- **CSS** with Google Fonts (Playfair Display and Lora) for styling
- **No JavaScript**
- **No database**

---

## Known Limitations

- Two players share the same screen and device, there is no network multiplayer
- The leaderboard is stored locally and does not persist across different servers or deployments
- The AI opponent uses a simple greedy strategy and does not adapt to the human player's position

---

## Authors

John Gomez & Jason Lopez
