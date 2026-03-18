# Quick Deployment Checklist for Render.com

**Application:** Centralized Payment Hub with Pawapay  
**Target:** Render.com  
**Status:** Ready for production deployment  
**Estimated Time:** 2-3 hours  

---

## Pre-Deployment: Security & Cleanup (30 minutes)

### Local Machine Setup

```powershell
# 1. Navigate to project root
cd "c:\Users\HP\Centralized Payment Hub with Pawapay"

# 2. Update .gitignore to exclude sensitive files
Add-Content -Path ".gitignore" -Value @"
.env
.env.local
.env.production
logs/
node_modules/
.vscode/
*.log
test-*.php
debug-*.php
setup-*.php
"@

# 3. Remove test files (these should NOT be in production)
Remove-Item -Path "test-*.php" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "debug-*.php" -Force -ErrorAction SilentlyContinue
Remove-Item -Path "setup-*.php" -Force -ErrorAction SilentlyContinue

# 4. Clear logs directory
Remove-Item -Path "logs\*" -Force -ErrorAction SilentlyContinue
New-Item -Path "logs\.gitkeep" -Force

# 5. Commit cleanup
git add -A
git commit -m "Security: Remove test files and prepare for production"
```

### Remove Credentials from Git History (CRITICAL)

```powershell
# 1. Check if .env is in Git history
git log --all -- .env

# If found, remove it using BFG Repo Cleaner:
# Download from: https://rclone.org/s3/

# Or use git filter-branch:
git filter-branch --tree-filter 'rm -f .env' HEAD

# Force push to remote
git push origin --force --all
```

### Prepare Production Credentials

```powershell
# 1. Generate secure JWT secret
# Use PowerShell:
$bytes = New-Object Byte[] 32
[System.Security.Cryptography.RNGCryptoServiceProvider]::new().GetBytes($bytes)
$jwtSecret = [Convert]::ToBase64String($bytes)
Write-Host "JWT_SECRET: $jwtSecret"

# Output example: Az9kL2mN5pQ8vR1xY3uW6tZ9bC2dE4fG5hI7jK0lM

# 2. Generate new database password (if using new database)
# Use: pwgen 32 1  or  openssl rand -base64 32
```

### Test Locally First

```bash
# 1. Start development environment
npm run dev
# (or php -S localhost:8000 if no npm setup)

# 2. Test login
# Visit: http://localhost:3000/login
# Username: admin
# Password: admin123

# 3. Verify database connection works
# Visit: http://localhost:3000/dashboard
# Should load without errors
```

---

## Step 1: Set Up Backend on Render (30 minutes)

### 1.1 Create Backend Service

1. **Go to: https://dashboard.render.com**
2. **Click:** New + → Web Service
3. **Connect GitHub:**
   - Select your payment hub repository
   - Select branch: `main`
4. **Configure:**
   - **Service Name:** `payment-hub-api`
   - **Environment:** `PHP`
   - **Plan:** `Starter ($7/month)`
   - **Region:** Select closest to users

### 1.2 Build & Start Commands

In the "Build" section:
- **Build Command:** Leave empty (or `composer install` if using composer)
- **Start Command:** `php -S 0.0.0.0:10000`

OR (better for production):
- **Start Command:** `cd public_html && php -S 0.0.0.0:10000`

### 1.3 Add Environment Variables

Click **Environment** section, add all variables below:

```
DB_HOST=yamanote.proxy.rlwy.net
DB_PORT=25898
DB_USER=root
DB_PASS=<new-secure-password>
DB_NAME=railway
DB_CHARSET=utf8mb4

APP_ENV=production
APP_DEBUG=false
APP_DOMAIN=https://payment-hub-api-xxxxx.onrender.com

FORCE_HTTPS=true
CORS_ALLOWED_ORIGINS=https://payment-hub-app-xxxxx.onrender.com

JWT_SECRET=<generated-from-step-above>
JWT_EXPIRATION=86400

PAWAPAY_API_TOKEN=<your-pawapay-token>
PAWAPAY_API_URL=https://sandbox.pawapay.io
PAWAPAY_MERCHANT_ID=303

LOG_LEVEL=debug
```

**Note:** After creating service, Render will assign URL like `https://payment-hub-api-abc123.onrender.com`

### 1.4 Wait for Initial Deploy

