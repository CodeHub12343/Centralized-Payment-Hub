# 📤 Step-by-Step Virtualmin Upload Guide
**Detailed guide with exact button locations**

---

## Prerequisites Before Starting

✅ You have your ZIP file ready: `Centralized Payment Hub with Pawapay.zip`
✅ You're logged into Virtualmin: https://109.199.123.51:10000
✅ You're logged in as: `root`

---

## 🎯 STEP 1: Open Virtualmin File Manager

### What You See Now:
After logging into Virtualmin, you should see the main dashboard with a left sidebar menu.

### Find File Manager:
Look at the **left sidebar** for:
```
Servers (or top menu)
  ↓
File Manager
```

**Click on "File Manager"**

---

## 📍 STEP 2: Navigate to the Correct Folder

### After clicking File Manager, you'll see:
A file browser showing directories.

### What you need to find:
Look for your subdomain folder. The path should be:
```
/home/pivotpointinv/domains/pay.pivotpointinv.com/public_html/
```

### How to navigate there:

**In the address bar at top**, you'll see the current path.

Click on the path field and type:
```
/home/pivotpointinv/domains/pay.pivotpointinv.com/public_html
```

Press **Enter**

### What you should see inside:
You might see:
- `index.html` (default page)
- Or empty folder

This is where we'll upload your backend! ✅

---

## 📤 STEP 3: Upload Your ZIP File

### Look for the Upload Button:
At the **top of the File Manager**, you should see buttons like:

```
[Upload]  [Create]  [Delete]  [Permissions]  [Properties]
```

**Click the blue [Upload] button**

### A file dialog appears:
```
Open file dialog
┌─────────────────────────┐
│ Centralized Payment ... │ ← Your ZIP file
│ Desktop               │
│ Downloads            │
└─────────────────────────┘
```

### Select your ZIP:
1. Navigate to where you saved the ZIP
2. Find: `Centralized Payment Hub with Pawapay.zip`
3. Click it
4. Click **"Open"** button

### Upload starts:
You'll see a progress bar:
```
Uploading... 50%
```

⏳ Wait for it to complete (usually takes 10-30 seconds depending on file size)

✅ When done, you'll see:
```
"File uploaded successfully"
```

---

## 📊 STEP 4: Extract the ZIP File

### The ZIP file now appears in your folder:
You'll see it listed as:
```
📦 Centralized Payment Hub with Pawapay.zip
```

### Extract it:
1. **Right-click** on the ZIP file
2. Select: **"Extract"** 
   (or "Decompress" or "Unzip" - depends on Virtualmin version)

### Wait for extraction:
A progress bar appears. After extraction, you'll see all folders appear:

```
📁 app/
📁 logs/
📁 public_html/
📄 .env
📦 Centralized Payment Hub with Pawapay.zip (the original ZIP)
```

### Delete the ZIP (optional but clean):
1. Right-click on the ZIP file
2. Select **"Delete"**
3. Confirm: **"Yes"**

✅ Now your files are extracted and ready!

---

## 🔍 STEP 5: Verify Folder Structure

### Current location should show:
```
/home/pivotpointinv/domains/pay.pivotpointinv.com/public_html/
```

### Inside, you should see:
```
Folders:
  📁 app/
  📁 logs/
  📁 public_html/

Files:
  📄 .env
  📄 README.md (if included)
  📄 IMPLEMENTATION_GUIDE.md (if included)
  📄 DATABASE_SETUP_GUIDE.md (if included)
  📄 SERVER_SETUP_FROM_SCRATCH.md (if included)
```

⚠️ **IMPORTANT**: If you see:
```
📁 Centralized Payment Hub with Pawapay/
   └─ app/
   └─ public_html/
```

Then files are nested one level too deep! You need to **move them up** (see Step 6).

---

## 🔧 STEP 6: Fix Nesting (If Needed)

### Only do this if you see nested structure like:
```
📁 Centralized Payment Hub with Pawapay/
   📁 app/
   📁 public_html/
   📄 .env
```

### Steps to fix:

**Inside the "Centralized Payment Hub with Pawapay" folder:**

1. **Select all** (Ctrl+A or click "Select All" button)

2. **Cut** (Ctrl+X or right-click → Cut)

3. **Go back up one level** (click back button or ".." in file list)

4. **Paste** (Ctrl+V or right-click → Paste)

5. You're now back in public_html with files directly there ✅

---

## 🔐 STEP 7: Set File Permissions

### What are permissions?
They tell the server who can READ / WRITE / EXECUTE files.

### Correct permissions:
```
Folders: 755 (read/write/execute)
Files: 644 (read/write, others read-only)
.env: 600 (only owner can read/write - most secure)
```

### How to set them:

**Select all files/folders:**
1. In File Manager, click **"Select All"** 
   (or Ctrl+A)

2. All files highlight in blue

### Open Permissions:
1. Look for **"Permissions"** button in toolbar
2. Click it

### Dialog appears:
```
Change Permissions
┌──────────────────┐
│ Owner: [755    ] │  ← Folders
│ Group: [755    ] │
│ Other: [755    ] │
└──────────────────┘
```

### Set correctly:
- **Owner**: 7 (read+write+execute)
- **Group**: 5 (read+execute)
- **Other**: 5 (read+execute)

**Result: 755** ✅

### For .env file specifically:
1. Navigate so you can see .env
2. Right-click on **.env**
3. Select **"Permissions"**
4. Change to: **600**
   ```
   Owner: 6
   Group: 0
   Other: 0
   ```
5. Click **"Save"** or **"Apply"**

✅ Permissions set!

---

## 📝 STEP 8: Verify Your .env File

### Check .env exists:
In the File Manager, you should see:
```
📄 .env
```

### View .env contents (to verify it's correct):

1. Right-click on **.env**
2. Select **"Edit"**
3. A text editor opens

### You should see:
```env
APP_ENV=production
APP_DEBUG=false
APP_DOMAIN=https://pay.pivotpointinv.com

DB_HOST=localhost
DB_USER=pivotpointinv
DB_PASS=2b8feeac59d00d24
DB_NAME=payhub_db

PAWAPAY_API_TOKEN=eyJraWQiOiIxI...
... (the long token)
```

✅ If it looks correct, close the editor

---

## 🧪 STEP 9: Test Backend is Running

### In your web browser, visit:

```
https://pay.pivotpointinv.com/public_html/webhook.php
```

### What you should see:

**Option A: Success** ✅
```
POST method required
```

This means the backend is running!

**Option B: Error** ❌
```
500 Internal Server Error
OR
[Error message about database]
```

If you see errors, check:
- Is DB_HOST value correct? (should be localhost)
- Is database imported? (next step)
- Are all files uploaded?

---

## 🗄️ STEP 10: Import Database Tables

This is the NEXT major step after upload.

### SSH into your server:
Open PowerShell/Terminal and run:

```bash
ssh root@109.199.123.51
```

When prompted, enter password: `Komana@97`

### Once connected, run:

```bash
mysql -u pivotpointinv -p payhub_db < /home/pivotpointinv/domains/pay.pivotpointinv.com/app/Database/schema.sql
```

### It will ask:
```
Enter password:
```

Enter: `2b8feeac59d00d24`

### If successful:
**No output appears** (which means success!) ✅

### If error:
You'll see something like:
```
ERROR 1045: Access denied
```

If this happens, check your DB credentials.

---

## ✅ Final Checklist

After all steps, verify:

- [ ] ZIP uploaded to Virtualmin
- [ ] ZIP extracted successfully
- [ ] Files NOT nested (app/ and public_html/ at same level)
- [ ] Permissions set (755 for folders, 644 for files)
- [ ] .env file has correct DB credentials
- [ ] PAWAPAY_API_TOKEN is filled in
- [ ] Backend loads at https://pay.pivotpointinv.com/public_html/webhook.php
- [ ] Database tables imported (via SSH + mysql command)

---

## 🎯 What's Next After Upload?

Once all above is done:

1. ✅ Backend is live
2. ✅ Database is initialized
3. ✅ Next: Build Admin Dashboard Frontend

You'll create HTML/CSS forms that connect to your backend API endpoints.

---

## 🐛 Troubleshooting

### "404 - File Not Found"
- Check files are in the right folder
- Make sure public_html/ subfolder exists with pay.php inside

### "500 - Internal Server Error"
- Check .env file exists and has correct DB credentials
- Check database is actually imported
- Check app/ folder is there

### "Cannot read .env file"
- Check .env file permissions (should be 600 or 644)
- Make sure file owner is correct (www-data or root)

### "Database connection refused"
- Check DB_HOST=localhost (not an IP address)
- Check DB_USER and DB_PASS are EXACTLY correct (case-sensitive!)
- Make sure database and user were created in Virtualmin

---

## 💡 Pro Tips

✅ **Always ZIP entire project** - easier than uploading individual files

✅ **Set permissions immediately** - prevents "permission denied" errors later

✅ **Delete original ZIP** after extracting - saves disk space

✅ **Test webhook.php first** - it's a quick way to verify backend is running

✅ **Keep .env secure** - use 600 permissions, never commit to Git

---

## 🎉 You're Done!

Your backend is now:
- ✅ Uploaded to Contabo
- ✅ Configured with .env
- ✅ Database tables imported
- ✅ Ready to receive payment requests!

**Next step:** Build the Admin Dashboard frontend! 🚀

