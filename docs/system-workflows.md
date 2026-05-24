# YOPY — System Workflows

## 1. Overview

This document describes the **main operational workflows of the YOPY platform**.
Workflows represent the sequence of actions performed by users and system components during typical interactions.

The objective is to clarify:

* how users interact with the system
* how requests are processed internally
* how data flows between modules
* how results are stored and displayed

The main workflows include:

1. User authentication
2. Child game session
3. Score recording
4. Parent dashboard monitoring
5. Administrative management

---

# 2. User Authentication Workflow

The authentication workflow allows users to securely access the platform.

### Process Description

1. User opens the login page.
2. Credentials are entered.
3. The server validates the information.
4. A secure session is created.
5. The user is redirected based on their role.

### Workflow Diagram

```text
User
 │
 │ enter credentials
 ▼
Login Interface
 │
 │ submit form
 ▼
Authentication Controller
 │
 │ verify user credentials
 ▼
Database Query
 │
 │ valid credentials?
 ├── No → Access denied
 │
 └── Yes
       │
       ▼
Session Created
       │
       ▼
Redirect Based on Role
```

---

# 3. Child Game Session Workflow

This workflow describes how a child interacts with the platform to play a game.

### Process Description

1. Child logs into the system.
2. Character selection screen appears.
3. Child chooses a character.
4. Game menu is displayed.
5. Child selects a game.
6. The game engine starts the session.

### Workflow Diagram

```text
Child Login
     │
     ▼
Character Selection
     │
     ▼
Game Menu
     │
     ▼
Select Game
     │
     ▼
Game Loader
     │
     ▼
Game Engine Starts
```

---

# 4. Gameplay Interaction Workflow

During gameplay, the child interacts with game elements while the system tracks progress.

### Process Description

1. Game engine initializes.
2. Child performs actions.
3. Game rules evaluate actions.
4. Score variables are updated.

### Workflow Diagram

```text
Game Engine Start
        │
        ▼
Game State Initialized
        │
        ▼
Child Interaction
        │
        ▼
Game Logic Evaluation
        │
        ▼
Score Updated
```

---

# 5. Score Recording Workflow

At the end of the game, the system calculates and stores the score.

### Process Description

1. Game ends.
2. Score engine calculates results.
3. Session information is collected.
4. Score and session are stored in the database.

### Workflow Diagram

```text
Game Completed
       │
       ▼
Score Engine Calculates Score
       │
       ▼
Create Game Session Record
       │
       ▼
Store Score in Database
       │
       ▼
Display Result to Child
```

---

# 6. Parent Monitoring Workflow

Parents can review the activity and progress of their children.

### Process Description

1. Parent logs into the system.
2. Parent dashboard loads.
3. System retrieves child activity.
4. Statistics are generated.
5. Dashboard displays performance indicators.

### Workflow Diagram

```text
Parent Login
      │
      ▼
Parent Dashboard
      │
      ▼
Retrieve Child Sessions
      │
      ▼
Retrieve Scores
      │
      ▼
Generate Statistics
      │
      ▼
Display Progress Data
```

---

# 7. Child Account Management Workflow

Parents can manage child accounts.

### Process Description

1. Parent opens account management.
2. Parent adds or edits a child profile.
3. System validates the input.
4. Data is stored in the database.

### Workflow Diagram

```text
Parent Dashboard
       │
       ▼
Manage Children
       │
       ▼
Add / Edit Child
       │
       ▼
Validate Input
       │
       ▼
Store Child Data
```

---

# 8. Administrative Management Workflow

Administrators manage system resources and user accounts.

### Process Description

1. Admin logs into the platform.
2. Admin accesses the administration panel.
3. Admin manages users or games.
4. System updates the database.

### Workflow Diagram

```text
Admin Login
      │
      ▼
Admin Dashboard
      │
      ▼
Select Management Task
      │
      ├── Manage Users
      └── Manage Games
            │
            ▼
Update Database
```

---

# 9. Data Flow Summary

The system workflows involve several components interacting together.

```text
User Interface
      │
      ▼
Controller Layer
      │
      ▼
Business Logic
      │
      ▼
Database Access
      │
      ▼
Data Storage
```

Each user request follows this structured path.

---

# 10. Workflow Benefits

Documenting workflows provides several advantages:

* clarifies system behavior
* simplifies debugging
* improves system maintenance
* helps developers understand interactions

These workflows represent the **operational backbone of the YOPY platform**, ensuring that user actions, gameplay interactions, and monitoring operations are processed in a structured and reliable manner.
