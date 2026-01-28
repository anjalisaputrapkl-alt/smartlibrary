# 🏗️ TECHNICAL IMPLEMENTATION GUIDE

## Barcode Scanner System - Complete Architecture

---

## 📊 System Overview

```
┌──────────────────────────────────────────────────────────────────┐
│                      BARCODE SCANNER SYSTEM                      │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  TIER 1: PRESENTATION LAYER                                     │
│  ├─ Desktop Admin (borrows.php)        [Desktop-only, 1280px+] │
│  ├─ Mobile Scanner (barcode-scan.php)  [Responsive, Mobile]     │
│  └─ Real-time UI Updates (Polling)     [2s interval]            │
│                                                                  │
│  TIER 2: APPLICATION LAYER                                      │
│  ├─ Session Management                 [API endpoints]          │
│  ├─ Barcode Processing                 [Validation & Lookup]    │
│  ├─ Real-time Sync                     [Polling mechanism]      │
│  └─ Transaction Handler                [Database transaction]   │
│                                                                  │
│  TIER 3: DATA ACCESS LAYER                                      │
│  ├─ barcode_sessions                   [Session storage]        │
│  ├─ members                            [Member validation]      │
│  ├─ books                              [Book inventory]         │
│  └─ borrows                            [Transaction records]    │
│                                                                  │
│  TIER 4: INFRASTRUCTURE                                         │
│  ├─ PHP 7.4+                           [Backend]                │
│  ├─ MySQL 5.7+                         [Database]               │
│  ├─ html5-qrcode library               [Camera scanner]         │
│  └─ PDO with prepared statements       [Data security]          │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 🔐 Security Architecture

### Session Token Generation

```php
$token = bin2hex(random_bytes(16));  // 32 char random hex string
// Stored in database with school_id scope
// Auto-expires in 30 minutes
```

### Database Security

```
✓ PDO Prepared Statements        - SQL injection protection
✓ Type casting                    - Integer casting for IDs
✓ School ID verification          - Multi-tenancy protection
✓ Admin authentication            - Role-based access control
✓ Input sanitization              - htmlspecialchars() for output
```

### API Authentication

```
create-barcode-session.php       → Admin session REQUIRED
verify-barcode-session.php       → Token verification REQUIRED
process-barcode-scan.php         → Session ID verification REQUIRED
get-barcode-session-data.php     → Session ID verification REQUIRED
complete-barcode-borrowing.php   → Admin session REQUIRED
```

---

## 🔄 Data Flow Architecture

### Request Flow Diagram

```
1. ADMIN INITIATES SESSION
   Admin clicks button
        ↓
   POST /api/create-barcode-session.php
        ↓
   Backend:
   - Generate token
   - Create record in barcode_sessions
   - Return token + session_id
        ↓
   Frontend:
   - Display token
   - Store session_id
   - Show barcode panel

2. SMARTPHONE JOINS SESSION
   User scans token
        ↓
   POST /api/verify-barcode-session.php {token}
        ↓
   Backend:
   - Verify token exists
   - Check not expired
   - Check status = "active"
   - Return session_id
        ↓
   Frontend:
   - Initialize camera
   - Ready to scan

3. SCANNING PROCESS
   User scans barcode (member/book)
        ↓
   POST /api/process-barcode-scan.php
   {
     session_id: number,
     barcode: string,
     type: "member" | "book"
   }
        ↓
   Backend:
   - Validate session
   - Lookup member/book in database
   - Validate business rules:
     * For member:
       - Member exists
       - Member active
     * For book:
       - Book exists
       - Stock > 0
       - Member already scanned
       - Not duplicate scan in session
   - Update barcode_sessions JSON
   - Return success + data
        ↓
   Frontend:
   - Add to scanned items list
   - Show visual feedback

