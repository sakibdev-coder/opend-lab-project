# Social Development Events Platform (CommunityAid Dhaka)
### A Database-Driven Community Welfare & Event Management Web Application
**Technologies:** PHP (PDO) • MySQL / MariaDB • Apache (XAMPP) • HTML5 • Bootstrap 5.3

---

## 🌟 Project Overview
This project is an end-to-end community-driven event management platform built for local social welfare and humanitarian initiatives across Dhaka (such as *Winter Clothes Distribution in Mirpur 10*, *Blanket Distribution in Mohammadpur*, *Free Medical Camps in Slum Communities*, and *Tree Plantation Drives*).

The application meets all requirements of the academic database project assignment:
- **5 Relational Tables** normalized up to **3NF** (Third Normal Form)
- **Primary Keys, Foreign Keys (ON UPDATE CASCADE / ON DELETE RESTRICT & CASCADE), CHECK Constraints, and UNIQUE Constraints**
- **Relational Schema & Mermaid ER Diagram**
- **Full CRUD Operations** (Create, Read, Update, Delete) accessible via web forms
- **10+ Verified SQL Queries** operating from the web application:
  - **2 INSERT Queries**
  - **5+ SELECT Queries** (including Multi-Table `JOIN`, `GROUP BY` with aggregations, Correlated `SUBQUERY`, and an SQL `VIEW`)
  - **2 UPDATE Queries**
  - **1+ DELETE Queries**
- **Prepared Statements (PDO)** on all user-supplied input to prevent SQL Injection
- Dedicated **Interactive Viva Showcase (`queries-showcase.php`)** with live 1-click query testing

---

## 📂 Project Directory Structure

```text
opend lab project/
├── config/
│   └── database.php             # PDO Connection & helper functions (XAMPP ready)
├── database/
│   └── schema.sql               # Complete MySQL DDL, constraints, VIEW & Dhaka seed data
├── includes/
│   ├── header.php               # Responsive navbar with Bootstrap 5 & FontAwesome
│   └── footer.php               # Unified footer with viva references
├── assets/
│   ├── css/
│   │   └── style.css            # Custom Dhaka-theme green/amber responsive styles
│   └── js/
│       └── main.js              # Client-side validation & action confirmation modals
├── index.php                    # Dashboard with KPI cards, SQL VIEW feed & search
├── events.php                   # Read & Search with multi-table JOIN & filters
├── event-create.php             # CREATE Operation: Web form & prepared INSERT (Query #1)
├── event-details.php            # Single Record READ (JOIN), Volunteer INSERT (Query #2), Donations
├── event-edit.php               # UPDATE Operation: Edit event details & quotas (Query #8)
├── event-delete.php             # DELETE Operation: Cascading event removal (Query #10)
├── registration-manage.php      # UPDATE Operation: Volunteer status (Query #9) & DELETE (Query #10)
├── analytics.php                # Advanced READ: GROUP BY reports and Correlated SUBQUERY
├── queries-showcase.php         # Dedicated Lab Viva Console with live 1-click execution
├── setup_db.php                 # 1-Click Browser-based Database Installer & Seed Tool
├── ER_DIAGRAM_AND_SCHEMA.md     # Detailed ER diagram, FDs, and 3NF normalization proof
└── README.md                    # Setup guide and Viva demonstration walkthrough
```

---

## 🚀 How to Run on Localhost (XAMPP Setup)

### Option 1: Running with XAMPP (Standard)

1. **Start XAMPP**:
   - Open **XAMPP Control Panel**.
   - Start **Apache** and **MySQL**.

2. **Copy or Link Project to `htdocs`**:
   - Copy this project folder into your XAMPP `htdocs` directory:
     - **Windows:** `C:\xampp\htdocs\social-events\`
     - **macOS:** `/Applications/XAMPP/xamppfiles/htdocs/social-events/`
   *(Or keep it in your current workspace and create a symbolic link into `htdocs`).*

3. **Initialize the Database**:
   You have two simple ways to set up the database:
   - **Method A (Easiest - 1-Click via Browser):**
     Open your browser and visit:  
     `http://localhost/social-events/setup_db.php`  
     Click the blue button **"Initialize / Reset Database Now"**. Everything will be created and seeded automatically!
   - **Method B (via phpMyAdmin):**
     1. Open `http://localhost/phpmyadmin/`.
     2. Click on the **"Import"** tab.
     3. Choose the file `database/schema.sql` from this project.
     4. Click **"Import"** at the bottom.

