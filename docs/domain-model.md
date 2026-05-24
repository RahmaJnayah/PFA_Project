# YOPY — Domain Model

## 1. Domain Model Overview

The domain model describes the **core entities and relationships** that represent the functional structure of the YOPY platform.

The platform revolves around **children playing educational games**, while **parents monitor progress** through dashboards and **administrators manage the system**.

The domain model organizes these responsibilities into several core domains:

* User Management
* Child Interaction
* Game System
* Score Tracking
* Parent Monitoring

---

# 2. Core Domain Entities

The main entities of the system are shown below.

```text
User
 ├── Parent
 ├── Child
 └── Admin
```

```text
Child
 ├── Character
 ├── GameSession
 └── Score
```

```text
Game
 └── GameSession
       └── Score
```

---

# 3. User Domain

The **User** entity represents any authenticated person interacting with the platform.

### Attributes

| Attribute     | Type    | Description          |
| ------------- | ------- | -------------------- |
| id            | integer | unique identifier    |
| username      | string  | login username       |
| email         | string  | user email           |
| password_hash | string  | hashed password      |
| role          | enum    | parent, child, admin |

---

## Parent

Parents supervise children and access analytical dashboards.

### Responsibilities

* monitor child progress
* view game history
* manage child accounts
* configure settings

### Attributes

| Attribute | Type    |
| --------- | ------- |
| parent_id | integer |
| user_id   | integer |

---

## Child

Children interact with the platform through games.

### Attributes

| Attribute | Type    |
| --------- | ------- |
| child_id  | integer |
| parent_id | integer |
| nickname  | string  |
| age       | integer |
| avatar    | string  |

---

## Admin

Administrators manage the platform.

### Responsibilities

* manage users
* manage games
* monitor system activity
* generate reports

---

# 4. Character Domain

Each child selects a character or avatar used during gameplay.

### Entity: Character

| Attribute    | Type    |
| ------------ | ------- |
| character_id | integer |
| name         | string  |
| avatar_image | string  |

Relationship:

```text
Child
  │
  └── selects → Character
```

---

# 5. Game Domain

The **Game** entity represents a playable activity.

### Entity: Game

| Attribute   | Type    |
| ----------- | ------- |
| game_id     | integer |
| name        | string  |
| category    | string  |
| difficulty  | string  |
| description | text    |

Example games:

* Memory Game
* Math Game
* Puzzle Game

---

# 6. Game Session Domain

A **GameSession** represents a single play session.

### Entity: GameSession

| Attribute  | Type     |
| ---------- | -------- |
| session_id | integer  |
| child_id   | integer  |
| game_id    | integer  |
| start_time | datetime |
| end_time   | datetime |

Relationship:

```text
Child
  │
  └── plays → GameSession
                │
                └── belongs to → Game
```

---

# 7. Score Domain

Each game session produces a score.

### Entity: Score

| Attribute       | Type     |
| --------------- | -------- |
| score_id        | integer  |
| session_id      | integer  |
| points          | integer  |
| completion_time | integer  |
| created_at      | datetime |

Relationship:

```text
GameSession
      │
      └── generates → Score
```

---

# 8. Parent Monitoring Domain

Parents analyze their child’s activity through aggregated data.

### Derived Data

Examples of computed data:

* total games played
* average score
* most played game
* progress trends

These values are computed from:

```text
Child
   │
   └── GameSession
           │
           └── Score
```

---

# 9. Entity Relationship Overview

Complete simplified relationship model:

```text
User
 ├── Parent
 │      │
 │      └── manages → Child
 │
 ├── Child
 │      │
 │      ├── selects → Character
 │      │
 │      └── plays → GameSession
 │                     │
 │                     ├── belongs to → Game
 │                     │
 │                     └── generates → Score
 │
 └── Admin
        │
        └── manages → Users / Games
```

---

# 10. Database Mapping

Example relational tables derived from the domain model.

```text
users
parents
children
characters
games
game_sessions
scores
```

Example relationship:

```text
children.parent_id → parents.parent_id
game_sessions.child_id → children.child_id
scores.session_id → game_sessions.session_id
```

---

# 11. Domain Constraints

Important constraints in the system include:

* a parent can manage multiple children
* a child belongs to exactly one parent
* each game session belongs to one child
* each game session produces one score

---

# 12. Domain Model Benefits

The domain model provides:

* clear representation of system entities
* well-defined relationships
* consistent data organization
* simplified database schema design

This model serves as the foundation for the platform's database structure and application logic.