4. REAL-TIME SYNC (ADMIN)
   Polling loop (every 2 seconds)
        ↓
   GET /api/get-barcode-session-data.php?session_id=X
        ↓
   Backend:
   - Fetch barcode_sessions record
   - Decode books_scanned JSON
   - Return current state
        ↓
   Frontend:
   - Update member info
   - Update books list
   - Update counter

5. FINALIZATION (ADMIN)
   Admin sets due_date + clicks save
        ↓
   POST /api/complete-barcode-borrowing.php
   {
     session_id: number,
     due_date: "YYYY-MM-DD"
   }
        ↓
   Backend (Transaction):
   - Validate session + due_date
   - For each book in books_scanned:
     * INSERT into borrows table
     * UPDATE books.copies--
   - UPDATE barcode_sessions status="completed"
   - COMMIT transaction
   - Return success + borrow_ids
        ↓
   Frontend:
   - Show success dialog
   - Reload page
```

---

## 📦 API Response Patterns

### Success Pattern

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // operation-specific data
  }
}
```

### Error Pattern

```json
{
  "success": false,
  "message": "Error description"
  // optional: type for client-side handling
}
```

### HTTP Status Codes

```
200 OK                - Request successful
400 Bad Request       - Invalid input/validation fail
401 Unauthorized      - Auth required or failed
404 Not Found         - Resource not found
405 Method Not Allowed- Wrong HTTP method
410 Gone              - Session expired
500 Server Error      - Database/system error
```

---

## 💾 Database Schema Details

### barcode_sessions Table

```sql
-- Primary key for session identification
`id` int(11) AUTO_INCREMENT PRIMARY KEY

-- Multi-tenancy support
`school_id` int(11) FOREIGN KEY REFERENCES schools(id)

-- Session identifier (client-facing token)
`session_token` varchar(32) UNIQUE NOT NULL
-- Format: 32-character hex string
-- Example: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
-- Generated: bin2hex(random_bytes(16))

-- Session lifecycle state
`status` enum('active','completed','expired') DEFAULT 'active'
-- active: Can receive scans
-- completed: Finalized, no more changes
-- expired: Auto-set after 30 minutes

-- Member barcode that was scanned
`member_barcode` varchar(255) DEFAULT NULL
-- Stores the actual barcode value from scan
-- Example: "0094234" (NISN)

-- Foreign key to validated member
`member_id` int(11) FOREIGN KEY REFERENCES members(id)
-- Set when member barcode is scanned and validated

-- JSON array of scanned books
`books_scanned` longtext DEFAULT NULL COMMENT 'JSON array'
-- Format: JSON array of book objects
-- Each book: {book_id, title, isbn, scanned_at}
-- Example:
-- [
--   {"book_id": 1, "title": "Buku A", "isbn": "123", "scanned_at": "2026-01-28 10:15:30"},
--   {"book_id": 5, "title": "Buku B", "isbn": "456", "scanned_at": "2026-01-28 10:16:45"}
-- ]

-- Tanggal jatuh tempo (set by admin when finalizing)
`due_date` datetime DEFAULT NULL

-- Timestamps
`created_at` timestamp DEFAULT current_timestamp()
`updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp()
`expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + INTERVAL 30 MINUTE)
-- expires_at: Auto-set to NOW() + 30 minutes when created
-- Used to determine session expiration without separate cleanup job
```

### Related Existing Tables

```sql
-- members table (validation)
- id
- school_id
- name
- nisn (barcode field)
- status (active/inactive)

-- books table (inventory)
- id
- school_id
- title
- isbn (barcode field)
- copies (stock quantity)

-- borrows table (transaction records)
- id
- school_id
- book_id (foreign key)
- member_id (foreign key)
- borrowed_at (timestamp)
- due_at (timestamp)
- returned_at (nullable)
- status (enum)
```

---

## 🎯 Business Logic Rules

### Member Validation

```
✓ Member must exist in members table
✓ Member.status must be 'active'
✓ Member.school_id must match session.school_id
✓ Only ONE member can be scanned per session
```