4. **Open the Website**:
   Visit `http://localhost/social-events/index.php` in your browser.

---

### Option 2: Running with Built-in PHP Server (Fastest for testing)

If you have PHP and MySQL installed on your system:
1. Ensure your MySQL server is running (e.g. from XAMPP on port 3306 with user `root` and empty password).
2. Open terminal in this folder and start the server:
   ```bash
   php -S localhost:8000
   ```
3. Open `http://localhost:8000/setup_db.php` in your browser once to initialize the database.
4. Visit `http://localhost:8000/` to use the platform.

---

## 🗄️ Relational Database Schema & 3NF Normalization

### Tables & Relationships
1. **`users`** (`user_id` [PK], `full_name`, `email` [UNIQUE], `phone`, `role`, `address`, `created_at`)
2. **`categories`** (`category_id` [PK], `category_name` [UNIQUE], `description`)
3. **`events`** (`event_id` [PK], `title`, `description`, `category_id` [FK], `organizer_id` [FK], `area`, `exact_location`, `event_date`, `target_volunteers`, `status`, `created_at`)
   - `category_id` references `categories(category_id)` ON UPDATE CASCADE ON DELETE RESTRICT
   - `organizer_id` references `users(user_id)` ON UPDATE CASCADE ON DELETE CASCADE
   - `CHECK (target_volunteers > 0)`
4. **`registrations`** (`registration_id` [PK], `event_id` [FK], `user_id` [FK], `role_type`, `status`, `registered_at`, `notes`)
   - `event_id` references `events(event_id)` ON UPDATE CASCADE ON DELETE CASCADE
   - `user_id` references `users(user_id)` ON UPDATE CASCADE ON DELETE CASCADE
   - `UNIQUE (event_id, user_id)` (prevents double registration)
5. **`donations`** (`donation_id` [PK], `event_id` [FK], `donor_name`, `donor_contact`, `item_type`, `quantity`, `unit`, `donated_at`)
   - `event_id` references `events(event_id)` ON UPDATE CASCADE ON DELETE CASCADE
   - `CHECK (quantity > 0)`

### Why this is in 3NF:
- **1NF:** All column values are atomic (e.g. no comma-separated volunteers or phone numbers). Primary keys defined on every table.
- **2NF:** In 1NF and no non-prime attribute is partially dependent on any composite candidate key.
- **3NF:** In 2NF and no transitive functional dependency exists ($X \rightarrow Y$ where $X$ is non-key and $Y$ is non-key). For example, Category names are stored in `categories`, not duplicated inside `events`; organizer details are stored in `users`, not inside `events`.

