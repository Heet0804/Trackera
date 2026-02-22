# 🎓 Trackera — School ERP System

A full-stack School Management ERP system built with PHP and MySQL. Trackera helps schools, colleges and tutorial classes manage their day-to-day operations efficiently — all in one place.

---

## 📸 Overview

Trackera provides two separate portals:
- **Faculty Portal** — for teachers to manage students, attendance, marks, fees and more
- **Student Portal** — for students to view their attendance, marks, fees and schedule

---

## ✨ Features

### 👨‍🏫 Faculty Portal
| Feature | Description |
|---|---|
| 🏠 Dashboard | Overview of total students, classes today, subjects and notices |
| ✅ Mark Attendance | Mark student attendance subject-wise and grade/division-wise |
| 📝 Enter Marks | Enter exam marks for students subject-wise |
| 📢 Post Notices | Post announcements to specific grades or all students |
| 📅 Manage Schedule | Create and manage class timetables |
| 👥 View Students | View all students with filters by grade and division |
| 🏷️ Assign Divisions | Assign students to divisions (A/B/C/D/E) |
| 🔢 Assign Roll Numbers | Assign and auto-generate roll numbers |
| 🎓 Promote Students | Promote students to next grade at year end |
| 📚 Manage Subjects | Add and delete subjects grade-wise |
| 🌐 Language Setup | Manage language subject selection for Grade 7-10 |
| 💰 Manage Fees | Add, update and track student fee payments |
| 🔒 Reset Password | Change account password |

### 👨‍🎓 Student Portal
| Feature | Description |
|---|---|
| 🏠 Dashboard | View attendance %, fees status, today's schedule and notices |
| 📊 View Attendance | Subject-wise attendance with history and percentage |
| 📝 View Marks | Exam-wise marks with percentage and pass/fail status |
| 🌐 Language Selection | Grade 7 students can select their language subject |
| 🔒 Reset Password | Change account password |

---

## 🛠️ Tech Stack

| Technology | Usage |
|---|---|
| PHP 8.2 | Backend logic and server-side rendering |
| MySQL | Database management |
| HTML/CSS | Frontend UI |
| JavaScript | Dynamic interactions |
| XAMPP | Local development server |
| Apache | Web server |

---

## 🗄️ Database Structure

Database name: `attendance_erp`

| Table | Description |
|---|---|
| `users` | Stores all students and faculty |
| `subjects` | Subjects per grade |
| `schedule` | Class timetable |
| `notices` | Announcements |
| `marks` | Student exam marks |
| `fees` | Student fee records |
| `attendance` | Daily attendance records |
| `language_selection` | Grade 7-10 language choices |
| `academic_year` | Current academic year |

---

## 📁 Project Structure

```
trackera/
│
├── db_connect.php              # Database connection
├── session_helper.php          # Session management helpers
├── logout.php                  # Logout handler
│
├── loginpage.php               # Login page (student + faculty)
├── registration.php            # Student registration
├── resetpassword.php           # Password reset
│
├── studentdashboard.php        # Student home
├── studentattendancepage.php   # Student attendance view
├── studentmarkspage.php        # Student marks view
├── studentlanguageselection.php # Language selection (Grade 7)
│
├── facultydashboard.php        # Faculty home
├── facultymarksattendance.php  # Mark attendance
├── facultyentermarks.php       # Enter marks
├── facultypostnotice.php       # Post notices
├── facultymanageschedule.php   # Manage schedule
├── facultyviewstudents.php     # View students
├── facultyassigndivision.php   # Assign divisions
├── facultyassignrollnumbers.php # Assign roll numbers
├── facultypromotestudents.php  # Promote students
├── facultymanagesubjects.php   # Manage subjects
├── facultylanguagesetup.php    # Language setup
└── facultymanagefees.php       # Manage fees
```

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP (PHP 8.2+, MySQL, Apache)
- Web browser

### Steps

**1. Clone the repository:**
```bash
git clone https://github.com/yourusername/trackera.git
```

**2. Move to XAMPP htdocs:**
```
C:/xampp/htdocs/trackera/
```

**3. Start XAMPP:**
- Start **Apache** and **MySQL** from XAMPP Control Panel

