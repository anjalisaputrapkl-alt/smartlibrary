# 📑 Index - Error 401 Debugging & Documentation

## 🎯 Start Here

**For fastest resolution:** [README_ERROR_401.md](README_ERROR_401.md) (2 min)

---

## 📚 Documentation Files

### 🚀 Quick Guides

| File                                                 | Purpose                  | Read Time | Best For           |
| ---------------------------------------------------- | ------------------------ | --------- | ------------------ |
| [README_ERROR_401.md](README_ERROR_401.md)           | Index & overview         | 2 min     | Navigation         |
| [LOGIN_401_QUICKFIX.md](LOGIN_401_QUICKFIX.md)       | Quick fix steps          | 5 min     | Fast solution      |
| [LOGIN_ERROR_401_GUIDE.md](LOGIN_ERROR_401_GUIDE.md) | Detailed troubleshooting | 15 min    | Comprehensive help |

### 📖 Deep Dives

| File                                                           | Purpose             | Read Time | Best For       |
| -------------------------------------------------------------- | ------------------- | --------- | -------------- |
| [ERROR_401_EXPLANATION.md](ERROR_401_EXPLANATION.md)           | System architecture | 20 min    | Understanding  |
| [NISN_LOGIN_TROUBLESHOOTING.md](NISN_LOGIN_TROUBLESHOOTING.md) | Initial debugging   | 10 min    | First approach |
| [TOOLS_REFERENCE.md](TOOLS_REFERENCE.md)                       | All tools guide     | 10 min    | Tool usage     |

### 📋 Info

| File                                           | Purpose          |
| ---------------------------------------------- | ---------------- |
| [NISN_LOGIN_CHANGES.md](NISN_LOGIN_CHANGES.md) | Database changes |
| [INDEX.md](INDEX.md)                           | This file        |

---

## 🛠️ Debugging Tools

### Command Line Tools

```bash
# List all students in database
php check-students.php

# Test login (see if credentials work)
php test-login-cli.php NISN PASSWORD

# Test API directly (isolated from browser)
php test-api-direct.php NISN PASSWORD

# Fix NISN data synchronization
php fix-nisn-sync.php

# Additional testing utilities
php debug-nisn.php              # Database inspection
php test-password-hash.php      # Hash verification
php test-add-students.php       # Bulk student creation
```

### Browser Tools

```
http://sekolah.localhost/test-api-login.html
```

Features:

- Load student list from database
- Interactive login testing
- Password hash verification
- API response inspection
- Troubleshooting tips

### PHP Files Created

| File                                             | Purpose             | Run                                 |
| ------------------------------------------------ | ------------------- | ----------------------------------- |
| [check-students.php](check-students.php)         | List all students   | `php check-students.php`            |
| [test-login-cli.php](test-login-cli.php)         | CLI login test      | `php test-login-cli.php NISN PASS`  |
| [test-api-direct.php](test-api-direct.php)       | Direct API test     | `php test-api-direct.php NISN PASS` |
| [fix-nisn-sync.php](fix-nisn-sync.php)           | Sync NISN data      | `php fix-nisn-sync.php`             |
| [debug-nisn.php](debug-nisn.php)                 | Database inspection | `php debug-nisn.php`                |
| [test-password-hash.php](test-password-hash.php) | Hash testing        | HTTP: `?nisn=X`                     |
| [test-add-students.php](test-add-students.php)   | Bulk add students   | `php test-add-students.php`         |

### HTML Files

| File                                       | Purpose          | URL                    |
| ------------------------------------------ | ---------------- | ---------------------- |
| [test-api-login.html](test-api-login.html) | Browser testing  | `/test-api-login.html` |
| [test-login.html](test-login.html)         | Alternative test | `/test-login.html`     |

---

## 🚦 Usage Flowchart

```
Error 401 pada login?
│
├─ Baca: README_ERROR_401.md (2 min)
│
├─ Jalankan: php check-students.php
│  │
│  ├─ Tidak ada siswa?
│  │  └─ Tambah siswa di "Kelola Murid"
│  │
│  ├─ Ada siswa dengan NISN?
│  │  └─ Lanjut ke step berikutnya
│  │
│  └─ NISN NULL atau role salah?
│     └─ Jalankan: php fix-nisn-sync.php
│
├─ Jalankan: php test-login-cli.php NISN PASSWORD
│  │
│  ├─ ✅ Login would SUCCEED?
│  │  └─ Buka browser, test di http://localhost/perpustakaan-online
│  │     └─ Pastikan password = NISN (exact match)
│  │
│  └─ ❌ Login gagal?
│     └─ Baca: LOGIN_ERROR_401_GUIDE.md
│        └─ Follow troubleshooting steps
│
├─ Jika masih error
│  └─ Baca: ERROR_401_EXPLANATION.md
│     └─ Debug tools section
│
└─ Jika butuh referensi lengkap
   └─ Baca: TOOLS_REFERENCE.md
```

---

## 📊 Quick Decision Matrix

### "Saya ingin..."

| Tujuan                    | Action                        | Waktu  |
| ------------------------- | ----------------------------- | ------ |
| Lihat dokumentasi singkat | Baca README_ERROR_401.md      | 2 min  |
| Fix login error cepat     | Ikuti LOGIN_401_QUICKFIX.md   | 5 min  |
| Troubleshoot masalah      | Baca LOGIN_ERROR_401_GUIDE.md | 15 min |
| Pahami sistem login       | Baca ERROR_401_EXPLANATION.md | 20 min |
| Test login dari CLI       | Jalankan test-login-cli.php   | 1 min  |
| Test login dari browser   | Buka test-api-login.html      | 2 min  |
| Lihat siswa yang ada      | Jalankan check-students.php   | 1 min  |
| Fix data NISN sync        | Jalankan fix-nisn-sync.php    | 2 min  |
| Referensi semua tools     | Baca TOOLS_REFERENCE.md       | 10 min |