*(See [ER_DIAGRAM_AND_SCHEMA.md](file:///Users/sakibalhasan/opend%20lab%20project/ER_DIAGRAM_AND_SCHEMA.md) for the complete mathematical proof and Mermaid ER diagram).*

---

## 📋 The 10+ SQL Queries Assignment Mapping

Every required query is implemented in the application and testable directly on **[queries-showcase.php](file:///Users/sakibalhasan/opend%20lab%20project/queries-showcase.php)**:

| Query ID | Type | Web Page | Purpose & Description |
| :---: | :---: | :--- | :--- |
| **Q1** | **INSERT** | `event-create.php` | Inserts a new social development event with prepared statement. |
| **Q2** | **INSERT** | `event-details.php` | Registers a volunteer user for an event (enforcing UNIQUE constraint). |
| **Q3** | **SELECT (JOIN)** | `events.php` | Multi-table INNER JOIN across `events`, `categories`, and `users`. |
| **Q4** | **SELECT (GROUP BY)** | `analytics.php` | Groups events by category to compute total events, volunteers, and donations. |
| **Q5** | **SELECT (SUBQUERY)** | `analytics.php` | Correlated subquery selecting events with volunteer signups $\ge$ system average. |
| **Q6** | **SELECT (VIEW)** | `index.php` | Queries the relational database SQL VIEW `vw_event_summary`. |
| **Q7** | **SELECT (Search/Filter)** | `events.php` | Prepared statement multi-parameter filter by locality (Mirpur, Mohammadpur) and status. |
| **Q8** | **UPDATE** | `event-edit.php` | Modifies existing event details, target quota, and schedule status. |
| **Q9** | **UPDATE** | `registration-manage.php`| Updates volunteer participation status (Registered $\rightarrow$ Confirmed $\rightarrow$ Attended). |
| **Q10** | **DELETE** | `event-delete.php` & `registration-manage.php` | Deletes an event (with cascading delete) or cancels a volunteer registration. |
| **Q11** | **Bonus Subquery** | `queries-showcase.php` | Uses `IN` and `HAVING` inside a subquery to select active organizers. |

---

## 🎓 Lab Viva & Live Demonstration Guide

When the instructor asks you to demonstrate your project during the viva:

### 1. Live CRUD Demonstration Walkthrough:
1. **Create (C):**
   - Click **"Create Event"** in the top navigation.
   - Enter a title (e.g. *"Winter Warmth Blanket Drive – Mirpur 10"*), select Category *"Winter Relief"*, Organizer, set target volunteers to 20, and click **Save Event**.
   - Show the instructor: The record is immediately inserted using PDO prepared statements (`INSERT INTO events ...`).
2. **Read (R):**
   - Go to **"Explore Events"**.
   - Show how the table fetches combined data from `events`, `categories`, and `users` using a **3-Table INNER JOIN**.
   - Type *"Mirpur"* in the search box to demonstrate the live filter query.
   - Click **"Analytics"** to show the **GROUP BY** aggregations and the **SUBQUERY** result.
3. **Update (U):**
   - Click the yellow **Edit** icon next to any event.
   - Change the status from *"Upcoming"* to *"Ongoing"* or adjust the target volunteers to 30.
   - Click **Update Event** and show the updated status badge.
4. **Delete (D):**
   - Go to **"Volunteers"** in the navbar.
   - Click the red **Trash** icon next to a registration to cancel/delete it.
   - Point out the confirmation dialog and the success alert confirming the DELETE execution.

---

### 2. Common Viva Questions & Model Answers:

**Q1: Why did you use PDO instead of mysqli?**
> *Answer:* PDO (PHP Data Objects) is object-oriented, supports named parameters in prepared statements, works across 12 different database drivers, and enforces exception handling (`PDO::ERRMODE_EXCEPTION`) for robust security and error management.

**Q2: How do your prepared statements protect against SQL Injection?**
> *Answer:* In prepared statements, the SQL query template is compiled by the database server *before* parameters are injected. User inputs are treated strictly as data literals, never as executable SQL code, completely neutralizing malicious input such as `' OR 1=1 --`.

**Q3: Explain the difference between `ON DELETE CASCADE` and `ON DELETE RESTRICT` in your schema.**
> *Answer:* 
> - In `events`, the category foreign key uses `ON DELETE RESTRICT` because we don't want a category to be deleted if active events belong to it.
> - In `registrations` and `donations`, we use `ON DELETE CASCADE` linked to `events(event_id)` so that if an event is deleted, its associated volunteer signups and donation logs are cleanly purged without leaving orphan records.

**Q4: How did you verify your schema is in 3NF?**
> *Answer:* 
> 1. It is in **1NF** because every attribute is atomic and there are no repeating groups.
> 2. It is in **2NF** because every non-prime attribute is fully functionally dependent on the entire primary key (no partial dependencies).
> 3. It is in **3NF** because there are no transitive dependencies: non-key attributes depend only on the primary key, and entities like users and categories are factored out into their own dedicated relations.

**Q5: What is the purpose of the SQL VIEW `vw_event_summary`?**
> *Answer:* The view pre-defines a reusable multi-table join and aggregation query. It simplifies front-end queries on the home dashboard, enhances security by abstracting underlying table structures, and improves developer productivity.
