# Quick Start Testing Guide

## Prerequisites
- PHP 7.4+ with MySQL
- Node.js 16+
- npm/yarn

---

## 🟢 Step 1: Backend Setup

### 1. Ensure database is initialized
```bash
# Point to your MySQL database
# Check that admin_users table exists with demo user
mysql -u root -p payment_hub
SELECT * FROM admin_users;
```

### 2. Add JWT Secret (optional, uses default if not set)
```bash
# Set environment variable (Linux/Mac)
export JWT_SECRET="your-super-secret-key-here"

# Or in .env file for your hosting
```

### 3. Enable CORS for development
The api.php already defaults to `CORS_ALLOWED_ORIGINS=*` for dev.

---

## 🟢 Step 2: Frontend Setup

### 1. Install dependencies
```bash
cd app/Frontend
npm install
```

### 2. Configure API URL
Create `.env` file in `app/Frontend/`:
```
VITE_API_URL=http://localhost:8000/api
```

For testing locally, this points to your PHP development server.

### 3. Start dev server
```bash
npm run dev
```

Frontend will be available at: `http://localhost:5173`

---

## 🟢 Step 3: Start Backend

### 1. Using PHP built-in server
```bash
cd public_html
php -S localhost:8000
```

API will be available at: `http://localhost:8000/api`

### 2. Or use your existing web server
- Configure virtual host
- Point to `public_html` directory
- Update VITE_API_URL accordingly

---

## 🧪 Test the Integration

### 1. Open Login Page
```
http://localhost:5173/login
```

You should see:
- Clean login form
- "Payment Hub" header
- Demo credentials hint

### 2. Login with demo credentials
```
Username: admin
Password: admin123
```

You should:
- See loading state briefly
- Get redirected to dashboard
- See your username in sidebar

### 3. Test Dashboard
You should see:
- ✅ Real metrics (may be 0 if no transactions)
- ✅ Revenue chart (populated if transactions exist)
- ✅ Recent transactions list
- ✅ Payment method distribution

### 4. Test Transactions Page
```
http://localhost:5173/transactions
```

You should:
- ✅ See all transactions from database
- ✅ Search by TX ID or Order ID
- ✅ Filter by site
- ✅ Filter by status
- ✅ Export to CSV

### 5. Test Settings Page
```
http://localhost:5173/settings
```

You should:
- ✅ See configured websites
- ✅ Show/hide secret keys
- ✅ Add new website (creates in DB)
- ✅ Delete website (removes from DB)
- ✅ Generate random secret keys

### 6. Test Authentication
```
- Click user menu (top left)
- Click "Logout"
- Should redirect to login
- Try accessing dashboard directly
- Should redirect to login (protected route)
```

---

## 🐛 Common Testing Issues

### Issue: "Failed to fetch"
**Solution:**
```bash
# Check PHP server is running
lsof -i :8000

# Check VITE_API_URL in browser console
# (Press F12 → Console)
console.log(import.meta.env.VITE_API_URL)

# Should show: http://localhost:8000/api
```

### Issue: Login fails
**Solution:**
```bash
# Check admin user exists
mysql -u root -p
USE payment_hub;
SELECT * FROM admin_users WHERE username='admin';

# If missing, insert:
INSERT INTO admin_users (username, email, password_hash, is_active)
VALUES ('admin', 'admin@local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36ddqKFm', 1);
```

### Issue: No transactions showing
**Solution:**
```bash
# Check transactions table has data
mysql -u root -p
USE payment_hub;
SELECT COUNT(*) FROM transactions;

# If empty, it's expected - API is working correctly
# Create test transaction via Pawapay flow
```

### Issue: "CORS error"
**Solution:**
- This is expected if frontend/backend are on different domains
- Ensure `CORS_ALLOWED_ORIGINS` in config.php includes your frontend domain
- For dev: `define('CORS_ALLOWED_ORIGINS', '*');`

---

## ✅ Validation Checklist

- [ ] PHP server running (`localhost:8000`)
- [ ] Frontend server running (`localhost:5173`)
- [ ] Can access login page
- [ ] Can login with demo credentials
- [ ] Dashboard loads without errors
- [ ] Metrics display (may be 0)
- [ ] Transactions page loads
- [ ] Settings page loads
- [ ] Can add a new website
- [ ] Can delete a website
- [ ] Logout works
- [ ] Protected routes work

---

## 📊 API Endpoints - Quick Test

You can test the API directly using curl:

### 1. Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

Response:
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "admin": {
    "id": 1,
    "username": "admin",
    "email": "admin@pawapay.local"
  }
}
```

### 2. Get Metrics (with token)
```bash
TOKEN="<token-from-login-response>"

curl -X GET http://localhost:8000/api/dashboard/metrics \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Get Transactions
```bash
curl -X GET "http://localhost:8000/api/transactions" \
  -H "Authorization: Bearer $TOKEN"
```

### 4. Get Websites
```bash
curl -X GET http://localhost:8000/api/websites \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🎉 Success Indicators

When everything is working:

1. **Frontend loads smoothly** - No console errors
2. **Login works instantly** - JWT token validated
3. **Data loads quickly** - API responses are fast
4. **Dashboard updates real-time** - Reflects database changes
5. **Create/delete works** - Changes persist in database
6. **Search/filter works** - Results update instantly
7. **Logout redirects** - Protected routes work

---

## 📝 Next: Production Testing

Once local testing is complete:

1. Deploy backend to production server
2. Update VITE_API_URL to production domain
3. Set strong JWT_SECRET
4. Configure CORS for production domain
5. Enable FORCE_HTTPS
6. Change default admin password
7. Run full end-to-end test flow

---

## 💡 Debugging Tips

**Enable debug mode in browser:**
```javascript
// In browser console
localStorage.setItem('debug', 'true');
window.location.reload();
```

**Check API response:**
```javascript
// In browser console
const token = localStorage.getItem('auth_token');
fetch('http://localhost:8000/api/dashboard/metrics', {
  headers: { 'Authorization': `Bearer ${token}` }
})
.then(r => r.json())
.then(console.log);
```

**Monitor all requests:**
- Open DevTools → Network tab
- Perform actions
- Watch API calls in real-time
- Check response status and data

---

**Ready to test! 🚀**
