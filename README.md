# Study Buddy Finder

A modern web application that helps students connect with compatible study partners based on their major, subject, and study preferences.

---

## **Features**

   **User Authentication** - Secure registration and login system  
   **Smart Matching** - Find study partners in your major and subject  
   **Study Requests** - Send and receive partnership requests  
   **Confirmed Pairings** - Calendar view of active study sessions  
   **Profile Management** - Update your info and study preferences  
   **In-App Notifications** - See requests, responses, cancellations, and new messages  
   **Group Chat Support** - Create groups and chat with teammates  
   **Meeting Cancellation** - Notify partner when cancelling sessions  

---

## **System Requirements**

| Component | Version |
|-----------|---------|
| PHP | 7.4 or higher |
| MySQL | 5.7 or higher |
| Apache | Any recent version |
| Browser | Chrome, Firefox, Safari, or Edge |

**Recommended**: Use XAMPP (includes Apache, PHP, MySQL)

---

## **Quick Start (5 Minutes)**

### **Step 1: Setup Database**

```
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "New" → Create database "studybuddy"
3. Leave it empty - tables are auto-created!
```

### **Step 2: Install Files**

Place all project files in XAMPP's web directory:

- **macOS**: `/Applications/XAMPP/xamppfiles/htdocs/studybuddy/`
- **Windows**: `C:\xampp\htdocs\studybuddy\`
- **Linux**: `/opt/lampp/htdocs/studybuddy/`

### **Step 3: Verify Database Connection**

Edit `db.php` and ensure these match your setup:

```php
$host = "localhost";    // Your database host
$user = "root";         // Your database username
$pass = "";             // Your database password
$dbname = "studybuddy"; // Database name
```

### **Step 4: Start XAMPP & Access the App**

```bash
# Start XAMPP (Mac)
sudo /Applications/XAMPP/xamppfiles/xampp start

