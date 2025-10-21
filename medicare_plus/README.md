# MediCare Plus - Healthcare Management System

A comprehensive web-based healthcare management system built with PHP, MySQL, HTML, CSS, and JavaScript.

## 🏥 Features

### For Patients
- **Online Registration & Login**: Secure patient registration with profile management
- **Profile Management**: Update personal information, contact details, medical history, and password
- **Doctor Search & Filter**: Find doctors by specialization, name, and availability
- **Appointment Booking**: Book appointments online with preferred doctors
- **Dashboard**: View appointment history, medical reports, and profile information
- **Secure Messaging**: Communicate with doctors for follow-up questions
- **Medical Reports Access**: View and download lab results and prescriptions
- **Feedback & Ratings**: Rate doctors and services after appointments

### For Doctors
- **Professional Dashboard**: View today's and upcoming appointments
- **Patient Management**: Access patient details and medical history
- **Appointment Management**: Confirm or complete appointments
- **Medical Reports Upload**: Upload test results and prescriptions for patients
- **Messaging System**: Respond to patient inquiries

### For Administrators
- **Comprehensive Dashboard**: Overview of all system activities with real-time statistics
- **Doctor Management**: Add, edit, and remove doctor profiles with specializations
- **Patient Management**: View and manage patient accounts
- **Appointment Oversight**: Monitor all appointments across the system
- **Service Management**: Complete CRUD operations for healthcare services with categories
- **Password Management**: Reset passwords for users securely

## 📋 Requirements

- **XAMPP** (or any PHP development environment)
  - PHP 7.4 or higher (with MySQLi extension enabled)
  - MySQL 5.7 or higher
  - Apache Web Server 2.4+
- Modern web browser (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- Minimum 2GB RAM
- 500MB free disk space

## 🚀 Installation Guide

### Step 1: Set Up XAMPP
1. Download and install XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Start Apache and MySQL services from XAMPP Control Panel

### Step 2: Database Setup
1. Open your web browser and go to `http://localhost/phpmyadmin`
2. Create a new database or import the SQL file:
   - Click on "Import" tab
   - Choose the file: `database/medicare_plus.sql`
   - Click "Go" to execute

   **OR** Run the SQL manually:
   - Click on "SQL" tab
   - Copy and paste the contents of `database/medicare_plus.sql`
   - Click "Go"

### Step 3: Configure Database Connection
1. Open `config/database.php`
2. Verify the database credentials (default XAMPP settings):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'medicare_plus');
   ```

### Step 4: Access the Application
1. Place all project files in `C:\xampp\htdocs\medicare_plus\`
2. Open your browser and navigate to: `http://localhost/medicare_plus/`
3. The homepage should load successfully

## 🔐 Default Login Credentials

### Admin Account
- **Email**: `admin@medicareplus.com`
- **Username**: `admin`
- **Password**: `password`

**Important**: Change the admin password after first login for security!

### Test the System
1. **Register as a Patient**: Go to Register page and create a new patient account
2. **Login as Admin**: Use admin credentials to add doctors
3. **Book Appointments**: Login as patient and book appointments with doctors

## 📁 Project Structure

```
medicare_plus/
├── admin/                      # Admin panel files
│   ├── dashboard.php          # Admin dashboard with statistics
│   ├── doctors.php            # Manage doctors
│   ├── add-doctor.php         # Add new doctor
│   ├── edit-doctor.php        # Edit doctor details
│   ├── patients.php           # Manage patients
│   ├── edit-patient.php       # Edit patient details
│   ├── appointments.php       # Manage appointments
│   ├── services.php           # Manage healthcare services (NEW)
│   └── reset-password.php     # Password reset utility
├── assets/                     # Static assets
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   └── js/
│       └── main.js            # JavaScript functions
├── config/                     # Configuration files
│   └── database.php           # Database connection
├── database/                   # Database scripts
│   └── medicare_plus.sql      # Database schema and sample data
├── doctor/                     # Doctor panel files
│   ├── dashboard.php          # Doctor dashboard
│   ├── appointments.php       # Manage appointments
│   ├── patients.php           # View patients
│   ├── get-patient-appointments.php  # AJAX endpoint
│   ├── medical-reports.php    # Reports overview
│   ├── add-medical-report.php # Upload new report
│   ├── edit-medical-report.php # Edit report
│   ├── patient-medical-reports.php # Patient's reports
│   ├── view-medical-report.php # View report details
│   ├── messages.php           # Doctor messaging
│   └── profile.php            # Doctor profile management
├── includes/                   # Shared PHP files
│   └── auth.php               # Authentication functions
├── patient/                    # Patient panel files
│   ├── dashboard.php          # Patient dashboard
│   ├── profile.php            # Patient profile management (NEW)
│   ├── book-appointment.php   # Book appointments
│   ├── messages.php           # Patient messaging
│   ├── reports.php            # View medical reports
│   ├── view-report.php        # Report details
│   └── review.php             # Rate and review doctors
├── uploads/                    # File uploads directory
│   └── medical_reports/       # Medical report files
├── index.php                   # Homepage
├── login.php                   # Login page
├── register.php                # Registration page
├── logout.php                  # Logout handler
├── doctors.php                 # Public doctor listing
├── doctor-profile.php          # Public doctor profile view
├── services.php                # Services listing
├── generate-hash.php           # Password hash generator utility
├── README.md                   # This file
├── SETUP.md                    # Quick setup guide
├── FEATURES.md                 # Complete features list
├── TEST-PLAN.md                # Testing documentation
├── TEST-CASES.md               # Detailed test cases
├── TESTING-OVERVIEW.md         # Testing overview
└── FEEDBACK-EVALUATION.md      # Project evaluation
```

