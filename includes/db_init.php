<?php
declare(strict_types=1);

function hasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
    $columns = $stmt ? $stmt->fetchAll() : [];
    foreach ($columns as $col) {
        if (($col['name'] ?? '') === $column) {
            return true;
        }
    }

    return false;
}

function ensureProductColumns(PDO $pdo): void
{
    if (!hasColumn($pdo, 'products', 'requester_name')) {
        $pdo->exec('ALTER TABLE products ADD COLUMN requester_name TEXT NOT NULL DEFAULT ""');
    }

    if (!hasColumn($pdo, 'products', 'business_location')) {
        $pdo->exec('ALTER TABLE products ADD COLUMN business_location TEXT NOT NULL DEFAULT ""');
    }

    if (!hasColumn($pdo, 'products', 'source_request_id')) {
        $pdo->exec('ALTER TABLE products ADD COLUMN source_request_id INTEGER');
    }
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL CHECK (role IN ("compratore", "pescatore", "admin")),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            fish_type TEXT NOT NULL,
            catch_area TEXT NOT NULL,
            catch_date TEXT NOT NULL,
            microplastics_percent REAL NOT NULL,
            dissolved_oxygen REAL NOT NULL,
            salinity REAL NOT NULL,
            quality_label TEXT NOT NULL,
            fisherman_id INTEGER,
            FOREIGN KEY (fisherman_id) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS buoys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            zone TEXT NOT NULL,
            lat REAL NOT NULL,
            lng REAL NOT NULL,
            status TEXT NOT NULL,
            last_update TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS buoy_readings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            buoy_id INTEGER NOT NULL,
            salinity REAL NOT NULL,
            dissolved_oxygen REAL NOT NULL,
            microplastics_percent REAL NOT NULL,
            recorded_at TEXT NOT NULL,
            FOREIGN KEY (buoy_id) REFERENCES buoys(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS product_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fisherman_id INTEGER NOT NULL,
            requester_name TEXT NOT NULL,
            business_location TEXT NOT NULL,
            fish_type TEXT NOT NULL,
            catch_location TEXT NOT NULL,
            requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status TEXT NOT NULL DEFAULT "pending" CHECK (status IN ("pending", "approved", "rejected")),
            admin_note TEXT,
            generated_code TEXT,
            approved_by INTEGER,
            approved_at TEXT,
            FOREIGN KEY (fisherman_id) REFERENCES users(id),
            FOREIGN KEY (approved_by) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            target_user_id INTEGER,
            target_role TEXT,
            message TEXT NOT NULL,
            link TEXT,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (target_user_id) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            buyer_id INTEGER NOT NULL,
            fisherman_id INTEGER NOT NULL,
            rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (product_id, buyer_id),
            FOREIGN KEY (product_id) REFERENCES products(id),
            FOREIGN KEY (buyer_id) REFERENCES users(id),
            FOREIGN KEY (fisherman_id) REFERENCES users(id)
        )'
    );

    ensureProductColumns($pdo);

    seedDefaults($pdo);
}