# Or Windows: Click "Start" in XAMPP Control Panel
```

Then visit: **http://localhost/studybuddy**

---

## **File Structure & Purpose**

### **Frontend (HTML Pages)**

| File | Purpose |
|------|---------|
| `index.php` | Home page - login/register buttons |
| `login.php` | Login form with error messages |
| `register.php` | Registration form - create new account |
| `welcome.php` | Dashboard - see partners & requests |
| `edit_profile.php` | Update profile information |

### **Backend (PHP Processing)**

| File | Purpose |
|------|---------|
| `db.php` | Database connection & table creation |
| `process_login.php` | Verify username/password |
| `process_register.php` | Create new user account |
| `send_request.php` | Send study partnership request |
| `respond_request.php` | Accept/decline a request |
| `cancel_pairing.php` | Cancel confirmed pairing |
| `logout.php` | End user session |

### **Styling**

| File | Purpose |
|------|---------|
| `style.css` | Modern UI styling & animations |

---

## **Database Tables**

### **Students Table**
Stores user account information:
```
StudentID (Primary Key)
Username (Unique)
Password (Hashed)
StudentName
Major
Subject
PreferredStudyTime
ContactInfo (Email)
Bio
CreatedAt (Timestamp)
```

### **StudyRequests Table**
Tracks all partnership requests:
```
RequestID (Primary Key)
RequesterID (Student who sent request)
ReceiverID (Student who receives request)
Status (pending / accepted / declined)
Message (Optional notes)
CreatedAt (Timestamp)
```

### **Pairings Table**
Confirmed study partnerships:
```
PairingID (Primary Key)
StudentID1 (Partner 1)
StudentID2 (Partner 2)
CreatedAt (Timestamp)
```
### **Groups Table**
Stores study group rooms and their subject:
```
GroupID (Primary Key)
GroupName
Subject
CreatedAt (Timestamp)
```

### **GroupMembers Table**
Tracks members inside each group:
```
MemberID (Primary Key)
GroupID (FK to Groups)
StudentID (FK to Students)
JoinedAt (Timestamp)
```

### **Notifications Table**
Stores all in-app notifications for users:
```
NotificationID (Primary Key)
UserID (Recipient student)
ActorID (Triggering student)
Type (request_sent, request_accepted, request_declined, pairing_cancelled, new_message, group_message)
ItemID (Related record ID)
Message
IsRead (0/1)
CreatedAt (Timestamp)
```
---

## **How It Works**

### **1. User Registration** → `register.php` → `process_register.php`
- User creates account with username, password, major, subject
- Password is hashed for security
- Account stored in Students table

### **2. User Login** → `login.php` → `process_login.php`
- User enters username and password
- System verifies credentials
- Session created for authenticated user

### **3. Finding Partners** → `welcome.php`
- Dashboard shows all students studying same subject
- Filters available for major and subject selection
- Real-time subject dropdown updates

### **4. Sending Request** → `send_request.php`
- Student clicks "Send Request" on another student
- Creates entry in StudyRequests table with Status = "pending"
- Receiver sees notification on their dashboard

### **5. Responding to Request** → `respond_request.php`
- Student accepts or declines incoming request
- If accepted: Creates Pairing (shows in calendar)
- If declined: Request marked as declined

### **6. Calendar/Schedule** → `welcome.php`
- Shows "Next Study Session" prominently
- Lists all confirmed pairings
- Can cancel sessions anytime

### **7. Cancelling Session** → `cancel_pairing.php`
- Removes pairing from calendar
- Notifies other student via declined request notification
- Both students see cancellation alert
### **8. Group Creation & Group Chat** → `create_group.php`, `group_message.php`
- Start a new study group with a partner
- Add more teammates to the group after creation
- Share messages with all group members in one room
- Group members receive notifications for new group messages
---

## **UI/UX Features**

  **Modern Design**
- Purple gradient theme
- Smooth animations & hover effects
- Responsive mobile layout

  **Clear Navigation**
- Consistent header across pages
- Easy-to-find buttons and forms
- Visual feedback for all actions

  **Mobile Friendly**
- Works on phones, tablets, desktops
- Touch-friendly buttons
- Readable text on all screen sizes

---

## **Security Features**

  **Password Security**
- Passwords hashed using PHP's `password_hash()`
- Never stored as plain text
- Verified using `password_verify()`

  **SQL Injection Prevention**
- All database queries use prepared statements
- User input safely bound with `bind_param()`

  **Session Security**
- User must be logged in (session check on protected pages)
- Session destroyed on logout
- Unauthorized access redirected to login

---

## **Testing the App**

### **Test Scenario 1: Create Account & Login**
```
1. Go to http://localhost/studybuddy
2. Click "Create Account"
3. Fill in form:
   - Username: john_doe
   - Password: test123
   - Full Name: John Doe
   - Major: Computer Science
   - Subject: Data Structures
   - Time: Morning
   - Email: john@example.com
4. Click "Create Account"
5. Login with john_doe / test123
```

### **Test Scenario 2: Generate Test Data**
```
1. Go to http://localhost/studybuddy/debug.php
2. Click "Add 100 Test Users"
3. Test usernames: user010 through user99xx
4. Test password: password123
```

### **Test Scenario 3: Send & Accept Request**
```
1. Login as user1
2. See available partners
3. Click "Send Request" on user2
4. Logout (or new browser)
5. Login as user2
6. See pending request from user1
7. Click "Accept"
8. Both see each other in calendar
```

### **Test Scenario 4: Cancel Meeting**
```
1. From any user's calendar
2. Click "Cancel Session" under a partner
3. Both users see cancellation notification
4. Pairing removed from both calendars
```

---

## **Troubleshooting**

### **"Connection failed" Error**
**Problem**: Cannot connect to database  
**Solution**:
- Check if MySQL is running
- Verify `db.php` credentials match your setup
- Ensure database name is `studybuddy`

### **"No study partners found"**
**Problem**: Filter shows no results  
**Solution**:
- Add test users via `debug.php`
- Ensure you filtered by correct major/subject
- Check that other users have same major/subject

### **Can't create account - "Username already exists"**
**Problem**: Username is taken  
**Solution**:
- Choose a different username
- Usernames must be unique

### **"Session expired" Error**
**Problem**: Log out unexpectedly  
**Solution**:
- This is normal after browser/server restart
- Simply login again
- Session data saved in `php.ini` settings

---

## **Customization Guide**

### **Change Available Majors/Subjects**

Edit `register.php`, `welcome.php`, and `edit_profile.php`:
```javascript
// Find this in the <script> section:
const subjectData = {
    'Computer Science': ['Calculus', 'Data Structures', ...],
    'Mathematics': ['Algebra 1', 'Calculus 1', ...],
    'English': ['Composition', 'Literature', ...]
};