## 🎯 User Roles & Permissions

### Patient
- Register and manage profile
- Update personal information (phone, address, date of birth, gender, blood group)
- Manage medical history and emergency contacts
- Change password securely
- Search and view doctors
- Book and manage appointments
- View medical reports
- Send messages to doctors
- Rate and review doctors

### Doctor
- View assigned appointments
- Update appointment status
- Upload medical reports
- Respond to patient messages
- View patient details

### Admin
- Full system access
- Add/edit/delete doctors
- Manage all appointments
- View all patients
- Manage services
- System oversight

## 🛠️ Key Features Implementation

### Authentication System
- Role-based access control (Admin, Doctor, Patient)
- Secure password hashing with PHP `password_hash()`
- Session management
- CSRF protection tokens

### Appointment System
- Time slot validation
- Conflict prevention (double booking)
- Status tracking (pending, confirmed, completed, cancelled)
- Appointment history

### Messaging System
- Secure communication between patients and doctors
- Read/unread status tracking
- Message history

### Rating System
- 5-star rating system
- Patient reviews and comments
- Automatic doctor rating calculation
- Review display on doctor profiles

### Search & Filter
- Doctor search by name or specialization
- Service filtering by category
- Real-time table search functionality

## 🔒 Security Features

- Password hashing using bcrypt
- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`
- CSRF token validation
- Session-based authentication
- Role-based access control

## 📱 Responsive Design

The application is fully responsive and works on:
- Desktop computers (1920px and above)
- Laptops (1366px - 1920px)
- Tablets (768px - 1366px)
- Mobile phones (320px - 768px)

## ✨ UI/UX Enhancements

### Smooth Animations & Transitions
The application features modern, smooth animations throughout:

- **Button Effects**:
  - Material Design-inspired ripple effect on click
  - Hover animations with lift and scale effects
  - Smooth color transitions
  - Glow overlay on hover

- **Card Interactions**:
  - Lift effect on hover (-5px with scale)
  - Enhanced shadow transitions
  - Smooth cubic-bezier timing functions

- **Navigation**:
  - Smooth scroll behavior for anchor links
  - Link underline animations expanding from center
  - Logo rotation effects (360° spin on hover)
  - Navigation link glow and lift

- **Form Elements**:
  - Input focus animations with subtle scale
  - Smooth border color transitions with glow
  - Checkbox/radio button hover effects

- **Tables**:
  - Row hover with lift and shadow
  - Smooth background color transitions
  - Enhanced visual feedback

- **Dashboard Cards**:
  - Stat cards with shimmer effects
  - Sliding light overlay on hover
  - Dramatic lift and shadow animations

- **Service Cards**:
  - Bottom border animation expanding from center
  - Icon rotation (360° with bounce effect)
  - Color transitions on hover

- **Alert Messages**:
  - Slide-in animations from top
  - Auto-fade after 5 seconds
  - Smooth opacity transitions

### Animation Features:
- **Smooth Scrolling**: Page-wide smooth scroll behavior
- **Page Load Optimization**: Prevents animation jank on initial load
- **Hardware Acceleration**: Uses transform and opacity for 60fps animations
- **Material Design**: Cubic-bezier timing functions for natural motion
- **Interactive Feedback**: Visual responses to all user interactions

### Performance:
- GPU-accelerated animations
- Efficient CSS transitions
- Optimized JavaScript for ripple effects
- No animation conflicts or jank

## 🎨 Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla JS)
  - Modern CSS animations and transitions
  - Smooth transformations with cubic-bezier timing functions
  - Ripple effects and interactive UI elements
  - Responsive design with flexbox and CSS Grid
- **Backend**: PHP 7.4+ (MySQLi extension)
- **Database**: MySQL 5.7+
- **Server**: Apache 2.4 (via XAMPP)
- **Architecture**: MVC-inspired structure
- **Security**: bcrypt password hashing, prepared statements, XSS protection
- **Session Management**: PHP native sessions
- **UI/UX**: Material Design-inspired components with smooth animations

## 📝 Usage Guide

### For Patients

1. **Register Account**
   - Click "Register" from homepage
   - Fill in personal details
   - Create username and password
   - Submit registration

2. **Update Profile**
   - Login to your account
   - Click "Update Profile" from dashboard
   - Update personal information:
     - Phone number (required)
     - Date of birth
     - Gender (Male/Female/Other)
     - Blood group (A+, A-, B+, B-, AB+, AB-, O+, O-)
     - Address
     - Emergency contact
     - Medical history and allergies
   - Change password if needed (minimum 6 characters)
   - Click "Update Profile" to save changes

3. **Book Appointment**
   - Login to your account
   - Browse "Our Doctors"
   - Select a doctor and click "Book Appointment"
   - Choose date and time
   - Provide reason for visit
   - Submit booking

4. **View Appointments**
   - Go to "My Dashboard"
   - See all appointments in "My Appointments" section
   - Cancel if needed (before confirmation)

5. **Rate Doctor**
   - After appointment is completed
   - Click "Review" button
   - Provide star rating and comment
   - Submit review

### For Doctors

1. **Login**
   - Use credentials provided by admin
   - Access doctor dashboard

2. **View Appointments**
   - Today's appointments displayed on dashboard
   - View upcoming appointments
   - Access patient details

3. **Manage Appointments**
   - Confirm pending appointments
   - Mark appointments as completed
   - View appointment history

4. **Medical Reports**
   - Upload test results for patients
   - Add prescriptions
   - Edit and manage reports

5. **View Patient Details**
   - Click on appointment to see patient info
   - Access medical history if available
   - View patient contact information

### For Administrators

1. **Add New Doctor**
   - Go to Admin Dashboard
   - Click "Add New Doctor" or "Doctors" → "Add Doctor"
   - Fill in doctor details:
     - Personal information (name, email, phone)
     - Professional details (specialization, qualification, experience)
     - Consultation fee
     - Create username and password
   - Submit to create doctor account

2. **Manage Doctors**
   - View all doctors
   - Edit doctor profiles
   - Update doctor information
   - Reset doctor passwords if needed

3. **Manage Patients**
   - View all registered patients
   - Edit patient information
   - Monitor patient activities

4. **Manage Services** (NEW)
   - Go to Admin Dashboard → Services
   - **Add New Service**:
     - Enter service name (required)
     - Select or create category (with autocomplete)
     - Add description
     - Set base price
     - Set duration in minutes
     - Toggle active/inactive status
   - **Edit Services**: Click "Edit" to modify existing services
   - **Delete Services**: Remove services with confirmation
   - **Toggle Status**: Quickly activate/deactivate services
   - All active services are displayed on the public Services page

5. **Monitor System**
   - View all appointments
   - Check system statistics
   - Oversee overall system health

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL service is running in XAMPP
- Check database credentials in `config/database.php`
- Ensure database `medicare_plus` exists
- Make sure you imported `database/medicare_plus.sql`

### Page Not Found (404)
- Check if files are in `C:\xampp\htdocs\medicare_plus\`
- Verify Apache is running in XAMPP Control Panel
- Correct URL format: `http://localhost/medicare_plus/`

