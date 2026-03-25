# ⚖️ EviDemo — Blockchain-Based Evidence Management System (BEMS)

> A tamper-proof digital evidence management portal for law enforcement, built on a custom PHP blockchain with SHA-256 hash chaining, immutable audit trails, and full chain-of-custody tracking.

![PHP](https://img.shields.io/badge/PHP-8.0-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB_10.4-4479A1?logo=mysql&logoColor=white)
![Blockchain](https://img.shields.io/badge/Blockchain-Custom_SHA--256-orange)
![Status](https://img.shields.io/badge/status-Active-brightgreen)

---

## 📌 Overview

**EviDemo (BEMS)** is a government-grade evidence management system developed for the **Ministry of Justice — Digital Forensics Division**. It uses a custom-built blockchain (implemented in PHP) to guarantee that every piece of digital evidence and every modification to it is permanently and verifiably recorded.

Every case registration, evidence submission, evidence update, and custody transfer creates a new SHA-256 linked block — making unauthorized tampering immediately detectable via the built-in **Blockchain Explorer**.

---

## ✨ Key Features

- **Custom PHP Blockchain** — `Block` and `Blockchain` classes implement full SHA-256 hash chaining; each block stores the previous block's hash so any tampering breaks the chain.
- **Immutable Evidence Submissions** — Every new evidence entry is cryptographically hashed and sealed on-chain. Original blocks are never overwritten.
- **Non-destructive Editing** — Every modification to evidence creates a **new modification block** in `tblevidencehistory`, storing the full diff (old vs. new field values), reason for change, and linked hashes.
- **Chain Integrity Verification** — The Blockchain Explorer scans all blocks in real time and visually flags any `PreviousHash` mismatch as a tamper alert.
- **Case Management** — Register, update, and soft-delete legal cases (Criminal, Civil, CyberCrime) with document attachments.
- **Evidence Vault** — Submit evidence with file uploads (PDF, JPG, PNG, MP4, MP3), authority details, and auto-generated Evidence IDs (`EVID-YYYY-NNN`).
- **Case Explorer** — Browse all cases and evidence with status dashboards showing counts by workflow state (Active, Pending, Verified, Rejected, Deleted).
- **Blockchain Explorer** — Visual block-by-block ledger showing Genesis Block, Submission Blocks (gold), Modification Blocks (purple), and Tampered Blocks (red alert) with full diff tables.
- **Session-based Auth** — Login/logout with PHP sessions; all pages redirect unauthenticated users.
- **Soft Delete** — Cases and evidence are never permanently removed; `CaseStatus = 'Deleted'` and `RecordStatus = 'Deleted'` preserve audit history.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.0 |
| Database | MySQL / MariaDB 10.4 |
| Blockchain | Custom PHP (`Blockchain.php`) — SHA-256 |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Fonts | Google Fonts (Source Serif 4, Source Sans 3) |
| Server | Apache via XAMPP / WAMP / LAMP |
| Hash Algorithm | SHA-256 (`hash()` + `hash_file()`) |

---

## 📋 Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or WAMP / LAMP) with PHP >= 8.0, Apache, MySQL
- [phpMyAdmin](https://www.phpmyadmin.net/) for database setup
- A modern browser (Chrome, Firefox, Edge)

---

## 🚀 Installation

### 1. Clone or copy the project

```bash
git clone https://github.com/your-username/evidemo.git
```

Or copy the project folder into your server's web root:

```
XAMPP  →  C:/xampp/htdocs/evidemo/
WAMP   →  C:/wamp64/www/evidemo/
Linux  →  /var/www/html/evidemo/
```

### 2. Import the database

1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Create a new database named `db_evidemo22`
3. Click **Import** → select `db_evidemo22.sql` → click **Go**

### 3. Configure the database connection

Open `db.php` and update credentials if needed (defaults match XAMPP):

```php
$localhost = "localhost";
$root      = "root";
$password  = "";           // XAMPP default is blank
$database  = "db_evidemo22";
```

### 4. Create upload directories

Make sure these folders exist and are writable inside the project root:

```bash
mkdir -p CaseFolder EvidenceFolder uploads
chmod 777 CaseFolder EvidenceFolder uploads
```

On Windows (XAMPP), just create them manually — they will be written to automatically.

### 5. Open the application

```
http://localhost/evidemo/index.html
```

---

## 🔑 Default Login Credentials

| Username | Password | Role |
|---|---|---|
| `admin` | `123` | Administrator |
| `User` | `1234` | User |

> ⚠️ **Change these passwords before any real deployment.**

---

## 💻 How to Use

### Login
Navigate to `index.html` → enter credentials → redirected to `dashboard.php`.

### Register a Case — Module 01
```
Dashboard → Register Case → fill form → Submit
```
Captures Case Title, Type (Criminal / Civil / CyberCrime), Date of Incident, Location, Complainant details, and optional document uploads. Auto-generates `CASE-YYYY-NNN` IDs.

### Submit Evidence — Module 02
```
Dashboard → Submit Evidence → select Case ID → fill evidence details → upload files → Submit
```
- Auto-generates `EVID-YYYY-NNN` IDs.
- Computes SHA-256 block hash chained to the previous evidence block's hash.
- Stores `BlockchainHash` and `PreviousHash` in `tblevidence`.
- Supports file types: PDF, JPG, JPEG, PNG, MP4, MP3 (max 5 MB each).

### Modify Evidence
Edit any active evidence record via the Case Explorer. The system automatically:
1. Diffs old vs. new field values
2. Computes a new SHA-256 hash (linked to the old hash as `PreviousHash`)
3. Updates `tblevidence` with the latest values and new hash
4. Appends a full audit row to `tblevidencehistory` — the modification block — with the reason and field diff

### Explore Cases — Module 03
```
Dashboard → Explore Cases
```
Browse all cases with status breakdowns. Click into a case to see all its evidence, modification history, and blockchain hashes.

### Blockchain Explorer
```
http://localhost/evidemo/blockchain_explorer.php
```
Renders the full chain starting from the Genesis Block. Submission Blocks appear in gold, Modification Blocks in purple with a diff table, and any tampered block appears in red with a detailed hash mismatch alert.

---

## 📁 Project Structure

```
evidemo/
│
├── index.html                 # Login page
├── dashboard.php              # Main dashboard (3 module cards + live stats)
│
├── Register.php               # Case registration form (UI)
├── RegisterOperation.php      # Case CRUD AJAX handler
│
├── submit_evidence.php        # Evidence submission form (UI)
├── save_evidence.php          # Legacy evidence save handler
├── EvidenceOperation.php      # Evidence CRUD + blockchain AJAX handler
│
├── explorer_cases.php         # Case + evidence explorer (UI)
├── blockchain_explorer.php    # Full blockchain ledger viewer
│
├── Blockchain.php             # Core blockchain classes (Block, Blockchain, EvidenceBlockchain)
├── db.php                     # Database connection
├── LoginOperation.php         # Login handler
├── logout.php                 # Session destroy + redirect
├── demo.php                   # Development scratch file
│
├── hash_service.py            # Python hash utility (stub)
├── test_hash_service.py       # Python hash service tests (stub)
│
├── db_evidemo22.sql           # Full database schema + seed data
│
├── CaseFolder/                # Uploaded case documents (auto-created at runtime)
└── EvidenceFolder/            # Uploaded evidence files (auto-created at runtime)
```

---

## 🗄️ Database Schema

### `tblcaseregister` — Legal cases
| Column | Type | Description |
|---|---|---|
| `CaseUId` | VARCHAR(100) | UUID primary identifier |
| `CaseID` | VARCHAR(50) | Human-readable ID (e.g. `CASE-2026-001`) |
| `CaseTitle` | VARCHAR(255) | Title of the case |
| `CaseType` | ENUM | `Criminal`, `Civil`, `CyberCrime` |
| `DateOfIncident` | DATE | Date of the incident |
| `LocationOfIncident` | VARCHAR(255) | Location |
| `CaseDescription` | TEXT | Narrative description |
| `ComplainantName/Phone/Email` | VARCHAR | Complainant contact info |
| `DocumentPath` | TEXT | JSON array of uploaded file paths |
| `CaseStatus` | VARCHAR | `Active` / `Deleted` |

### `tblevidence` — Evidence records (Submission Blocks)
| Column | Type | Description |
|---|---|---|
| `EvidenceUId` | VARCHAR(36) | UUID primary identifier |
| `EvidenceID` | VARCHAR(50) | Human-readable ID (e.g. `EVID-2026-001`) |
| `CaseID` | VARCHAR(50) | Linked case ID |
| `EvidenceType` | VARCHAR(30) | Type of evidence |
| `EvidenceStatus` | VARCHAR(20) | `Pending`, `Verified`, `Rejected`, etc. |
| `SubmittedBy` | VARCHAR(100) | Submitting officer name |
| `AuthorityName` | VARCHAR(100) | Authority rank (e.g. Sub-Inspector) |
| `FilePaths` | LONGTEXT | JSON array of uploaded file paths |
| `BlockchainHash` | VARCHAR(64) | SHA-256 hash of this block |
| `PreviousHash` | VARCHAR(64) | Hash of the previous block (chain link) |
| `RecordStatus` | VARCHAR(20) | `Active` / `Deleted` (soft delete) |

### `tblevidencehistory` — Modification Blocks (Full Audit Trail)
| Column | Description |
|---|---|
| `HistoryUId` | UUID for this history record |
| `EvidenceUId` | Foreign key to `tblevidence` |
| `EvidenceID` | Human-readable evidence ID |
| `BlockType` | Always `EVIDENCE_MODIFY` |
| `BlockchainHash` | New SHA-256 hash for this modification |
| `PreviousHash` | Hash of the block being modified (chain link) |
| `ModifiedBy` | Officer who made the change |
| `ModifyReason` | Mandatory reason for modification |
| `ChangedFields` | JSON diff — `{ "FieldName": { "old": "...", "new": "..." } }` |

### `tblusers` — System users
| Column | Description |
|---|---|
| `UserName` | Login username |
| `Password` | Password |
| `FullName` | Display name |
| `Role` | `Administrator` / `User` |
| `Status` | `Active` / `Pending` / `Deleted` |

---

## 🔗 Blockchain Architecture

```
Genesis Block  (index 0)
│  PreviousHash: 0000...0000
│  Hash: sha256(0 + timestamp + "0000...0000" + "Genesis Block")
│
▼
Submission Block  (index N)
│  PreviousHash: hash of Block N-1
│  Hash: sha256(EvidenceUId + EvidenceID + CaseID + Type + SubmittedBy + Date + timestamp + previousHash)
│  Stored in: tblevidence
│
▼
Modification Block  (index N+1)
│  PreviousHash: BlockchainHash of the block being modified
│  Hash: sha256(HistoryUId + EvidenceUId + EvidenceID + CaseID + Type + Status + Reason + timestamp + oldHash)
│  Stored in: tblevidencehistory
│
▼
...
```

The Blockchain Explorer re-scans the full chain on every page load and highlights any `PreviousHash` mismatch as a tamper alert.

---

## 🛡️ Security Notes

> Address these before deploying in any production or official environment:

- **Plain-text passwords** — `tblusers` currently stores passwords as plain text. Replace with `password_hash()` / `password_verify()`.
- **Prepared statements** — AJAX handlers use `mysqli_real_escape_string()`. Migrate to `$stmt->bind_param()` throughout for stronger SQL injection protection.
- **`db.php` credentials** — never commit real credentials to version control. Use environment variables or a config file excluded from `.gitignore`.
- **File upload validation** — extension and size checks are in place; add server-side MIME type validation for additional safety.
- **Session hardening** — call `session_regenerate_id(true)` after successful login to prevent session fixation attacks.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "Add: description"`
4. Push to branch: `git push origin feature/your-feature`
5. Open a Pull Request

---

## 👥 Author

- **Aditya Dilpake** — [adityadilpake22@gmail.com](mailto:adityadilpake22@gmail.com)



> ⚠️ **Disclaimer:** EviDemo is an academic/demonstration project. A professional security audit is strongly recommended before use in any official law enforcement or legal context.

## 📸 Screenshots

### Login Page
![Login](screenshots/login.png)

### Dashboard
![Dashboard](dashboard.png)

### Register Case
![Register Case](screenshots/register-case.png)

### Submit Evidence
![Submit Evidence](screenshots/submit-evidence.png)

### Case Explorer
![Case Explorer](screenshots/case-explorer.png)

### Blockchain Explorer
![Blockchain Explorer](screenshots/blockchain-explorer.png)
