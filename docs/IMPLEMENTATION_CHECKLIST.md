# COMPREHENSIVE IMPLEMENTATION CHECKLIST

## Pre-Implementation Review

- [ ] Backup database: `perpustakaan_online`
- [ ] Backup folder: `C:\xampp\htdocs\perpustakaan-online`
- [ ] Read: `MULTI_TENANT_SYSTEM_DESIGN.md`
- [ ] Understand: Trust score system
- [ ] Understand: Trial limits
- [ ] Understand: Multi-tenant isolation pattern

---

## PHASE 1: Database Preparation

### Step 1.1: Backup Current Database

```bash
# Dump existing database for backup
# Use phpMyAdmin: Export -> perpustakaan_online.sql
```

- [ ] Database backed up
- [ ] Save to: `C:\xampp\htdocs\perpustakaan-online\backups\`

### Step 1.2: Review Migration SQL

- [ ] Read: `sql/migrations/02-multi-tenant-schema.sql`
- [ ] Understand all changes
- [ ] Check table names match existing schema

### Step 1.3: Execute Migration

- [ ] Open phpMyAdmin
- [ ] Select database: `perpustakaan_online`
- [ ] Go to SQL tab
- [ ] Copy entire content of `02-multi-tenant-schema.sql`
- [ ] Execute
- [ ] Check: No errors

### Step 1.4: Verify Migration Success

```sql
-- Run these in phpMyAdmin SQL tab:

-- Check schools table has new columns
DESCRIBE schools;
-- Should show: status, trust_score, activation_requested_at, etc.

-- Check new tables exist
SHOW TABLES LIKE '%activation%';
SHOW TABLES LIKE '%trust%';
SHOW TABLES LIKE '%trial%';

-- Check sample data
SELECT id, name, status, trust_score FROM schools LIMIT 3;
SELECT school_id, code FROM activation_codes LIMIT 3;
SELECT school_id, total_score FROM trust_scores LIMIT 3;
```

- [ ] schools table has `status` column
- [ ] activation_codes table created
- [ ] trust_scores table created
- [ ] trial_limits table created
- [ ] school_activities table created
- [ ] All existing schools have activation codes
- [ ] All existing schools have trial limits

---

## PHASE 2: Core PHP Classes

### Step 2.1: Copy MultiTenantManager.php

- [ ] File exists: `src/MultiTenantManager.php`
- [ ] Copy from workspace (already created)
- [ ] Or create manually from content

### Step 2.2: Copy TrialLimitsManager.php

- [ ] File exists: `src/TrialLimitsManager.php`
- [ ] Copy from workspace (already created)

### Step 2.3: Test Classes Manually

Create file: `test-managers.php` in project root

```php
<?php
$pdo = require 'src/db.php';
require_once 'src/MultiTenantManager.php';
require_once 'src/TrialLimitsManager.php';

$mtManager = new MultiTenantManager($pdo);
$limitsManager = new TrialLimitsManager($pdo, $mtManager);

// Test school 2
$school_id = 2;

echo "School 2 Status: " . $mtManager->getSchoolStatus($school_id) . "\n";
echo "Is Trial: " . ($mtManager->isSchoolTrial($school_id) ? 'Yes' : 'No') . "\n";
echo "Activation Code: " . $mtManager->getActivationCodeMasked($school_id) . "\n";
echo "Book Count: " . $limitsManager->getBookCount($school_id) . "\n";
echo "Student Count: " . $limitsManager->getStudentCount($school_id) . "\n";

echo "\nAll tests completed successfully!\n";
?>
```

Run in browser: `http://localhost/perpustakaan-online/test-managers.php`

- [ ] Test file runs without errors
- [ ] All functions return expected data
- [ ] Delete test-managers.php after testing

---

## PHASE 3: Update Login System

### Step 3.1: Review Current login.php

- [ ] File: `public/login.php`
- [ ] Find: authentication check code
- [ ] Understand: current flow

### Step 3.2: Update login.php

Add after password verification check:

```php
// Add after password_verify() check
if ($user && password_verify($password, $user['password'])) {
    // NEW CODE: Check school status
    require_once __DIR__ . '/../src/MultiTenantManager.php';
    $mtManager = new MultiTenantManager($pdo);

    if ($mtManager->isSchoolSuspended($user['school_id'])) {
        $message = 'Sekolah Anda telah dinonaktifkan. Hubungi admin platform.';
    } else {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'school_id' => $user['school_id'],
            'name' => $user['name'],
            'role' => $user['role']
        ];
        header('Location: /perpustakaan-online/public/index.php');
        exit;
    }
}
```

- [ ] Status check added to login.php
- [ ] school_id added to $\_SESSION
- [ ] Test login with school 2 admin
- [ ] Verify session contains school_id

### Step 3.3: Test Login Flow

1. Go to: `http://localhost/perpustakaan-online/public/login.php`
2. Login with school 2 admin credentials
3. Check: Session has school_id
4. Check: Redirected to dashboard

- [ ] Login works
- [ ] Session contains correct school_id
- [ ] Can access dashboard

---

## PHASE 4: Student Registration Page

### Step 4.1: Copy student-register.php

- [ ] File exists: `public/student-register.php`
- [ ] Check: Contains activation code verification
- [ ] Check: Form validation complete

### Step 4.2: Test Student Registration

1. Go to: `http://localhost/perpustakaan-online/public/student-register.php`
2. Select school: SMK BM3 (school 7)
3. Get activation code:
   ```sql
   SELECT code FROM activation_codes WHERE school_id = 7;
   ```
4. Input code in form
5. Fill other fields
6. Submit

- [ ] Form displays correctly
- [ ] School dropdown populated
- [ ] Validation works (wrong code → error)
- [ ] Correct code → success
- [ ] Student created in database

### Step 4.3: Verify Student Account

```sql
-- Check student was created
SELECT * FROM members WHERE school_id = 7 ORDER BY id DESC LIMIT 1;
SELECT * FROM users WHERE school_id = 7 AND role = 'student' ORDER BY id DESC LIMIT 1;
```

- [ ] Entry exists in members table
- [ ] Entry exists in users table
- [ ] school_id matches
- [ ] role = 'student'

---

## PHASE 5: Admin Dashboard

### Step 5.1: Copy admin-dashboard.php

- [ ] File exists: `public/admin-dashboard.php`
- [ ] Contains status display
- [ ] Contains trust score display
- [ ] Contains activation button

### Step 5.2: Test Admin Dashboard

1. Login as school 2 admin
2. Go to: `http://localhost/perpustakaan-online/public/admin-dashboard.php`
3. Verify display:
   - [ ] Status: TRIAL
   - [ ] Trust Score: 0/95
   - [ ] Activation Code (masked)
   - [ ] Days remaining
   - [ ] Capacity indicators (books, students, borrows)
   - [ ] Activation request button

### Step 5.3: Test Activation Request

1. Click "Ajukan Aktivasi" button
2. Check database:
   ```sql
   SELECT activation_requested_at, trust_score FROM schools WHERE id = 2;
   ```
3. Verify:
   - [ ] activation_requested_at is set (not NULL)
   - [ ] trust_score increased (should be >= 10)

---

## PHASE 6: Query Audit (Critical)

### Step 6.1: List Pages to Audit

Create checklist for each:

- [ ] public/books.php
- [ ] public/members.php
- [ ] public/borrows.php
- [ ] public/reports.php
- [ ] public/index.php (dashboard)

Plus all files in:

- [ ] public/api/\*.php
- [ ] public/partials/\*.php

### Step 6.2: Audit Pattern for Each Page

For each page, check:

1. **At file top:** Add session & school_id

   ```php
   session_start();
   if (empty($_SESSION['user'])) {
       header('Location: /perpustakaan-online/public/login.php');
       exit;
   }
   $school_id = $_SESSION['user']['school_id'];
   ```

2. **Every SELECT query:** Add WHERE school_id = :school_id

   ```php
   BEFORE: SELECT * FROM books
   AFTER:  SELECT * FROM books WHERE school_id = :school_id
   ```

3. **Every UPDATE query:** Add AND school_id = :school_id

   ```php
   BEFORE: UPDATE books SET title = ? WHERE id = ?
   AFTER:  UPDATE books SET title = ? WHERE id = ? AND school_id = ?
   ```