### Login Issues
- Clear browser cache and cookies
- Verify database contains user data (check phpMyAdmin)
- Default admin credentials:
  - Username: `admin` or Email: `admin@medicareplus.com`
  - Password: `password`
- Ensure SQL file was imported successfully

### Styling Issues
- Clear browser cache (Ctrl + Shift + Delete)
- Check if CSS file path is correct
- Verify CSS file exists in `assets/css/style.css`
- Check browser console for 404 errors on CSS files

### File Upload Issues
- Ensure `uploads/` directory exists
- Check folder permissions (should be writable)
- Verify `uploads/medical_reports/` subdirectory exists

## 🔄 Future Enhancements

Potential features to add:
- 💳 Online payment integration (Stripe, PayPal)
- 📧 Email notifications for appointments
- 📱 SMS reminders
- 🎥 Video consultation capability
- 📝 Health blog and articles section
- 📊 Advanced analytics dashboard with charts
- 🌍 Multi-language support (i18n)
- 📲 Mobile application (iOS/Android)
- 🔔 Push notifications (browser and mobile)
- 📅 Calendar integration (Google Calendar, Outlook)
- 💊 Prescription management system
- 🧪 Lab test result integration
- 🔍 Advanced search with filters and autocomplete
- 📈 Doctor performance analytics
- 🗓️ Appointment reminders (24hr before)
- 💬 Real-time chat system (WebSocket)
- 📊 Patient health tracking dashboard
- 🎨 Theme customization (dark mode)
- 🔐 Two-factor authentication (2FA)
- 📱 PWA (Progressive Web App) support

