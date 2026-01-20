# QUICK REFERENCE - Multi-Tenant Implementation

## File-File yang Sudah Dibuat (Ready to Use)

### PHP Classes (COPY KE src/)

```
src/MultiTenantManager.php
src/TrialLimitsManager.php
```

### PHP Pages (COPY KE public/)

```
public/student-register.php
public/admin-dashboard.php
```

### Database (RUN DI phpMyAdmin)

```
sql/migrations/02-multi-tenant-schema.sql
```

---

## Quick Checklist untuk Implementasi

### Step 1: Database (5 min)

```sql
-- Buka phpMyAdmin > perpustakaan_online > SQL
-- Copy-paste isi 02-multi-tenant-schema.sql
-- Execute
-- Done!
```

### Step 2: Copy PHP Classes (2 min)

```bash
# Copy ke src/ folder
src/MultiTenantManager.php
src/TrialLimitsManager.php
```

### Step 3: Update login.php (5 min)

Cari line: `password_verify()`

Tambahkan setelah itu:

```php
if ($user && password_verify($password, $user['password'])) {
    require_once __DIR__ . '/../src/MultiTenantManager.php';
    $mtManager = new MultiTenantManager($pdo);

    if ($mtManager->isSchoolSuspended($user['school_id'])) {
        $message = 'Sekolah dinonaktifkan';
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

### Step 4: Refactor Queries (varies)

Pattern untuk SETIAP query:

**SELECT:**

```php
// BEFORE
$stmt = $pdo->prepare('SELECT * FROM books');

// AFTER
$school_id = $_SESSION['user']['school_id'];
$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id = :school_id');
$stmt->execute(['school_id' => $school_id]);
```

**INSERT:**

```php
// BEFORE
'school_id' => $_POST['school_id']

// AFTER
'school_id' => $_SESSION['user']['school_id']
```

**UPDATE/DELETE:**

```php
// BEFORE
UPDATE books SET title = ? WHERE id = ?

// AFTER
UPDATE books SET title = ? WHERE id = ? AND school_id = ?
```

### Step 5: Add Trial Limits (10 min per page)

Sebelum INSERT data:

```php
$limitsManager = new TrialLimitsManager($pdo, $mtManager);

try {
    $limitsManager->checkBeforeAddBook($school_id);
    // ... insert code ...
} catch (TrialLimitException $e) {
    $error = $e->getMessage();
}
```

---

## Code Snippets - Copy & Paste Ready

### Session Setup (Add to Top of Every Page)

```php
<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: /perpustakaan-online/public/login.php');
    exit;
}

$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MultiTenantManager.php';
require_once __DIR__ . '/../src/TrialLimitsManager.php';

$school_id = $_SESSION['user']['school_id'];
$mtManager = new MultiTenantManager($pdo);
$limitsManager = new TrialLimitsManager($pdo, $mtManager);
?>
```

### Check Status Before Display

```php
<?php
if ($mtManager->isSchoolSuspended($school_id)) {
    die('Sekolah suspended');
}

