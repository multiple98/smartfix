# 📧 How to Verify Your Email - SmartFix

## 🤔 Why Am I Not Receiving Verification Emails?

The system is currently in **development mode** to avoid mail server configuration issues. Your verification emails are being **logged locally** instead of sent to your actual email address.

## ✅ How to Access Your Verification Email

### Step 1: Register Your Account
1. Go to: `http://localhost/smartfix/register.php`
2. Fill out the registration form
3. Click "Register"
4. You'll see: "Registration successful! Please check your email..."

### Step 2: Access the Development Email Viewer
1. Go to: `http://localhost/smartfix/dev_email_viewer.php`
2. You'll see your verification email details
3. Look for your email address in the list
4. Click the "Verify Now" button next to your account

### Step 3: Complete Verification
1. Clicking "Verify Now" will verify your account
2. You'll see: "Email verified successfully! You can now log in."
3. Now you can login at: `http://localhost/smartfix/login.php`

## 🔍 Alternative Method - Check Debug Log

If you want to see the actual email content:

1. Go to: `http://localhost/smartfix/dev_email_viewer.php`
2. Look at the "Recent Email Debug Log" section
3. Find your verification URL in the log
4. Copy and paste the verification URL into your browser

## 🛠️ For Production Use (Optional)

If you want to receive actual emails, you need to configure SMTP:

### Option 1: Use Gmail SMTP
Add to your `php.ini` file:
```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@gmail.com
```

### Option 2: Use XAMPP Mercury Mail
1. Open XAMPP Control Panel
2. Start Mercury Mail Server
3. Configure SMTP settings

### Option 3: Keep Debug Mode (Recommended for Development)
- No configuration needed
- Works perfectly for testing
- Manual verification available
- No external dependencies

## 🎯 Quick Access Links

- **Register:** http://localhost/smartfix/register.php
- **View Verification Emails:** http://localhost/smartfix/dev_email_viewer.php
- **Login:** http://localhost/smartfix/login.php
- **Home:** http://localhost/smartfix/index.php

## 📞 Need Help?

The system is working perfectly - you just need to use the development email viewer to access your verification links instead of checking your actual email inbox.

---

**Remember: In development mode, verification emails go to the debug viewer, not your actual email!**