# Complete Guide: Deploy to Render + Railway

**Timeline:** ~15-20 minutes total  
**Date:** March 17, 2026  
**Status:** Step-by-step deployment guide  
**Database:** Railway MySQL (Free tier)  
**Backend:** Render (Free tier)  

---

## 🎯 Overview

This guide walks you through deploying your Payment Hub backend to Render with Railway MySQL database - completely free, no credit card required for initial testing.

### Final Setup:
```
Railway (Hosted MySQL)
        ↓
Render (PHP Backend)
        ↓
Your Application
```

**Cost:** $0/month (both free tiers) ✅

---

# STEP 1: Railway MySQL Setup (5 minutes)

## 1.1 Create Railway Account

1. **Go to:** https://railway.app
2. **Click:** "Sign Up" (top right)
3. **Choose:** "Sign up with GitHub" (easiest) OR email
4. **Complete signup and verify email**

✅ **Account created**

---

## 1.2 Create New Project

1. **In Railway dashboard**, click **"+ New Project"**
2. **Select:** "MySQL"
3. **Click:** "Deploy"

⏳ Wait 30-60 seconds for database to initialize

✅ **MySQL database created**

---

## 1.3 Get Connection Credentials

After deployment, you'll see the database panel. Click on it to view credentials.

**Look for these environment variables:**

```
MYSQLHOST
MYSQLUSER
MYSQLPASSWORD
MYSQLDATABASE
MYSQLPORT
```

### Copy These:

1. **Click on the MySQL instance** (in your Railway project)
2. **Go to "Connect" tab**
3. **Look for the values:**
   - `MYSQLHOST` → This is your host
   - `MYSQLUSER` → Username
   - `MYSQLPASSWORD` → Password
   - `MYSQLDATABASE` → Database name (should default to `railway`)
   - `MYSQLPORT` → Port (usually 3306)

📋 **Copy all 5 values somewhere safe.** You'll need them soon.

**Example:**
```
Host:     mysql.railway.internal
User:     root
Password: xxxxxxxxxxxxx
Database: railway
Port:     3306
```

✅ **Credentials obtained**

---

## 1.4 Quick Verification

In Railway dashboard:
1. **Click on MySQL instance**
2. **Go to "Logs" tab**
3. You should see MySQL startup messages
4. If you see errors, wait 1-2 minutes and reload

✅ **Database is ready**

---

# STEP 2: Import Database Schema (3 minutes)

Now create your tables in Railway MySQL.

## 2.1 Connect to Railway MySQL

### Option A: Using Railway Web Interface

Railway provides a quick SQL editor:

1. **In Railway project**, click **MySQL instance**
2. **Go to "Connect" tab**
3. **Look for "Web Interface" or "MySQL GUI"**
4. **Go to that URL**
5. You'll see a browser-based SQL editor

### Option B: Using MySQL Command Line (If MySQL is installed)

Open PowerShell:

```powershell
mysql -h [MYSQLHOST] -u [MYSQLUSER] -p [MYSQLDATABASE]
```

Replace with your actual credentials:
- `[MYSQLHOST]` → Your host
- `[MYSQLUSER]` → Your username
- `[MYSQLUSER]` → Your password (keep -p without space, type password when prompted)
- `[MYSQLDATABASE]` → Your database (railway)

---

## 2.2 Import Schema Via Web Interface

**Easiest method:**

1. **Open your local file:** `app/Database/schema.sql`
2. **Copy ALL the SQL code**
3. **In Railway web interface**, paste the SQL
4. **Click "Execute"** or **"Run"**

⏳ Wait for execution to complete

✅ **Tables created**

---

## 2.3 Import Schema Via Command Line

If using PowerShell, once connected to MySQL:

1. **Exit MySQL** (type `exit`)
2. **Run this command:**

```powershell
mysql -h [MYSQLHOST] -u [MYSQLUSER] -p[MYSQLPASSWORD] [MYSQLDATABASE] < app/Database/schema.sql
```

**Important:** No space after `-p` (e.g., `-pYourPassword` NOT `-p YourPassword`)

⏳ Wait for import to complete

✅ **Tables created**

---

## 2.4 Verify Tables Were Created

In Railway web interface or MySQL CLI, run:

```sql
SHOW TABLES;
```

**You should see these 6 tables:**
- ✅ admin_users
- ✅ payment_locks
- ✅ transaction_logs
- ✅ transactions
- ✅ websites
- ✅ webhook_events

If you see all 6 → **Perfect!** ✅

If not → Re-run the SQL import

---

# STEP 3: Setup GitHub Repository (5 minutes)

Your backend needs to be on GitHub for Render to deploy it automatically.

## 3.1 Create GitHub Repository

1. **Go to:** https://github.com
2. **Sign in** (or create account if needed)
3. **Click:** "+" icon (top right)
4. **Select:** "New repository"
5. **Name:** `payment-hub-backend` (or any name)
6. **Description:** `Centralized Payment Hub - Backend API`
7. **Choose:** "Public" (Render needs to access it)
8. **Click:** "Create repository"

