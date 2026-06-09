# Frontend Architecture & Flow Analysis

**Date**: June 8, 2026  
**Status**: Needs Integration with Backend API

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Next.js 16.2.6 Frontend                  │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         Root Layout (layout.tsx)                     │   │
│  │  - AuthProvider wrapper                             │   │
│  │  - LayoutClient (navbar, logout)                    │   │
│  └──────────────────────────────────────────────────────┘   │
│                          ↓                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         AuthContext (context/AuthContext.tsx)       │   │
│  │  - user: User | null                                │   │
│  │  - token: string | null                             │   │
│  │  - loading: boolean                                 │   │
│  │  - login(), logout()                                │   │
│  │  - isAuthenticated: boolean                         │   │
│  │  - localStorage persistence                         │   │
│  └──────────────────────────────────────────────────────┘   │
│                          ↓                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         Services Layer (services/)                  │   │
│  │  - api.ts: Generic API client                       │   │
│  │  - auth.ts: login(), register()                     │   │
│  │  - studentService.ts: student CRUD                  │   │
│  │  - teacherService.ts: teacher CRUD + reports       │   │
│  │  - gradeService.ts: grade CRUD                      │   │
│  │  - attendanceService.ts: attendance CRUD           │   │
│  │  - userService.ts: user management                  │   │
│  └──────────────────────────────────────────────────────┘   │
│                          ↓                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         API Client (api.ts)                         │   │
│  │  Base URL: http://localhost:8000/api                │   │
│  │  - Adds Authorization header with token             │   │
│  │  - Error handling (throws on non-2xx)               │   │
│  └──────────────────────────────────────────────────────┘   │
│                          ↓                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │         Backend API (PHP)                           │   │
│  │  http://localhost:8000/auth/login                   │   │
│  │  http://localhost:8000/students                     │   │
│  │  http://localhost:8000/teachers                     │   │
│  │  http://localhost:8000/... (NO /api prefix)         │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Current Data Flow

### Login Flow

```
User Types Credentials
        ↓
    Login Page (/login)
        ↓
  handleSubmit() calls login(email, password)
        ↓
  auth.ts::login() → apiCall('/auth/login', POST)
        ↓
  api.ts::apiCall()
    - Adds headers: Content-Type, Authorization (if token exists)
    - Makes fetch request to http://localhost:8000/api/auth/login
        ↓
  Backend Returns: { success: true, data: { token, user }, message }
        ↓
  auth.ts stores in localStorage:
    - auth_token
    - auth_user
        ↓
  AuthContext::login() is NOT called!  ← ⚠️ ISSUE #1
        ↓
  Page redirects to /dashboard
```

**Issue**: There are TWO separate auth implementations:
1. **AuthContext.tsx** - Provides `login()` method (used in context)
2. **auth.ts** - Has `login()` and `register()` functions (used in LoginPage)

These don't work together!

### Protected Route Flow

```
User navigates to /students
        ↓
  Middleware.ts (does nothing - just returns NextResponse.next())
        ↓
  Layout component checks AuthContext::token from localStorage
        ↓
  If token exists → Show header navbar
  If no token and not on /login → Redirect to /login (AuthContext useEffect)
        ↓
  Student Page Loads
        ↓
  useEffect() calls:
    - getClasses() → api.ts → http://localhost:8000/api/classes
    - getUsers() → api.ts → http://localhost:8000/api/users
        ↓
  Backend returns data → Populate dropdowns
        ↓
  User fills form → handleSubmit() → createStudent()
        ↓
  createStudent() → apiCall('/students', POST)
        ↓
  Backend returns student → Display success message
```

---

## 3. Page Structure

### Existing Pages
- ✅ `/login` - Registration & Login (uses auth.ts)
- ✅ `/dashboard` - Main dashboard with links
- ✅ `/students` - Create student form + dropdowns
- ✅ `/teachers` - Create teacher form
- ✅ `/attendance` - Attendance form (check files)
- ✅ `/reports` - Reports dashboard with charts
- ✅ `/admin` - Admin panel (check files)

### Missing Pages
- ❌ `/grades` - Grade management (folder empty)
- ❌ `/subjects` - Subject management (NO folder)
- ❌ `/students/list` - List all students with edit/delete
- ❌ `/teachers/list` - List all teachers with edit/delete
- ❌ `/grades/list` - List all grades
- ❌ `/subjects/list` - List all subjects