### Book Validation

```
✓ Book must exist in books table
✓ Book.school_id must match session.school_id
✓ Book.copies must be > 0 (stock available)
✓ Member must already be scanned (precondition)
✓ Member must not have already borrowed this book (active/overdue)
✓ Book must not already be in this session's books_scanned list
```

### Session Rules

```
✓ Session auto-expires 30 minutes after creation
✓ Only admin can create sessions
✓ Only smartphone with valid token can verify
✓ Session can be used for multiple books
✓ Session status transitions: active → completed → (archived)
```

### Finalization Rules

```
✓ Must have at least 1 member scanned
✓ Must have at least 1 book scanned
✓ Due date must be valid date format (YYYY-MM-DD)
✓ Creates atomic transaction (all or nothing)
✓ Updates book inventory (copies--)
✓ Marks session as completed
```

---

## 🛠️ Implementation Details

### Session Token Security

```javascript
// Client generation is NOT allowed
// Server ONLY generates tokens

// Server-side (PHP):
$token = bin2hex(random_bytes(16));
// Result: 32 hex characters (0-9, a-f)
// Cryptographically secure random
```

### Polling Strategy

```javascript
// Polling every 2 seconds (2000ms)
// GET /api/get-barcode-session-data.php?session_id=X

// Advantages:
✓ Simple implementation
✓ No WebSocket overhead
✓ Works on all networks
✓ Fallback if JS WebSocket unavailable
✓ Data always fresh (< 2 seconds lag)

// When to stop polling:
1. Session completed
2. Session expired
3. User navigates away
4. Error occurs (admin manually)
```

### Barcode Processing Pipeline

```
Raw Barcode Input
    ↓
[Decode by html5-qrcode library]
    ↓
Decodedtext (string)
    ↓
[Send to server in POST body]
    ↓
Backend Validation:
    ├─ Session valid?
    ├─ Type (member/book)?
    ├─ Data exists in DB?
    ├─ Business rules met?
    └─ Return result
    ↓
Frontend Update:
    ├─ Add to scanned list
    ├─ Show success/error
    └─ Ready for next scan
```

### JSON Storage Strategy

```javascript
// books_scanned stored as JSON string in database
// Format: JSON array of objects

// Example:
const booksScanned = [
  {
    "book_id": 1,
    "title": "Mengunyah Rindu",
    "isbn": "982384",
    "scanned_at": "2026-01-28 10:15:30"
  },
  {
    "book_id": 5,
    "title": "The Psychology of Money",
    "isbn": "9786238371044",
    "scanned_at": "2026-01-28 10:16:45"
  }
];

// Stored in DB:
$stmt->execute(['books' => json_encode($booksScanned)]);

// Retrieved & decoded:
$booksScanned = json_decode($session['books_scanned'] ?? '[]', true);
// Now it's PHP array, can iterate/process normally
```

---

## ⚠️ Error Handling Strategy

### HTTP Status Codes

```php
http_response_code(400); // Bad request (validation fail)
http_response_code(401); // Unauthorized (auth required)
http_response_code(404); // Not found (resource not exist)
http_response_code(405); // Method not allowed
http_response_code(410); // Gone (session expired)
http_response_code(500); // Server error
```

### Client-Side Error Display

```javascript
// Toast/Alert pattern
function showError(element, message) {
  element.textContent = message;
  element.classList.add("show");

  // Auto-hide after 5 seconds
  setTimeout(() => {
    element.classList.remove("show");
  }, 5000);
}

// Usage:
showError(scanError, "Barcode not found");
```

### Server-Side Error Pattern

```php
try {
    // Operation
    if (!$validated) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validation failed']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
```

---

## 🔗 API Integration Checklist

### For Desktop Admin (borrows.php)

