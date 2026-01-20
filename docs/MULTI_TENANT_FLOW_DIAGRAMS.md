# Multi-Tenant System - Flow Diagrams & Summary

## 1. School Registration & Activation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ NEW SCHOOL REGISTRATION                                         │
└─────────────────────────────────────────────────────────────────┘

1. REGISTRATION
   School registers via landing page
   ├─ Input: School name, admin name, email, password
   ├─ Create schools row with status='trial'
   ├─ Create users row (role='admin') with school_id
   ├─ Create activation_codes row (generate 12-digit code)
   └─ Create trial_limits row (50 books, 100 students, 200 borrows/month)

2. LOGIN (Day 1)
   Admin logs in
   ├─ Session set: school_id, role='admin'
   ├─ Check school status != 'suspended' → allow
   └─ Redirect to dashboard

3. DASHBOARD (Day 1-13)
   Admin sees:
   ├─ Status: TRIAL
   ├─ Days remaining: 13 days
   ├─ Kode aktivasi (masked): ****-****-XXXX
   ├─ Kapasitas: 0/50 buku, 0/100 siswa, 0/200 transaksi
   ├─ Tombol: "Ajukan Aktivasi"
   ├─ Trust score: 0/95
   └─ Peringatan jika approaching limits

4. ACTIVATION REQUEST (Day 7+)
   Admin clicks "Ajukan Aktivasi"
   ├─ Update schools.activation_requested_at = NOW()
   ├─ Update schools.activation_requested_by = user_id
   ├─ Log activity: 'activation_request'
   ├─ Recalculate trust_score
   │  ├─ +10 (activation requested)
   │  ├─ +15 (if email .sch.id)
   │  ├─ +20 (if activation code used recently)
   │  ├─ +25 (if normal activity)
   │  ├─ +10 (if trial > 7 days)
   │  ├─ +10 (if >= 5 transactions)
   │  └─ +5 (if email verified)
   │
   └─ Check score >= 70?
      ├─ YES → AUTO ACTIVATE
      │  ├─ Update schools.status = 'active'
      │  ├─ Remove limits from trial_limits
      │  └─ Send notification
      │
      └─ NO → Stay trial (wait for score increase)

5. AUTO ACTIVATION TRIGGER (when score >= 70)
   ├─ Check at recalculation (request activation)
   ├─ Check daily (optional cron job)
   └─ Update schools.status = 'trial' → 'active'

6. ACTIVE SCHOOL (Day 14+)
   Admin now has:
   ├─ No limits on books, students, transactions
   ├─ Can export/print reports
   ├─ Full system access
   └─ No more trial warnings

7. TRIAL EXPIRED (Day 15+)
   If still in trial:
   ├─ Display warning on dashboard
   ├─ Option: Re-submit activation request
   └─ Can still use system with limits
```

---

## 2. Student Registration Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ STUDENT SELF-REGISTRATION                                       │
└─────────────────────────────────────────────────────────────────┘

1. ACCESS REGISTRATION PAGE
   Student goes to: /public/student-register.php
   ├─ Load dropdown: active schools (status != 'suspended')
   └─ Display form

2. FILL FORM
   ├─ Select school from dropdown
   ├─ Input activation code (from admin)
   ├─ Input NISN
   ├─ Input name, email, password
   └─ Click "Daftar"

3. VALIDATION (Backend)
   ├─ Check school exists
   ├─ Verify activation code
   │  └─ Query: SELECT FROM activation_codes WHERE school_id = ? AND code = ? AND is_active = TRUE
   ├─ Check school not suspended
   ├─ Check NISN unique (per school)
   ├─ Validate email format
   ├─ Validate password strength
   └─ If validation fails → show error, stay on form

4. REGISTRATION (if validation pass)
   ├─ Insert INTO members: school_id, name, email, nisn (status='active')
   ├─ Insert INTO users: school_id, name, email, nisn, password, role='student'
   ├─ Log activity: 'student_registration'
   └─ Redirect to login success page

5. POST-REGISTRATION
   Student can:
   ├─ Login with email + password
   ├─ View borrowing history
   ├─ See available books (only from their school)
   └─ Request to borrow (if school is active)

6. SECURITY NOTES
   ├─ Activation code prevents fake registrations
   ├─ Only students who know code can join
   ├─ Increases trust score for school (factor: activation_code_entered)
   └─ Siswa iseng tidak bisa create school palsu
```

---

## 3. Trial Limits Enforcement