---

## 🔄 Common Workflow

### Scenario 1: Just Added Student, Now Testing Login

```
1. Jalankan: php check-students.php
   └─ Lihat NISN yang baru ditambah

2. Copy NISN

3. Jalankan: php test-login-cli.php [NISN] [NISN]
   └─ Lihat apakah login OK

4. Buka browser, test di halaman login
   └─ Input NISN dan password (harus sama)
   └─ Klik login
```

### Scenario 2: Error 401, Need to Debug

```
1. Baca: README_ERROR_401.md
   └─ Understand masalahnya

2. Jalankan: php check-students.php
   └─ Verify siswa ada

3. Jalankan: php test-login-cli.php [NISN] [PASSWORD]
   └─ Cek apakah kombinasi valid

4. Jika fail, baca: LOGIN_ERROR_401_GUIDE.md
   └─ Follow problem-specific solutions

5. Jika ada NISN NULL, jalankan: php fix-nisn-sync.php
   └─ Repair data

6. Ulangi step 3
```

### Scenario 3: Complex Issue, Need Understanding

```
1. Baca: ERROR_401_EXPLANATION.md
   └─ Understand database structure
   └─ Understand login flow

2. Buka browser tools
   └─ http://sekolah.localhost/test-api-login.html
   └─ Run interactive tests

3. Baca: TOOLS_REFERENCE.md
   └─ Understand tool capabilities

4. Combine multiple tools untuk isolate issue
```

---

## 📈 Escalation Path

### Level 1: Self-Service (15 min)

- Read quick guides
- Run check-students.php
- Run test-login-cli.php
- Check browser console (F12)

### Level 2: Guided Troubleshooting (30 min)

- Read detailed guide
- Follow troubleshooting steps
- Run fix script if needed
- Re-test

### Level 3: Understanding System (60 min)

- Read system explanation
- Review database structure
- Understand login flow
- Debug with multiple tools
- Review source code if needed

### Level 4: Advanced Debugging (variable)

- Check server logs
- Review PDO/PHP errors
- Database inspection
- Network traffic analysis
- Code review

---

## ✅ Verification Checklist

Before declaring issue resolved:

- [ ] Run `php check-students.php`
- [ ] Verify student exists with correct NISN
- [ ] Run `php test-login-cli.php NISN PASSWORD`
- [ ] Verify output shows "✅ Login would SUCCEED"
- [ ] Open browser login page
- [ ] Test login with NISN and PASSWORD
- [ ] Verify redirect to student-dashboard.php
- [ ] Check F12 Network tab (no 401 errors)

---

## 🎓 Learning Outcome

After working through these docs & tools, you'll understand:

1. ✅ How NISN-based student login works
2. ✅ Why error 401 occurs and how to debug
3. ✅ How to use CLI tools for testing
4. ✅ How to use browser tools for testing
5. ✅ Database structure for authentication
6. ✅ Password hashing and verification
7. ✅ How to repair data sync issues
8. ✅ When and how to use each tool

---

## 📞 Support Resources

| Need                 | Resource                 |
| -------------------- | ------------------------ |
| Quick answer         | README_ERROR_401.md      |
| Step-by-step fix     | LOGIN_401_QUICKFIX.md    |
| Detailed help        | LOGIN_ERROR_401_GUIDE.md |
| System understanding | ERROR_401_EXPLANATION.md |
| Tool reference       | TOOLS_REFERENCE.md       |
| Browser testing      | test-api-login.html      |
| CLI testing          | test-login-cli.php       |
| Troubleshooting      | LOGIN_ERROR_401_GUIDE.md |

---

## 🗂️ File Organization

```
Root Directory (perpustakaan-online/)
│
├── 📚 Documentation/
│   ├── README_ERROR_401.md ← START HERE
│   ├── LOGIN_401_QUICKFIX.md
│   ├── LOGIN_ERROR_401_GUIDE.md
│   ├── ERROR_401_EXPLANATION.md
│   ├── NISN_LOGIN_TROUBLESHOOTING.md
│   ├── NISN_LOGIN_CHANGES.md
│   ├── TOOLS_REFERENCE.md
│   └── INDEX.md (this file)
│
├── 🛠️ Testing Tools (CLI)/
│   ├── check-students.php
│   ├── test-login-cli.php
│   ├── test-api-direct.php
│   ├── fix-nisn-sync.php
│   ├── debug-nisn.php
│   ├── test-password-hash.php
│   └── test-add-students.php
│
├── 🌐 Testing Tools (Browser)/
│   ├── test-api-login.html
│   └── test-login.html
│
├── 🔧 Source Code/
│   ├── public/api/login.php (enhanced with logging)
│   ├── src/db.php
│   ├── src/config.php
│   └── index.php (student login form)
│
└── 📊 Database/
    ├── sql/migrations/add_nisn_column.sql
    └── sql/perpustakaan_online.sql
```

---

## 🚀 Next Steps

1. **Read:** [README_ERROR_401.md](README_ERROR_401.md) - 2 minutes
2. **Run:** `php check-students.php` - 1 minute
3. **Run:** `php test-login-cli.php NISN PASSWORD` - 1 minute
4. **Test:** Login in browser - 2 minutes
5. **If error:** Read [LOGIN_ERROR_401_GUIDE.md](LOGIN_ERROR_401_GUIDE.md) - 15 minutes

---

**Version:** 1.0  
**Last Updated:** 2025-01-20  
**Status:** ✅ Complete