```
□ Import JavaScript: barcode-scan.js (already included)
□ Click handler: btnStartBarcodeSession
□ Session creation: POST /api/create-barcode-session.php
□ Polling: GET /api/get-barcode-session-data.php
□ Finalization: POST /api/complete-barcode-borrowing.php
□ UI state management: Active/Inactive panels
□ Error handling: Toast messages
```

### For Mobile Scanner (barcode-scan.php)

```
□ Input handler: Session token verification
□ POST to: /api/verify-barcode-session.php
□ Initialize camera: Html5Qrcode library
□ Scan callback: onScanSuccess() handler
□ Processing: POST /api/process-barcode-scan.php
□ Display: Real-time scanned items list
□ Completion: Send confirmation signal
```

### For Backend APIs

```
□ Session validation (every request)
□ Database transaction (finalization)
□ Error responses (consistent format)
□ CORS headers (if cross-domain)
□ Rate limiting (if needed)
□ Audit logging (optional)
```

---

## 📈 Performance Considerations

### Database Indexes

```sql
-- Recommended indexes for barcode_sessions table:
ALTER TABLE barcode_sessions ADD INDEX idx_school_session (school_id, session_token);
ALTER TABLE barcode_sessions ADD INDEX idx_expires (expires_at);
ALTER TABLE barcode_sessions ADD INDEX idx_member (member_id);
ALTER TABLE barcode_sessions ADD INDEX idx_status (status);
```

### Query Optimization

```
✓ Use prepared statements (already done)
✓ Select specific columns (not SELECT *)
✓ Use LIMIT if needed
✓ Index frequently queried columns
```

### Polling Optimization

```
✓ 2-second interval balances responsiveness vs server load
✓ Only fetch changed records
✓ Client-side filtering before update
```

---

## 🚀 Deployment Checklist

### Pre-Deployment

```
□ Database: barcode_sessions table created
□ Files: All API endpoints uploaded
□ Files: Frontend files uploaded (CSS, JS)
□ Permissions: API endpoints accessible
□ Testing: All functions tested locally
□ Security: No hardcoded credentials
□ Backup: Database backed up
```

### Post-Deployment

```
□ Test create-barcode-session (admin)
□ Test verify-barcode-session (smartphone)
□ Test process-barcode-scan (multiple times)
□ Test get-barcode-session-data (polling)
□ Test complete-barcode-borrowing (finalization)
□ Test real-time sync (desktop + mobile)
□ Monitor error logs
```

---

## 📊 Monitoring & Logging

### Recommended Logs to Track

```
- Session creation/expiration
- Successful barcode scans
- Validation failures
- API errors
- Database transactions
- User actions
```

### Query for Session Statistics

```sql
-- Active sessions
SELECT COUNT(*) FROM barcode_sessions
WHERE status = 'active' AND expires_at > NOW();

-- Completed sessions (today)
SELECT COUNT(*) FROM barcode_sessions
WHERE status = 'completed'
AND DATE(created_at) = CURDATE();

-- Session success rate
SELECT
  ROUND(COUNT(CASE WHEN status='completed' THEN 1 END)*100/COUNT(*), 2) as success_rate
FROM barcode_sessions
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY);
```

---

## 🔄 Version History

```
v1.0 (28 Jan 2026)
  - Initial implementation
  - Core features: Session, Scanner, Polling, Finalization
  - Security: Token-based, Database validation
  - Documentation: Complete guide
```

---

## 📞 Support & Maintenance

### Common Issues & Fixes

See `BARCODE_SCANNER_DOCUMENTATION.md` - Troubleshooting section

### Performance Tuning

- Adjust polling interval if needed
- Monitor database query performance
- Implement caching if scale increases

### Future Enhancements

- [ ] Barcode batch processing
- [ ] Offline mode with sync
- [ ] Advanced analytics dashboard
- [ ] Mobile app (PWA)
- [ ] Two-factor authentication

---

**Document Version:** 1.0  
**Last Updated:** 28 January 2026  
**Maintainer:** Development Team
