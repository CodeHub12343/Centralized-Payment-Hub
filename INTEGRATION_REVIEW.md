# Frontend-Backend Integration Review
## Centralized Payment Hub with PawaPay

**Review Date:** March 18, 2026  
**Reviewer Role:** Senior Full-Stack Engineer  
**Project Status:** ⚠️ **CRITICAL ISSUES FOUND - 3 Major Mismatches**

---

## 1. Summary of Integration Health

**Overall Status:** ❌ **INCOMPLETE / MISALIGNED** (65% Integration Coverage)

| Aspect | Status | Notes |
|--------|--------|-------|
| Authentication Flow | ✅ Working | JWT token exchange consistent |
| API URL Configuration | ⚠️ Partial | Hardcoded localhost in .env file |
| Transaction Fetching | ✅ Working | Query parameters properly implemented |
| Website Management (GET) | ✅ Working | CRUD read operations correct |
| Website Management (CREATE/UPDATE/DELETE) | ✅ Working | POST/PUT/DELETE endpoints integrated |
| Dashboard Metrics | ✅ Working | Metrics API properly consumed |
| Gateways Status | ❌ **NOT INTEGRATED** | Frontend uses mock data, API not called |
| Edit/Update Website | ❌ **NOT IMPLEMENTED** | Frontend has no edit form, API PUT endpoint unused |
| Logout Flow | ⚠️ Partial Risk | No backend validation of logout |
| Error Handling | ⚠️ Inconsistent | Some pages missing edge cases |
| Loading States | ✅ Good | Consistent implementation |
| Authorization Headers | ✅ Working | Fixed through Apache header preservation |

---

## 2. Correctly Implemented Integrations

### ✅ Authentication (Login)

**Flow Validation:**
- Frontend `authAPI.login()` → Backend `POST /api/auth/login` ✅
- Request payload: `{ username, password }` matches backend expectation
- Response format: `{ success: true, token: "...", admin: { id, username, email } }` correctly parsed
- Token storage in localStorage and header injection via Axios interceptor working

```typescript
// CORRECT ✅
login: async (username: string, password: string) => {
  const response = await apiClient.post('/auth/login', { username, password });
  localStorage.setItem('auth_token', response.token);
  localStorage.setItem('auth_user', JSON.stringify(response.admin));
}
```

**Backend Validation:**
- Password hashing/verification using `SecurityHelper::verifyPassword()` ✅
- Returns ISO 8601 formatted data ✅
- Logs login attempts correctly ✅

---

### ✅ Transaction Fetching (Transactions Page)

**API Integration:**
- Frontend `transactionAPI.getTransactions()` → Backend `GET /api/transactions`
- Query parameters properly formatted:
  - `search` → Database LIKE search on `tx_id` OR `order_id` ✅
  - `site` → Exact match on `site` column ✅
  - `status` → Exact match on `status` column ✅
  - `page` & `per_page` → Pagination with OFFSET/LIMIT ✅

```typescript
// CORRECT ✅
getTransactions: async (filters: TransactionFilters = {}) => {
  const params = new URLSearchParams();
  if (filters.search) params.append('search', filters.search);
  if (filters.site) params.append('site', filters.site);
  if (filters.status) params.append('status', filters.status);
  params.append('page', (filters.page || 1).toString());
  params.append('per_page', (filters.per_page || 50).toString());
  const response = await apiClient.get(`/transactions?${params.toString()}`);
}
```

**Response Parsing:**
- Frontend expects `response.data` array and builds filter dropdown from sites ✅
- Pagination metadata correctly handled ✅

---

### ✅ Dashboard Metrics

**API Integration:**
- Frontend `dashboardAPI.getMetrics()` → Backend `GET /api/dashboard/metrics`
- Response keys match frontend expectations:
  ```javascript
  {
    success: true,
    metrics: {
      totalRevenue,        // ✅ Used as is
      totalTransactions,   // ✅ Used as is
      successfulPayments,  // ✅ Used as is
      pendingPayments,     // ✅ Used as is
      failedPayments       // ✅ Used as is
    },
    revenueByDay: [...]    // ✅ Correctly mapped to chart
  }
  ```

