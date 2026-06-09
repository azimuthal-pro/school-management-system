# Frontend Integration - Priority Issues & Action Plan

**Date**: June 8, 2026  
**Status**: Ready for Backend Integration

---

## Priority Issues to Fix

### 🔴 CRITICAL (Must Fix Before Testing)

#### Issue #1: Wrong API URL
**Severity**: 🔴 CRITICAL  
**Location**: `next.config.js` line 4, `api.ts` line 1  
**Problem**:
```javascript
// WRONG - adds /api which backend doesn't have
NEXT_PUBLIC_API_URL: 'http://localhost:8000/api'

// Results in:
POST http://localhost:8000/api/auth/login  ← 404
GET http://localhost:8000/api/students  ← 404

// Should be:
POST http://localhost:8000/auth/login  ← ✓ Correct
GET http://localhost:8000/students  ← ✓ Correct
```

**Impact**: ALL API calls will fail with 404

**Fix**:
```javascript
// next.config.js
NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000'
// (without /api)

// api.ts already correct - uses the env var directly
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';
```

---

#### Issue #2: Duplicate Login Logic
**Severity**: 🔴 CRITICAL  
**Location**: `LoginPage` uses `auth.ts` but `AuthContext` also has `login()`  
**Problem**:
```typescript
// LoginPage calls auth.ts::login()
const handleSubmit = async () => {
  await login(email, password);  // from auth.ts
}

// But AuthContext has its own login() that's never called!
const { login } = useAuth();  // NOT USED in LoginPage
```

**Result**: 
- Two separate login implementations
- Inconsistent state management
- Hard to maintain

**Fix**: Use AuthContext::login() instead
```typescript
// LoginPage should be:
const { login } = useAuth();
const handleSubmit = async () => {
  await login(email, password);  // from AuthContext
}

// And remove auth.ts from LoginPage
```

---

#### Issue #3: Error Handling in API Client
**Severity**: 🔴 CRITICAL  
**Location**: `services/api.ts` lines 13-19  
**Problem**:
```typescript
if (!response.ok) {
  const text = await response.text().catch(() => '');
  const body = text || response.statusText;
  throw new Error(`API Error: ${response.status} - ${body}`);
  // Returns raw text, not parsed JSON
  // Validation errors lost
}
```

**Result**: 
- Validation error details lost
- Users see "API Error: 422 - {json}" instead of actual error
- Can't show field-level validation errors

**Fix**: Parse JSON response and extract error details
```typescript
if (!response.ok) {
  let errorMessage = 'API Error';
  try {
    const data = await response.json();
    errorMessage = data.message || data.error || errorMessage;
    // Store validation errors for display
    window.__apiErrors = data.errors;  // or throw custom error
  } catch {
    errorMessage = `HTTP ${response.status}`;
  }
  throw new Error(errorMessage);
}
```

---

### 🟡 HIGH PRIORITY (Fix Soon)

#### Issue #4: TypeScript Deprecation Warnings
**Severity**: 🟡 HIGH  
**Location**: `tsconfig.json` lines 2-3  
**Problem**:
```json
{
  "target": "es5",      // ⚠️ Deprecated in TypeScript 5.6+
  "lib": ["dom", "dom.iterable", "esnext"]
}
```

**Impact**: Compilation warnings, potential future breaks

**Fix**:
```json
{
  "target": "es2020",   // Modern equivalent
  "lib": ["dom", "dom.iterable", "esnext"]
}
```

---

#### Issue #5: Missing Pages
**Severity**: 🟡 HIGH  
**Location**: Missing files  
**Problem**:
```
❌ /grades/page.tsx - Folder exists but empty
❌ No Subjects page at all
❌ No /students/list - Can't view/edit students
❌ No /teachers/list - Can't view/edit teachers
❌ No /grades/list - Can't view/edit grades
```

**Impact**: Can only CREATE, not manage existing data

**Fix**: Create the missing pages (details in next section)

---

#### Issue #6: Mock Data in Reports
**Severity**: 🟡 HIGH  
**Location**: `app/reports/page.tsx` lines 80, 108  
**Problem**:
```typescript
// Ignores actual API data, uses hardcoded mock instead
const mockData = [
  { name: 'Present', value: 85 },
  { name: 'Absent', value: 10 },
  { name: 'Late', value: 5 },
];
setReportData({ attendance: mockData });
```

**Fix**: Use the actual data from API
```typescript
// Use data returned from getReports() instead of mockData
const data = await getReports(filters);
if (data && data.attendance) {
  setReportData(data);
} else {
  setReportData({ attendance: [] });  // empty, not mock
}
```

---

### 🟢 MEDIUM PRIORITY (Nice to Have)

#### Issue #7: No Frontend Form Validation
**Severity**: 🟢 MEDIUM  
**Location**: All form pages  
**Problem**: Forms don't validate before sending (only backend validates)

**Fix**: Add client-side validation for common cases

#### Issue #8: No Loading States
**Severity**: 🟢 MEDIUM  
**Location**: All pages  
**Problem**: Users don't know if request is in progress

**Fix**: Show spinner during API calls

---

## Pages to Create

### 1. Grades List Page
**Path**: `frontend/app/grades/page.tsx`
**Features**:
- List all grades
- Filter by student/subject/term
- Edit grade
- Delete grade