```
┌─────────────────────────────────────────────────────────────────┐
│ HARD LIMITS FOR TRIAL SCHOOLS                                   │
└─────────────────────────────────────────────────────────────────┘

CHECK POINTS:

┌─ Add Book
│  ├─ Is school trial?
│  ├─ Count books: SELECT COUNT(*) FROM books WHERE school_id = ?
│  ├─ Check: count < 50?
│  │  ├─ YES → Allow insert
│  │  └─ NO → TrialLimitException("Max 50 books")
│  └─ Log activity: 'book_creation'
│
├─ Add Student
│  ├─ Is school trial?
│  ├─ Count students: SELECT COUNT(*) FROM members WHERE school_id = ? AND status='active'
│  ├─ Check: count < 100?
│  │  ├─ YES → Allow insert
│  │  └─ NO → TrialLimitException("Max 100 students")
│  └─ Log activity: 'student_registration'
│
├─ Create Borrow
│  ├─ Is school trial?
│  ├─ Count borrows this month: SELECT COUNT(*) FROM borrows WHERE school_id = ? AND MONTH(borrowed_at) = MONTH(NOW())
│  ├─ Check: count < 200?
│  │  ├─ YES → Allow insert
│  │  └─ NO → TrialLimitException("Max 200/month")
│  └─ Log activity: 'borrow_creation'
│
├─ Export Report
│  ├─ Is school trial?
│  │  ├─ YES → TrialFeatureException("Only for active schools")
│  │  └─ NO → Allow export
│  └─ Log activity: 'report_export'
│
└─ Check Trial Expiry
   ├─ Calculate: created_at + 14 days
   ├─ If now > expiry AND school.status = 'trial'
   │  ├─ Show warning: "Trial expired, activate to continue"
   │  └─ Can still use with limits
   └─ If activated before expiry → no issue
```

---

## 4. Trust Score Calculation

```
┌─────────────────────────────────────────────────────────────────┐
│ TRUST SCORE FACTORS                                             │
└─────────────────────────────────────────────────────────────────┘

ADDITIVE FACTORS:
┌─────────────────────────────────────────────────┐
│ Factor                         | Points | Source │
├─────────────────────────────────────────────────┤
│ Activation requested           |  +10   | action │
│ Email admin .sch.id            |  +15   | static │
│ Activation code used           |  +20   | action │
│ Normal activity (no anomalies) |  +25   | check  │
│ Trial duration > 7 days        |  +10   | time   │
│ Minimal 5 transactions         |  +10   | count  │
│ Email verified                 |  +5    | static │
├─────────────────────────────────────────────────┤
│ MAXIMUM POSSIBLE               |  95    |        │
│ ACTIVATION THRESHOLD           |  70    |        │
└─────────────────────────────────────────────────┘

CALCULATION FLOW:
┌─────────────────────────────────────────────────────────┐
│ recalculateTrustScore($school_id)                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ 1. Check activation requested?                          │
│    ├─ SELECT activation_requested_at FROM schools      │
│    └─ If NOT NULL → score += 10                        │
│                                                         │
│ 2. Check email domain (.sch.id)?                        │
│    ├─ SELECT email FROM users WHERE role='admin'       │
│    └─ If LIKE '%.sch.id' → score += 15                │
│                                                         │
│ 3. Check activation code used recently?                 │
│    ├─ SELECT FROM activation_codes                     │
│    └─ If regenerated_at > 30 days ago → score += 20   │
│                                                         │
│ 4. Check for anomalies                                  │
│    ├─ Bulk upload books > 100 in 1 hour → penalty -20 │
│    ├─ Delete > 50 data in 1 day → penalty -25         │
│    ├─ Login failed > 10x → penalty -15                 │
│    └─ No anomalies → score += 25                       │
│                                                         │
│ 5. Check trial age > 7 days?                            │
│    ├─ DATEDIFF(NOW(), trial_started_at)               │
│    └─ If > 7 → score += 10                             │
│                                                         │
│ 6. Check transaction count >= 5?                        │
│    ├─ SELECT COUNT(*) FROM borrows                     │
│    └─ If >= 5 → score += 10                            │
│                                                         │
│ 7. Check email verified?                                │
│    ├─ SELECT email FROM schools WHERE email NOT NULL  │
│    └─ If present → score += 5                          │
│                                                         │
│ 8. Clamp score to max 95                               │
│    └─ score = min(score, 95)                           │
│                                                         │
│ 9. Check if score >= 70?                                │
│    ├─ YES → autoActivateSchool()                       │
│    │        Update status = 'active'                   │
│    │        Log to trust_score_history                 │
│    │        Remove trial_limits                        │
│    │                                                   │
│    └─ NO → Stay trial, wait for more factors           │
│                                                         │
└─────────────────────────────────────────────────────────┘

EXAMPLE PROGRESSION:
┌───────────────────────────────────────┐
│ Day 1  │ Register                     │ Score: 0
├────────┼──────────────────────────────┼─────────
│ Day 3  │ Add admin email @sch.id      │ Score: 15
├────────┼──────────────────────────────┼─────────
│ Day 7  │ Add 5 students (verify code) │ Score: 35 (15 + 20)
├────────┼──────────────────────────────┼─────────
│ Day 8  │ Submit activation request    │ Score: 45 (35 + 10)
├────────┼──────────────────────────────┼─────────
│ Day 10 │ Create 10 borrowing trans    │ Score: 70 (45 + 25)
│        │ No anomalies detected        │
├────────┼──────────────────────────────┼─────────
│ Day 10 │ SCORE >= 70!                 │ AUTO ACTIVATE!
│        │ Status changes to 'ACTIVE'   │
└───────────────────────────────────────┘

ANOMALY DETECTION:
┌──────────────────────────────────────────────────────┐
│ Activity                       | Penalty | Trigger   │
├──────────────────────────────────────────────────────┤
│ Bulk book upload > 100/hour    |  -20    | insert   │
│ Bulk delete data > 50/day      |  -25    | delete   │
│ Failed login > 10/session      |  -15    | login    │
│ SQL injection detected         |  -50    | query    │
│ Multiple schools from same IP  |  -30    | register │
└──────────────────────────────────────────────────────┘
```

