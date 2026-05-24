# YOPY — Game Architecture

## 1. Overview

The **Game Architecture** defines how educational games are integrated, executed, and monitored within the YOPY platform.

Games represent the **core interactive component** of the system. They allow children to:

* engage with educational content
* develop cognitive skills
* receive performance feedback
* generate measurable activity data for parents

The game system is designed to be **modular**, allowing new games to be added without modifying the entire platform.

The architecture integrates three layers:

* **User Interface Layer** — rendering and interaction
* **Game Logic Layer** — gameplay mechanics
* **Persistence Layer** — storing session and score data

---

# 2. Game System Structure

The game subsystem is structured as follows:

```text
Child Interface
      │
Game Menu
      │
Game Loader
      │
Game Engine
      │
Score Engine
      │
Database Storage
```

Each component performs a specific responsibility in the execution lifecycle of a game.

---

# 3. Game Interface Layer

The **Game Interface Layer** handles all visual interactions between the child and the game.

This layer is implemented using:

* HTML
* CSS
* JavaScript
* Bootstrap

Responsibilities include:

* rendering the game interface
* handling user input
* displaying animations
* updating scores visually

Example interaction flow:

```text
Child selects game
       │
Game interface loads
       │
Child interacts with game elements
       │
Visual feedback displayed
```

---

# 4. Game Loader

The **Game Loader** is responsible for initializing the selected game.

Tasks performed:

* retrieving game configuration
* loading required assets
* initializing the game engine

Example loading sequence:

```text
Game selected
      │
Load game configuration
      │
Load assets (images / sounds)
      │
Start game engine
```

---

# 5. Game Engine

The **Game Engine** contains the core gameplay logic.

Responsibilities include:

* rule enforcement
* event processing
* game state management
* scoring triggers

Each game implements its own logic while respecting the system interface.

Example internal structure:

```text
Game Engine
   ├── Input Handler
   ├── Game State Manager
   ├── Event Processor
   └── Score Trigger
```

The engine ensures that all games behave consistently within the platform.

---

# 6. Game State Management

Game states define the lifecycle of a game session.

Typical states include:

```text
INITIALIZED
     │
STARTED
     │
PLAYING
     │
COMPLETED
     │
RESULT STORED
```

State transitions are controlled by the game engine.

---

# 7. Score Engine

The **Score Engine** calculates the performance of the child during gameplay.

Scores may depend on:

* number of correct actions
* time taken
* difficulty level
* completion success

Example scoring logic:

```text
score = correct_actions × difficulty_multiplier
```

The calculated score is then stored in the system database.

---

# 8. Game Session Tracking

Each gameplay instance creates a **Game Session**.

Session information includes:

* child identifier
* game identifier
* start time
* end time
* resulting score

Session flow:

```text
Game starts
      │
Session created
      │
Game played
      │
Score calculated
      │
Session stored
```

Game sessions allow parents to monitor activity and performance.

---

# 9. Game Categories

The system can support multiple educational game types.

Examples include:

### Memory Games

Improve short-term memory and pattern recognition.

Examples:

* card matching
* sequence repetition

---

### Logic Games

Develop reasoning and problem-solving abilities.

Examples:

* puzzles
* pattern completion

---

### Mathematics Games

Improve arithmetic skills.

Examples:

* addition challenges
* number puzzles

---

# 10. Game Asset Management

Games may require assets such as:

* images
* icons
* audio feedback

Assets are stored in the project structure:

```text
/assets
   ├── images
   ├── icons
   └── sounds
```

Assets are loaded dynamically during game initialization.

---

# 11. Integration With Parent Dashboard

Game results are used by the **parent monitoring system**.

Information available to parents includes:

* number of games played
* average score
* most played games
* progress trends

Example data flow:

```text
Child plays game
      │
Game session stored
      │
Score recorded
      │
Parent dashboard retrieves statistics
```

---

# 12. Adding New Games

The architecture allows developers to easily add new games.

Typical steps:

1. create new game folder
2. implement game interface
3. implement game logic
4. integrate scoring system
5. register game in database

Example directory:

```text
/games
   ├── memory_game
   ├── puzzle_game
   └── math_game
```

---

# 13. Performance Considerations

To maintain smooth gameplay:

* minimize server requests during gameplay
* handle most interactions on the client side
* store results only when a game finishes

This reduces latency and improves responsiveness.

---

# 14. Security Considerations

Game inputs must be validated to prevent manipulation.

Potential risks include:

* score tampering
* session manipulation
* client-side modification

Mitigation strategies:

* validate scores on server
* store session timestamps
* limit acceptable score ranges

---

# 15. Architecture Summary

The YOPY game system is designed to provide:

* modular game integration
* consistent gameplay lifecycle
* reliable score tracking
* parent monitoring capabilities

The architecture ensures that the platform can **grow with new educational games while maintaining stability and security**.