4. **Every DELETE query:** Add AND school_id = :school_id

   ```php
   BEFORE: DELETE FROM books WHERE id = ?
   AFTER:  DELETE FROM books WHERE id = ? AND school_id = ?
   ```

5. **INSERT queries:** Remove school_id from POST, use session instead
   ```php
   BEFORE: 'school_id' => $_POST['school_id']
   AFTER:  'school_id' => $school_id (from session)
   ```

### Step 6.3: Example: Refactor public/books.php

Open `public/books.php` and find all database queries.

For each SELECT, UPDATE, DELETE:

1. Use "Find & Replace" to add conditions
2. Test the page works after changes
3. Try to access data from another school → should fail

### Step 6.4: Test Each Refactored Page

For `books.php`:

1. Login as school 2 admin
2. View books → only school 2 books shown
3. Try to access school 7 books → not found
4. Try to delete school 7 book (manual SQL injection test) → should fail

- [ ] books.php refactored & tested
- [ ] members.php refactored & tested
- [ ] borrows.php refactored & tested
- [ ] reports.php refactored & tested
- [ ] index.php refactored & tested
- [ ] All API endpoints refactored & tested

---

## PHASE 7: Trial Limits Enforcement

### Step 7.1: Integrate TrialLimitsManager to Add Book

In `public/books.php` (or wherever book is added):

```php
<?php
session_start();
require_once __DIR__ . '/../src/MultiTenantManager.php';
require_once __DIR__ . '/../src/TrialLimitsManager.php';

$pdo = require __DIR__ . '/../src/db.php';
$mtManager = new MultiTenantManager($pdo);
$limitsManager = new TrialLimitsManager($pdo, $mtManager);
$school_id = $_SESSION['user']['school_id'];

if ($_POST && $_SESSION['user']['role'] === 'admin') {
    try {
        // Check trial limits BEFORE insert
        $limitsManager->checkBeforeAddBook($school_id);

        // ... insert book code ...

        $mtManager->logActivity($school_id, 'book_creation', 'create', 1, $_SESSION['user']['id']);
    } catch (TrialLimitException $e) {
        $error = $e->getMessage();
    }
}
?>
```

- [ ] TrialLimitsManager integrated to book add
- [ ] checkBeforeAddBook() called before insert
- [ ] Activity logging added

### Step 7.2: Do Same for Student Add & Borrow Create

- [ ] Student add: use `checkBeforeAddStudent()`
- [ ] Borrow create: use `checkBeforeBorrow()`
- [ ] Activity logging for each

### Step 7.3: Test Trial Limits

**Test 1: Add book (should work, < 50)**

1. Login as school 2 admin (trial)
2. Go to books page
3. Add new book
4. Verify: success
5. Check: activity logged in school_activities

- [ ] Can add book (count < 50)
- [ ] Activity logged

**Test 2: Reach limit (should fail)**

1. Add books until exactly 50
2. Try to add 51st book
3. Verify: Error message shows
4. Verify: Book NOT inserted

- [ ] Limit enforcement works
- [ ] Error message clear
- [ ] No data inserted

**Test 3: Active school (no limits)**

1. Manually change school 7 to active:
   ```sql
   UPDATE schools SET status = 'active' WHERE id = 7;
   ```
2. Login as school 7 admin
3. Try to add unlimited books
4. Verify: All successful

- [ ] Active schools have no limits
- [ ] Can add unlimited data

---

## PHASE 8: Feature Restrictions for Trial

### Step 8.1: Block Export/Print for Trial

In `public/reports.php` (export function):

```php
<?php
$limitsManager = new TrialLimitsManager($pdo, $mtManager);

if ($_POST['action'] === 'export') {
    try {
        $limitsManager->checkBeforeExportReport($school_id);
        // ... do export ...
    } catch (TrialFeatureException $e) {
        $error = $e->getMessage();
    }
}
?>
```

- [ ] Export check added
- [ ] Trial schools blocked from export
- [ ] Active schools can export

### Step 8.2: Show Trial Warnings on Dashboard

In `public/index.php`:

```php
<?php
$warnings = $limitsManager->getWarnings($school_id);
if (!empty($warnings)) {
    foreach ($warnings as $warning) {
        echo "<div class='warning'>" . htmlspecialchars($warning['message']) . "</div>";
    }
}
?>
```

- [ ] Warnings display on dashboard
- [ ] Warnings appear when approaching limits
- [ ] Warnings appear when trial expiring

---

## PHASE 9: Trust Score & Auto Activation

### Step 9.1: Manual Trust Score Test

In phpMyAdmin SQL tab:

```php
<?php
// Test with school 2
$school_id = 2;

// 1. Submit activation request first
// (Click button in admin-dashboard.php)

// 2. Then manually recalculate
$mtManager = new MultiTenantManager($pdo);
$newScore = $mtManager->recalculateTrustScore($school_id);
echo "New Score: " . $newScore . "\n";

// 3. Check if auto-activated
$stmt = $pdo->prepare('SELECT status FROM schools WHERE id = ?');
$stmt->execute([$school_id]);
$status = $stmt->fetch()['status'];
echo "Status: " . $status . "\n";
?>
```

- [ ] Trust score calculated
- [ ] Score increases on activation request
- [ ] Auto activation works (if score >= 70)

### Step 9.2: Simulate Full Activation Scenario

1. Use existing school 7 (trial, has some data)
2. Login as admin
3. Go to admin-dashboard.php
4. Click "Ajukan Aktivasi"
5. Check database trust score
6. Add several factors to reach 70:
   - Manually update email to .sch.id domain
   - Verify email in database
   - Create more transactions
   - Recalculate trust score
7. When >= 70, should auto-activate
8. Verify: status changes to 'active'

- [ ] Activation request works
- [ ] Trust score calculates correctly
- [ ] Auto-activation triggers at score >= 70
- [ ] Trial limits removed after activation

---

## PHASE 10: Security Testing

### Step 10.1: SQL Injection Test

Try to access data from different school via URL hack:

```
// Try to view school 2 data as school 7 user
// Should fail because session school_id != input
```

- [ ] Cannot access other school's data
- [ ] Query filtering prevents cross-tenant access

### Step 10.2: Session Hijacking Test

Modify session school_id value:

```php
// Try to change session
$_SESSION['user']['school_id'] = 999;
// Should still only see school 7 data
```

- [ ] Changes don't affect query results
- [ ] Backend always uses session value

### Step 10.3: POST Injection Test

Send POST with different school_id:

```html
<!-- Try to create student for wrong school -->
<input type="hidden" name="school_id" value="999" />
```

- [ ] System uses session, ignores POST
- [ ] Student created for correct school

### Step 10.4: Limit Bypass Test

Try to add > 50 books via API directly:

```
POST /api/books.php
[Insert 100 books programmatically]
```

- [ ] Still limited to 50 for trial
- [ ] Limits enforced at backend

- [ ] All security tests passed

---

## PHASE 11: Comprehensive Testing

### Step 11.1: Functional Tests

- [ ] **Scenario 1: New school trial flow**
  - Register school → Status trial
  - Admin logs in → See trial dashboard
  - Add books < 50 → Success
  - Add books > 50 → Error
  - Request activation → Trust score increases
  - When score >= 70 → Auto activate

- [ ] **Scenario 2: Student registration**
  - Go to student register page
  - Wrong school code → Error
  - Right school code → Success
  - Can login as student
  - Can view school's books only

- [ ] **Scenario 3: Feature restrictions**
  - Trial school → Cannot export report
  - Active school → Can export report
  - Trial school → Cannot do bulk operations
  - Active school → Can do bulk operations

- [ ] **Scenario 4: Data isolation**
  - School 1 admin → Can only see school 1 data
  - School 2 admin → Can only see school 2 data
  - Student school 1 → Can only see school 1 books
  - Student school 2 → Can only see school 2 books

### Step 11.2: Performance Tests

- [ ] Dashboard loads < 1 second
- [ ] Book list loads < 2 seconds
- [ ] Queries use indexes (school_id)
- [ ] No N+1 query problems

### Step 11.3: Edge Cases

- [ ] School created, then activated manually via SQL
- [ ] School deleted (cascade delete test)
- [ ] Activation code regenerated
- [ ] User deleted (data orphaning test)
- [ ] Concurrent login from same account

