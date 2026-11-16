<?php
declare(strict_types=1);
/**
 * Đồng bộ 3 tài khoản demo (u01 / u02 / u25) giống local.
 * Local:   php database/seed_demo_users.php
 * Railway: railway run php database/seed_demo_users.php
 */
require_once dirname(__DIR__) . '/backend/bootstrap.php';

$hash = '$2y$10$/FRhOvA.xweR/mKOVrjXwukbYebNt3hanmHZaYIlAZNBlPwBH61R.'; // password
$rows = [
    ['Admin GymFinder', 'u01@gymfinder.test', '0900000001', 'admin', 'active', '2023-10-01 09:00:00'],
    ['Nguyễn Văn An', 'u02@gymfinder.test', '0900000002', 'user', 'active', '2024-01-15 10:20:00'],
    ['Võ Thị Em (Khóa)', 'u25@gymfinder.test', '0900000025', 'user', 'locked', '2026-07-21 11:30:00'],
];

$pdo = gf_pdo();
$find = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$upd = $pdo->prepare(
    'UPDATE users SET full_name=?, phone=?, password_hash=?, role=?, status=?,
            must_change_password=0, temp_password_expires_at=NULL, created_at=COALESCE(created_at, ?)
     WHERE email=?'
);
$ins = $pdo->prepare(
    'INSERT INTO users (full_name, email, phone, password_hash, role, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);

foreach ($rows as [$name, $email, $phone, $role, $status, $created]) {
    $find->execute([$email]);
    $id = $find->fetchColumn();
    if ($id) {
        $upd->execute([$name, $phone, $hash, $role, $status, $created, $email]);
        echo "UPDATED {$email} id={$id} ({$role}/{$status})\n";
    } else {
        $ins->execute([$name, $email, $phone, $hash, $role, $status, $created]);
        echo "INSERTED {$email} ({$role}/{$status})\n";
    }
}
echo "Done. Password for all: password\n";