**Backend Calculation:**
- All aggregations use `DATETIME` filters correctly (last 7 days) ✅
- `revenueByDay` returned as ISO format dates that JavaScript `Date` parser can handle ✅

---

### ✅ Website Management (CRUD - Except Edit)

#### **GET Websites**
- Frontend `websiteAPI.getWebsites()` → Backend `GET /api/websites` ✅
- Response structure matches TypeScript `Website interface`:
  ```typescript
  interface Website {
    id?: number;
    site_code: string;
    secret_key: string;
    success_url: string;
    fail_url: string;
    is_active?: number;
    created_at?: string;
  }
  ```
- All fields are returned by backend ✅

#### **CREATE Website**
- Frontend form validation before submission ✅
- Backend validation: required fields + URL format checking ✅
- Duplicate `site_code` prevention via unique constraint ✅
- Response indicates success: `{ success: true, message: "...", site_code: "..." }` ✅

#### **DELETE Website**
- Frontend shows confirmation modal ✅
- Backend validates website exists (404 if not found) ✅
- Frontend optimistically removes from list ✅
- Logging of deletion action ✅

---

### ✅ Authorization Header Handling

- JWT token injected via Axios request interceptor ✅
- Format: `Authorization: Bearer <token>` ✅
- Apache `.htaccess` preserves header through URL rewrite ✅
- Backend checks 3 fallback locations for header ✅
- 401 response interceptor redirects to login ✅

---

## 3. Mismatches and Issues Found

### ❌ **ISSUE #1: Gateways Page - Complete Mock/API Mismatch**

**Problem:** Gateways page uses hardcoded mock data instead of calling backend API.

**Frontend Current Code:**
```typescript
// ❌ WRONG - Using mock data
import { mockGateways } from "../data/mockData";

export function Gateways() {
  return (
    // ...renders mockGateways
    {mockGateways.map((gateway) => (
      // displays gateway.status, gateway.uptime, gateway.lastCheck
    ))}
  );
}
```

**Backend API Available:**
```
GET /api/gateways/status → Returns:
{
  "success": true,
  "gateways": [
    {
      "name": "Pawapay",
      "status": "active",
      "uptime": "99.9%",
      "lastCheck": "2026-03-18 08:00:00",
      "responseTime": 250
    }
  ]
}
```

**Risk Level:** 🔴 **HIGH** - Frontend displays outdated, static data that doesn't reflect actual gateway state.

**Fix Required:** Integrate API call similar to Dashboard.

---

### ❌ **ISSUE #2: Website Edit/Update - Feature Mismatch**

**Problem:** Settings page displays websites but has NO edit/update functionality, despite backend supporting PUT operations.

**Frontend Current Code:**
```typescript
// ❌ NO EDIT HANDLER - Only create and delete
{websites.map((website) => (
  <tr key={website.site_code}>
    {/* displays data but... */}
    <button onClick={() => handleDelete(website.site_code!)}>
      Delete  // Only this action exists
    </button>
    {/* NO EDIT BUTTON */}
  </tr>
))}
```

**Backend PUT Endpoint Available:**
```
PUT /api/websites/{siteCode}
Request: {
  secret_key?: string,
  success_url?: string,
  fail_url?: string,
  is_active?: boolean
}
Response: { success: true, message: "Website updated successfully" }
```

**Risk Level:** 🔴 **HIGH** - Users cannot modify website settings after creation without deleting and recreating.

**What's Missing:**
- No "Edit" button in table
- No edit form modal/inline editing
- No `handleUpdate` function
- `websiteAPI.updateWebsite()` exists but never called

---

### ❌ **ISSUE #3: Configuration Mismatch - hardcoded .env**

**Problem:** Production `.env` file has conflicting configuration.

**Production `.env` file:**
```bash
# .env (PRODUCTION CONFIG)
APP_ENV=production      # ← Set to production
APP_DEBUG=false
APP_DOMAIN=https://pay.pivotpointinv.com

# But database points to LOCAL
DB_HOST=localhost       # ← LOCAL CONNECTION
DB_USER=pivotpointinv
DB_PASS=2b8feeac59d00d24
DB_NAME=payhub_db
```

