# Query Audit & Security Refactoring Guide

## Pattern: Before vs After

### Pattern 1: SELECT Query

**BEFORE (INSECURE - Trusting user input)**

```php
<?php
// public/books.php
$school_id = $_GET['school_id'] ?? 1; // WRONG! User bisa change nilai

$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id = :school_id');
$stmt->execute(['school_id' => $school_id]);
$books = $stmt->fetchAll();
?>
```

**AFTER (SECURE - Using session)**

```php
<?php
// public/books.php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: /perpustakaan-online/public/login.php');
    exit;
}

$school_id = $_SESSION['user']['school_id']; // CORRECT! From session

$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id = :school_id');
$stmt->execute(['school_id' => $school_id]);
$books = $stmt->fetchAll();
?>
```

---

### Pattern 2: INSERT with Validation

**BEFORE (Vulnerable)**

```php
<?php
if ($_POST) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $school_id = $_POST['school_id']; // WRONG! Trusting user input

    $stmt = $pdo->prepare('
        INSERT INTO books (school_id, title, author)
        VALUES (:school_id, :title, :author)
    ');
    $stmt->execute([
        'school_id' => $school_id,
        'title' => $title,
        'author' => $author
    ]);
}
?>
```

**AFTER (Secure with limits check)**

```php
<?php
session_start();
require_once __DIR__ . '/../src/MultiTenantManager.php';
require_once __DIR__ . '/../src/TrialLimitsManager.php';

$pdo = require __DIR__ . '/../src/db.php';

if (empty($_SESSION['user'])) {
    header('Location: /perpustakaan-online/public/login.php');
    exit;
}

$mtManager = new MultiTenantManager($pdo);
$limitsManager = new TrialLimitsManager($pdo, $mtManager);
$school_id = $_SESSION['user']['school_id'];

if ($_POST && $_SESSION['user']['role'] === 'admin') {
    try {
        // Check trial limits BEFORE insert
        $limitsManager->checkBeforeAddBook($school_id);

        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');

        if (!$title) {
            throw new Exception('Judul buku harus diisi');
        }

        // Insert dengan school_id dari session (bukan user input)
        $stmt = $pdo->prepare('
            INSERT INTO books (school_id, title, author, created_at)
            VALUES (:school_id, :title, :author, NOW())
        ');
        $stmt->execute([
            'school_id' => $school_id,
            'title' => $title,
            'author' => $author
        ]);

        // Log activity
        $mtManager->logActivity(
            $school_id,
            'book_creation',
            'create',
            1,
            $_SESSION['user']['id']
        );

        $success = 'Buku berhasil ditambahkan';
    } catch (TrialLimitException $e) {
        $error = $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
```

---

### Pattern 3: UPDATE Query

**BEFORE (Vulnerable)**

```php
<?php
if ($_POST && isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $title = $_POST['title'];

    // WRONG! No school_id filter, hacker bisa update buku sekolah lain
    $stmt = $pdo->prepare('UPDATE books SET title = :title WHERE id = :id');
    $stmt->execute(['title' => $title, 'id' => $book_id]);
}
?>
```

**AFTER (Secure with school_id verification)**

```php
<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

if (empty($_SESSION['user'])) {
    header('Location: /perpustakaan-online/public/login.php');
    exit;
}

$school_id = $_SESSION['user']['school_id'];
$book_id = (int)($_GET['id'] ?? 0);

if ($_POST && $_SESSION['user']['role'] === 'admin') {
    // FIRST: Verify book belongs to this school
    $stmt = $pdo->prepare('SELECT school_id FROM books WHERE id = :id');
    $stmt->execute(['id' => $book_id]);
    $book = $stmt->fetch();

    if (!$book || $book['school_id'] != $school_id) {
        $error = 'Buku tidak ditemukan atau akses ditolak';
    } else {
        $title = trim($_POST['title'] ?? '');

        // CORRECT! Filter by BOTH id AND school_id
        $stmt = $pdo->prepare('
            UPDATE books
            SET title = :title, updated_at = NOW()
            WHERE id = :id AND school_id = :school_id
        ');
        $stmt->execute([
            'title' => $title,
            'id' => $book_id,
            'school_id' => $school_id
        ]);

        $success = 'Buku berhasil diupdate';
    }
}
?>
```