function seedDefaults(PDO $pdo): void
{
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, username, email, password_hash, role)
             VALUES (:full_name, :username, :email, :password_hash, :role)'
        );

        $defaults = [
            [
                'full_name' => 'Amministratore Lanz',
                'username' => 'admin',
                'email' => 'admin@lanz.it',
                'password_hash' => password_hash('Admin123!', PASSWORD_DEFAULT),
                'role' => 'admin',
            ],
            [
                'full_name' => 'Pescatore Demo',
                'username' => 'pescatore_demo',
                'email' => 'pescatore@lanz.it',
                'password_hash' => password_hash('Pescatore123!', PASSWORD_DEFAULT),
                'role' => 'pescatore',
            ],
            [
                'full_name' => 'Compratore Demo',
                'username' => 'compratore_demo',
                'email' => 'compratore@lanz.it',
                'password_hash' => password_hash('Compratore123!', PASSWORD_DEFAULT),
                'role' => 'compratore',
            ],
        ];

        foreach ($defaults as $user) {
            $stmt->execute($user);
        }
    }

    $buoyCount = (int) $pdo->query('SELECT COUNT(*) FROM buoys')->fetchColumn();
    if ($buoyCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO buoys (name, zone, lat, lng, status, last_update)
             VALUES (:name, :zone, :lat, :lng, :status, :last_update)'
        );

        $rows = [
            ['name' => 'Boa Lanz 01', 'zone' => 'Bari Vecchia', 'lat' => 41.1291, 'lng' => 16.8727, 'status' => 'Online', 'last_update' => '2026-03-10 10:15'],
            ['name' => 'Boa Lanz 02', 'zone' => 'Pane e Pomodoro', 'lat' => 41.1132, 'lng' => 16.8922, 'status' => 'Online', 'last_update' => '2026-03-10 10:10'],
            ['name' => 'Boa Lanz 03', 'zone' => 'San Girolamo', 'lat' => 41.1438, 'lng' => 16.8341, 'status' => 'Manutenzione', 'last_update' => '2026-03-10 09:50'],
            ['name' => 'Boa Lanz 04', 'zone' => 'Torre a Mare', 'lat' => 41.0614, 'lng' => 17.0125, 'status' => 'Online', 'last_update' => '2026-03-10 10:09'],
        ];

        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    $productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($productCount === 0) {
        $fishermanIdStmt = $pdo->prepare('SELECT id FROM users WHERE role = :role LIMIT 1');
        $fishermanIdStmt->execute(['role' => 'pescatore']);
        $fishermanId = (int) ($fishermanIdStmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare(
            'INSERT INTO products (
                code, fish_type, catch_area, catch_date,
                microplastics_percent, dissolved_oxygen, salinity,
                quality_label, fisherman_id
             ) VALUES (
                :code, :fish_type, :catch_area, :catch_date,
                :microplastics_percent, :dissolved_oxygen, :salinity,
                :quality_label, :fisherman_id
             )'
        );

        $rows = [
            [
                'code' => 'LZ-2026-0001',
                'fish_type' => 'Orata',
                'catch_area' => 'Bari - Zona Nord',
                'catch_date' => '2026-03-08',
                'microplastics_percent' => 0.8,
                'dissolved_oxygen' => 7.4,
                'salinity' => 37.9,
                'quality_label' => 'Eccellente',
                'fisherman_id' => $fishermanId,
            ],
            [
                'code' => 'LZ-2026-0002',
                'fish_type' => 'Polpo',
                'catch_area' => 'Bari - Torre a Mare',
                'catch_date' => '2026-03-09',
                'microplastics_percent' => 1.1,
                'dissolved_oxygen' => 7.1,
                'salinity' => 38.2,
                'quality_label' => 'Ottima',
                'fisherman_id' => $fishermanId,
            ],
        ];

        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    $readingCount = (int) $pdo->query('SELECT COUNT(*) FROM buoy_readings')->fetchColumn();
    if ($readingCount === 0) {
        $buoyIds = $pdo->query('SELECT id FROM buoys ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
        $readingStmt = $pdo->prepare(
            'INSERT INTO buoy_readings (
                buoy_id, salinity, dissolved_oxygen, microplastics_percent, recorded_at
            ) VALUES (
                :buoy_id, :salinity, :dissolved_oxygen, :microplastics_percent, :recorded_at
            )'
        );

        foreach ($buoyIds as $buoyId) {
            for ($i = 11; $i >= 0; $i--) {
                $readingStmt->execute([
                    'buoy_id' => (int) $buoyId,
                    'salinity' => mt_rand(365, 390) / 10,
                    'dissolved_oxygen' => mt_rand(65, 82) / 10,
                    'microplastics_percent' => mt_rand(5, 16) / 10,
                    'recorded_at' => date('Y-m-d H:i', strtotime('-' . ($i * 2) . ' hours')),
                ]);
            }
        }
    }
}