// Modify to add your own majors and subjects
```

### **Change Color Scheme**

Edit `style.css`:
```css
/* Current purple theme - change these colors */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* To use blue theme instead */
background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
```

### **Add New Fields to Student Profile**

1. Add column to `Students` table in `db.php`
2. Add field to `register.php` form
3. Add field to `edit_profile.php` form
4. Update `process_register.php` to save new field
5. Update `edit_profile.php` to save on update

---

## **Support & Maintenance**

### **Regular Maintenance**
- Check database size monthly (via phpMyAdmin)
- Clean up old declined requests (optional)
- Monitor user feedback

### **Common Issues to Watch**
- Database running out of space
- Too many active users causing slowness
- Outdated PHP/MySQL versions

### **Backup Your Data**
```bash
# Export database from phpMyAdmin
1. Go to phpMyAdmin
2. Select "studybuddy" database
3. Click "Export" tab
4. Download SQL file
```

---

## **License & Usage**

This is an educational project. Feel free to:
-  Use for learning
-  Modify for your needs
-  Run in school/university
-  Share with others (with credit)

---

## **Future Enhancements**

Possible improvements:
- Email notifications for new requests
- User ratings/reviews system
- Meeting scheduling calendar
- Chat between study partners
- Advanced search filters
- User profiles with photos
- Admin dashboard

---

## **FAQ**

**Q: Can multiple users login simultaneously?**  
A: Yes! Each user gets their own session. Open in different browsers to test.

**Q: What happens if both users cancel at same time?**  
A: Both cancellations are handled independently. Both see notification.

**Q: Can users message each other?**  
A: Yes — the app now supports direct chats between paired study partners.
   - Chats: `/message.php` lists your partners and opens a separate thread per partner.
   - Messages are stored with `SenderID`/`ReceiverID` for reliable history.
   - Only users who are paired (or have messaging history) appear in your chat list.
   - New message notifications are created and shown in the dashboard.

**Q: Can users create study groups?**  
A: Yes — the app supports group creation and group chat.
   - Create a group from the dashboard with the **"Add to Group"** button.
   - The new group opens in `group_message.php`.
   - Group members can be added and all members receive group message notifications.

**Q: How long do sessions last?**  
A: Default is 24 minutes (PHP `session.gc_maxlifetime`). Can be changed in `php.ini`

**Q: Is this production-ready?**  
A: It's great for learning! For production, add email verification, rate limiting, and monitoring.

---

## **Learning Resources**

- [PHP Official Documentation](https://www.php.net/manual/)
- [MySQL Basics](https://dev.mysql.com/doc/)
- [Web Security Best Practices](https://owasp.org/)
- [HTML/CSS Guide](https://developer.mozilla.org/en-US/docs/Learn)

---

**Happy studying!**
   - Go to `http://localhost/studybuddy/`
   - Click **"Login to Your Account"**
   - Enter your username and password
   - Click **"Login"**

### 3. **Dashboard Overview** (welcome.php)
   After login, you'll see:

   **Your Profile**
   - Shows your current major, subject, and study time/option botton in session for study another major asside to the major in the registration then prompt to the other user
   - Click **"Edit Profile"** to make changes

   **Active Study Partnerships**
   - Lists students you're currently paired with
   - Shows their contact info and study schedule
   --Create an mocks users to pairs(100)

   **Study Requests**
   - Shows incoming partnership requests
   - **Accept** - Creates a partnership
   - **Decline** - Rejects the request

   **Available Study Partners**
   - Shows other students in your major/subject
   - Click **"Send Study Request"** to reach out
   - Include an optional message explaining why you want to study together

### 4. **Edit Your Profile**
   - Click **"Edit Profile"** button
   - Update any of your information
   - **Optional**: Change your password
   - Click **"Save Changes"**

