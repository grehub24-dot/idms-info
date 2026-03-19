You are a senior full-stack software architect, UX designer, and database engineer.

Your task is to design and generate a COMPLETE PRODUCTION-READY WEBSITE AND STUDENT MANAGEMENT SYSTEM for:

INFOTESS – Information Technology Students’ Society  
Department of Information Technology Education (DITE)  
Faculty of Applied Sciences and Mathematics Education (FASME)  
Akenten Appiah-Menka University of Skills Training and Entrepreneurial Development (AAMUSTED)

Reference sources for scraping department information:

https://aamusted.edu.gh/fasme  
https://aamusted.edu.gh/fasme/dite/

The system will function as BOTH:

1. Official INFOTESS Department Website
2. School Dues Management System (SDMS)

The main target users are:

INFOTESS students  
INFOTESS executives  
Department administrators  
Alumni  
Event participants  

---

TECH STACK REQUIREMENTS

The entire system MUST use the following stack:

Frontend
HTML5  
CSS3  
Vanilla JavaScript  

Backend
PHP

Database
MySQL

Libraries and tools

PHPMailer (email integration)  
DOMPDF or TCPDF (PDF receipt generation)  
QR Code generator library  
Chart.js (analytics dashboards)  
AJAX (asynchronous requests)

The system must be deployable on standard shared hosting (cPanel compatible).

---

SYSTEM MODULES

The platform must include the following modules:

Authentication  
Student Registration  
Student Management  
Payment Recording  
Receipt Generation  
Email Integration  
Student Dashboard  
Admin Dashboard  
Reporting  
Search & Verification  
QR Verification  
Advanced Analytics  
Database Management  
Infrastructure Setup  

---

WEBSITE STRUCTURE

Design the website with the following pages:

Home  
About INFOTESS  
Department Overview  
INFOTESS Executives  
INFOTESS Activities  
Events  
Projects & Innovations  
Student Resources  
Membership  
Alumni Network  
News & Blog  
Gallery  
Contact  

---

EXECUTIVES PAGE

Create a dedicated page displaying the INFOTESS leadership structure.

Positions include:

President  
Vice President  
Secretary  
Financial Secretary  
Organizing Secretary  
Public Relations Officer  
Technical Lead  
Patron  

Each profile must include:

Photo  
Full name  
Position  
Short biography  
Email  
LinkedIn  
GitHub  

---

INFOTESS ACTIVITIES PAGE

Display all student society activities such as:

Tech workshops  
Hackathons  
Seminars  
Coding bootcamps  
Community tech outreach  
Innovation showcases  

Each activity must include:

Title  
Description  
Date  
Images  
Registration link  

---

DEPARTMENT DATA SCRAPING

Create a background service that periodically scrapes relevant data from:

https://aamusted.edu.gh/fasme  
https://aamusted.edu.gh/fasme/dite/

Extract and display:

Department description  
Faculty overview  
Programs offered  
Announcements  

Automatically update the department overview page.

---

AUTHENTICATION SYSTEM

Student Login

Index Number + Password

Admin Login

Email + Password

Features:

Password hashing  
Password reset via email  
Role-based access control  
Session management  
Auto logout after 10 minutes inactivity  

Roles include:

Student  
Executive  
Admin  
Super Admin  

---

5.2 STUDENT REGISTRATION MODULE

Admin registers students with the following fields:

Full Name  
Index Number (Unique)  
Department  
Level  
Email  
Phone Number  

Validation rules:

Unique index number  
Valid email format  
Required fields enforced  

---

5.3 PAYMENT RECORDING MODULE

Admin records payment details including:

Student (via index number)  
Amount paid  
Academic year  
Semester  
Payment method  

Supported payment methods:

Cash  
Mobile Money  
Bank Transfer  

Date of payment must also be recorded.

System Automatically Generates:

Unique receipt number.

Receipt format example:

SDMS-2026-0001

Duplicate Payment Detection:

Prevent duplicate payment entries for the same:

Student  
Semester  
Academic year  

Unless admin explicitly overrides.

---

5.4 AUTOMATIC RECEIPT GENERATION MODULE

Immediately after payment recording:

The system automatically generates a PDF receipt.

The receipt must contain:

School logo  
INFOTESS logo  
Unique receipt number  
Student details  
Payment details  
Digital signature  
QR code for verification  
Payment date  

Receipt must be:

Downloadable  
Printable  
Attached to confirmation email  

---

5.5 EMAIL NOTIFICATION SYSTEM

After payment is recorded:

System automatically sends an email to the student.

Email subject:

School Dues Payment Confirmation – 2026

Email content includes:

Payment summary  
Receipt PDF attachment  
Official INFOTESS contact information  

Optional enhancement:

SMS notification integration via third-party API.

---

5.6 STUDENT DASHBOARD

Students must be able to:

View payment history  
Download previous receipts  
View payment status  
View outstanding balance  
Update profile information  

Dashboard should be simple and mobile friendly.

---

5.7 ADMIN DASHBOARD

Admin panel must allow administrators to:

Search student by:

Name  
Index number  

View payment records

Edit incorrect entries (with audit log)

Delete duplicate entries (admin-only permission)

Generate reports including:

Payments per department  
Payments per academic year  
Payments per semester  

Export reports to:

Excel  
PDF  

---

5.8 SEARCH & VERIFICATION MODULE

During student clearance:

Admin enters index number.

System displays:

Full payment history  
Payment dates  
Receipt numbers  
Payment status  

Optional feature:

Scan QR code on receipt to verify authenticity.

---

QR VERIFICATION SYSTEM

Each generated receipt must include a QR code.

When scanned:

The QR code opens a verification page showing:

Receipt number  
Student name  
Amount paid  
Payment date  
Verification status  

This prevents receipt fraud.

---

ADVANCED ANALYTICS

Use Chart.js to generate interactive dashboards showing:

Membership growth  
Revenue statistics  
Payment trends  
Event attendance  

Charts must be dynamic and filterable by:

Academic year  
Semester  

---

DATABASE DESIGN

Create a MySQL schema including the following tables:

users  
students  
executives  
activities  
events  
payments  
receipts  
audit_logs  
notifications  

Each table must include:

Primary keys  
Foreign keys  
Timestamps  

Ensure relational integrity.

---

NON-FUNCTIONAL REQUIREMENTS

6.1 PERFORMANCE

System should support at least:

5,000+ students.

Search response time must be:

Less than 2 seconds.

Support concurrent users.

---

6.2 SECURITY

Implement:

Encrypted passwords  
HTTPS secure connection  
SQL injection prevention  
Role-based access control  
Database encryption where applicable  
Audit logging for admin actions  

---

6.3 RELIABILITY

System must include:

Automated daily database backups  
Error logging system  
Target uptime of 99 percent  

---

6.4 SCALABILITY

System must use modular architecture allowing expansion for:

Online payment gateway integration  
Multi-campus deployment  
Integration with university student portal  

---

PROJECT FILE STRUCTURE

Create an organized structure:

/public  
/css  
/js  
/images  
/uploads  

/admin  

/includes  

/api  

/database  

/receipts  

/logs  

---

DELIVERABLES REQUIRED

Generate the following in detail:

Complete system architecture  
Full MySQL database schema  
Website page wireframes  
Authentication flow  
Payment workflow  
Receipt generation logic  
QR verification workflow  
Email system logic  
Admin dashboard architecture  
Student dashboard architecture  
Deployment instructions  

The output must be extremely detailed so that developers can build the full system.