- Render will automatically build and deploy
- Watch the **Logs** section for any errors
- Deploy should complete in 2-3 minutes
- Status should show "Live"

### 1.5 Test Backend is Running

```powershell
# Test 1: Check service is up
$url = "https://payment-hub-api-abc123.onrender.com/api/"
$response = Invoke-WebRequest -Uri $url
Write-Host "Backend is running: $($response.StatusCode)"

# Test 2: Try login (should work or give proper error)
$loginUrl = "https://payment-hub-api-abc123.onrender.com/api/auth/login"
$body = @{ username = "admin"; password = "admin123" } | ConvertTo-Json
$response = Invoke-WebRequest -Uri $loginUrl -Method Post -Body $body -ContentType "application/json"
$response.Content | ConvertFrom-Json | Format-List
```

**Expected response (if admin user exists):**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "admin": {...}
}
```

### 1.6 Initialize Database

**Option A: Via Render Shell (Easiest)**
1. Go to Render dashboard → payment-hub-api service
2. Click **Shell** tab at top
3. Run:
```bash
cd /var/task
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < app/Database/schema.sql

# Create admin user
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME << 'EOF'
INSERT INTO admin_users (username, password_hash, email, is_active, created_at)
VALUES ('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRcx3e', 'admin@example.com', 1, NOW());
EOF
```

**Option B: Via SSH from Local Machine**
```powershell
mysql -h yamanote.proxy.rlwy.net -u root -p'<password>' railway < "app/Database/schema.sql"
```

### 1.7 Verify Database Tables Created

```powershell
mysql -h yamanote.proxy.rlwy.net -u root -p'<password>' railway -e "SHOW TABLES;"
```

Should output:
```
Tables_in_railway
admin_users
gateways
transactions
websites
```

---

## Step 2: Set Up Frontend on Render (30 minutes)

### 2.1 Build Frontend Locally

```powershell
cd "app/Frontend"
npm install
npm run build