### 5. **Send Study Request**
   - Browse available partners on the dashboard
   - Click **"Send Study Request"** on any partner card
   - Add an optional message
   - Click **"Send Request"**

### 6. **Respond to Requests**
   - Incoming requests appear in the **"Study Requests"** section
   - Click **"Accept"** to create a partnership
   - Click **"Decline"** to reject

### 7. **Logout**
   - Click **"Logout"** in the top right
   - You'll be redirected to the home page

---

## Database Schema

### Students Table
```
StudentID          - Unique identifier
Username           - Unique username
Password           - Hashed password
StudentName        - Full name
Major              - Degree program
Subject            - Primary study subject
Availability       - Available date
PreferredStudyTime - Morning/Afternoon/Evening/Night/Flexible
ContactInfo        - Email address
Bio                - User description
Status             - 'available' or other status
PairedWith         - Link to paired student (unused in current version)
CreatedAt          - Account creation timestamp
UpdatedAt          - Last update timestamp
```

### StudyRequests Table
```
RequestID   - Unique identifier
RequesterID - Student sending request
ReceiverID  - Student receiving request
Status      - 'pending', 'accepted', or 'declined'
Message     - Optional message with request
CreatedAt   - Request timestamp
RespondedAt - Response timestamp
```

### Pairings Table
```
PairingID   - Unique identifier
StudentID1  - First paired student
StudentID2  - Second paired student
Status      - 'active' or 'ended'
CreatedAt   - Pairing creation timestamp
EndedAt     - Pairing end timestamp
```

---

## Troubleshooting

### "Connection failed" Error
- **Solution**: Check your MySQL credentials in `db.php`
- Ensure MySQL service is running in XAMPP Control Panel

### "Table doesn't exist" Error
- **Solution**: The tables auto-create on first run. Refresh the page.
- Or delete and recreate the database

### "Username already exists" Error
- **Solution**: Choose a different username - usernames must be unique

### "No study partners available" Message
- **Solution**: 
  - Create multiple test accounts with the SAME major and subject
  - They will appear as available partners to each other

### Passwords Not Working After Upgrade
- **Cause**: Old passwords in DB may be plain text
- **Solution**: 
  - Re-register accounts with the new system
  - Or update old passwords manually using phpMyAdmin

### Request Modal Not Opening
- **Solution**: Clear browser cache and refresh page
- Check browser console for JavaScript errors (F12)

---

## Testing the Application

### Quick Test Scenario

1. **Create Account 1**
   - Username: `student1`
   - Major: `Computer Science`
   - Subject: `Computer Science`
   - Study Time: `Afternoon`

2. **Create Account 2** (Open in another browser/tab)
   - Username: `student2`
   - Major: `Computer Science`
   - Subject: `Computer Science`
   - Study Time: `Evening`

3. **Send Request**
   - Login as `student1`
   - Find `student2` in available partners
   - Click "Send Study Request"
   - Add message: "Hi! Want to study together?"

4. **Accept Request**
   - Login as `student2` (different browser tab)
   - See request from `student1`
   - Click "Accept"

5. **Verify Partnership**
   - Both students now see each other in "Active Study Partnerships"

---

## Security Notes

- All passwords are hashed using PHP's `password_hash()` function
- All user inputs are validated and escaped
- SQL injection is prevented using prepared statements
- HTML output is escaped using `htmlspecialchars()`

---

## Future Enhancements

Potential features to add:
- [x] In-app messaging system
- [ ] Review/rating system for partners
- [ ] Advanced group management and group approvals
- [ ] Advanced search filters
- [ ] Study session scheduling
- [ ] Admin dashboard
- [ ] Analytics & statistics

---

## Support & Debugging

### Debug Database
Open `http://localhost/studybuddy/debug.php` to view:
- Database table structure
- Sample data from Students table

### Check Session Data
Add this to any PHP file to see logged-in user data:
```php
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
```

---

## License

This project is open source and available for educational purposes.

---

## Author Notes

**Study Buddy Finder** was built to help students connect efficiently based on:
- Same academic major
- Same study subject
- Compatible study schedules
- Student goals and availability

Thank you for using Study Buddy Finder! 🎓