- [ ] All edge cases handled

---

## PHASE 12: Documentation & Handoff

### Step 12.1: Document Custom Code

- [ ] Code comments added to new functions
- [ ] API endpoints documented
- [ ] Database schema documented

### Step 12.2: Create Admin Guide

- [ ] How to activate school manually
- [ ] How to suspend school
- [ ] How to view trust scores
- [ ] How to check activity logs

### Step 12.3: Create User Guide

- [ ] Admin: How to use dashboard
- [ ] Admin: How to request activation
- [ ] Student: How to register
- [ ] Student: How to borrow books

- [ ] All documentation complete

---

## Final Verification

### Verification Checklist

```sql
-- Run these final checks:

-- 1. Check all schools have status column
SELECT COUNT(*) as total FROM schools;
SELECT COUNT(*) as with_status FROM schools WHERE status IS NOT NULL;
-- Both should be equal

-- 2. Check activation codes exist
SELECT COUNT(*) as total FROM schools;
SELECT COUNT(*) as with_codes FROM activation_codes;
-- Should be equal

-- 3. Check trust scores exist
SELECT COUNT(*) as total FROM schools;
SELECT COUNT(*) as with_scores FROM trust_scores;
-- Should be equal

-- 4. Check trial limits exist
SELECT school_id, COUNT(*) as limits_count FROM trial_limits GROUP BY school_id;
-- Each trial school should have 3 rows (books, students, borrows)

-- 5. Verify no school has NULL status
SELECT * FROM schools WHERE status IS NULL;
-- Should return 0 rows

-- 6. Check sample data
SELECT id, name, status, trust_score FROM schools WHERE id IN (2, 7);
```

- [ ] All schools have status
- [ ] All schools have activation codes
- [ ] All schools have trust scores
- [ ] All trial schools have limits set
- [ ] No NULL values in critical fields
- [ ] Sample queries return expected data

---

## Go-Live Checklist

Before declaring "LIVE":

- [ ] All PHASE 1-12 checklists completed
- [ ] All tests passed
- [ ] Security audit completed
- [ ] Documentation complete
- [ ] Team trained on new system
- [ ] Rollback plan documented (DB backup exists)
- [ ] Monitoring setup (activity logs)
- [ ] User communication sent

---

## Rollback Plan (If Needed)

If critical issue found:

1. **Restore Database:**

   ```bash
   # In phpMyAdmin, restore from backup
   # File: backups/perpustakaan_online.sql
   ```

2. **Restore PHP Files:**

   ```bash
   # Copy from backup folder
   # Or revert from Git if version controlled
   ```

3. **Clear Sessions:**
   ```bash
   # Delete old session files
   # Or set session.gc_maxlifetime = 0
   ```

- [ ] Backup verified & accessible
- [ ] Rollback procedure documented
- [ ] Can rollback in < 30 minutes

---

## Post-Implementation Monitoring (First Week)

- [ ] Check error logs daily
- [ ] Monitor activity logs for anomalies
- [ ] Check trust score calculations
- [ ] Verify auto-activation triggers correctly
- [ ] Monitor performance metrics
- [ ] Collect user feedback

---

## Future Enhancements (Backlog)

- [ ] Admin platform for manual activation review
- [ ] Email notifications (activation status, trial expiry)
- [ ] Payment integration for upgrade
- [ ] Advanced analytics dashboard
- [ ] Anomaly detection alerts
- [ ] Bulk import/export data
- [ ] Compliance audit reports

---

## Document References

Keep these files for reference:

- [MULTI_TENANT_SYSTEM_DESIGN.md](MULTI_TENANT_SYSTEM_DESIGN.md) - Overall concept
- [IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md) - Step-by-step guide
- [QUERY_AUDIT_GUIDE.md](QUERY_AUDIT_GUIDE.md) - Query patterns
- [MULTI_TENANT_FLOW_DIAGRAMS.md](MULTI_TENANT_FLOW_DIAGRAMS.md) - Diagrams

---

## Sign-Off

```
Implementation Date: _______________
Completed By: _______________
Verified By: _______________
Notes: _______________________________________________
       _______________________________________________
```
