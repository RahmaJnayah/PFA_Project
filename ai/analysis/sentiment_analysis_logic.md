# Behavioral Sentiment Analysis Logic

This document describes the non-ML, rule-based behavior analysis used to infer a "behavioral mood indicator" from gameplay data. The goal is to translate observable play patterns into four score buckets: Focus, Frustration, Boredom, Joy, then choose a dominant state with a confidence score.

The system uses two data sources:
- raw_signals: event-level data (reaction, success, error, pace_change, etc.)
- signals: summary metrics (latency_avg, error_rate, session_duration, event_count)

Raw events are prioritized for timing logic. Summary data is used as a fallback and is down-weighted to reflect lower fidelity.

---

## 1) Age-Based Calibration

A child's latency must be interpreted relative to age. We define a baseline latency Lb for each age group and (for some games) per game.

Age groups:
- 4-6
- 7-9
- 10-12

Example baselines (ms):
- Memory Match: 2500 / 1600 / 1000
- Snake Retro: 1200 / 800 / 500
- Default for future games: 2000 / 1300 / 900

Baseline error rate Eb (max expected):
- 4-6: 0.20
- 7-9: 0.12
- 10-12: 0.06

Performance ratio used in logic:
R = Observed Latency / Lb

---

## 2) Emotional Buckets

Each session starts with zero:
- Focus_Points
- Frustration_Points
- Boredom_Points
- Joy_Points

---

## 3) Event-Triggered Rules

These rules are applied per event in time order. Points are added as triggers occur.

Focus and Flow:
- Rhythmic Rule: 3 consecutive success events with latency variance < 15% of Lb
  + Focus += 3
- Quick Recovery: an error followed by a success within 1.2 * Lb
  + Focus += 2

Frustration and Stress:
- Spam Detector: 3+ reaction signals within 500 ms
  + Frustration += 5
- Error Spiral: 2 errors within 3 seconds
  + Frustration += 4

Boredom and Disengagement:
- Sluggish Rule: R > 2.0 for 3 consecutive reaction events
  + Boredom += 3
- Long Pause: no signal for > 10 seconds while active
  + Boredom += 5

Joy and Competence:
- Streak: 5+ success signals with no errors
  + Joy += 2

Memory Match Special Handling:
- The first two errors are ignored.
- The "same non-matching pair twice" frustration rule only applies if pair identifiers exist in raw_signals.

---

## 4) Temporal Decay

Emotions are transient. Every 30 seconds of play, all current scores are multiplied by 0.8.
This ensures a recent shift in behavior can overtake earlier frustration or boredom.

---

## 5) Session Weighting and Data Quality

Each session score is weighted by:
- Game weight (relevance to emotional signals)
- Data quality factor

Data quality weights:
- raw_signals: 1.0
- summary_only: 0.6
- no_data: 0.0

Future games that send signals but lack event data still contribute, but less.

---

## 6) Period Aggregation

We analyze a time window (default: last 1 day) and require at least 2 sessions with usable data.
All weighted session scores are summed into a single period score.

---

## 7) Historical Context

The current period is merged with the last two analyses so recent past still influences the state.
Weights:
- Most recent analysis: 0.10
- Previous analysis: 0.03

These weights decay by time (half-life style over 7 days).

---

## 8) Final Interpretation

1. Find the highest bucket score.
2. Confidence = (highest / total) * 100
3. If confidence < 40 or scores are nearly equal, return Neutral / Evaluating.
4. Mapping:
- Frustration (confidence > 40): Agitated / Angry
- Focus (confidence > 50): High Engagement
- Boredom: Disengaged / Sad
- Joy: Happy / Confident

---

# Game Weights and Rationale

Weights reflect how directly a game produces emotional signals (reaction speed, pacing, error bursts). Fast, reactive games have higher weights. Slow or turn-based puzzles have lower weights.

Default weight for future games: 1.0

