# Database Structure Documentation

This directory contains all database-related files for the CCS Screening System.

## Directory Structure

```
database/
├── migrations/           # Database structure changes
│   └── 001_initial_schema.sql    # Initial database schema
├── seeds/               # Initial and test data
│   ├── 001_super_admin.sql       # Super admin account creation
│   └── 002_test_applicants.sql   # Test applicant data
└── README.md           # This documentation file
```

## Database Setup Instructions

1. Create the database and tables:
```bash
mysql -u root < database/migrations/001_initial_schema.sql
```

2. Create the super admin account:
```bash
mysql -u root ccs_screening < database/seeds/001_super_admin.sql
```

3. (Optional) Add test data:
```bash
mysql -u root ccs_screening < database/seeds/002_test_applicants.sql
```

## Super Admin Credentials
- Email: superadmin@ccs.edu.ph
- Password: password

## Database Schema Overview

### Main Tables
1. `users` - Base table for all user types (super_admin, admin, applicant)
2. `applicants` - Applicant information and progress
3. `admins` - Admin user information
4. `super_admins` - Super admin user information
5. `exams` - Exam configuration and settings
6. `questions` - Exam questions and answers
7. `interview_schedules` - Interview scheduling and results

### Supporting Tables
1. `activity_logs` - User activity tracking
2. `notifications` - System notifications
3. `announcements` - System announcements
4. `email_logs` - Email sending history

## Migration Files
Each migration file in the `migrations` directory represents a change to the database structure. Files are prefixed with a number to ensure they are executed in the correct order.

## Seed Files
Seed files in the `seeds` directory contain initial or test data. They should be executed in order as indicated by their numeric prefix.