## 📞 Support

For issues or questions:
1. Check this README file and SETUP.md
2. Review FEATURES.md for complete feature list
3. Check TEST-CASES.md for testing guidance
4. Review code comments in files
5. Check error logs in XAMPP:
   - Apache: `C:\xampp\apache\logs\error.log`
   - PHP: Check phpMyAdmin or configure in `php.ini`
6. Verify database structure in phpMyAdmin

### Common Questions

**Q: How do I add a new doctor?**
A: Login as admin, go to Admin Dashboard → Doctors → Add New Doctor

**Q: Can patients register themselves?**
A: Yes, patients can register using the registration page

**Q: How do I reset a password?**
A: Admin can reset passwords via admin panel, or use the generate-hash.php utility

**Q: Where are uploaded files stored?**
A: In the `uploads/medical_reports/` directory

**Q: How do I change the database credentials?**
A: Edit `config/database.php` file

## 📄 License

This project is created for educational purposes as part of a web development assignment.

## 👥 Credits

Developed as a comprehensive healthcare management solution for MediCare Plus.

## 🔧 Utility Files

### Password Hash Generator
The project includes a utility file `generate-hash.php` to generate secure password hashes:

1. Navigate to `http://localhost/medicare_plus/generate-hash.php`
2. Enter the password you want to hash
3. Copy the generated hash
4. Use it in database or update queries

**Usage Example:**
```php
// To manually create a user with hashed password
INSERT INTO users (username, email, password, role) 
VALUES ('newuser', 'user@example.com', 'GENERATED_HASH_HERE', 'patient');
```

## 📚 Additional Documentation

This project includes comprehensive documentation:

- **README.md** - Main documentation (this file)
- **SETUP.md** - Quick 5-minute setup guide
- **FEATURES.md** - Complete list of all implemented features
- **TEST-PLAN.md** - Testing strategy and methodology
- **TEST-CASES.md** - Detailed test cases for all features
- **TESTING-OVERVIEW.md** - Testing summary and results
- **FEEDBACK-EVALUATION.md** - Project evaluation and feedback

## 🔒 Security Best Practices

### Implemented Security Measures:
1. ✅ **Password Hashing** - Using PHP's `password_hash()` with bcrypt
2. ✅ **SQL Injection Prevention** - Prepared statements with MySQLi
3. ✅ **XSS Protection** - `htmlspecialchars()` on all user outputs
4. ✅ **Session Security** - Secure session management with validation
5. ✅ **Role-Based Access Control** - Permission checks on all pages
6. ✅ **Input Validation** - Server-side validation for all forms
7. ✅ **CSRF Protection** - Token validation on sensitive operations

### Recommended for Production:
- ⚠️ Enable HTTPS/SSL certificate
- ⚠️ Move uploads folder outside web root
- ⚠️ Implement rate limiting on login
- ⚠️ Add email verification for registration
- ⚠️ Enable detailed error logging
- ⚠️ Set up regular database backups
- ⚠️ Use environment variables for sensitive config
- ⚠️ Implement two-factor authentication (2FA)

## 🧪 File Upload Guidelines

### Medical Reports Upload
- **Location**: `uploads/medical_reports/`
- **Allowed Types**: PDF, JPG, PNG, DOCX
- **Max Size**: Configure in `php.ini` (default: 2MB)
- **Security**: Files should be validated before upload

### Creating Upload Directories
If the uploads folder is missing:
```bash
# In project root directory
mkdir uploads
mkdir uploads\medical_reports
```

### Setting Permissions (if needed)
Ensure the uploads directory is writable by the web server.

## 💻 Browser Compatibility

