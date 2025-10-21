# QUICK SETUP GUIDE - MediCare Plus

## ⚡ Quick Start (5 Minutes)

### Step 1: Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Click **Start** for:
   - ✅ Apache
   - ✅ MySQL

### Step 2: Create Database
1. Open browser: `http://localhost/phpmyadmin`
2. Click **"New"** in left sidebar
3. Database name: `medicare_plus`
4. Click **"Create"**

### Step 3: Import Database
1. Click on `medicare_plus` database
2. Go to **"Import"** tab
3. Click **"Choose File"**
4. Select: `database/medicare_plus.sql` from project folder
5. Click **"Go"** at bottom
6. Wait for success message ✓

### Step 4: Access Application
1. Open browser
2. Go to: `http://localhost/New folder/`
3. You should see the MediCare Plus homepage!

## 🔐 Test Login

### Admin Login
```
URL: http://localhost/New folder/login.php
Username: admin
Password: password
```

### Create Patient Account
```
URL: http://localhost/New folder/register.php
Fill in the registration form
```

## ✅ Verify Installation

1. **Homepage loads?** ✓
2. **Can login as admin?** ✓
3. **Can register new patient?** ✓
4. **Can view doctors list?** ✓

If all YES, you're ready to go! 🎉

## 🚨 Common Issues

### "Database connection failed"
- ✓ Check if MySQL is running in XAMPP
- ✓ Verify database name is `medicare_plus`
- ✓ Check `config/database.php` settings

### "Page not found"
- ✓ Verify files are in `C:\xampp\htdocs\New folder\`
- ✓ Check if Apache is running
- ✓ Try: `http://localhost/New folder/index.php`

### "Cannot login"
- ✓ Make sure database is imported
- ✓ Check if tables exist in phpMyAdmin
- ✓ Default password is: `password`

## 📋 What to Do Next

1. **Login as Admin** → Add some doctors
2. **Register as Patient** → Test patient features
3. **Book Appointment** → Test booking system
4. **Explore Dashboard** → View all features

## 🎯 Key Features to Test

### As Admin:
- ✅ Add new doctor
- ✅ View all patients
- ✅ Manage appointments
- ✅ View statistics

### As Patient:
- ✅ Search doctors
- ✅ Book appointment
- ✅ Send message to doctor
- ✅ Rate doctor after appointment

### As Doctor:
(After admin adds doctor account)
- ✅ View appointments
- ✅ Confirm/Complete appointments
- ✅ View patient details

## 📞 Need Help?

1. Check `README.md` for detailed documentation
2. Review code comments in files
3. Check browser console for JavaScript errors
4. Check XAMPP error logs

---

**Happy Testing! 🏥**