# Output should be in: dist/ folder
```

### 2.2 Create Frontend Service

1. **Go to: https://dashboard.render.com**
2. **Click:** New + → **Static Site** (not Web Service)
3. **Connect GitHub:**
   - Select payment hub repository
   - Branch: `main`
4. **Configure:**
   - **Name:** `payment-hub-app`
   - **Build Command:** `cd app/Frontend && npm install && npm run build`
   - **Publish Directory:** `app/Frontend/dist`

### 2.3 Add Environment Variables

Click **Environment**, add:
```
VITE_API_URL=https://payment-hub-api-xxxxx.onrender.com/api
```

(Replace `xxxxx` with your backend service ID from previous step)

### 2.4 Create Redirects File

For React Router to work correctly, create redirect rule:

1. In Render dashboard, go to **Settings**
2. Scroll to **Redirects** section
3. Add:
   - **Source:** `/*`
   - **Destination:** `/index.html`
   - **Status:** `200`

### 2.5 Deploy

Render will auto-deploy after config. Watch the **Logs** for completion.

---

## Step 3: Verification & Testing (20 minutes)

### 3.1 Test Frontend Loads

```powershell
# Visit frontend URL (check Render dashboard for exact URL)
Start-Process "https://payment-hub-app-abc123.onrender.com"

# Should see login page with:
# - Username field
# - Password field  
# - Login button
```

### 3.2 Test Login Flow

1. **Username:** `admin`
2. **Password:** `admin123`
3. **Click:** Login
4. **Expected:** Redirects to dashboard (should load within 3 seconds)

### 3.3 Test Dashboard

On dashboard, verify:
- ✅ Metrics card shows (Revenue, Transaction Count, etc.)
- ✅ Chart renders
- ✅ Recent transactions table has data
- ✅ Gateway status shows
- ✅ No red errors shown

### 3.4 Test Navigation

- ✅ Click **Transactions** → Loads correctly
- ✅ Click **Settings** → Loads correctly
- ✅ Click **Gateways** → Loads correctly
- ✅ Click **Logout** → Returns to login page

### 3.5 Browser Console Check

1. **Open DevTools:** F12
2. **Go to Console tab**
3. **Check:** No red errors
4. **Check:** No failed network requests (except possibly images)

### 3.6 Test API Directly

```powershell
# Get your token from login
$token = "<token-you-received-from-login>"

# Test metrics endpoint
$headers = @{ "Authorization" = "Bearer $token" }
$response = Invoke-WebRequest `
  -Uri "https://payment-hub-api-abc123.onrender.com/api/dashboard/metrics" `
  -Headers $headers
$response.Content | ConvertFrom-Json
```

---

## Step 4: Post-Deployment Configuration (20 minutes)

### 4.1 Enable Auto-Deployment

**Backend:**
1. Service → Settings
2. Auto-Deploy: Enable "Deploy latest push to main"

**Frontend:**
1. Service → Settings
2. Auto-Deploy: Enable "Deploy latest push to main"

Now every `git push origin main` will auto-deploy.

### 4.2 Set Up Custom Domain (Optional)

**For Backend:**
1. Service → Settings → Custom Domain
2. Type: `api.yourdomain.com`
3. Add CNAME record to your DNS:
   - Name: `api`
   - Value: `payment-hub-api-abc123.onrender.com`
4. Render auto-provisions SSL cert

**For Frontend:**
1. Service → Settings → Custom Domain
2. Type: `app.yourdomain.com`
3. Add CNAME record to DNS
4. Render auto-provisions SSL cert

### 4.3 Change Admin Password

**Important:** Change default password immediately after first login

1. **Login as admin** with password `admin123`
2. **Click:** Settings (gear icon)
3. **Change Password:** Update to secure password
4. **Save**

### 4.4 Set Up Backups (Optional but Recommended)

Database backups from Railway:
1. Go to Railway dashboard
2. Your Railway project → Backups
3. Enable automated backups

### 4.5 Set Up Error Monitoring (Optional)

For even better production stability, set up Sentry:

1. Go to https://sentry.io
2. Create free account
3. Create new project (PHP)
4. Get your Sentry DSN
5. Add to backend environment variables:
   ```
   SENTRY_DSN=https://xxxxx@xxxx.ingest.sentry.io/xxxxx
   ```

---

## Step 5: Enable Monitoring & Alerts (10 minutes)

### 5.1 Render Built-in Monitoring

1. **Backend Service → Metrics**
   - Shows: CPU, RAM, Requests, Errors
   - Monitor daily for first week

2. **Logs → All Logs**
   - Watch for errors
   - Check response times

### 5.2 Set Up Email Alerts (Recommended)

1. **Account → Notifications**
2. Enable:
   - ☑ Deploy succeeded
   - ☑ Deploy failed
   - ☑ Service exceeded memory limit
   - ☑ Service crashed

### 5.3 Manual Health Check (To Run Daily First Week)

```powershell
# Create a scheduled task or just run manually daily:

$apiUrl = "https://payment-hub-api-abc123.onrender.com/health"
$try {
  $response = Invoke-WebRequest -Uri $apiUrl -ErrorAction Stop
  Write-Host "✅ Backend is healthy"
} catch {
  Write-Host "❌ Backend is down: $($_.Exception.Message)"
}

$appUrl = "https://payment-hub-app-abc123.onrender.com"
$try {
  $response = Invoke-WebRequest -Uri $appUrl -ErrorAction Stop
  Write-Host "✅ Frontend is accessible"
} catch {
  Write-Host "❌ Frontend is down: $($_.Exception.Message)"
}
```

---

## Common Issues During Deployment

### ❌ Backend Won't Start

**Check:**
1. Render → Service → Logs (scroll to bottom)
2. Look for red error messages
3. Common causes:
   - Database connection failed (wrong credentials)
   - Missing PHP extensions
   - Syntax error in PHP code

**Fix:**
```bash
# SSH into Render shell and test database
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS -e "SELECT 1;"

# Check PHP version
php -v

# Check for syntax errors
php -l api.php
```

### ❌ Frontend Shows "Cannot GET /"

**Fix:**
1. Render → Service → Settings
2. Verify Publish Directory: `app/Frontend/dist`
3. Verify Redirects configured correctly
4. Rebuild frontend

### ❌ Login Returns "Invalid credentials"

**Fix:**
1. Check admin user exists in database
2. Recreate:
   ```bash
   mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME << 'EOF'
   DELETE FROM admin_users WHERE username='admin';
   INSERT INTO admin_users (username, password_hash, email, is_active, created_at)
   VALUES ('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRcx3e', 'admin@example.com', 1, NOW());
   EOF
   ```

### ❌ "Mixed Content" Error in Browser

**Fix:**
1. Backend URL must be HTTPS
2. Frontend .env must have:
   ```
   VITE_API_URL=https://... (NOT http://)
   ```
3. Rebuild frontend

---

## Post-Deployment: First 48 Hours

### Day 1 (First 24 Hours)

- ✅ Monitor logs every 1 hour for errors
- ✅ Try each feature at least once
- ✅ Check dashboard loads in < 3 seconds
- ✅ Verify database backups are working
- ✅ Check error tracking (if Sentry enabled)

### Day 2 (Next 24 Hours)

- ✅ Monitor logs periodically
- ✅ Test under realistic load (if possible)
- ✅ Verify scheduled tasks (if any)
- ✅ Document any issues and fixes

### Week 1

- ✅ Continue monitoring for stability
- ✅ Fix any issues that arise
- ✅ Gather usage metrics
- ✅ Plan for optimization if needed

---

## Rollback Procedure (If Needed)

### Quick Rollback

```powershell
# 1. In Git, revert the problematic commit
git revert <commit-hash>
git push origin main

# 2. Render will auto-deploy the reverted code within 2 minutes

# 3. Monitor logs to ensure it deploys successfully
```

### Emergency: Redirect to Previous Version

If backend is down and you need immediate fix:

1. **Keep a backup URL** of the previous Render deployment
2. **Update environment variables** in frontend to point to old backend
3. **Trigger new frontend deployment**
4. This buys time while you debug and fix backend

---

## Performance Baseline (For Future Monitoring)

After deployment, these are your baseline metrics:

| Metric | Target | Concern |
|--------|--------|---------|
| API Response Time | < 500ms | > 2s = investigate |
| Dashboard Load Time | < 2s | > 5s = issue |
| Transaction Search | < 1s | > 3s = optimization needed |
| Memory Usage | < 100MB | > 256MB = potential memory leak |
| CPU Usage | < 30% | > 80% = potential issue |
| Database Connection Time | < 50ms | > 200ms = network issue |
| Login Success Rate | 99%+ | < 95% = auth issue |
| Error Rate | < 0.1% | > 1% = serious problem |

---

## Success Checklist - All Complete ✅

When you've completed all steps:

```
Backend Deployment:
  ☑ Service created on Render
  ☑ Environment variables configured
  ☑ Database schema initialized
  ☑ Admin user created
  ☑ API endpoints responding (tested with curl/Postman)
  ☑ Auto-deploy enabled
  ☑ Custom domain configured (optional)

Frontend Deployment:
  ☑ Static site created on Render
  ☑ Build command configured
  ☑ Environment variables configured
  ☑ Redirects configured
  ☑ Auto-deploy enabled
  ☑ Custom domain configured (optional)

Testing:
  ☑ Frontend loads (no 404 errors)
  ☑ Login works with admin credentials
  ☑ Dashboard displays data
  ☑ Navigation works
  ☑ All features tested
  ☑ No console errors
  ☑ API requests successful

Production Setup:
  ☑ Auto-deploy enabled
  ☑ Monitoring/alerts configured
  ☑ Backups enabled (database)
  ☑ Error tracking setup (optional but recommended)
  ☑ Admin password changed from default
  ☑ Documentation updated
  ☑ Team has access to Render dashboard
  ☑ Emergency contacts documented

Completed at: __________ (timestamp)
Deployed by: __________ (name)
```

---

## Support & Troubleshooting

**Render Documentation:**
- https://render.com/docs

**PHP on Render:**
- https://render.com/docs/deploy-php

**Static Sites on Render:**
- https://render.com/docs/static-sites

**Database Issues:**
- Check Railway documentation: https://railway.app/docs

**For Help:**
- Render Support: support@render.com
- Check application logs: Render Dashboard → Logs
- Database logs: Railway Dashboard → Logs

---

**🎉 Deployment Complete!**

Your Centralized Payment Hub is now live in production.

**Share these URLs with your team:**
- Frontend: `https://payment-hub-app-xxxxx.onrender.com`
- Backend API: `https://payment-hub-api-xxxxx.onrender.com/api`
- Admin: username `admin` (password: changed during setup)

**Keep all passwords and credentials secure in a password manager.**