if ($mtManager->isSchoolTrial($school_id)) {
    $warnings = $limitsManager->getWarnings($school_id);
    // Display warnings
}
?>
```

### Add Book with Limit Check

```php
<?php
try {
    $limitsManager->checkBeforeAddBook($school_id);

    $stmt = $pdo->prepare('
        INSERT INTO books (school_id, title, author, created_at)
        VALUES (:school_id, :title, :author, NOW())
    ');
    $stmt->execute([
        'school_id' => $school_id,
        'title' => trim($_POST['title']),
        'author' => trim($_POST['author'])
    ]);

    $mtManager->logActivity($school_id, 'book_creation', 'create', 1, $_SESSION['user']['id']);

    $success = 'Buku ditambahkan';
} catch (TrialLimitException $e) {
    $error = $e->getMessage();
}
?>
```

### Display Activation Code

```php
<?php
$code = $mtManager->getActivationCodeMasked($school_id);
echo "Kode Aktivasi: " . htmlspecialchars($code);
?>
```

### Display Capacity Indicators

```php
<?php
$books = $limitsManager->getBookCountWithCapacity($school_id);
echo "Buku: {$books['current']}/{$books['max']} ({$books['percentage']}%)";
?>
```

### Display Warnings

```php
<?php
$warnings = $limitsManager->getWarnings($school_id);
foreach ($warnings as $w) {
    echo "<div class='warning'>" . htmlspecialchars($w['message']) . "</div>";
}
?>
```

---

## Testing Commands (phpMyAdmin SQL Tab)

### Check All Schools

```sql
SELECT id, name, status, trust_score, activation_requested_at
FROM schools;
```

### Get Activation Code

```sql
SELECT code FROM activation_codes WHERE school_id = 2;
```

### Check Trust Score

```sql
SELECT * FROM trust_scores WHERE school_id = 2;
```

### Check Trial Limits

```sql
SELECT * FROM trial_limits WHERE school_id = 2;
```

### Check Activity Log

```sql
SELECT * FROM school_activities
WHERE school_id = 2
ORDER BY recorded_at DESC
LIMIT 20;
```

### Recalculate Trust Score Manually

```php
<?php
$pdo = require 'src/db.php';
require_once 'src/MultiTenantManager.php';

$mtManager = new MultiTenantManager($pdo);
$newScore = $mtManager->recalculateTrustScore(2);
echo "New Score: $newScore";
?>
```

---

## Common Mistakes & Fixes

### ❌ Mistake 1: Getting school_id from POST

```php
// WRONG
$school_id = $_POST['school_id'];

// CORRECT
$school_id = $_SESSION['user']['school_id'];
```

### ❌ Mistake 2: Missing WHERE school_id in query

```php
// WRONG
SELECT * FROM books WHERE title LIKE '%...'

// CORRECT
SELECT * FROM books WHERE school_id = :school_id AND title LIKE '%...'
```

### ❌ Mistake 3: Not checking trial limits before insert

```php
// WRONG
if ($_POST) {
    $stmt->execute(...);  // No limit check!
}

// CORRECT
if ($_POST) {
    $limitsManager->checkBeforeAddBook($school_id);
    $stmt->execute(...);
}
```

### ❌ Mistake 4: Not checking school status

```php
// WRONG
if ($_POST) {
    // Process request
}

// CORRECT
if ($mtManager->isSchoolSuspended($school_id)) {
    die('Akses ditolak');
}
if ($_POST) {
    // Process request
}
```

### ❌ Mistake 5: Not logging activities

```php
// WRONG
$stmt->execute(...);  // No logging

// CORRECT
$stmt->execute(...);
$mtManager->logActivity($school_id, 'book_creation', 'create', 1, $_SESSION['user']['id']);
```

---

## Pages That MUST Be Refactored

Priority order:

1. **`public/login.php`** - Add status check ⭐⭐⭐
2. **`public/books.php`** - Add school_id filter ⭐⭐⭐
3. **`public/members.php`** - Add school_id filter ⭐⭐⭐
4. **`public/borrows.php`** - Add school_id filter ⭐⭐⭐
5. **`public/index.php`** - Add school_id filter ⭐⭐
6. **`public/reports.php`** - Add school_id filter + export check ⭐⭐
7. **All `public/api/*.php`** - Add school_id filter ⭐⭐

---

## Performance Tips

### 1. Add Indexes (for speed)

Database migration already includes:

```sql
INDEX `idx_school_id` (`school_id`)
```

### 2. Use Prepared Statements (always!)

```php
// CORRECT
$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id = :school_id');
$stmt->execute(['school_id' => $school_id]);
```

### 3. Cache Trust Scores

```php
// Store in session to avoid recalculating
$_SESSION['trust_score'] = $mtManager->getTrustScore($school_id);
```

---

## Security Checklist - Before Going Live

- [ ] All SELECT queries have `WHERE school_id = ?`
- [ ] All UPDATE/DELETE queries have `AND school_id = ?`
- [ ] school_id always from `$_SESSION`, never from request
- [ ] Trial limits enforced before INSERT
- [ ] School status checked before operations
- [ ] Activities logged for audit trail
- [ ] No sensitive data in logs
- [ ] HTTPS enabled (if production)
- [ ] SQL injection tests passed
- [ ] Cross-tenant access tests passed

---

## Monitoring After Go-Live

### Daily Checks

```sql
-- Check for suspicious activities
SELECT activity_type, COUNT(*) as count
FROM school_activities
WHERE recorded_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY activity_type;

-- Check activation requests
SELECT s.name, s.activation_requested_at, ts.total_score
FROM schools s
JOIN trust_scores ts ON s.id = ts.school_id
WHERE s.status = 'trial'
ORDER BY s.activation_requested_at DESC;

-- Check limit approaching
SELECT s.name, COUNT(b.id) as books
FROM schools s
JOIN books b ON s.id = b.school_id
WHERE s.status = 'trial'
GROUP BY s.id
HAVING books > 40;
```

### Weekly Checks

- Review error logs
- Check auto-activation triggers
- Verify anomaly detection working
- Check user feedback

---

## Emergency: Revert Changes

If need to revert:

1. **Restore database from backup:**

   ```bash
   # In phpMyAdmin: Import > select backup SQL file
   ```

2. **Revert PHP files:**

   ```bash
   # Copy from backup folder or git revert
   ```

3. **Clear sessions:**
   ```bash
   # Delete files in session directory
   # Or wait for gc_maxlifetime to clear
   ```

---

## Additional Resources

All documentation in `/docs/`:

1. `README.md` - Overview (start here)
2. `MULTI_TENANT_SYSTEM_DESIGN.md` - Concept & design
3. `IMPLEMENTATION_GUIDE.md` - Step-by-step
4. `QUERY_AUDIT_GUIDE.md` - Code patterns
5. `MULTI_TENANT_FLOW_DIAGRAMS.md` - Diagrams
6. `IMPLEMENTATION_CHECKLIST.md` - Detailed checklist

---

## Support & Questions

### For Concept Questions

→ Read `MULTI_TENANT_SYSTEM_DESIGN.md`

### For Implementation

→ Follow `IMPLEMENTATION_GUIDE.md`

### For Code Patterns

→ Reference `QUERY_AUDIT_GUIDE.md`

### For Progress Tracking

→ Use `IMPLEMENTATION_CHECKLIST.md`

### For Debugging

→ Check `MULTI_TENANT_FLOW_DIAGRAMS.md`

---

## Success Indicators

System is working correctly when:

✓ Trial school can't exceed 50 books → Error shown
✓ Trial school can't exceed 100 students → Error shown
✓ Trial school can't export report → Error shown
✓ Trust score increases with activation request
✓ School auto-activates when score >= 70
✓ Student can register with correct code
✓ Student can't register with wrong code
✓ School admin can only see own school data
✓ Warnings appear when approaching limits
✓ Activities logged in school_activities table

---

## Estimated Timeline

```
Phase 1 (Database)         : 10 min
Phase 2 (Core Classes)     : 5 min
Phase 3 (Update Login)     : 10 min
Phase 4 (Student Reg)      : 5 min
Phase 5 (Dashboard)        : 5 min
Phase 6 (Query Refactor)   : 2-3 hours
Phase 7 (Testing)          : 1-2 hours
---
Total: 3-4 hours (1 working day)
```

---

## Go-Live Checklist

Before declaring LIVE:

- [ ] Database migration executed
- [ ] All PHP classes copied
- [ ] All pages refactored
- [ ] Login status check added
- [ ] Student registration tested
- [ ] Admin dashboard tested
- [ ] Trial limits tested
- [ ] Security audit passed
- [ ] Team trained
- [ ] Rollback plan ready
- [ ] Monitoring setup

---

**Happy implementing! 🚀**

Untuk pertanyaan atau clarification, refer ke dokumentasi di `/docs/` folder.