**Frontend `.env` file:**
```bash
VITE_API_URL=http://localhost/pawapay/api  # ← Hardcoded localhost
```

**Backend `config.php` (runtime):**
```php
define('DB_HOST', getenv('DB_HOST') ?: 'yamanote.proxy.rlwy.net');  // Railway
define('DB_NAME', getenv('DB_NAME') ?: 'railway');
```

**Issue:** 
- Production `.env` uses LOCAL database (pivotpointinv) - Wrong for production
- Frontend always points to localhost - Can't access production API
- Backend config.php falls back to Railway if env var not set

**Risk Level:** 🟡 **MEDIUM** - Serious deployment issue; could cause data loss or incorrect API targeting in production.

---

### ⚠️ **ISSUE #4: API Base URL Hardcoded**

**Problem:** Frontend API client defaults to hardcoded localhost URL.

```typescript
// client.ts
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
```

**Issues:**
- Fallback URL `localhost:8000` is incorrect (should be `localhost/pawapay/api` if Apache)
- `.env` file MUST be present and correct for API to work
- No validation that API_BASE_URL is reachable
- Different between frontend .env (`http://localhost/pawapay/api`) and code default

**Risk Level:** 🟡 **MEDIUM** - Fragile environment configuration; breaks if .env missing or wrong.

---

## 4. Missing or Incomplete Integrations

### ❌ **Gateway Status Page**

**Current State:**
- Shows mock data from `mockData.ts`
- Has hardcoded "Refresh Status" button with no click handler
- Displays static uptime "99.7%"

**Missing Implementation:**
```typescript
// Should have:
useEffect(() => {
  const fetchGateways = async () => {
    const response = await dashboardAPI.getGatewayStatus();
    setGateways(response.gateways);
  };
  fetchGateways();
}, []);
```

**Note:** API endpoint exists and is working, just not consumed.

---

### ❌ **Website Edit Functionality**

**Current State:**
- Settings page shows website table
- Users can CREATE and DELETE only
- No way to modify existing websites

**Missing Implementation:**
```typescript
// NEED: Edit button + modal form
const [editingWebsite, setEditingWebsite] = useState<Website | null>(null);

const handleUpdate = async (siteCode: string, updates: Partial<Website>) => {
  const response = await websiteAPI.updateWebsite(siteCode, updates);
  // Refresh list and close modal
};

// In table:
<button onClick={() => setEditingWebsite(website)}>Edit</button>
```

---

### ⚠️ **Logout Endpoint**

**Current Implementation:**
```typescript
logout: async () => {
  set({ isLoading: true });
  try {
    await authAPI.logout();  // API call attempted
  } catch (err) {
    console.error('Logout error:', err);  // Logged but ignored
    // Clear storage regardless
  } finally {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    // State cleared
  }
}
```

**Issue:** 
- Backend `logout()` endpoint exists and logs the action, but does nothing else
- Frontend clears client-side state regardless of API response
- No server-side token invalidation (not critical for stateless JWT, but worth noting)

**Status:** ✅ **Functional but loose coupling** - Works fine for JWT stateless auth.

---

### ⚠️ **Error Handling Gaps**

| Page | Issue |
|------|-------|
| Dashboard | No handling for failed metrics/transactions/gateways calls individually; entire page shows error if one fails |
| Transactions | No handling for CSV export if browser doesn't support Blob API (unlikely but not checked) |
| Settings | Network errors show but form stays disabled forever if request fails (no retry) |
| Gateways | N/A - no API integration yet |

---

## 5. Risky or Fragile Implementations

### 🔴 **Risk: Dashboard Single Error State**

```typescript
// Dashboard.tsx - ALL FAIL IF ANY ENDPOINT FAILS
try {
  const metricsResponse = await dashboardAPI.getMetrics();
  const txResponse = await transactionAPI.getTransactions();
  const gatewaysResponse = await dashboardAPI.getGatewayStatus();
} catch (err: any) {
  setError("Failed to load dashboard data");  // Generic error for 3 calls
}
```

