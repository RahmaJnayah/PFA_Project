# YOPY — System Architecture

## 1. Architecture Overview

YOPY is implemented as a **web-based platform** designed for children’s interactive learning through games while allowing parents to monitor progress through a dedicated dashboard.

The system follows a **layered MVC-based architecture** with role-based interfaces.

The architecture separates responsibilities into:

* Presentation layer
* Application layer
* Domain / service layer
* Data layer

This separation improves maintainability, scalability, and security.

---

# 2. High-Level Architecture

The overall system architecture is composed of four main layers.

```
Client (Browser)
       │
       ▼
Presentation Layer (Views + JavaScript + Bootstrap)
       │
       ▼
Controller Layer (PHP MVC Controllers)
       │
       ▼
Service Layer (Business Logic)
       │
       ▼
Data Layer (Models + MySQL Database)
```

### Responsibilities

| Layer        | Description                                     |
| ------------ | ----------------------------------------------- |
| Presentation | UI rendering, animations, and interaction logic |
| Controllers  | Handle HTTP requests and route them to services |
| Services     | Implement business logic and system operations  |
| Models       | Database interaction and persistence            |

---

# 3. System Actors

The platform defines three primary user roles.

### Child

The child interacts with the platform through games.

Main features:

* Character selection
* Game menu
* Interactive educational games
* Score generation

### Parent

Parents access a monitoring dashboard.

Main features:

* Child progress monitoring
* Game statistics
* Activity history
* Account management

### Administrator

Administrators manage the platform.

Main features:

* User management
* Game management
* Platform monitoring

---

# 4. Role-Based Interface Architecture

## Child Interface

The child interface is designed to be simple and interactive.

```
Child Interface
 ├── Character Selection
 ├── Game Menu
 └── Games
      ├── Memory Game
      ├── Math Game
      └── Puzzle Game
```

### Interaction Flow

```
Child Login
      │
      ▼
Character Selection
      │
      ▼
Game Menu
      │
      ▼
Game Session
      │
      ▼
Score Submission
```

Game interactions are handled primarily through **JavaScript**, while **PHP services store results and manage sessions**.

---

## Parent Interface

The parent interface provides analytical insights about the child's activities.

```
Parent Dashboard
 ├── Child Progress
 ├── Game History
 ├── Statistics
 └── Settings
```

The dashboard retrieves data from the backend using secure API endpoints.

---

## Admin Interface

The admin panel is used for platform administration.

```
Admin Panel
 ├── Manage Users
 ├── Manage Games
 ├── Platform Reports
 └── System Logs
```

Administrative operations require elevated permissions and authentication checks.

---

# 5. MVC Structure

The backend follows the **Model–View–Controller pattern**.

### Controllers

Controllers handle incoming requests.

Examples:

```
AuthController
ChildController
ParentController
AdminController
GameController
```

Responsibilities:

* receive HTTP requests
* validate input
* call service layer
* return responses

---

### Services

The service layer contains the application’s business logic.

Examples:

```
AuthService
GameService
ScoreService
ParentDashboardService
ChildProfileService
```

Services coordinate operations between controllers and models.

---

### Models

Models represent persistent data stored in the database.

Examples:

```
UserModel
ChildModel
GameModel
ScoreModel
SessionModel
```

Responsibilities:

* database queries
* data persistence
* entity representation

---

# 6. Game System Architecture

Games are implemented as **client-side interactive modules**.

```
Frontend Game Engine
 ├── memoryGame.js
 ├── mathGame.js
 └── puzzleGame.js
```

JavaScript handles:

* game interaction
* animations
* scoring logic

PHP handles:

* session validation
* score storage
* statistics generation

Game flow:

```
Play Game
   │
   ▼
JavaScript calculates score
   │
   ▼
API request to PHP backend
   │
   ▼
ScoreService stores result
   │
   ▼
Database update
```

---

# 7. Database Architecture

The system uses a relational database implemented with MySQL.

Main entities include:

```
Users
Children
Games
Scores
Sessions
```

Example relationships:

```
User
 └── Child
       └── GameSession
              └── Score
```

---

# 8. Security Architecture

Security mechanisms implemented in the system include:

* password hashing
* session management
* CSRF protection
* input validation
* role-based access control

Security modules:

```
AuthManager
InputValidator
CSRFMiddleware
```

---

# 9. Project Directory Structure

```
yopy-platform
│
├── controllers
├── services
├── models
├── middleware
│
├── views
│   ├── child
│   ├── parent
│   ├── admin
│   └── auth
│
├── public
│   ├── css
│   ├── js
│   │   ├── games
│   │   └── ui
│
├── games
├── database
├── config
└── docs
```

---

# 10. Technology Stack

Frontend technologies:

* JavaScript
* Bootstrap

Backend technologies:

* PHP
* MySQL

Development environment:

* Git
* Git Bash
* Local PHP server

---

# 11. Data Flow Example

Example: Child playing a game.

```
Child Browser
     │
     ▼
Game Interface (JavaScript)
     │
     ▼
API Request
     │
     ▼
GameController
     │
     ▼
GameService
     │
     ▼
ScoreModel
     │
     ▼
MySQL Database
```

---

# 12. Architectural Benefits

The architecture provides several advantages:

* clear separation of responsibilities
* modular system structure
* role-based interfaces
* maintainable code organization
* scalable game system

This design allows the platform to evolve with additional games, features, and analytical tools in the future.