---

### Pattern 4: DELETE Query

**BEFORE (Vulnerable)**

```php
<?php
if (isset($_GET['delete_id'])) {
    $book_id = $_GET['delete_id'];

    // WRONG! No school filter, bisa delete data sekolah lain
    $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
    $stmt->execute(['id' => $book_id]);
}
?>
```

**AFTER (Secure)**

```php
<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /perpustakaan-online/public/login.php');
    exit;
}

$school_id = $_SESSION['user']['school_id'];
$book_id = (int)($_GET['delete_id'] ?? 0);

if ($book_id) {
    // CORRECT! Filter by BOTH id AND school_id
    $stmt = $pdo->prepare('
        DELETE FROM books
        WHERE id = :id AND school_id = :school_id
    ');
    $stmt->execute(['id' => $book_id, 'school_id' => $school_id]);

    // Log activity
    $mtManager->logActivity($school_id, 'book_deletion', 'delete', 1, $_SESSION['user']['id']);

    header('Location: /perpustakaan-online/public/books.php?deleted=1');
    exit;
}
?>
```

---

### Pattern 5: JOIN Query

**BEFORE (Vulnerable)**

```php
<?php
// public/reports.php
$stmt = $pdo->prepare('
    SELECT b.title, COUNT(bw.id) as borrow_count
    FROM books b
    LEFT JOIN borrows bw ON b.id = bw.book_id
    GROUP BY b.id
');
$stmt->execute();
$report = $stmt->fetchAll();
?>
```

**AFTER (Secure)**

```php
<?php
// public/reports.php
session_start();
$pdo = require __DIR__ . '/../src/db.php';

if (empty($_SESSION['user'])) {
    header('Location: /perpustakaan-online/public/login.php');
    exit;
}

$school_id = $_SESSION['user']['school_id'];

// CORRECT! Filter JOIN tables by school_id
$stmt = $pdo->prepare('
    SELECT b.title, b.id, COUNT(bw.id) as borrow_count
    FROM books b
    LEFT JOIN borrows bw ON b.id = bw.book_id AND bw.school_id = :school_id
    WHERE b.school_id = :school_id
    GROUP BY b.id
');
$stmt->execute(['school_id' => $school_id]);
$report = $stmt->fetchAll();
?>
```

---

### Pattern 6: API Response with Status Check

**BEFORE (Vulnerable)**

```php
<?php
// public/api/get-books.php
header('Content-Type: application/json');

$school_id = $_GET['school_id'] ?? 1; // WRONG!

$stmt = $pdo->prepare('SELECT * FROM books WHERE school_id = :school_id');
$stmt->execute(['school_id' => $school_id]);

echo json_encode($stmt->fetchAll());
?>
```

**AFTER (Secure)**

```php
<?php
// public/api/get-books.php
session_start();
header('Content-Type: application/json');

// Check auth
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Use session school_id
$school_id = $_SESSION['user']['school_id'];

$pdo = require __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/MultiTenantManager.php';

$mtManager = new MultiTenantManager($pdo);

// Check school status
if ($mtManager->isSchoolSuspended($school_id)) {
    http_response_code(403);
    echo json_encode(['error' => 'School suspended']);
    exit;
}

// Query dengan school_id filter
$stmt = $pdo->prepare('
    SELECT id, title, author, copies
    FROM books
    WHERE school_id = :school_id
    ORDER BY title ASC
');
$stmt->execute(['school_id' => $school_id]);

echo json_encode([
    'success' => true,
    'data' => $stmt->fetchAll()
]);
?>
```

---

## Checklist: Pages to Refactor

### Critical Pages (MUST FIX)

- [ ] `public/books.php` - list buku
  - [ ] Add `WHERE school_id = :school_id` to SELECT
  - [ ] Add `AND school_id = :school_id` to UPDATE/DELETE
  - [ ] Add trial limits check sebelum insert