---

## 5. Data Isolation (Multi-Tenant)

```
┌─────────────────────────────────────────────────────────┐
│ ISOLATION GUARANTEE: school_id in EVERY TABLE           │
└─────────────────────────────────────────────────────────┘

TABLE STRUCTURE:
┌────────────────┬──────────────┬──────────────────────┐
│ Table          │ Has Column   │ Isolation Method     │
├────────────────┼──────────────┼──────────────────────┤
│ schools        │ id           │ PK                   │
│ users          │ school_id FK │ WHERE school_id = ?  │
│ members        │ school_id FK │ WHERE school_id = ?  │
│ books          │ school_id FK │ WHERE school_id = ?  │
│ borrows        │ school_id FK │ WHERE school_id = ?  │
│ activation_... │ school_id FK │ WHERE school_id = ?  │
│ trust_scores   │ school_id FK │ WHERE school_id = ?  │
│ trial_limits   │ school_id FK │ WHERE school_id = ?  │
└────────────────┴──────────────┴──────────────────────┘

QUERY PATTERN (MUST FOLLOW):
┌──────────────────────────────────────────────────────┐
│ SELECT * FROM books WHERE school_id = :school_id    │ ✓ CORRECT
├──────────────────────────────────────────────────────┤
│ SELECT * FROM books WHERE title LIKE '%...'         │ ✗ WRONG
└──────────────────────────────────────────────────────┘

EXAMPLE DATA:
┌─────────────────────────────────────────────────────────┐
│ Admin School 1 (School ID = 2)                          │
├─────────────────────────────────────────────────────────┤
│ Session: school_id = 2                                  │
│                                                         │
│ Query: SELECT * FROM books WHERE school_id = 2        │
│ ├─ Returns: Books for school 2 only ✓                 │
│ │          (10 books)                                  │
│ │                                                     │
│ └─ Try to access school 7 data?                       │
│    SELECT * FROM books WHERE school_id = 7           │
│    └─ Returns: Empty (permissions denied) ✓           │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 6. Security Layers

```
┌─────────────────────────────────────────────────────────┐
│ MULTI-LAYER SECURITY ARCHITECTURE                       │
└─────────────────────────────────────────────────────────┘

LAYER 1: Authentication
┌─────────────────────────────────────────┐
│ Check: Is user logged in?                │
├─────────────────────────────────────────┤
│ if (empty($_SESSION['user']))           │
│    → Redirect to login                  │
│                                          │
│ Extract: school_id from SESSION         │
│ (NOT from GET/POST)                     │
└─────────────────────────────────────────┘
         ↓

LAYER 2: Authorization
┌─────────────────────────────────────────┐
│ Check: User role (admin/student)         │
├─────────────────────────────────────────┤
│ if ($_SESSION['user']['role'] != 'admin')│
│    → Deny access                         │
└─────────────────────────────────────────┘
         ↓