**4. Create the database:**
- Open `http://localhost/phpmyadmin`
- Create a new database called `attendance_erp`
- Click **Import** and upload the `attendance_erp.sql` file

**5. Configure database connection:**

Open `db_connect.php` and update if needed:
```php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "attendance_erp";
```

**6. Open the app:**
```
http://localhost/trackera/loginpage.php
```

---

## 👤 How to Use

### For Faculty:
1. Go to `loginpage.php`
2. Enter your faculty email (format: `name.surname@school.ac.in`)
3. Account is **auto-created** on first login
4. Access all features from the dashboard

### For Students:
1. Go to `registration.php`
2. Register with email format: `name.surname08@school.ac.in`
3. Login via `loginpage.php`

---

## 📧 Email Format

| Role | Format | Example |
|---|---|---|
| Student | `firstname.lastname08@school.ac.in` | `heet.lakhani08@school.ac.in` |
| Faculty | `firstname.lastname@school.ac.in` | `megha.lakhani@school.ac.in` |

---

## 🏷️ Division Assignment Logic

| Grade | Assignment Method |
|---|---|
| Grade 1 | Auto-assigned with gender balancing (max 42 per division) |
| Grade 2-7 | Manually assigned by faculty |
| Grade 8-10 | Based on language selection (Sanskrit→A, French→E, Hindi→B/C/D) |

---

## 💰 Fees Tracking

Faculty can track:
- Total fees per student
- Paid amount
- Pending amount
- Payment date, mode and receipt number
- Status: ✅ Paid / ⚠️ Partial / ❌ Pending

---

## 🔒 Security Features

- Passwords hashed using `password_hash()` (bcrypt)
- SQL injection prevention using `mysqli_real_escape_string()`
- Session-based authentication
- Role-based access control (student/faculty)

---

## 📌 Notes

- Faculty accounts are **auto-created** on first login — no separate registration needed
- Grade 7 students get a language selection prompt on their dashboard
- Student promotion resets division and roll number for reassignment
- Academic year is managed from the `academic_year` table

---

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

## 👨‍💻 Author

Built with ❤️ using PHP and MySQL.

> Trackera — Smart School Management System# 🎓 Trackera — School ERP System

A full-stack School Management ERP system built with PHP and MySQL. Trackera helps schools, colleges and tutorial classes manage their day-to-day operations efficiently — all in one place.

---

## 📸 Overview

Trackera provides two separate portals:
- **Faculty Portal** — for teachers to manage students, attendance, marks, fees and more
- **Student Portal** — for students to view their attendance, marks, fees and schedule

---

## ✨ Features

### 👨‍🏫 Faculty Portal
| Feature | Description |
|---|---|
| 🏠 Dashboard | Overview of total students, classes today, subjects and notices |
| ✅ Mark Attendance | Mark student attendance subject-wise and grade/division-wise |
| 📝 Enter Marks | Enter exam marks for students subject-wise |
| 📢 Post Notices | Post announcements to specific grades or all students |
| 📅 Manage Schedule | Create and manage class timetables |
| 👥 View Students | View all students with filters by grade and division |
| 🏷️ Assign Divisions | Assign students to divisions (A/B/C/D/E) |
| 🔢 Assign Roll Numbers | Assign and auto-generate roll numbers |
| 🎓 Promote Students | Promote students to next grade at year end |
| 📚 Manage Subjects | Add and delete subjects grade-wise |
| 🌐 Language Setup | Manage language subject selection for Grade 7-10 |
| 💰 Manage Fees | Add, update and track student fee payments |
| 🔒 Reset Password | Change account password |

### 👨‍🎓 Student Portal
| Feature | Description |
|---|---|
| 🏠 Dashboard | View attendance %, fees status, today's schedule and notices |
| 📊 View Attendance | Subject-wise attendance with history and percentage |
| 📝 View Marks | Exam-wise marks with percentage and pass/fail status |
| 🌐 Language Selection | Grade 7 students can select their language subject |
| 🔒 Reset Password | Change account password |

---

## 🛠️ Tech Stack

| Technology | Usage |
|---|---|
| PHP 8.2 | Backend logic and server-side rendering |
| MySQL | Database management |
| HTML/CSS | Frontend UI |
| JavaScript | Dynamic interactions |
| XAMPP | Local development server |
| Apache | Web server |