✅ **GitHub repo created**

---

## 3.2 Push Your Code to GitHub

### On Your Computer:

Open PowerShell and navigate to your project:

```powershell
cd "C:\Users\HP\Centralized Payment Hub with Pawapay"
```

### Initialize Git:

```powershell
git init
git add .
git commit -m "Initial commit: Payment Hub Backend"
```

### Connect to GitHub:

Replace `YOUR_USERNAME` with your GitHub username and `payment-hub-backend` with your repo name:

```powershell
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/payment-hub-backend.git
git push -u origin main
```

### When prompted for credentials:
- **Username:** Your GitHub username
- **Password:** Your GitHub personal access token (create one at https://github.com/settings/tokens if needed)

⏳ Wait for upload to complete

✅ **Code pushed to GitHub**

---

## 3.3 Verify on GitHub

1. **Go to:** https://github.com/YOUR_USERNAME/payment-hub-backend
2. **You should see all your files:**
   - ✅ app/ folder
   - ✅ public_html/ folder
   - ✅ .env (should be in .gitignore, so it won't show - that's good)
   - ✅ logs/ folder
   - ✅ All PHP files

---

# STEP 4: Deploy to Render (8 minutes)

## 4.1 Create Render Account

1. **Go to:** https://render.com
2. **Click:** "Sign Up"
3. **Choose:** "Sign up with GitHub" (easiest)
4. **Authorize** Render to access your GitHub

✅ **Render account created**

---

## 4.2 Create New Web Service

1. **In Render dashboard**, click **"+ New +"**
2. **Select:** "Web Service"
3. **Connect your repository:**
   - Click **"Connect repository"**
   - Search for: `payment-hub-backend`
   - Click **"Connect"**

✅ **Repository connected**

---

## 4.3 Configure Service

Fill in the configuration form:

### Name
```
payment-hub-backend
```

### Environment
```
PHP
```

### Build Command
```bash
composer install
```

### Start Command
```bash
php -S 0.0.0.0:10000 -t public_html
```

⚠️ **This uses PHP's built-in server (fine for testing)**

### Region
```
Choose nearest to you (US or EU)
```

### Plan
```
Free (perfect for testing)
```

✅ **Configuration complete**

---

## 4.4 Set Environment Variables

**Before deploying, add your Railway MySQL credentials:**

1. **In Render**, scroll down to **"Environment Variables"**
2. **Click:** "Add Environment Variable"
3. **Add these variables:**

```
KEY                     VALUE
---                     -----
APP_ENV                 production
APP_DEBUG               false
APP_DOMAIN              https://payment-hub-backend.onrender.com

DB_HOST                 [Your MYSQLHOST]
DB_USER                 [Your MYSQLUSER]
DB_PASS                 [Your MYSQLPASSWORD]
DB_NAME                 [Your MYSQLDATABASE]
DB_PORT                 3306

PAWAPAY_API_TOKEN       eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ...
PAWAPAY_API_URL         https://sandbox.pawapay.io
PAWAPAY_MERCHANT_ID     303

FORCE_HTTPS             true
SESSION_NAME            ph_admin_session
SESSION_TIMEOUT         3600

RATE_LIMIT_ENABLED      true
RATE_LIMIT_ATTEMPTS     100
RATE_LIMIT_WINDOW       3600
```

### Replace These Values:

- `[Your MYSQLHOST]` → From Railway (e.g., `mysql.railway.internal`)
- `[Your MYSQLUSER]` → From Railway (e.g., `root`)
- `[Your MYSQLPASSWORD]` → From Railway
- `[Your MYSQLDATABASE]` → From Railway (e.g., `railway`)
- `eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ...` → Your actual PawaPay token

---

## 4.5 Deploy

1. **Click:** "Create Web Service"
2. ⏳ **Wait for deployment** (~2-5 minutes)

You'll see:
```
=== Deployment in progress...
Building your application
...
✓ Deployment successful
```

✅ **Deployment complete**

---

## 4.6 Get Your Live URL

After successful deployment:

1. **In Render dashboard**, find your service
2. **Look for:** URL at top (looks like "https://payment-hub-backend.onrender.com")
3. Or click the service to see its URL

📋 **Copy this URL** - it's your live backend!

---

# STEP 5: Test Your Backend (3 minutes)

## 5.1 Test Webhook Endpoint

Open your browser and visit:

```
https://payment-hub-backend.onrender.com/public_html/webhook.php
```

### Expected Result:
```
POST method required
```

✅ **If you see this, backend is working!**

---

## 5.2 Test Payment Entry

```
https://payment-hub-backend.onrender.com/public_html/pay.php?token=test
```

Should show:
```
Token validation failed
```

✅ **Backend is responding**

---

## 5.3 Check Logs

In Render dashboard:
1. **Go to your service**
2. **Click:** "Logs"
3. **You should see PHP output**
4. Check for any errors

✅ **Logs are working**

---

## 5.4 Database Connection Test

Your backend will automatically test DB connection on first request.

### If you see database errors:

1. **Check Render environment variables** - are they correct?
2. **Verify Railway credentials** - did you copy them correctly?
3. **Check password** - does it have special characters?
4. **Restart Render service** - click "Tools" → "Restart"

---

# STEP 6: Update Your Local Configuration

## 6.1 Update .env For Testing

In your local project, update `.env`:

```env
APP_DOMAIN=https://payment-hub-backend.onrender.com

DB_HOST=mysql.railway.internal
DB_USER=root
DB_PASS=[Your Railway Password]
DB_NAME=railway
DB_PORT=3306

PAWAPAY_API_URL=https://sandbox.pawapay.io
```

## 6.2 Push Changes to GitHub

```powershell
git add .
git commit -m "Update .env for Railway + Render deployment"
git push
```

⏳ Render will automatically redeploy with new changes

✅ **Configuration updated**

---

# 🎉 SUCCESS! You Now Have:

✅ **Live Backend URL:** `https://payment-hub-backend.onrender.com`
✅ **Live Database:** Railway MySQL (free tier)
✅ **All schemas imported:** 6 tables ready
✅ **Environment variables:** Properly configured
✅ **Auto-deployment:** Git push = auto redeploy
✅ **Cost:** $0/month 🎉

---

# 📊 Testing Summary

| Component | Status | Live URL |
|-----------|--------|----------|
| Backend | ✅ Live | https://payment-hub-backend.onrender.com |
| Database | ✅ Live | Railway MySQL |
| Webhook | ✅ Working | /webhook.php |
| Payment Entry | ✅ Working | /pay.php |
| Return Handler | ✅ Working | /return.php |
| Cost | ✅ Free | $0/month |

---

# 🚀 Next Steps

Once backend is live:

1. **Build Admin Dashboard Frontend** (React/Next.js on Vercel)
2. **Connect frontend to your backend URL**
3. **Test complete payment flow**
4. **Add test website to admin panel**
5. **Test payment token generation**
6. **Test webhook processing**

---

# 🆘 Troubleshooting

## "Connection refused" or "Cannot connect to database"

**Solution:**
1. Check Railway credentials in Render environment
2. Verify database is running in Railway (check Railway dashboard)
3. Verify DB_HOST is correct (usually `mysql.railway.internal`)
4. Restart Render service: "Tools" → "Restart"
5. Wait 2-3 minutes and try again

---

## Backend Shows 503 "Service Unavailable"

**Solution:**
1. Check Render logs for errors
2. Verify all environment variables are set
3. Check PHP errors in logs
4. Restart service

---

## Database Tables Don't Exist

**Solution:**
1. Check schema.sql was imported correctly
2. In Railway, run: `SHOW TABLES;`
3. If empty, re-import schema.sql
4. Verify no SQL errors during import

---

## Can't Access Railway Database from Render

**Solution:**
1. Railway uses internal DNS: `mysql.railway.internal`
2. Use this EXACT hostname in DB_HOST
3. Verify port is 3306
4. Check credentials match exactly (copy/paste!)

---

## "Post method required" vs Database Error

**Good:** If you see "POST method required" → backend is running ✅

**Bad:** If you see database connection error → check credentials

---

# 📞 Debugging Commands

### Check Render Logs:
```bash
# In Render dashboard: click your service → Logs tab
```

### Check Railway Database Status:
```bash
# In Railway dashboard: click MySQL → Logs tab
```

### Test Connection Manually (if MySQL installed):
```bash
mysql -h mysql.railway.internal -u root -p railroad
```

### Query Tables:
```sql
SHOW TABLES;
SELECT * FROM transactions LIMIT 5;
SELECT * FROM websites;
```

---

# 💰 Cost Breakdown

| Service | Free Tier | Cost |
|---------|-----------|------|
| Railway MySQL | ✅ Yes | $0/month |
| Render Backend | ✅ Yes | $0/month |
| Bandwidth | ✅ Included | $0 |
| **Total** | | **$0/month** 🎉 |

**Perfect for testing and development!**

---

# 📋 Deployment Checklist

- [ ] Railway MySQL database created
- [ ] Schema imported (6 tables)
- [ ] GitHub repo created & code pushed
- [ ] Render account created
- [ ] Environment variables configured
- [ ] Backend deployed to Render
- [ ] Webhook.php responds (POST method required)
- [ ] Database connection successful
- [ ] All 6 tables visible in Railway

---

**You're ready to deploy!** 🚀

Once you complete these steps and backend is live:

1. **Report back** the live URL
2. **Test the endpoints** (webhook.php, pay.php)
3. **Verify database** connection
4. **Then we build the Admin Dashboard Frontend!**

---

**Any issues? Check the Troubleshooting section above!** 👍
