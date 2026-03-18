# 🗄️ Database Setup Guide - Step by Step
**For Beginners with Contabo VPS + Virtualmin**

---

## What is a Database?

Think of it like a digital filing cabinet:
- **Database** = The whole cabinet
- **Tables** = Drawers in the cabinet
- **Records** = Individual files in each drawer

The Payment Hub needs a database to store:
- Transactions (payments made)
- Websites (CMS sites using the hub)
- Webhooks (payment confirmations)
- Admin users (login credentials)

---

## 📋 What You Need to Collect

By the end of this guide, you'll have 4 values to put in your `.env` file:

```env
DB_HOST=???          # Where the database lives (server address)
DB_USER=???          # Username to access the database
DB_PASS=???          # Password for that username
DB_NAME=???          # Name of your database
```

---

## ✅ Step 1: Access Your Virtualmin/Webmin Panel

**You already have login credentials from your PDF** (the one Temboh shared with you)

### Option A: Access via Browser (Easiest)

1. Open your web browser
2. Go to: `https://your_vps_ip:10000`
   - Replace `your_vps_ip` with your actual VPS IP (e.g., `203.123.45.67`)
   - Example: `https://203.123.45.67:10000`

3. You should see a login screen that looks like this:
   ```
   Virtualmin / Webmin Login
   Username: [_________]
   Password: [_________]
   ```

4. Enter credentials from your PDF
5. Click Login

---

## 📍 Step 2: Navigate to MySQL Management

Once logged in, you'll see a dashboard:

### Left Sidebar Menu Path:
```
Servers
  ↓
MySQL Database Server
```

**Click on "MySQL Database Server"** (or sometimes it says "MySQL Databases")

You should see a screen showing:
- "Create a new database"
- "Create a new user"
- List of existing databases

---

## 🆕 Step 3: Create a New Database

### On the MySQL Database Server page:

1. Look for a button or link that says:
   - **"Create a new database"** OR
   - **"New Database"**

2. Click it

3. A form will appear with fields:

   ```
   Database name: [____________]
   ```

4. Enter the database name:
   ```
   payment_hub
   ```

5. Click **"Create Database"**

🎉 **First credential found!**
```env
DB_NAME=payment_hub
```

---

## 👤 Step 4: Create a MySQL User

Now you need to create a username and password to access this database.

### Still on the MySQL Database Server page:

1. Find the button/link that says:
   - **"Create a new user"** OR  
   - **"New MySQL User"**

2. Click it

3. A form appears with:
   ```
   Username: [________________________]
   Password: [________________________]
   Password again: [________________________]  
   ```

4. Fill in the fields:
   - **Username**: `payment_hub_user`
   - **Password**: `Create_Strong_Password_123!`
     - Use something secure! Example: `Pw@yHubSecure2024!`
     - Write it down somewhere safe (or use a password manager)

5. Make note of this password:
   ```env
   DB_USER=payment_hub_user
   DB_PASS=Pw@yHubSecure2024!   # <-- Your password
   ```

6. Click **"Create User"**

🎉 **Second and third credentials found!**

---

## 🔗 Step 5: Grant User Access to Database

The user needs **permission** to access the database you created.

### Back on MySQL Database Server page:

1. You should see a list of databases
2. Click on **"payment_hub"** (your database)
3. You'll see an option like:
   - **"Grant a privilege to a user"** OR
   - **"Add User to Database"**

4. Click it

5. Select/enter:
   - **User**: `payment_hub_user`
   - **Permissions**: Select ALL (or at minimum: SELECT, INSERT, UPDATE, DELETE)

6. Click **"Grant Privilege"** or **"Add User"**

---

## 🖥️ Step 6: Find Your DB_HOST

The **DB_HOST** is the address where your MySQL server is located.

### For Contabo VPS, it's almost always one of these:

```env
DB_HOST=localhost
```

OR if that doesn't work, use:

```env
DB_HOST=127.0.0.1
```

Usually it's `localhost` for servers running on the same machine.

---

## 📝 Step 7: Verify Via Terminal (Optional but Recommended)

Let's test that everything works by connecting to MySQL directly.

### SSH into your VPS:

```bash
ssh root@your_vps_ip
```

### Test the connection:

```bash
mysql -h localhost -u payment_hub_user -p payment_hub
```

**What happens:**
1. Type the command above
2. Press Enter
3. It will ask: `Enter password:`
4. Type your password (the one you created)
5. If successful, you'll see:
   ```
   mysql> 
   ```

If you see `mysql>` prompt, **everything works!**

Type `exit` to quit:
```bash
exit
```

---

## 📋 Complete Your .env File

Now you have all 4 values! Fill them in:

```env
# DATABASE CONFIGURATION
DB_HOST=localhost
DB_USER=payment_hub_user
DB_PASS=Pw@yHubSecure2024!
DB_NAME=payment_hub
```

Save this in a file called `.env` in your project root directory.

---

## 🚀 Step 8: Import Database Tables

The database is empty. You need to import the pre-built table structure.

### SSH into your VPS:

```bash
cd /path/to/your/project
mysql -u payment_hub_user -p payment_hub < app/Database/schema.sql
```

**What this does:**
- Reads the `schema.sql` file (which has all your table definitions)
- Imports them into your `payment_hub` database
- When prompted, enter your password: `Pw@yHubSecure2024!`

**If successful, no error message appears!**

---

## ✅ Verify Tables Were Created

Still SSH'd in, check if tables exist:

```bash
mysql -u payment_hub_user -p payment_hub
```

Enter your password. Then type:

```sql
SHOW TABLES;
```

You should see:
```
+-----------------------+
| Tables_in_payment_hub |
+-----------------------+
| admin_users           |
| payment_locks         |
| transaction_logs      |
| transactions          |
| websites              |
| webhook_events        |
+-----------------------+
6 rows in set
```

Perfect! ✅ All tables created successfully!

Type `exit` to leave MySQL:
```bash
exit
```

---

## 🎯 Final .env File

Your complete `.env` file should look like:

```env
# ================================================
# APPLICATION ENVIRONMENT
# ================================================
APP_ENV=production
APP_DEBUG=false
APP_DOMAIN=https://pay.pivotpointinv.com

# ================================================
# DATABASE CONFIGURATION
# ================================================
DB_HOST=localhost
DB_USER=payment_hub_user
DB_PASS=Pw@yHubSecure2024!
DB_NAME=payment_hub

# ================================================
# PAWAPAY CONFIGURATION
# ================================================
PAWAPAY_API_TOKEN=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ...  # Your token from earlier
PAWAPAY_API_URL=https://sandbox.pawapay.io
PAWAPAY_MERCHANT_ID=303

# ================================================
# SECURITY CONFIGURATION
# ================================================
FORCE_HTTPS=true

# ================================================
# SESSION CONFIGURATION
# ================================================
SESSION_NAME=ph_admin_session
SESSION_TIMEOUT=3600

# ================================================
# RATE LIMITING
# ================================================
RATE_LIMIT_ENABLED=true
RATE_LIMIT_ATTEMPTS=100
RATE_LIMIT_WINDOW=3600
```

---

## 🧪 Test Your Connection

Create a simple test file to verify everything works:

```bash
# In your project root:
cat > test_db.php << 'EOF'
<?php
require_once 'app/Config/config.php';
require_once 'app/Core/Database.php';

try {
    $db = Database::getInstance();
    echo "✅ Database connection successful!\n";
    
    $result = $db->selectOne("SELECT COUNT(*) as count FROM websites");
    echo "✅ Tables exist! Found " . $result['count'] . " websites\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
EOF
```

Run it:
```bash
php test_db.php
```

You should see:
```
✅ Database connection successful!
✅ Tables exist! Found 0 websites
```

---

## 🐛 Troubleshooting

### "Access denied for user 'payment_hub_user'"
- Check your password is correct
- Make sure user is granted permission to the database
- Try: `GRANT ALL PRIVILEGES ON payment_hub.* TO 'payment_hub_user'@'localhost';`

### "Unknown database 'payment_hub'"
- Make sure you created the database
- Check spelling (payment_hub, not payment-hub)

### "Can't connect to MySQL server on 'localhost'"
- MySQL service might not be running
- Try: `systemctl status mysql`
- Or restart: `systemctl restart mysql`

### "No such file or directory" when importing schema
- Make sure `app/Database/schema.sql` exists
- Check path is correct from where you're running the command

---

## 💾 Safe Password Storage

⚠️ **Important**: Do NOT commit `.env` to Git!

```bash
# Add to .gitignore
echo ".env" >> .gitignore
git add .gitignore
git commit -m "Add .env to gitignore"
```

---

## 🎓 Quick Reference

| Term | What is it? | Example |
|------|-----------|---------|
| **DB_HOST** | Where database lives | `localhost` |
| **DB_USER** | Username to access DB | `payment_hub_user` |
| **DB_PASS** | Password for that user | `Pw@yHubSecure2024!` |
| **DB_NAME** | Name of your database | `payment_hub` |

---

## ✨ You're Done!

Your database is ready. You now have:
- ✅ Database created (`payment_hub`)
- ✅ User created with password
- ✅ Tables imported
- ✅ 4 credentials for `.env` file

**Next**: Update the remaining values in `.env` (PawaPay token, domain, etc.) and test your payment hub! 🚀
