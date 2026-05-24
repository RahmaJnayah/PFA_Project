# Game Weights For Analysis

This document explains the default per-game weight used by the behavior analyzer. These weights scale how much each session contributes to the final child analysis. They are based on how directly the game exposes reaction timing, pacing changes, and error/success rhythm that reflect engagement and stress.

You can override any weight without changing this file by creating:
- ai/analysis/analysis_config.override.php

Example override:
```php
<?php
return [
    'game_weights' => [
        'memory_game' => 1.3,
        'tic_tac_toe' => 1.0,
    ],
];
```

## Default Weights

| Game slug | Weight | Rationale |
| --- | --- | --- |
| memory_game | 1.2 | High signal density from reaction timing and match errors. |
| simon_says | 1.1 | Sequence recall and timing show focus and recovery. |
| snake_retro | 1.2 | Fast pacing with continuous input reflects stress and flow. |
| whack_a_mole | 1.2 | Reaction-speed game with clear error and pace spikes. |
| tower_blocks | 1.15 | Timing pressure and rhythm are strong engagement signals. |
| canon_defender | 1.15 | Target reaction and misses show stress/flow changes. |
| math_sprint | 1.1 | Speeded answers show focus and frustration patterns. |
| maze_runner | 1.0 | Moderate signal quality; pacing less spiky. |
| tile_puzzle | 0.95 | Slower puzzle play, fewer high-frequency signals. |
| image_puzzle | 0.95 | Similar to tile_puzzle, lower temporal density. |
| sudoku | 0.9 | Low reaction signals; mostly cognitive and slow. |
| sudoku_pro | 0.9 | Same as sudoku, low temporal signal density. |
| tic_tac_toe | 0.9 | Turn-based pacing, weaker timing signals. |
| tetris | 1.0 | Continuous play with moderate timing signals. |
| tetris_block | 1.0 | Same as tetris; continuous flow signals. |
| word_scramble | 0.95 | Moderate cognitive load, fewer rapid events. |
| spelling_bee | 0.95 | Audio-to-spell timing is useful but slow. |
| hangman_quest | 0.95 | Guessing cadence is slower and less dense. |
| synonym_challenge | 0.95 | Fast answers can help, but event density varies. |
| cut_the_rope | 1.0 | Physics puzzle with moderate timing feedback. |

## Notes

- New games default to weight 1.0 if not listed.
- Use higher weights for games that emit frequent reaction or pace signals.
- Use lower weights for turn-based or slow puzzle games.
