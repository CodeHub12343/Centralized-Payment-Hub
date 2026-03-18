# Complete Guide: Deploy to Render + PlanetScale

**Timeline:** ~20-30 minutes total  
**Date:** March 17, 2026  
**Status:** Step-by-step deployment guide

---

## 🎯 Overview

This guide walks you through deploying your Payment Hub backend to Render with PlanetScale MySQL database - a completely independent testing environment separate from Virtualmin.

### Final Setup:
```
PlanetScale (Hosted MySQL)
        ↓
Render (PHP Backend)
        ↓
Your Domain
```

---

# STEP 1: PlanetScale Setup (5 minutes)

## 1.1 Create PlanetScale Account

1. **Go to:** https://planetscale.com
2. **Click:** "Sign Up" (top right)
3. **Choose:** "Sign up with GitHub" (easier) OR email
4. **Complete** the signup process

✅ **Account created**

---

## 1.2 Create Your First Database

After signup, you'll see the dashboard:

1. **Click:** "Create a database"
2. **Name your database:** `payhub_db`
3. **Choose region:** US (closest to you) or EU
4. **Click:** "Create database"

⏳ Wait ~1-2 minutes for database to initialize

✅ **Database created**

---

## 1.3 Get Connection Credentials

1. **On the database page**, click the **"Connect"** button
2. **Select:** "Connect with MySQL Client"
3. **You'll see a connection string:**

```
mysql -h aws.connect.psdb.cloud -u xxxxxxxxxxxxx -pxxxxxxxxxxxxx payhub_db
```

**Break this down:**
```
Host:     aws.connect.psdb.cloud
Username: xxxxxxxxxxxxx (copy this)
Password: xxxxxxxxxxxxx (copy this)
Database: payhub_db
Port:     3306 (default)
```

📋 **Copy these credentials somewhere safe.** You'll need them in Step 3.

✅ **Credentials obtained**

---

## 1.4 Create an API Key (for app access)

1. **In PlanetScale dashboard**, click your profile icon (top right)
2. **Select:** "Account Settings"
3. **Go to:** "API tokens"
4. **Click:** "Create token"
5. **Name it:** `render-deployment`
6. **Select:** "Admin" permission
7. **Click:** "Create"

📋 **Copy the token** - you'll need it later.

✅ **API token created**

---

# STEP 2: Import Database Schema (2 minutes)

Now create your tables in PlanetScale.

## 2.1 Import Schema Via Browser

### Option A: Using PlanetScale Console (Easier)

1. **In PlanetScale dashboard**, go to your database
2. **Click:** "Branches" or "Main"
3. **Click:** "SQL Editor"
4. **Open your local file:** `app/Database/schema.sql`
5. **Copy ALL the SQL code**
6. **Paste into PlanetScale SQL editor**
7. **Click:** "Execute"

✅ **Tables created**

---

### Option B: Using MySQL Command Line (If you have MySQL installed)

```bash
mysql -h aws.connect.psdb.cloud -u [USERNAME] -p[PASSWORD] payhub_db < app/Database/schema.sql
```

When prompted for password, paste your PlanetScale password.

✅ **Tables created**

---

## 2.2 Verify Tables Were Created

1. **In PlanetScale dashboard**, click **"Tables"**
2. **You should see these 6 tables:**
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

It will ask for your GitHub credentials:
- **Username:** Your GitHub username
- **Password:** Your GitHub personal access token (create one at github.com/settings/tokens)

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

# STEP 4: Deploy to Render (10 minutes)

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

**Before deploying, add your PlanetScale credentials:**

1. **In Render**, scroll down to **"Environment Variables"**
2. **Click:** "Add Environment Variable"
3. **Add these variables:**