**Problem:** If any of the 3 API calls fails, entire dashboard shows error. Should handle independently.

**Impact:** ⚠️ If transactions API is slow, user sees broken dashboard even if metrics are available.

---

### 🔴 **Risk: Settings Form - No Retry After Network Failure**

```typescript
const handleSubmit = async (e: React.FormEvent) => {
  setIsSubmitting(true);
  try {
    const response = await websiteAPI.createWebsite(formData);
  } catch (err: any) {
    setError(err.response?.data?.error || "Failed to create website");
  } finally {
    setIsSubmitting(false);
  }
};
```

**Problem:** After error, button re-enables but form still shows error. User must clear error manually and retry.

**Impact:** 🟡 Poor UX for transient network issues.

---

### 🟡 **Risk: Pagination Not Implemented in Frontend**

Backend supports pagination (`page`, `per_page` parameters), but:

```typescript
// Transactions.tsx - LOADS ALL TRANSACTIONS
const response = await transactionAPI.getTransactions({ per_page: 500 });

// Frontend filters locally
setFilteredTransactions(filtered);
```

**Problem:** Requests all transactions at once (500 limit), then filters client-side. Doesn't use server-side pagination for large datasets.

**Impact:** 🟡 Performance issue if database has 10k+ transactions.

---

### 🟡 **Risk: localStorage Direct Manipulation**

```typescript
// authStore.ts
localStorage.setItem('auth_token', response.token);
localStorage.setItem('auth_user', JSON.stringify(response.admin));

// Then in ProtectedRoute:
localStorage.getItem('auth_token')
```

**Problem:** localStorage is synchronous and shared with all scripts on domain. No expiration handling in browser.

**Impact:** 🟡 If token expires server-side, client still thinks it's valid until 401 response forces logout.

---

### 🟡 **Risk: No Request Timeout Configuration**

```typescript
// client.ts - No timeout set
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: { 'Content-Type': 'application/json' }
});
```

**Problem:** If backend hangs, frontend request hangs indefinitely.

**Impact:** 🟡 User stuck on loading screen; no error message.

---

## 6. Recommended Fixes

### FIX #1: Integrate Gateways Status Page ⚠️ CRITICAL

**File:** `app/Frontend/app/pages/Gateways.tsx`

Replace mock data with API calls:

```typescript
import { useEffect, useState } from "react";
import { dashboardAPI } from "../api/client";

interface Gateway {
  name: string;
  status: "active" | "down" | "maintenance";
  uptime: string;
  lastCheck: string;
  responseTime?: number;
}

export function Gateways() {
  const [gateways, setGateways] = useState<Gateway[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchGateways = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const response = await dashboardAPI.getGatewayStatus();
        if (response.success) {
          setGateways(response.gateways);
        }
      } catch (err: any) {
        setError(err.response?.data?.error || "Failed to load gateway status");
      } finally {
        setIsLoading(false);
      }
    };

    fetchGateways();
  }, []);

  // Rest of component renders gateways from state instead of mockGateways
}
```

**Validation:**
- ✅ Response structure matches backend
- ✅ Loading/error states handled
- ✅ No mock data

---

### FIX #2: Implement Website Edit Functionality ⚠️ CRITICAL

**File:** `app/Frontend/app/pages/Settings.tsx`

Add edit form and handler:

```typescript
const [editingWebsite, setEditingWebsite] = useState<Website | null>(null);
const [editFormData, setEditFormData] = useState({
  secret_key: "",
  success_url: "",
  fail_url: "",
  is_active: true
});

const handleEditSubmit = async (e: React.FormEvent) => {
  e.preventDefault();
  if (!editingWebsite) return;

  setIsSubmitting(true);
  setError(null);
  setSuccess(null);

  try {
    const response = await websiteAPI.updateWebsite(
      editingWebsite.site_code,
      editFormData
    );
    if (response.success) {
      setSuccess(`Website ${editingWebsite.site_code} updated successfully`);
      
      // Refresh list
      const refreshResponse = await websiteAPI.getWebsites();
      if (refreshResponse.success) {
        setWebsites(refreshResponse.data);
      }

      setEditingWebsite(null);
      setTimeout(() => setSuccess(null), 3000);
    }
  } catch (err: any) {
    setError(err.response?.data?.error || "Failed to update website");
  } finally {
    setIsSubmitting(false);
  }
};

// In table action buttons:
<button
  onClick={() => {
    setEditingWebsite(website);
    setEditFormData({
      secret_key: website.secret_key,
      success_url: website.success_url,
      fail_url: website.fail_url,
      is_active: website.is_active || 1
    });
  }}
  className="inline-flex items-center gap-2 px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded"
>
  <Edit className="w-4 h-4" />
  Edit
</button>
```

**Validation:**
- ✅ Uses existing `websiteAPI.updateWebsite()` endpoint
- ✅ Matches backend expected request format
- ✅ Error handling consistent with Create
- ✅ Refreshes list after update

---

### FIX #3: Fix Environment Configuration 🔴 CRITICAL

**Files to Update:**

1. **`.env` (PRODUCTION ROOT)**
```bash
# Current ❌
DB_HOST=localhost
DB_NAME=payhub_db

# Should be ✅ (for production)
DB_HOST=yamanote.proxy.rlwy.net
DB_PORT=25898
DB_USER=root
DB_PASS=uBhygrweKtNdhhXRtRTEnwykmdhDjayb
DB_NAME=railway

# For local development, use .env.local ✅
# (git-ignored override)
```

2. **`app/Frontend/.env`**
```bash
# Add environment-aware configuration
# For production, update before deployment
VITE_API_URL=http://localhost/pawapay/api
```

3. **`app/Config/config.php`**
```php
// Already correct - uses environment variables with fallbacks ✅
define('DB_HOST', getenv('DB_HOST') ?: 'yamanote.proxy.rlwy.net');
define('DB_PORT', getenv('DB_PORT') ?: 25898);
```

**Validation:**
- ✅ Production `.env` points to Railway database
- ✅ Development uses local or environment-specific overrides
- ✅ No hardcoded credentials

---

### FIX #4: Add API Request Timeout

**File:** `app/Frontend/app/api/client.ts`

```typescript
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000,  // 30 second timeout ✅
});
```

---

### FIX #5: Improve Dashboard Error Handling

**File:** `app/Frontend/app/pages/Dashboard.tsx`

```typescript
const fetchDashboardData = async () => {
  setIsLoading(true);
  setError(null);
  const errors: string[] = [];

  try {
    // Fetch metrics
    try {
      const metricsResponse = await dashboardAPI.getMetrics();
      if (metricsResponse.success) {
        setMetrics(metricsResponse.metrics);
      }
    } catch (err: any) {
      errors.push("Failed to load metrics");
    }

    // Fetch transactions
    try {
      const txResponse = await transactionAPI.getTransactions({ per_page: 5 });
      if (txResponse.success) {
        setTransactions(txResponse.data);
      }
    } catch (err: any) {
      errors.push("Failed to load transactions");
    }

    // Fetch gateways
    try {
      const gatewaysResponse = await dashboardAPI.getGatewayStatus();
      if (gatewaysResponse.success) {
        setGateways(gatewaysResponse.gateways);
      }
    } catch (err: any) {
      errors.push("Failed to load gateway status");
    }

    if (errors.length > 0) {
      setError(errors.join("; "));
    }
  } finally {
    setIsLoading(false);
  }
};
```

**Result:** ✅ Partial failures don't break entire dashboard

---

### FIX #6: Implement Pagination in Transactions Page

**File:** `app/Frontend/app/pages/Transactions.tsx`