LAYER 3: Tenant Isolation
┌─────────────────────────────────────────┐
│ Check: school_id in every query          │
├─────────────────────────────────────────┤
│ WHERE school_id = :school_id            │
│        (from SESSION, not request)       │
│                                          │
│ Verify: Resource belongs to school      │
│ DELETE FROM books                       │
│ WHERE id = ? AND school_id = ?          │
└─────────────────────────────────────────┘
         ↓

LAYER 4: Status Check
┌─────────────────────────────────────────┐
│ Check: School status (trial/active/sus) │
├─────────────────────────────────────────┤
│ if (isSchoolSuspended())                │
│    → Deny access                        │
│                                          │
│ if (isSchoolTrial())                    │
│    → Check limits before action         │
└─────────────────────────────────────────┘
         ↓

LAYER 5: Business Logic Validation
┌─────────────────────────────────────────┐
│ Check: Hard limits (trial)               │
├─────────────────────────────────────────┤
│ if (count >= MAX_BOOKS)                 │
│    → Throw TrialLimitException          │
│                                          │
│ Check: Feature restrictions             │
│ if (isTrial && isExport())              │
│    → Throw TrialFeatureException        │
└─────────────────────────────────────────┘
         ↓

LAYER 6: Audit & Monitoring
┌─────────────────────────────────────────┐
│ Log: All activities                      │
├─────────────────────────────────────────┤
│ INSERT INTO school_activities:          │
│ - activity_type                         │
│ - action                                │
│ - user_id                               │
│ - ip_address                            │
│ - timestamp                             │
│                                          │
│ Detect: Anomalies                       │
│ - Bulk operations                       │
│ - Unusual patterns                      │
│ - Apply penalties to trust score        │
└─────────────────────────────────────────┘
```

---

## 7. Key Differences: Before vs After

```
BEFORE (Vulnerable)          │ AFTER (Secure)
─────────────────────────────┼──────────────────────
No school isolation          │ Every query filters school_id
school_id from GET/POST      │ school_id from SESSION
No status checks             │ Check trial/active/suspended
No limits                    │ Hard limits for trial
No activation flow           │ Trust score + auto activation
Siswa bisa buat sekolah fake │ Sekolah code verification
No activity logging          │ Full audit trail
No anomaly detection         │ Anomaly detection + penalties
```

---

## Roadmap: Implementation Phases

```
PHASE 1: DATABASE (Day 1)
└─ Run migration SQL
└─ Verify new tables created

PHASE 2: CORE LOGIC (Day 1-2)
└─ Copy MultiTenantManager.php
└─ Copy TrialLimitsManager.php
└─ Test functions manually

PHASE 3: AUTH REFACTOR (Day 2)
└─ Update login.php (status check)
└─ Update register.php (admin)
└─ Add session school_id

PHASE 4: STUDENT REG (Day 3)
└─ Create student-register.php
└─ Add code verification
└─ Test registration flow

PHASE 5: DASHBOARD (Day 3-4)
└─ Create admin-dashboard.php
└─ Display status & trust score
└─ Add activation request button

PHASE 6: QUERY AUDIT (Day 4-6)
└─ Refactor books.php
└─ Refactor members.php
└─ Refactor borrows.php
└─ Refactor reports.php
└─ Refactor all API endpoints

PHASE 7: TESTING (Day 6-7)
└─ Security testing
└─ Functional testing
└─ Performance testing

PHASE 8: MONITORING (Day 7+)
└─ Activity logging
└─ Anomaly detection
└─ Optional admin panel
```

---

## Documentation Files Created

```
docs/
├─ MULTI_TENANT_SYSTEM_DESIGN.md    ← Overview & concept
├─ IMPLEMENTATION_GUIDE.md           ← Step-by-step guide
├─ QUERY_AUDIT_GUIDE.md              ← Before/after patterns
└─ MULTI_TENANT_FLOW_DIAGRAMS.md    ← This file

src/
├─ MultiTenantManager.php            ← Core functions
├─ TrialLimitsManager.php            ← Limit enforcement
├─ (ADD THESE)

public/
├─ student-register.php              ← New student reg form
├─ admin-dashboard.php               ← Admin dashboard
├─ login.php                         ← (UPDATE)
├─ (REFACTOR others)
│
└─ api/
   └─ school-activation.php          ← New API

sql/
└─ migrations/
   └─ 02-multi-tenant-schema.sql    ← New migration
```
