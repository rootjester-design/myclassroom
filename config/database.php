<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dbDir = dirname(DB_PATH);
        if (!is_dir($dbDir) && !mkdir($dbDir, 0755, true) && !is_dir($dbDir)) {
            throw new PDOException('Unable to create database directory: ' . $dbDir);
        }

        // Ensure the database directory and file are writable. On deployed servers
        // a common cause of "attempt to write a readonly database" is incorrect
        // ownership/permissions (e.g., file owned by root). Attempt a safe chmod,
        // but if it fails, raise a clear error with remediation steps.
        $dbFile = DB_PATH;
        if (file_exists($dbFile)) {
            if (!is_writable($dbFile)) {
                @chmod($dbFile, 0664);
                if (!is_writable($dbFile)) {
                    throw new PDOException('Database file is not writable: ' . $dbFile . '.\n' .
                        'Fix: on the server run e.g. `chown -R www-data:www-data ' . dirname(DB_PATH) . '` and `chmod -R 775 ' . dirname(DB_PATH) . '` (adjust user for your webserver).');
                }
            }
        } else {
            // If the file doesn't exist yet, the directory itself must be writable
            if (!is_writable($dbDir)) {
                @chmod($dbDir, 0775);
                if (!is_writable($dbDir)) {
                    throw new PDOException('Database directory is not writable: ' . $dbDir . '.\n' .
                        'Fix: on the server run e.g. `chown -R www-data:www-data ' . $dbDir . '` and `chmod -R 775 ' . $dbDir . '` (adjust user for your webserver).');
                }
            }
        }

        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec('PRAGMA journal_mode=WAL');
            $this->pdo->exec('PRAGMA foreign_keys=ON');
            $this->initDatabase();
        } catch (PDOException $e) {
            $message = 'Database connection failed: ' . $e->getMessage();
            error_log($message);
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
            } else {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8');
                echo $message;
            }
            exit;
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPDO(): PDO {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): string {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }

    private function initDatabase(): void {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'super_admin',
                is_active INTEGER DEFAULT 1,
                last_login TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS tutors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                phone TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                display_name TEXT,
                description TEXT,
                experience TEXT,
                qualifications TEXT,
                subjects TEXT,
                profile_image TEXT,
                banner_image TEXT,
                social_links TEXT,
                contact_info TEXT,
                is_active INTEGER DEFAULT 1,
                is_approved INTEGER DEFAULT 0,
                is_suspended INTEGER DEFAULT 0,
                rating REAL DEFAULT 0,
                total_students INTEGER DEFAULT 0,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                phone TEXT UNIQUE NOT NULL,
                email TEXT,
                password TEXT NOT NULL,
                birthday TEXT,
                address TEXT,
                profile_image TEXT,
                student_id TEXT UNIQUE,
                is_active INTEGER DEFAULT 1,
                is_verified INTEGER DEFAULT 0,
                is_suspended INTEGER DEFAULT 0,
                last_login TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT,
                icon TEXT,
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT,
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS courses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tutor_id INTEGER NOT NULL,
                subject_id INTEGER,
                title TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT,
                thumbnail TEXT,
                price REAL DEFAULT 0,
                monthly_fee REAL DEFAULT 0,
                duration TEXT,
                level TEXT DEFAULT 'beginner',
                is_active INTEGER DEFAULT 1,
                is_featured INTEGER DEFAULT 0,
                is_approved INTEGER DEFAULT 1,
                total_students INTEGER DEFAULT 0,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS enrollments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                course_id INTEGER NOT NULL,
                tutor_id INTEGER NOT NULL,
                status TEXT DEFAULT 'active',
                enrolled_at TEXT DEFAULT (datetime('now')),
                progress INTEGER DEFAULT 0,
                UNIQUE(student_id, course_id),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                course_id INTEGER NOT NULL,
                tutor_id INTEGER NOT NULL,
                amount REAL NOT NULL,
                payment_reference TEXT,
                payment_slip TEXT,
                notes TEXT,
                status TEXT DEFAULT 'pending',
                reviewed_by INTEGER,
                reviewed_at TEXT,
                reject_reason TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS recordings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                tutor_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                video_url TEXT,
                duration TEXT,
                thumbnail TEXT,
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS zoom_links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                tutor_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                zoom_url TEXT NOT NULL,
                meeting_id TEXT,
                passcode TEXT,
                scheduled_at TEXT,
                duration_minutes INTEGER DEFAULT 60,
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS announcements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER,
                tutor_id INTEGER,
                admin_id INTEGER,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                type TEXT DEFAULT 'course',
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE SET NULL,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS course_materials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                tutor_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                file_path TEXT,
                file_type TEXT,
                file_size INTEGER,
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INTEGER NOT NULL,
                tutor_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                due_date TEXT,
                max_marks INTEGER DEFAULT 100,
                file_path TEXT,
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS student_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                course_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                content TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                updated_at TEXT DEFAULT (datetime('now')),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS otp_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                phone TEXT NOT NULL,
                otp TEXT NOT NULL,
                purpose TEXT DEFAULT 'register',
                is_used INTEGER DEFAULT 0,
                expires_at TEXT NOT NULL,
                created_at TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                user_type TEXT NOT NULL,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                type TEXT DEFAULT 'info',
                is_read INTEGER DEFAULT 0,
                related_id INTEGER,
                related_type TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS banners (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                image TEXT NOT NULL,
                link TEXT,
                is_active INTEGER DEFAULT 1,
                sort_order INTEGER DEFAULT 0,
                created_at TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                user_type TEXT,
                action TEXT NOT NULL,
                description TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at TEXT DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS ratings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                tutor_id INTEGER NOT NULL,
                course_id INTEGER,
                rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
                review TEXT,
                created_at TEXT DEFAULT (datetime('now')),
                UNIQUE(student_id, tutor_id, course_id),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (tutor_id) REFERENCES tutors(id) ON DELETE CASCADE
            );
        ");

        // Seed default super admin
        $admin = $this->fetch("SELECT id FROM admins WHERE email = ?", [SUPER_ADMIN_DEFAULT_EMAIL]);
        if (!$admin) {
            $this->insert(
                "INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)",
                ['superadmin', SUPER_ADMIN_DEFAULT_EMAIL, password_hash(SUPER_ADMIN_DEFAULT_PASSWORD, PASSWORD_BCRYPT), 'super_admin']
            );
        }

        // Seed default categories
        $catCount = $this->fetch("SELECT COUNT(*) as cnt FROM categories")['cnt'];
        if ($catCount == 0) {
            $categories = [
                ['Mathematics', 'mathematics', 'Math subjects'],
                ['Science', 'science', 'Science subjects'],
                ['Languages', 'languages', 'Language courses'],
                ['Technology', 'technology', 'Tech & Computing'],
                ['Arts', 'arts', 'Arts & Creativity'],
            ];
            foreach ($categories as $cat) {
                $this->insert(
                    "INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)",
                    $cat
                );
            }
        }
    }
}