### 2. Subjects Page (Create)
**Path**: `frontend/app/subjects/page.tsx`
**Features**:
- Create subject form (name, code, description)
- List subjects
- Edit subject
- Delete subject

### 3. Students List Page
**Path**: `frontend/app/students/list/page.tsx`
**Features**:
- List all students
- Search/filter by class
- Edit student
- Delete student
- View student details

### 4. Teachers List Page
**Path**: `frontend/app/teachers/list/page.tsx`
**Features**:
- List all teachers
- Filter by subject
- Edit teacher
- Delete teacher
- View teacher details

### 5. Attendance Management
**Path**: `frontend/app/attendance/page.tsx` (update existing)
**Features**:
- Mark attendance for a class
- View attendance reports

---

## File Changes Required

### 1. Fix API URL
**File**: `frontend/next.config.js`
```diff
const nextConfig = {
  reactStrictMode: true,
  env: {
-   NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
+   NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000',
  },
};
```

### 2. Fix TypeScript Config
**File**: `frontend/tsconfig.json`
```diff
{
  "compilerOptions": {
-   "target": "es5",
+   "target": "es2020",
```

### 3. Fix API Error Handling
**File**: `frontend/services/api.ts`
```typescript
// Add proper JSON error parsing
// Extract message from response.data
// Handle validation errors
```

### 4. Fix Login Flow
**File**: `frontend/app/login/page.tsx`
```typescript
// Import useAuth instead of auth functions
// Call AuthContext::login() instead of auth.ts::login()
```

### 5. Refactor AuthContext
**File**: `frontend/context/AuthContext.tsx`
```typescript
// Make sure login() calls API and updates context
// Ensure consistency with login service behavior
```

---

## Testing Strategy

### Phase 1: Authentication Testing
```
1. ✅ Fix API URL
2. ✅ Test login endpoint
3. ✅ Verify token stored
4. ✅ Check redirects work
5. ✅ Test logout
6. ✅ Test protected routes
```

### Phase 2: CRUD Testing
```
1. ✅ Fix error handling
2. ✅ Create student → verify in DB
3. ✅ Read student list
4. ✅ Update student
5. ✅ Delete student
6. ✅ Repeat for teachers, classes, etc.
```

### Phase 3: Missing Pages
```
1. Create Grades page
2. Create Subjects page
3. Create List pages
4. Test all CRUD operations
```

### Phase 4: End-to-End Testing
```
1. Full user journey: register → login → create student
2. Error scenarios: invalid credentials, validation errors
3. Authorization: student vs admin flows
```

---

## Environment Setup

### Dev Server
```bash
cd frontend
npm install  # if not done
npm run dev
# Runs on http://localhost:3000
```

### Required Environment
```bash
# .env.local (create if needed)
NEXT_PUBLIC_API_URL=http://localhost:8000

# Backend must be running
# Terminal 1: npm run dev (frontend)
# Terminal 2: /opt/lampp/bin/php -S localhost:8000 (backend)
```

---

## Dependency Analysis

### Frontend Dependencies
```json
{
  "next": "^16.2.6",
  "react": "^18.2.0",
  "react-dom": "^18.2.0",
  "recharts": "^2.10.0",
  "tailwindcss": "^3.3.0",
  "typescript": "^5.6.0"
}
```

### Issues
- ✅ No missing dependencies
- ✅ Versions are compatible
- ⚠️ No form validation library (could add zod/yup later)
- ⚠️ No HTTP client library (using fetch is fine)

---

## Estimated Time to Fix

| Task | Estimate | Priority |
|------|----------|----------|
| Fix API URL | 5 min | CRITICAL |
| Fix error handling | 30 min | CRITICAL |
| Fix login flow | 20 min | CRITICAL |
| Fix TypeScript config | 5 min | HIGH |
| Create Grades page | 20 min | HIGH |
| Create Subjects page | 20 min | HIGH |
| Create List pages | 60 min | HIGH |
| Test all endpoints | 30 min | HIGH |
| Fix reports mock data | 15 min | MEDIUM |
| **TOTAL** | **205 min** | **~3.5 hours** |

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|-----------|
| API URL wrong | HIGH | Fix first, test immediately |
| Auth logic broken | HIGH | Use AuthContext only |
| Error handling missing | MEDIUM | Add JSON parsing |
| Missing pages | MEDIUM | Create after auth works |
| TypeScript warnings | LOW | Update target version |

---

## Success Criteria

✅ All tests passing when:
- [ ] Login works with correct credentials
- [ ] Invalid credentials show error
- [ ] Protected routes redirect unauthenticated users
- [ ] Token persists across page reloads
- [ ] Can create student/teacher/class
- [ ] Can view list of resources
- [ ] Can edit existing resource
- [ ] Can delete resource
- [ ] Validation errors displayed to user
- [ ] All pages render without 404s

---

## Next Steps

1. **Start with Critical Fixes** (30 min):
   - Fix API URL in next.config.js
   - Fix error handling in api.ts
   - Fix login flow to use AuthContext
   
2. **Test Authentication** (15 min):
   - Verify login works
   - Check token handling
   - Test protected routes

3. **Create Missing Pages** (90 min):
   - Grades page
   - Subjects page
   - List pages

4. **Full End-to-End Testing** (60 min):
   - Test all CRUD operations
   - Test error scenarios
   - Verify authorization

**Estimated Total: 3-4 hours**