---

## 4. Critical Issues Found

### ⚠️ Issue #1: Duplicate Login Logic
**Location**: `auth.ts` vs `AuthContext.tsx`

**Problem**:
- `LoginPage` uses `auth.ts::login()` 
- `AuthContext` has its own `login()` method
- They don't communicate!

**Current Flow**:
```
LoginPage → auth.ts::login() → localStorage ← Independent!
                                      ↓
                            AuthContext reads from localStorage
                            (only on next render)
```

**What should happen**:
```
LoginPage → AuthContext::login() → API call
                                      ↓
                              Updates state + localStorage
```

### ⚠️ Issue #2: Wrong API URL
**Location**: `next.config.js` and `api.ts`

**Problem**:
```javascript
// next.config.js
NEXT_PUBLIC_API_URL: 'http://localhost:8000/api'

// But backend doesn't have /api:
// POST http://localhost:8000/api/auth/login  ← WRONG
// Should be: http://localhost:8000/auth/login ← CORRECT
```

### ⚠️ Issue #3: Error Handling
**Location**: `api.ts`

**Problem**:
```typescript
if (!response.ok) {
  const text = await response.text().catch(() => '');
  const body = text || response.statusText;
  throw new Error(`API Error: ${response.status} - ${body}`);
  // Only shows raw text, not JSON errors with validation details
}
```

**What we need**:
- Parse JSON response
- Extract error message and validation field errors
- Show field-level errors to user

### ⚠️ Issue #4: TypeScript Configuration
**Location**: `tsconfig.json`

**Problems**:
```json
{
  "target": "es5",     // ⚠️ Deprecated in TS 5.6
  "baseUrl": ".",
  "paths": { "@/*": ["./*"] }  // Can cause module resolution issues
}
```

### ⚠️ Issue #5: Mock Data in Reports
**Location**: `app/reports/page.tsx`

**Problem**:
```typescript
// Hardcoded mock data instead of API data
const mockData = [
  { name: 'Present', value: 85 },
  { name: 'Absent', value: 10 },
  { name: 'Late', value: 5 },
];
```

---

## 5. Service Layer Architecture

### Current Services

#### `api.ts`
```
Generic fetch wrapper
├─ Takes endpoint and options
├─ Adds auth token from localStorage
├─ Adds Content-Type header
├─ Throws on non-2xx responses
└─ Returns parsed JSON
```

#### `auth.ts`
```
Authentication endpoints
├─ login(email, password)
│  └─ Stores token + user in localStorage
└─ register(name, email, password)
   └─ Returns user object
```

#### `studentService.ts`
```
Student management
├─ createStudent(payload)
├─ getStudents()
├─ getClasses()
└─ getUsers()
```

#### `teacherService.ts`
```
Teacher management + Reports
├─ createTeacher(payload)
├─ getTeachers()
└─ getReports(filters)
```

#### Other Services
```
gradeService.ts, attendanceService.ts, userService.ts
(Similar pattern - CRUD operations)
```

---

## 6. Component Structure

### Pages (App Routes)
- `layout.tsx` - Root layout with AuthProvider
- `login/page.tsx` - Auth page
- `dashboard/page.tsx` - Dashboard
- `students/page.tsx` - Student form
- `teachers/page.tsx` - Teacher form
- `attendance/page.tsx` - Attendance form
- `reports/page.tsx` - Reports dashboard
- `admin/page.tsx` - Admin panel

### Components
```
components/
├─ LayoutClient.tsx (Header nav + logout)
├─ dashboard/ (Dashboard charts)
├─ forms/ (Form components)
├─ layout/ (Layout helpers)
├─ tables/ (Data table components)
└─ ui/ (UI components)
```

### Context
```
context/
└─ AuthContext.tsx (Auth state + login/logout)
```

---

## 7. Frontend Environment Variables

**Not currently set** - Uses defaults:
```
NEXT_PUBLIC_API_URL = 'http://localhost:8000/api'  ← WRONG! Should be without /api
```

---

## 8. Authentication Flow in Detail

### Initial Load
```
1. Browser loads app
2. AuthProvider useEffect runs
3. Reads localStorage for auth_token + auth_user
4. If exists: setToken() + setUser()
5. If not exists: setLoading(false)
6. Second useEffect: If !token && pathname !== '/login' → redirect to /login
```