```
KEY                     VALUE
---                     -----
APP_ENV                 production
APP_DEBUG               false
APP_DOMAIN              https://payment-hub-backend.onrender.com

DB_HOST                 aws.connect.psdb.cloud
DB_USER                 [Your PlanetScale USERNAME]
DB_PASS                 [Your PlanetScale PASSWORD]
DB_NAME                 payhub_db

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

**Replace:**
- `[Your PlanetScale USERNAME]` → Your actual PlanetScale username
- `[Your PlanetScale PASSWORD]` → Your actual PlanetScale password
- `eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ...` → Your actual PawaPay token

---

## 4.5 Deploy

1. **Click:** "Create Web Service"
2. ⏳ **Wait for deployment** (~2-5 minutes)

You'll see:
```
Building...
Building your Docker image
...
Success!
```

✅ **Deployment complete**

---

## 4.6 Get Your Live URL

After successful deployment:

1. **In Render dashboard**, find your service
2. **Look for:** "onrender.com" URL at top
3. **It will look like:**
```
https://payment-hub-backend.onrender.com
```

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

## 5.2 Test Other Endpoints

### Test Payment Entry:
```
https://payment-hub-backend.onrender.com/public_html/pay.php?token=test
```

Should show error about invalid token (that's normal):
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

✅ **Logs are working**

---

## 5.4 Database Connection Test

Your backend will automatically test DB connection on first request.

**If you see errors:**
- Database connection failed
- Check your PlanetScale credentials
- Verify they're correct in Render environment variables

---

# STEP 6: Update Your Configuration

## 6.1 Update .env For Testing

In your local project, update `.env`:

```env
APP_DOMAIN=https://payment-hub-backend.onrender.com

DB_HOST=aws.connect.psdb.cloud
DB_USER=xxxxx_username
DB_PASS=pscale_pw_xxxxx
DB_NAME=payhub_db

PAWAPAY_API_URL=https://sandbox.pawapay.io
```

## 6.2 Push Changes to GitHub

```bash
git add .
git commit -m "Update .env for Render deployment"
git push
```

⏳ Render will automatically redeploy with new changes

---

# 🎉 SUCCESS! You Now Have:

✅ **Live Backend URL:** `https://payment-hub-backend.onrender.com`
✅ **Live Database:** PlanetScale hosted MySQL
✅ **All schemas imported:** 6 tables ready
✅ **Environment variables:** Properly configured
✅ **Auto-deployment:** Git push = auto redeploy

---

# 📊 Testing Summary

| Component | Status | Live URL |
|-----------|--------|----------|
| Backend | ✅ Live | https://payment-hub-backend.onrender.com |
| Database | ✅ Live | PlanetScale (aws.connect.psdb.cloud) |
| Webhook | ✅ Working | /webhook.php |
| Payment Entry | ✅ Working | /pay.php |
| Return Handler | ✅ Working | /return.php |

---

# 🚀 Next Steps

Once testing is complete, you can:

1. **Build frontend** (React/Next.js on Vercel)
2. **Connect frontend to your backend URL**
3. **Test complete payment flow**
4. **Go live with Contabo** (when ready)

---

# 🆘 Troubleshooting

## Backend Shows 404 Error

**Solution:**
1. Check Render logs
2. Verify .env variables
3. Check GitHub is updated
4. Restart service in Render ("Tools" → "Restart")

---

## Database Connection Failed

**Solution:**
1. Verify PlanetScale credentials
2. Check password doesn't have special characters (or escape them)
3. Ensure PlanetScale database is created
4. Test locally with: `mysql -h aws.connect.psdb.cloud -u [user] -p[pass] payhub_db`

---

## Render Won't Deploy

**Solution:**
1. Check GitHub integration
2. Verify repository is public
3. Check build logs in Render
4. Check PHP version compatibility

---

# 📞 Support Commands

If you need to debug:

### SSH into running container:
```bash
# Via Render dashboard: Tools → Shell
```

### Check database:
```bash
mysql -h aws.connect.psdb.cloud -u [user] -p[pass] payhub_db
SHOW TABLES;
SELECT * FROM transactions LIMIT 5;
```

### View backend logs:
```bash
# In Render dashboard: Logs tab (live streaming)
```

---

**You're ready to deploy!** 🚀

Once you complete these steps, report back and we'll move to the next phase: **Building the Admin Dashboard Frontend!**
