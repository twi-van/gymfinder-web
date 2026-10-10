<?php
declare(strict_types=1);

// Admin resource: dashboard.

/**
 * Trả về thống kê tổng quan cho dashboard admin.
 */
function gf_admin_stats(): array
{
    $p = gf_pdo();
    return [
        'gyms'                  => (int) $p->query('SELECT COUNT(*) FROM gyms')->fetchColumn(),
        'trainers'              => (int) $p->query('SELECT COUNT(*) FROM trainers')->fetchColumn(),
        'users'                 => (int) $p->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
        'reviews'               => (int) $p->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
        'pending_reviews'       => (int) $p->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn(),
        'total_users'           => (int) $p->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'total_gyms_active'     => (int) $p->query("SELECT COUNT(*) FROM gyms WHERE status='active'")->fetchColumn(),
        'total_trainers_active' => (int) $p->query("SELECT COUNT(*) FROM trainers WHERE status='active'")->fetchColumn(),
        'total_categories_active' => (int) $p->query('SELECT COUNT(*) FROM categories WHERE is_active=1')->fetchColumn(),
        'approved_reviews'      => (int) $p->query("SELECT COUNT(*) FROM reviews WHERE status='approved'")->fetchColumn(),
    ];
}

// ---------------------------------------------------------------------------
// ADMIN GYM CRUD
// ---------------------------------------------------------------------------