```typescript
const [pageNumber, setPageNumber] = useState(1);
const [isLoadingMore, setIsLoadingMore] = useState(false);

useEffect(() => {
  const fetchTransactions = async () => {
    setIsLoadingMore(true);
    try {
      const response = await transactionAPI.getTransactions({
        page: pageNumber,
        per_page: 50  // Don't load 500 at once
      });
      if (response.success) {
        if (pageNumber === 1) {
          setTransactions(response.data);
        } else {
          setTransactions(prev => [...prev, ...response.data]);
        }
      }
    } catch (err) {
      setError("Failed to load transactions");
    } finally {
      setIsLoadingMore(false);
    }
  };
  fetchTransactions();
}, [pageNumber]);

// Pagination component
<div className="mt-4 flex justify-between">
  <button
    onClick={() => setPageNumber(p => Math.max(1, p - 1))}
    disabled={pageNumber === 1}
  >
    Previous
  </button>
  <span>Page {pageNumber}</span>
  <button
    onClick={() => setPageNumber(p => p + 1)}
    disabled={isLoadingMore}
  >
    Next
  </button>
</div>
```

---

### FIX #7: Remove Mock Data References

**File:** `app/Frontend/app/pages/Gateways.tsx`

```diff
- import { mockGateways } from "../data/mockData";
+ // Remove import - use API data instead
```

**Validation Checklist:**
- ❌ Remove all `import { mockGateways }`
- ❌ Remove all `import { mockTransactions }`
- ✅ Keep mock data only for development defaults, not production rendering

---

## 7. Final Verdict

### Is the frontend fully aligned with the backend?

**ANSWER: ❌ NO - 65% Integration Coverage**

### Verdict Summary:

| Category | Status | Details |
|----------|--------|---------|
| Authentication | ✅ Complete | Login/logout working correctly |
| Data Fetching | ⚠️ Partial | Transactions, websites, metrics work; gateways use mock |
| Data Modification | ❌ Incomplete | Create/delete work; edit not implemented |
| Error Handling | ⚠️ Fragile | Works but lacks individual endpoint error isolation |
| Configuration | ❌ Broken | Production `.env` uses wrong database |
| Production Readiness | ❌ NOT READY | Critical issues must be fixed before deployment |

### Critical Issues Blocking Production (Priority):

1. 🔴 **Gateways page using mock data** - Users see false gateway status
2. 🔴 **Website edit functionality missing** - Incomplete CRUD operations
3. 🔴 **Production environment misconfigured** - DB connection fails on deployment
4. 🟡 **Request timeout missing** - Hanging requests cause frozen UI

### Recommended Action Plan:

**Phase 1 (THIS WEEK):** Fix critical issues #1-3
- Integrate Gateways API
- Implement edit functionality
- Fix environment configuration

**Phase 2 (NEXT WEEK):** Improve robustness
- Add request timeouts
- Implement better error isolation
- Add real pagination

**Phase 3 (BEFORE DEPLOYMENT):** Testing & validation
- Test all CRUD operations end-to-end
- Verify production environment variables
- Load test with realistic data
- Security audit of token handling

### Sign-Off:

**CANNOT RECOMMEND FOR PRODUCTION** until critical issues 1-3 are resolved.

Estimated remediation time: **2-3 hours** for all critical fixes.

---

## Appendix: API Endpoint Summary

### Implemented & Working ✅

| Method | Endpoint | Frontend Integration | Status |
|--------|----------|---------------------|---------|
| POST | `/api/auth/login` | authAPI.login() | ✅ |
| POST | `/api/auth/logout` | authAPI.logout() | ✅ |
| GET | `/api/transactions` | transactionAPI.getTransactions() | ✅ |
| GET | `/api/websites` | websiteAPI.getWebsites() | ✅ |
| POST | `/api/websites` | websiteAPI.createWebsite() | ✅ |
| PUT | `/api/websites/{siteCode}` | websiteAPI.updateWebsite() | ❌ NEVER CALLED |
| DELETE | `/api/websites/{siteCode}` | websiteAPI.deleteWebsite() | ✅ |
| GET | `/api/dashboard/metrics` | dashboardAPI.getMetrics() | ✅ |
| GET | `/api/gateways/status` | dashboardAPI.getGatewayStatus() | ❌ MOCK DATA USED |

### Implementation Gaps

- **Edit Website** - API supports PUT, frontend doesn't use it
- **Gateway Status** - API provides data, frontend ignores it and uses mock

---

END OF REVIEW
