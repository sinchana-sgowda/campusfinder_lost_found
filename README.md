 
 ## CampusFinder – College Lost & Found Platform

A full-stack web application built with **PHP + MySQL** that helps college students report and find lost items on campus.

---

## 📌 Project Overview

CampusFinder is a **College Lost & Found Management System** where students can:
- Report items they have **lost** on campus
- Report items they have **found** on campus
- **Search and browse** all reported items
- **Claim** items they recognize
- Track their own reports and claims

Administrators can manage all items, approve/reject claims, and monitor student activity through a dedicated admin panel.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Server | Apache (XAMPP) |

---

## 🗄️ Database Tables

| Table | Description |
|-------|-------------|
| `STUDENT` | Stores registered student details |
| `ADMIN` | Stores admin credentials |
| `ITEM` | Stores lost and found item reports |
| `CLAIM` | Stores claims submitted by students |

---

## ✨ Features

### 🎓 Student Portal
- ✅ Register and Login
- ✅ Report Lost or Found items
- ✅ Browse all items on campus
- ✅ Search by title, location, or category
- ✅ Filter by Lost / Found / Category
- ✅ Claim items with a message
- ✅ Track your own reported items
- ✅ Track your claim status

### 🔐 Admin Portal
- ✅ View dashboard with statistics
- ✅ Manage all reported items (Close / Delete)
- ✅ Approve or Reject student claims
- ✅ View and manage all registered students

---

## 📁 Project Structure

```
campusfinder-lost-found/
├── lostfound_index.php         ← Main login & register page
├── lostfound_config.php        ← Database connection
├── lostfound_dashboard.php     ← Student dashboard
├── lostfound_admin.php         ← Admin panel
├── lostfound_logout.php        ← Logout handler
└── lostfound_database.sql      ← Database setup script

---

## 🎓 Subject

**Database Management System (DBMS)** – 4th Semester Project

---

## 📄 License

This project is for educational purposes only.