### Login (Current - PROBLEMATIC)
```
1. User submits LoginPage form
2. handleSubmit() calls auth.ts::login()
3. auth.ts::login() calls apiCall('/auth/login', POST)
4. Response stored in localStorage
5. Page redirects to /
6. AuthContext reads from localStorage (with delay)
7. State updates → renders with token
```

### Login (Should Be)
```
1. User submits LoginPage form
2. handleSubmit() calls AuthContext.login()
3. AuthContext.login() calls API
4. Updates context state + localStorage (same action)
5. useEffect sees updated token
6. Redirects to dashboard
```

---

## 9. Current Limitations

| Feature | Status | Issue |
|---------|--------|-------|
| Login/Register | ⚠️ Works but broken | Duplicate logic, no error handling |
| Token Management | ✅ Works | localStorage-based (not httpOnly) |
| Protected Routes | ✅ Works | Redirect works via context |
| CRUD Operations | ⚠️ Partially | API URL wrong, no error parsing |
| Validation Errors | ❌ Not handled | API errors not displayed to user |
| List Pages | ❌ Missing | Only create forms, no list/edit/delete |
| Grades Page | ❌ Missing | Folder empty, no implementation |
| Subjects Page | ❌ Missing | Not even created |
| Reports | ⚠️ Mock data | Shows hardcoded instead of real data |
| Admin Panel | ⚠️ Unknown | Need to check implementation |

---

## 10. Integration Checklist

To connect frontend to backend:

- [ ] Fix API URL (remove `/api` suffix)
- [ ] Consolidate login flow (use AuthContext only)
- [ ] Add proper error handling (parse JSON errors, show validation)
- [ ] Fix TypeScript config (es5 → es2020)
- [ ] Create missing pages (Grades, Subjects, Lists)
- [ ] Wire reports to real API data
- [ ] Implement list/edit/delete for students & teachers
- [ ] Test full authentication flow
- [ ] Test all CRUD operations
- [ ] Test error scenarios

---

## 11. Next.js Routing Structure

```
app/
├─ layout.tsx              # Root layout
├─ page.tsx                # / (redirects to /login or /dashboard)
├─ login/
│  └─ page.tsx            # /login
├─ dashboard/
│  └─ page.tsx            # /dashboard
├─ students/
│  └─ page.tsx            # /students (create form)
├─ teachers/
│  └─ page.tsx            # /teachers (create form)
├─ attendance/
│  └─ page.tsx            # /attendance
├─ reports/
│  └─ page.tsx            # /reports
├─ grades/                 # Empty folder
│  └─ (create page.tsx)   # /grades (MISSING)
├─ admin/
│  └─ page.tsx            # /admin
└─ globals.css            # Global styles
```

---

## 12. Key Files to Understand Flow

1. **entry point**: `app/layout.tsx` - Sets up AuthProvider
2. **auth context**: `context/AuthContext.tsx` - Auth state management
3. **auth service**: `services/auth.ts` - API calls (PROBLEMATIC)
4. **api client**: `services/api.ts` - Generic fetch wrapper
5. **login page**: `app/login/page.tsx` - Uses auth.ts (should use context)
6. **student page**: `app/students/page.tsx` - Example of CRUD
7. **middleware**: `middleware.ts` - Currently disabled (relying on client-side)

---

## Summary

**Frontend Status**: ⚠️ **Partially Ready**

**What Works**:
- ✅ Basic page structure
- ✅ Authentication context setup
- ✅ Protected route protection
- ✅ localStorage token persistence
- ✅ Service layer pattern

**What's Broken**:
- ❌ API URL pointing to wrong endpoint (`/api` suffix)
- ❌ Duplicate login logic (auth.ts vs AuthContext)
- ❌ No error handling for validation errors
- ❌ TypeScript deprecation warnings
- ❌ Mock data in reports instead of real API

**What's Missing**:
- ❌ Grades page
- ❌ Subjects page
- ❌ List/edit/delete pages for all resources
- ❌ Form validation on frontend
- ❌ Proper error display to users

**Next Actions**:
1. Fix API URL configuration
2. Consolidate auth flow
3. Add proper error handling
4. Create missing pages
5. Test against backend
