# 🚀 Server Setup From Scratch
**Complete beginner guide - Starting from zero**

---

## What You Need to Do (In Order)

```
STEP 1: Buy a server from Contabo ✅
   ↓
STEP 2: Receive credentials email
   ↓
STEP 3: Access Virtualmin control panel
   ↓
STEP 4: Create database & user
   ↓
STEP 5: Upload your PHP code
   ↓
STEP 6: Test payment hub
```

---

## STEP 1️⃣: Buy a VPS Server from Contabo

A **VPS** (Virtual Private Server) is basically a computer on the internet that runs your code 24/7.

### Go to Contabo Website:
1. Open browser → https://contabo.com
2. Click **"VPS"** or **"Virtual Servers"**
3. Choose a plan:
   - **Recommended for beginners**: **VPS M**
     - 4 vCPU cores
     - 8 GB RAM
     - 160 GB SSD storage
     - ~$5-8 per month

### Select Operating System:
- Choose: **Ubuntu 22.04 LTS** (most popular, easiest to use)

### Add Extensions:
- Add **Virtualmin** (for easy control panel) ✅
  - This adds a nice web interface instead of command line
  - Makes database management point-and-click

### Complete Purchase:
1. Add to cart
2. Proceed to checkout
3. Enter payment info (credit card/PayPal)
4. Complete order

⏳ **Wait**: Within **5-30 minutes** you'll receive an email with:
- Server IP address
- Root username & password
- Login URL for Virtualmin

---

## STEP 2️⃣: Check Your Email for Credentials

You'll receive an email from **Contabo** (check inbox AND spam folder).

The email will contain something like this:

```
Subject: Your new Server is ready!

Server Information:
─────────────────
IP Address: 203.123.45.67
Hostname: vps12345.contaboserver.com
Root Username: root
Root Password: xKf9$mP2@qLp8nR

Virtualmin URL: https://203.123.45.67:10000
Virtualmin Username: root
Virtualmin Password: xKf9$mP2@qLp8nR
```

**📌 Save this email!** You'll need these credentials.

---

## STEP 3️⃣: Access Virtualmin Control Panel

This is your **dashboard** for managing the server.

### In your web browser:

1. Copy the **Virtualmin URL** from the email:
   ```
   https://203.123.45.67:10000
   ```

2. Replace `203.123.45.67` with YOUR IP address from the email

3. Go to that URL in browser

4. **Security warning** will appear:
   ```
   "Your connection is not private"
   "NET::ERR_CERT_AUTHORITY_INVALID"
   ```
   This is normal! Click:
   - **"Advanced"** → **"Proceed to [IP]"** (Chrome)
   - **"Accept the Risk and Continue"** (Firefox)

5. Login form appears:
   ```
   Username: root
   Password: [________________]
   ```

6. Enter credentials from email → Click **Login**

🎉 You're now in Virtualmin! You should see a dashboard.

---

## STEP 4️⃣: Create Database & MySQL User

You're in Virtualmin. Now create the database.

### Navigate to MySQL:

**Left Sidebar Menu:**
```
Servers
  ↓
MySQL Database Server
```

(Or search for "MySQL" in the sidebar)

---

### Create Database:

1. Look for button: **"Create a new database"**

2. Fill in:
   ```
   Database name: payment_hub
   ```

3. Click **"Create Database"**

✅ First credential: `DB_NAME=payment_hub`

---

### Create MySQL User:

1. Click **"Create a new user"** (on same MySQL page)

2. Fill in:
   ```
   Username: payment_hub_user
   Password: Pw@yHubSecure2024!
   Repeat password: Pw@yHubSecure2024!
   ```

3. Click **"Create User"**

✅ Second & third credentials:
```env
DB_USER=payment_hub_user
DB_PASS=Pw@yHubSecure2024!
```

---

### Grant User Access:

1. Find **"payment_hub"** database in the list
2. Click it
3. Click **"Add User"** or **"Grant Privilege"**
4. Select user: `payment_hub_user`
5. Select permissions: **ALL**
6. Click **Save** or **Grant**

---

### Find DB_HOST:

For Contabo with Virtualmin, it's always:
```env
DB_HOST=localhost
```

✅ Fourth credential found!

---

## STEP 5️⃣: Your Database Credentials

Now you have all 4! Update your `.env` file:

```env
# ================================================
# DATABASE CONFIGURATION
# ================================================
DB_HOST=localhost
DB_USER=payment_hub_user
DB_PASS=Pw@yHubSecure2024!
DB_NAME=payment_hub
```

---

## STEP 6️⃣: Upload Your PHP Code

Your backend code needs to go to the server.

### Option A: Using Virtualmin File Manager (Easiest)

