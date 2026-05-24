# YOPY — System Overview

## 1. Introduction

**YOPY** is a web-based platform designed to provide a **safe, interactive, and educational digital environment for children**, while allowing **parents to supervise and monitor their activity**.

The system integrates:

* educational mini-games
* child profile customization
* performance tracking
* parental dashboards
* administrative management tools

The primary objective is to create a platform where **children can learn and play**, while **parents maintain visibility over their progress and digital behavior**.

The system is implemented using:

* **Backend:** PHP
* **Frontend:** HTML, CSS, JavaScript, Bootstrap
* **Database:** MySQL

The architecture follows a **modular MVC structure**, separating presentation, application logic, and data management.

---

# 2. System Objectives

The platform aims to achieve several technical and functional goals.

### Educational Engagement

Provide interactive games that stimulate:

* logical thinking
* memory
* problem solving
* cognitive development

### Parental Monitoring

Allow parents to:

* monitor game activity
* analyze child performance
* manage child profiles
* supervise platform usage

### Safe Digital Environment

Ensure that the platform:

* restricts unsafe interactions
* maintains controlled content
* protects child data

### Administrative Control

Enable administrators to manage the platform by:

* managing users
* managing games
* monitoring system activity

---

# 3. Target Users

The platform supports three categories of users.

## Child

Children are the primary users of the system.

They can:

* select a character
* access the game menu
* play educational games
* earn scores
* view simple progress indicators

The child interface focuses on:

* visual simplicity
* intuitive navigation
* engaging animations

---

## Parent

Parents supervise their children through a dedicated dashboard.

Parents can:

* create and manage child accounts
* monitor scores and activity
* track game sessions
* analyze progress

The parent dashboard provides structured analytical data.

---

## Administrator

Administrators manage the platform's operational environment.

Administrative capabilities include:

* managing users
* managing game catalog
* monitoring system usage
* maintaining platform stability

---

# 4. System Components

The system is organized into several major modules.

## Authentication Module

Handles all user authentication processes.

Features include:

* user registration
* login and logout
* role identification
* session management

---

## Child Interaction Module

Provides the interactive environment used by children.

Key components:

* character selection
* game menu
* mini-games
* score feedback

This module emphasizes usability and engagement.

---

## Game System

The game subsystem manages all educational games.

Responsibilities include:

* loading games
* managing gameplay logic
* calculating scores
* storing results

Each game session produces a **score record** associated with a child profile.

---

## Parent Dashboard

The parent dashboard provides monitoring tools.

Features include:

* activity history
* score statistics
* performance analysis
* child account management

Data is retrieved from game session records.

---

## Administration Module

The administration module enables system control.

Capabilities include:

* user management
* game management
* system monitoring
* platform maintenance

---

# 5. High-Level Architecture

The platform follows a **three-layer web architecture**.

```
Client Layer
│
├── Browser
│      ├── HTML
│      ├── CSS
│      └── JavaScript
│
Application Layer
│
├── PHP Controllers
├── Business Logic
└── Game Logic
│
Data Layer
│
└── MySQL Database
```

### Client Layer

The client layer manages:

* user interface rendering
* user interaction
* animations and visual feedback

This layer is implemented using **HTML, CSS, JavaScript, and Bootstrap**.

---

### Application Layer

The application layer contains:

* request handling
* business logic
* game logic
* authentication processing

This layer is implemented in **PHP**.

---

### Data Layer

The data layer stores all persistent system data.

Key stored elements include:

* user accounts
* child profiles
* games
* game sessions
* scores

The database is implemented using **MySQL**.

---

# 6. Security Considerations

The system integrates several security mechanisms.

### Authentication Security

* password hashing
* session management
* role-based access control

### Data Protection

* restricted access to child data
* input validation
* SQL injection prevention

### Platform Integrity

Administrative monitoring allows detection of abnormal activity.

---

# 7. System Workflow

Typical user workflow is illustrated below.

```
User Login
      │
      ▼
Role Identification
      │
 ┌────┴────┐
 │         │
Child    Parent
 │         │
 ▼         ▼
Game Menu  Dashboard
 │         │
 ▼         ▼
Play Game  Monitor Progress
 │
 ▼
Score Stored in Database
```

---

# 8. Expected Benefits

The platform provides several advantages.

### For Children

* engaging learning experience
* gamified education
* safe online environment

### For Parents

* visibility of child activity
* progress monitoring
* controlled digital exposure

### For the Platform

* scalable architecture
* modular design
* clear role separation

---

# 9. Documentation Structure

The system documentation is divided into several technical documents.

| Document               | Purpose                         |
| ---------------------- | ------------------------------- |
| **overview.md**        | General system description      |
| **architecture.md**    | Technical architecture design   |
| **domain-model.md**    | Core entities and relationships |
| **database-schema.md** | Database structure              |

This documentation structure provides a complete reference for developers and maintainers of the YOPY platform.