---

## 🗄️ Database Structure

Database name: `attendance_erp`

| Table | Description |
|---|---|
| `users` | Stores all students and faculty |
| `subjects` | Subjects per grade |
| `schedule` | Class timetable |
| `notices` | Announcements |
| `marks` | Student exam marks |
| `fees` | Student fee records |
| `attendance` | Daily attendance records |
| `language_selection` | Grade 7-10 language choices |
| `academic_year` | Current academic year |

---

## 📁 Project Structure

```
trackera/
│
├── db_connect.php              # Database connection
├── session_helper.php          # Session management helpers
├── logout.php                  # Logout handler
│
├── loginpage.php               # Login page (student + faculty)
├── registration.php            # Student registration
├── resetpassword.php           # Password reset
│
├── studentdashboard.php        # Student home
├── studentattendancepage.php   # Student attendance view
├── studentmarkspage.php        # Student marks view
├── studentlanguageselection.php # Language selection (Grade 7)
│
├── facultydashboard.php        # Faculty home
├── facultymarksattendance.php  # Mark attendance
├── facultyentermarks.php       # Enter marks
├── facultypostnotice.php       # Post notices
├── facultymanageschedule.php   # Manage schedule
├── facultyviewstudents.php     # View students
├── facultyassigndivision.php   # Assign divisions
├── facultyassignrollnumbers.php # Assign roll numbers
├── facultypromotestudents.php  # Promote students
├── facultymanagesubjects.php   # Manage subjects
├── facultylanguagesetup.php    # Language setup
└── facultymanagefees.php       # Manage fees
```

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP (PHP 8.2+, MySQL, Apache)
- Web browser

### Steps

**1. Clone the repository:**
```bash
git clone https://github.com/yourusername/trackera.git
```

**2. Move to XAMPP htdocs:**
```
C:/xampp/htdocs/trackera/
```

**3. Start XAMPP:**
- Start **Apache** and **MySQL** from XAMPP Control Panel

**4. Create the database:**
- Open `http://localhost/phpmyadmin`
- Create a new database called `attendance_erp`
- Click **Import** and upload the `attendance_erp.sql` file

**5. Configure database connection:**

Open `db_connect.php` and update if needed:
```php
$servername = "localhost";
$username   = "root";
$password   = "";
$database   = "attendance_erp";
```

**6. Open the app:**
```
http://localhost/trackera/loginpage.php
```

---

## 👤 How to Use

### For Faculty:
1. Go to `loginpage.php`
2. Enter your faculty email (format: `name.surname@school.ac.in`)
3. Account is **auto-created** on first login
4. Access all features from the dashboard

### For Students:
1. Go to `registration.php`
2. Register with email format: `name.surname08@school.ac.in`
3. Login via `loginpage.php`

---

## 📧 Email Format

| Role | Format | Example |
|---|---|---|
| Student | `firstname.lastname08@school.ac.in` | `heet.lakhani08@school.ac.in` |
| Faculty | `firstname.lastname@school.ac.in` | `megha.lakhani@school.ac.in` |

---

## 🏷️ Division Assignment Logic

| Grade | Assignment Method |
|---|---|
| Grade 1 | Auto-assigned with gender balancing (max 42 per division) |
| Grade 2-7 | Manually assigned by faculty |
| Grade 8-10 | Based on language selection (Sanskrit→A, French→E, Hindi→B/C/D) |

---

## 💰 Fees Tracking

Faculty can track:
- Total fees per student
- Paid amount
- Pending amount
- Payment date, mode and receipt number
- Status: ✅ Paid / ⚠️ Partial / ❌ Pending

---

## 🔒 Security Features

- Passwords hashed using `password_hash()` (bcrypt)
- SQL injection prevention using `mysqli_real_escape_string()`
- Session-based authentication
- Role-based access control (student/faculty)

---

## 📌 Notes

- Faculty accounts are **auto-created** on first login — no separate registration needed
- Grade 7 students get a language selection prompt on their dashboard
- Student promotion resets division and roll number for reassignment
- Academic year is managed from the `academic_year` table

---

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

## 👨‍💻 Author

Built with ❤️ using PHP and MySQL.

> Trackera — Smart School Management System
