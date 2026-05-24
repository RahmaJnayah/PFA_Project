# YOPY — Database Schema

## 1. Overview

This document defines the **relational database structure** used by the YOPY platform.
The schema is designed to support:

* user authentication
* child account management
* game sessions
* score tracking
* parental monitoring
* administrative management

The database system used is **MySQL**.

The schema follows **normalized relational design principles** to ensure:

* data consistency
* minimal redundancy
* efficient querying

---

# 2. Entity Relationship Summary

The database is built around the following core entities:

```text
Users
 ├── Parents
 │      └── Children
 │             ├── Characters
 │             └── GameSessions
 │                     └── Scores
 │
 └── Admins

Games
```

---

# 3. Tables Overview

| Table         | Purpose                           |
| ------------- | --------------------------------- |
| users         | stores authentication information |
| parents       | parent user profiles              |
| children      | child accounts                    |
| characters    | avatars for children              |
| games         | list of available games           |
| game_sessions | records each game play            |
| scores        | stores score results              |

---

# 4. Users Table

The **users** table stores all platform accounts.

### Table: users

| Field         | Type         | Description            |
| ------------- | ------------ | ---------------------- |
| id            | INT (PK)     | unique user identifier |
| username      | VARCHAR(50)  | login name             |
| email         | VARCHAR(100) | user email             |
| password_hash | VARCHAR(255) | hashed password        |
| role          | ENUM         | parent, child, admin   |
| created_at    | TIMESTAMP    | account creation date  |

---

# 5. Parents Table

Stores information about parent accounts.

### Table: parents

| Field     | Type     | Description              |
| --------- | -------- | ------------------------ |
| parent_id | INT (PK) | parent identifier        |
| user_id   | INT (FK) | reference to users table |

Relationship:

```
parents.user_id → users.id
```

---

# 6. Children Table

Stores child profiles managed by parents.

### Table: children

| Field     | Type         | Description        |
| --------- | ------------ | ------------------ |
| child_id  | INT (PK)     | child identifier   |
| parent_id | INT (FK)     | associated parent  |
| nickname  | VARCHAR(50)  | child display name |
| age       | INT          | child age          |
| avatar    | VARCHAR(255) | selected avatar    |

Relationship:

```
children.parent_id → parents.parent_id
```

---

# 7. Characters Table

Defines available characters or avatars.

### Table: characters

| Field        | Type         | Description          |
| ------------ | ------------ | -------------------- |
| character_id | INT (PK)     | character identifier |
| name         | VARCHAR(50)  | character name       |
| avatar_image | VARCHAR(255) | image path           |

---

# 8. Games Table

Stores available games on the platform.

### Table: games

| Field       | Type         | Description            |
| ----------- | ------------ | ---------------------- |
| game_id     | INT (PK)     | unique game identifier |
| name        | VARCHAR(100) | game title             |
| category    | VARCHAR(50)  | type of game           |
| difficulty  | VARCHAR(20)  | difficulty level       |
| description | TEXT         | game description       |

---

# 9. Game Sessions Table

Records each instance of a child playing a game.

### Table: game_sessions

| Field      | Type     | Description               |
| ---------- | -------- | ------------------------- |
| session_id | INT (PK) | unique session identifier |
| child_id   | INT (FK) | child playing the game    |
| game_id    | INT (FK) | game played               |
| start_time | DATETIME | session start             |
| end_time   | DATETIME | session end               |

Relationships:

```
game_sessions.child_id → children.child_id
game_sessions.game_id → games.game_id
```

---

# 10. Scores Table

Stores performance results for each game session.

### Table: scores

| Field           | Type      | Description          |
| --------------- | --------- | -------------------- |
| score_id        | INT (PK)  | score identifier     |
| session_id      | INT (FK)  | related game session |
| points          | INT       | score value          |
| completion_time | INT       | duration in seconds  |
| created_at      | TIMESTAMP | record timestamp     |

Relationship:

```
scores.session_id → game_sessions.session_id
```

---

# 11. Database Relationships

Complete simplified relational structure:

```text
users
  │
  ├── parents
  │       │
  │       └── children
  │               │
  │               ├── characters
  │               │
  │               └── game_sessions
  │                       │
  │                       ├── games
  │                       │
  │                       └── scores
  │
  └── admins
```

---

# 12. Indexing Strategy

To improve query performance, the following indexes are recommended:

| Table         | Indexed Column |
| ------------- | -------------- |
| users         | email          |
| users         | username       |
| children      | parent_id      |
| game_sessions | child_id       |
| game_sessions | game_id        |
| scores        | session_id     |

---

# 13. Data Integrity Constraints

Important constraints include:

* **foreign key enforcement**
* **cascade deletion where appropriate**
* **unique email constraint for users**
* **non-null authentication fields**

Example constraints:

```
users.email UNIQUE
children.parent_id NOT NULL
scores.session_id NOT NULL
```

---

# 14. Security Considerations

The database schema supports several security practices:

### Password Security

Passwords are stored using **secure hashing algorithms** (e.g., bcrypt).

### Access Control

Application-level access control ensures:

* children access only their games
* parents access only their children
* admins manage global resources

### Data Protection

Sensitive information is protected through:

* input validation
* prepared SQL statements
* restricted database permissions

---

# 15. Future Extensions

The schema can be extended to support additional features such as:

* achievements or badges
* learning analytics
* adaptive difficulty algorithms
* AI-driven game recommendations

These features can be implemented without major modifications to the existing schema.