- [ ] `public/members.php` - list siswa
  - [ ] Add `WHERE school_id = :school_id` to SELECT
  - [ ] Add `AND school_id = :school_id` to UPDATE/DELETE
  - [ ] Add trial limits check sebelum insert

- [ ] `public/borrows.php` - list peminjaman
  - [ ] Add `WHERE school_id = :school_id` to SELECT
  - [ ] Add `AND school_id = :school_id` to UPDATE/DELETE
  - [ ] Add trial limits check sebelum insert

- [ ] `public/reports.php` - laporan
  - [ ] Add `WHERE school_id = :school_id` to all queries
  - [ ] Add trial feature check (prevent export)

- [ ] `public/login.php`
  - [ ] Add school status check
  - [ ] Add school_id ke session

### API Pages (MUST FIX)

- [ ] `public/api/*.php` - all API endpoints
  - [ ] Add session auth check
  - [ ] Add school_id filter to queries
  - [ ] Add status check

---

## Refactoring Workflow

### Per-Page Steps

1. **Identify all queries in page**

   ```bash
   grep -n "SELECT\|INSERT\|UPDATE\|DELETE" public/books.php
   ```

2. **For each SELECT:**
   - Add `WHERE school_id = :school_id`
   - Add parameter: `'school_id' => $school_id`

3. **For each INSERT:**
   - Add `:school_id` column
   - Add value from session: `$_SESSION['user']['school_id']`
   - Check trial limits before insert

4. **For each UPDATE/DELETE:**
   - Add `AND school_id = :school_id` to WHERE
   - Add parameter

5. **Add top of file:**

   ```php
   session_start();
   if (empty($_SESSION['user'])) {
       header('Location: /perpustakaan-online/public/login.php');
       exit;
   }
   $school_id = $_SESSION['user']['school_id'];
   ```

6. **Test:**
   - Login as admin school 1
   - Try view/edit data from school 2 → should fail
   - Try exceed trial limits → should show error

---

## Testing the Refactoring

### Unit Test Template

```php
<?php
// Test script: test-multi-tenant-security.php

session_start();
$_SESSION['user'] = [
    'id' => 1,
    'school_id' => 1,
    'role' => 'admin'
];

$pdo = require __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/MultiTenantManager.php';

$mtManager = new MultiTenantManager($pdo);

echo "=== Multi-Tenant Security Tests ===\n\n";

// Test 1: Get books for school 1
echo "Test 1: Get books for school 1\n";
$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM books WHERE school_id = ?');
$stmt->execute([1]);
$count = $stmt->fetch()['count'];
echo "  Result: $count books\n\n";

// Test 2: Try to access school 2 (should fail)
echo "Test 2: Try to access school 2 data (should be empty)\n";
$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM books WHERE school_id = ?');
$stmt->execute([999]); // Non-existent school
$count = $stmt->fetch()['count'];
echo "  Result: $count books (OK)\n\n";

// Test 3: Check activation code
echo "Test 3: Get activation code for school 1\n";
$code = $mtManager->getActivationCodeMasked(1);
echo "  Result: $code\n\n";

// Test 4: Check trial status
echo "Test 4: Check school 1 trial status\n";
$isTrial = $mtManager->isSchoolTrial(1);
echo "  Result: " . ($isTrial ? 'TRIAL' : 'ACTIVE') . "\n\n";

echo "=== All tests complete ===\n";
?>
```

Run:

```bash
cd C:\xampp\htdocs\perpustakaan-online
php test-multi-tenant-security.php
```

---

## Migration Order

1. **Phase 1 (Database):** Run migration SQL
2. **Phase 2 (Core):** Copy MultiTenantManager & TrialLimitsManager
3. **Phase 3 (Auth):** Update login.php + register.php
4. **Phase 4 (Critical Pages):** Refactor books, members, borrows
5. **Phase 5 (API):** Refactor API endpoints
6. **Phase 6 (Dashboard):** Add admin dashboard
7. **Phase 7 (Testing):** Comprehensive security testing