| Game slug | Weight | Interpretation of weight choice |
| --- | --- | --- |
| memory_game | 1.2 | Frequent reaction timing and match errors reflect attention and frustration clearly. |
| simon_says | 1.1 | Sequence recall shows focus and recovery, but less frequent than reaction games. |
| snake_retro | 1.2 | Continuous reaction and pace shifts capture stress/flow well. |
| whack_a_mole | 1.2 | Pure reaction game with strong spam and latency signals. |
| tower_blocks | 1.15 | Timing pressure with rhythmic success/fail moments. |
| canon_defender | 1.15 | Target hits/misses provide clear stress and pace changes. |
| math_sprint | 1.1 | Timed answers show frustration vs focus, moderate signal density. |
| maze_runner | 1.0 | Mid-level pacing and fewer rapid events. |
| tile_puzzle | 0.95 | Slow, thoughtful play; fewer time-sensitive signals. |
| image_puzzle | 0.95 | Similar to tile_puzzle, lower event density. |
| sudoku | 0.9 | Turn-based and slow; low reaction timing signal. |
| sudoku_pro | 0.9 | Same as sudoku; low event frequency. |
| tic_tac_toe | 0.9 | Turn-based; low continuous signal. |
| tetris | 1.0 | Continuous play but not always event-dense. |
| tetris_block | 1.0 | Same as tetris. |
| word_scramble | 0.95 | Moderate cognitive load, fewer rapid events. |
| spelling_bee | 0.95 | Audio-to-answer is useful but not high frequency. |
| hangman_quest | 0.95 | Guessing cadence is slower and less reactive. |
| synonym_challenge | 0.95 | Event density varies; moderate relevance. |
| cut_the_rope | 1.0 | Physics puzzle with timing, moderate signal quality. |

---

# Notes

- This system is a behavioral proxy. It measures actions, not internal emotional states.
- It should be presented as a behavioral mood indicator, not a clinical diagnosis.
- Touchscreen latency is not adjusted yet. If touchscreen use is common, consider adding +15% to baselines.

---

# AI Algorithm Choice

This system uses a deterministic, rule-based behavioral scoring engine rather than a machine-learning model. The algorithm is a multi-tiered scoring framework that maps observable play behavior into four emotional buckets (Focus, Frustration, Boredom, Joy) and then derives a dominant state and confidence score.

Why this choice:
- The project requires interpretability for parents and admins.
- The available data is event-level gameplay telemetry, not labeled emotion data.
- The system must work offline and without ML training pipelines.
- Rules can be tuned per game and per age group without retraining.

Core algorithm summary:
- Age-calibrated baselines for latency and error rate.
- Rule triggers from raw_signals (event stream) with temporal decay.
- Per-game weighting to reflect signal quality differences.
- Confidence derived from bucket dominance.

This is not a clinical diagnosis. It is a behavioral mood indicator grounded in measurable interactions.

---

# Technical Integration Details

## Data Flow
1. Games send behavioral telemetry to the backend (signals and raw_signals).
2. Data is stored in game_behaviors and game_events.
3. BehaviorAnalyzer parses sessions and applies the rule engine.
4. Results are stored in child_behavior_analysis or served live via the parent API.

## Components
- Analysis Engine: ai/analysis/BehaviorAnalyzer.php
  - analyzeSessionRecord(): computes per-session scores and state.
  - analyzeChildPeriod(): aggregates sessions into a period analysis.
  - storeAnalysis(): upserts the analysis into child_behavior_analysis.

- Parent API: api/parent_analysis_api.php
  - Validates parent session.
  - Accepts start/end range.
  - Returns children + per-session analysis results.

- Parent UI: views/parent/dashboard/unicef_indexation/njareb
  - app.js fetches live analysis and renders charts/insights.
  - index.php contains placeholders and range controls.
  - style.css styles the new dynamic components.

## Storage
- child_behavior_analysis: stores period summaries (scores, dominant state, confidence, details).
- game_behaviors + game_events: store raw and aggregated game telemetry.

## Runtime Behavior
- Admin can run analysis manually or via scheduled CLI job.
- Parent dashboard pulls analysis dynamically by date range.
- For real-time trends, sessions are analyzed per session and plotted per child.

## Error Handling and Fallbacks
- If raw_signals are missing, summary signals are used with reduced weight.
- If no valid signals exist, the analysis returns Neutral / Evaluating with 0 confidence.
- Memory Match has special handling for error logic when pair metadata is missing.
