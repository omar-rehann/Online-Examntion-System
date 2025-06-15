# Welcome to Online Exam System Project
This project is a comprehensive online examination platform designed specifically for the Faculty of Science at Port Said University. It allows faculty members to create and manage exams, while enabling students to easily access and take their exams online.
The platform was developed to streamline the student assessment process in an organized and secure manner, providing a smooth user experience for both instructors and students.
# Demo
https://github.com/omar-rehann/Online-Examntion-System.git
## Team

1. Omar Rehann &nbsp;&nbsp;&nbsp;&nbsp;  2-Bassem Ibrahim &nbsp;&nbsp;&nbsp;&nbsp; 3-Mahmoud Elzonfly  
4. Maryam Rabei &nbsp;&nbsp;&nbsp;&nbsp;5-Maryam Rashad &nbsp;&nbsp;&nbsp;&nbsp;   6-Reem Adel  &nbsp;&nbsp;&nbsp;&nbsp;7. Esraa Soliman


# Project Supervisor
This graduation project was carried out under the supervision of
Dr. Mostafa Herajy,
Lecturer at the Department of Mathematics and Computer Science,
Faculty of Science – Port Said University.
## Objectives 	
 of the system is to completely automate the traditional manual exam process into a fully computerized system.

The system allows students to take exams without having to register themselves manually, as student accounts are created by the system administrator or instructor.

Instructors are required to register and log in to the system. They can add questions, create exams, and assign them to specific groups of students. Instructors can also view a list of all students who have taken the exam along with their grades.

Once a student logs in using the provided credentials, they can access the exams assigned to them through their group memberships only. After completing an exam, the student can view their result and review their answers.

## Fetures
-Diverse Test Creation: Ability to design tests with multiple question types (multiple choice, true/false, fill-in-the-blank).



-Automatic Grading: Instant correction of answers with accurate results.



-Analytical Reports: Generate detailed performance reports to enhance the learning process.



-Time Management: Set time limits for tests with automatic notifications.



-Security: Protect student data and ensure exam integrity.

## How to Install?
1. Clone the project to your web folder (www)
2. excute the attached sql database file
3. login as instructor using admin using default email and password
## Simple flow shart
```mermaid
flowchart TD
    A[<b>Login</b>] -->|Admin| B[<b>Admin Panel</b>]
    A -->|Doctor| C[<b>Doctor Panel</b>]
    A -->|Student| D[<b>Student Panel</b>]

    %% Admin
    B --> B1[➕ Add Users]
    B --> B2[📤 Import/Export]
    B --> B3[📊 Results]
    B --> B4[👤 Profile]

    %% Doctor
    C --> C1[👥 Groups]
    C1 --> C1a[🖊️ Manual Add]
    C1 --> C1b[🔢 Join Code]
    
    C --> C2[📝 Questions]
    C2 --> C2a[📜 Essay]
    C2 --> C2b[✅ True/False]
    C2 --> C2c[🔘 MCQ]
    C2 --> C2d[🔗 Matching]
    C2 --> C2e[∑ Math]
    C2 --> C2f[📤 Import/Export]

    C --> C3[🛠️ Create Exam]
    C --> C4[📈 View Results]
    C --> C5[👤 Profile]

    %% Student
    D --> D1[✏️ Take Exam]
    D --> D2[📊 Results]
    D --> D3[👥 Join Group]
    D3 --> D3a[🔢 Enter Code]
    D3 --> D3b[➕ Added by Doctor]
    D --> D4[👤 Profile]

    %% Database Links
    B1 & B2 & C1a & C1b & C2f & D1 & D3a & D3b --> H[(🛢️ Database)]