| Browser | Minimum Version | Status |
|---------|----------------|--------|
| Google Chrome | 90+ | ✅ Fully Supported |
| Mozilla Firefox | 88+ | ✅ Fully Supported |
| Microsoft Edge | 90+ | ✅ Fully Supported |
| Safari | 14+ | ✅ Fully Supported |
| Opera | 76+ | ✅ Fully Supported |
| Internet Explorer | - | ❌ Not Supported |

## 🤝 Contributing

This is an educational project. If you want to improve it:

1. Test thoroughly before making changes
2. Follow existing code style and structure
3. Update documentation when adding features
4. Add comments to complex logic
5. Ensure security best practices are maintained

## ⚠️ Known Limitations

1. **No Email System** - Currently no email notifications (can be added)
2. **No Payment Gateway** - Consultation fees are displayed but not processed
3. **Single Time Zone** - No timezone support for appointments
4. **No Calendar View** - Appointments shown in table format only
5. **Basic Search** - Simple search, no advanced filtering
6. **No API** - No RESTful API for mobile apps
7. **Limited File Types** - Only specific formats for medical reports
8. **No Backup System** - Manual database backup required

## 🛠️ Development Notes

### Database Connection
- Uses MySQLi extension (not PDO)
- Connection function in `config/database.php`
- Always close connections after use

### Session Management
- Sessions initialized in `includes/auth.php`
- Role-based access checks on protected pages
- Session timeout: Default PHP settings

### Code Structure
- Procedural PHP (not object-oriented)
- Include `config/database.php` for DB access
- Include `includes/auth.php` for authentication
- Separate folders for each user role

## 📊 Project Statistics

- **Total PHP Files**: 35+
- **Total Lines of Code**: ~6000+
- **CSS Lines**: ~650+ (with animations)
- **JavaScript Lines**: ~200+
- **Database Tables**: 10
- **User Roles**: 3 (Admin, Doctor, Patient)
- **Core Features**: 11
- **Admin Management Pages**: 7
- **Animation Effects**: 15+ keyframes
- **Security Features**: 8
- **Documentation Files**: 7
- **Utility Classes**: 20+
- **Responsive Breakpoints**: 4

## 🔍 Testing

Refer to the testing documentation:
- See `TEST-PLAN.md` for testing strategy
- See `TEST-CASES.md` for detailed test scenarios
- See `TESTING-OVERVIEW.md` for testing summary

### Quick Test Checklist:
- [ ] Database imported successfully
- [ ] Admin login works
- [ ] Patient registration works
- [ ] Doctor can be added by admin
- [ ] Services can be managed (add/edit/delete)
- [ ] Appointments can be booked
- [ ] Messages can be sent
- [ ] Medical reports can be uploaded
- [ ] Reviews can be submitted
- [ ] Search and filter work
- [ ] All pages load without errors
- [ ] Animations and transitions work smoothly
- [ ] Responsive design works on mobile/tablet
- [ ] No console errors in browser

## 📌 Version Information

- **Version**: 1.1.0
- **Release Date**: 2025
- **Last Updated**: January 2025
- **PHP Version Required**: 7.4+
- **MySQL Version Required**: 5.7+
- **Browser Compatibility**: Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- **License**: Educational/Academic Use

### Changelog

#### Version 1.1.0 (January 2025)
- ✅ Added Patient Profile Management page with full update capabilities
- ✅ Added Admin Services Management (CRUD operations)
- ✅ Implemented smooth CSS animations and transitions
- ✅ Added Material Design-inspired ripple effects
- ✅ Enhanced button hover and click effects
- ✅ Added card lift and shadow animations
- ✅ Implemented navigation underline animations
- ✅ Added logo rotation effects
- ✅ Enhanced form input focus animations
- ✅ Added table row hover effects
- ✅ Implemented stat card shimmer effects
- ✅ Added service icon rotation animations
- ✅ Enhanced alert slide-in animations
- ✅ Optimized for 60fps performance
- ✅ Added GPU-accelerated transforms
- ✅ Implemented preload animation prevention
- ✅ Added smooth scroll behavior
- ✅ Extended utility CSS classes

#### Version 1.0.0 (2024)
- Initial release with core features

---

## 👨‍⚕️ About MediCare Plus

MediCare Plus is a comprehensive healthcare management system designed to:
- Streamline patient-doctor interactions
- Simplify appointment scheduling
- Provide secure medical record storage
- Enable efficient communication
- Deliver role-based management capabilities

**Built for**: Educational purposes, web development learning, and as a foundation for healthcare projects.

**Target Users**: Hospitals, clinics, medical centers, and healthcare providers looking for a simple, effective management system.

---

**Note**: This is a demo application. For production use, implement additional security measures, data validation, and error handling.