1. Still in Virtualmin, look for **"File Manager"** or **"Edit Files"**
2. Navigate to: `/home/public_html/` or `/var/www/html/`
3. Upload your files:
   - `app/` folder
   - `public_html/` folder
   - `.env` file (with your credentials)
   - `Database/` folder

### Option B: Using SFTP Client (Recommended)

1. Download **FileZilla** (free SFTP client): https://filezilla-project.org/

2. Open FileZilla → File → Site Manager → New Site

3. Fill in:
   ```
   Protocol: SFTP - SSH File Transfer Protocol
   Host: 203.123.45.67 (your IP)
   Port: 22
   Username: root
   Password: [password from email]
   ```

4. Click **Connect**

5. Navigate to `/var/www/html/`

6. Drag and drop your project files there

### Option C: Using Command Line (Most efficient)

**On your local computer**, open terminal/PowerShell:

```bash
# Upload entire project to server
scp -r "c:\Users\HP\Centralized Payment Hub with Pawapay" root@203.123.45.67:/var/www/html/payment_hub
```

Replace `203.123.45.67` with your IP.

It will ask for password → enter the root password from email.

---

## STEP 7️⃣: Import Database Tables

SSH into your server and import the schema:

### Using PuTTY or Terminal:

```bash
ssh root@203.123.45.67
```

(Replace IP with yours, enter password)

### Once connected, run:

```bash
mysql -u payment_hub_user -p payment_hub < /var/www/html/payment_hub/app/Database/schema.sql
```

Enter password: `Pw@yHubSecure2024!`

✅ **If no error appears**, tables were imported successfully!

---

## STEP 8️⃣: Configure Domain (Optional but Recommended)

Your payment hub needs a domain like: `pay.pivotpointinv.com`

### In Virtualmin:

1. **Servers** → **Create Virtual Server**

2. Fill in:
   ```
   Domain: pay.pivotpointinv.com
   ```

3. Virtualmin will set up Apache to serve your code at that domain

### Point your domain to server:

1. Go to your domain registrar (GoDaddy, Namecheap, etc.)
2. Find **DNS Settings**
3. Add **A Record**:
   ```
   Name: pay
   Type: A
   Value: 203.123.45.67  (your server IP)
   TTL: 3600
   ```

4. Point root domain:
   ```
   Name: @  (or leave blank)
   Type: A
   Value: 203.123.45.67
   TTL: 3600
   ```

⏳ **Wait** 5-30 minutes for DNS to propagate

---

## STEP 9️⃣: Test Payment Hub

### Test via IP:

```
https://203.123.45.67/public_html/pay.php?token=test
```

### Test via Domain (after DNS fully propagates):

```
https://pay.pivotpointinv.com/public_html/pay.php?token=test
```

You should see proper error messages (not "connection refused").

---

## Complete Checklist

Before you start, make sure you have ready:

- [ ] Contabo account (free to create)
- [ ] Credit card for VPS payment
- [ ] Your project files (already created! ✅)
- [ ] Email access (to receive server credentials)

---

## 📊 Order of Steps

| Step | What | Time |
|------|------|------|
| 1 | Order VPS from Contabo | 5 min |
| 2 | Wait for credentials email | 5-30 min |
| 3 | Access Virtualmin | 2 min |
| 4 | Create database & user | 5 min |
| 5 | Update `.env` file | 2 min |
| 6 | Upload PHP code | 5 min |
| 7 | Import database tables | 1 min |
| 8 | Configure domain | 5 min |
| 9 | Test payment hub | 2 min |
| **TOTAL** | **From zero to live** | **~45 minutes** |

---

## 💡 What Happens Next?

Once your server is set up:

1. ✅ Backend API will be running
2. ✅ Database will be live
3. ✅ CMS websites can connect via PaymentConnector.php
4. ✅ Payments will be processed through PawaPay

---

## 🆘 Need Help?

### Contabo Server Not Responding?
- Wait 5-10 minutes, server might still be booting
- Check email for correct IP address
- Try ping: `ping 203.123.45.67`

### Can't Access Virtualmin?
- Make sure port `:10000` is open
- Try different browser
- Wait for server to fully initialize

### MySQL Won't Connect?
- Check MySQL service is running: `systemctl status mysql`
- Verify credentials are correct
- Make sure user has database privileges

### Domain Not Working?
- DNS changes take 5-30 minutes to propagate
- Check DNS was pointed correctly at registrar
- Use `nslookup pay.pivotpointinv.com` to verify

---

## Ready to Start? 🚀

1. Go to: **https://contabo.com**
2. Order a **VPS M with Ubuntu 22.04 + Virtualmin**
3. **Come back here** when you get the credentials email
4. Follow STEP 3 above

**Good luck!** You're building a real payment system! 💪
