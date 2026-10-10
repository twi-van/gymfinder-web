<?php
declare(strict_types=1);

// Test script for Sprint 0, 1, 2, 3 Backend & Database functionality
require_once __DIR__ . '/../backend/bootstrap.php';

function test_assert(bool $condition, string $message): void {
    if (!$condition) {
        echo "[FAIL] $message\n";
        throw new RuntimeException("Assertion failed: $message");
    }
    echo "[PASS] $message\n";
}

echo "========================================================\n";
echo "STARTING GYMFINDER BACKEND & DB INTEGRATION TESTS (S0-S3)\n";
echo "========================================================\n\n";

$pdo = gf_db();

// -----------------------------------------------------------------------------
// SPRINT 0: DATABASE SCHEMA & SEED VERIFICATION
// -----------------------------------------------------------------------------
echo "--- SPRINT 0: Database & Core Infrastructure ---\n";

// 1. Check all 12 tables exist
$expectedTables = [
    'districts', 'categories', 'amenities', 'specialties',
    'users', 'gyms', 'gym_images', 'gym_categories', 'gym_amenities',
    'trainers', 'reviews', 'favorites'
];

foreach ($expectedTables as $table) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
    $count = (int) $stmt->fetchColumn();
    test_assert($count >= 0, "Table '$table' exists and is queryable (rows: $count)");
}

// 2. Check seed counts
$userCount = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
test_assert($userCount >= 6, "Seed users exist (count: $userCount, expects >= 6)");

$gymCount = (int) $pdo->query("SELECT COUNT(*) FROM gyms")->fetchColumn();
test_assert($gymCount >= 8, "Seed gyms exist (count: $gymCount, expects >= 8)");

$trainerCount = (int) $pdo->query("SELECT COUNT(*) FROM trainers")->fetchColumn();
test_assert($trainerCount >= 10, "Seed trainers exist (count: $trainerCount, expects >= 10)");

// 3. Test CSRF generation & verification
$csrf = gf_csrf_token();
test_assert(strlen($csrf) === 64, "CSRF token generated with 64 hex chars");
test_assert(gf_verify_csrf($csrf), "CSRF verification passes with matching token");
test_assert(!gf_verify_csrf("invalid_csrf_token"), "CSRF verification fails with invalid token");


// -----------------------------------------------------------------------------
// SPRINT 1: AUTH & PROFILE
// -----------------------------------------------------------------------------
echo "\n--- SPRINT 1: Authentication & User Profile ---\n";

// 1. Registration
$testEmail = 'tester_' . time() . '@example.com';
$regRes = gf_register('Nguyen Van Test', $testEmail, 'Password123!', '0901234567', 'giam_can');
test_assert($regRes['ok'] === true, "User registered successfully");
$testUser = $regRes['user'];
test_assert($testUser['email'] === $testEmail, "Registered email matches: $testEmail");

// Duplicate registration -> duplicate email check
$regDup = gf_register('Nguyen Van Test 2', $testEmail, 'Password123!');
test_assert($regDup['ok'] === false && $regDup['code'] === 'DUPLICATE_EMAIL', "Duplicate email registration caught correctly with DUPLICATE_EMAIL");

// 2. Login
$loginSuccess = gf_login($testEmail, 'Password123!');
test_assert($loginSuccess['ok'] === true && $loginSuccess['user']['email'] === $testEmail, "User logged in successfully");

$loginFail = gf_login($testEmail, 'WrongPassword!');
test_assert($loginFail['ok'] === false && $loginFail['code'] === 'INVALID_CREDENTIALS', "Login fails with INVALID_CREDENTIALS");

// 3. Locked User Login Check
$lockedLogin = gf_login('u06@gymfinder.test', 'password'); // U06 is locked
test_assert($lockedLogin['ok'] === false && $lockedLogin['code'] === 'ACCOUNT_LOCKED', "Locked user login rejected with ACCOUNT_LOCKED");

// 4. Update Profile
gf_update_profile((int)$testUser['id'], [
    'full_name' => 'Nguyen Van Test Updated',
    'phone' => '0988776655'
]);
$stmt = $pdo->prepare("SELECT full_name, phone FROM users WHERE id = ?");
$stmt->execute([(int)$testUser['id']]);
$updatedUser = $stmt->fetch();
test_assert($updatedUser['full_name'] === 'Nguyen Van Test Updated', "User full_name updated successfully");
test_assert($updatedUser['phone'] === '0988776655', "User phone updated successfully");

// 5. Change Password
$pwdChanged = gf_change_password((int)$testUser['id'], 'Password123!', 'NewPassword456!');
test_assert($pwdChanged['ok'] === true, "Password changed successfully with correct old password");

$pwdFail = gf_change_password((int)$testUser['id'], 'WrongOldPwd!', 'AnotherPass789!');
test_assert($pwdFail['ok'] === false, "Password change rejected with incorrect old password");


// -----------------------------------------------------------------------------
// SPRINT 2: GYM MODULE & TAXONOMIES
// -----------------------------------------------------------------------------
echo "\n--- SPRINT 2: Gym Module & Taxonomies ---\n";

// 1. Taxonomies
$districts = gf_list_districts();
test_assert(count($districts) >= 3, "Districts taxonomy fetched (count: " . count($districts) . ")");
$categories = gf_list_categories(false);
test_assert(count($categories) >= 5, "Categories taxonomy fetched (count: " . count($categories) . ")");
$amenities = gf_list_amenities();
test_assert(count($amenities) >= 5, "Amenities taxonomy fetched (count: " . count($amenities) . ")");
$specialties = gf_list_specialties();
test_assert(count($specialties) >= 4, "Specialties taxonomy fetched (count: " . count($specialties) . ")");

// 2. Gym Search with Various Filters
// Filter by district
$d1Gyms = gf_search_gyms_paged(['district_id' => 1, 'page' => 1, 'limit' => 10]);
test_assert($d1Gyms['total'] > 0, "Gym search by district_id=1 returns results (total: {$d1Gyms['total']})");
foreach ($d1Gyms['items'] as $gym) {
    test_assert((int)$gym['district_id'] === 1, "Gym {$gym['name']} belongs to district 1");
}

// Filter by amenity AND logic
$amenityGyms = gf_search_gyms_paged(['amenities' => [1, 2], 'page' => 1, 'limit' => 10]);
test_assert(is_array($amenityGyms['items']), "Gym search with multiple amenities (AND logic) executes");

// Search validation
$gymParamRes = gf_validate_gym_search_params(['min_price' => 500000, 'max_price' => 100000], false);
test_assert(!$gymParamRes['valid'] && isset($gymParamRes['details']['price_range']), "Validation catches min_price > max_price");

// 3. Gym Detail (id or slug)
$gymRaw = gf_get_gym_by_key('iron-fitness-center');
test_assert($gymRaw !== null, "Gym fetched by slug 'iron-fitness-center'");
$gymDetail = gf_public_gym_detail($gymRaw, null);
test_assert(isset($gymDetail['categories']) && is_array($gymDetail['categories']), "Gym detail includes categories array");
test_assert(isset($gymDetail['amenities']) && is_array($gymDetail['amenities']), "Gym detail includes amenities array");
test_assert(isset($gymDetail['reviews']) && is_array($gymDetail['reviews']), "Gym detail includes reviews preview array");

// 4. Admin Gym Management & District Sync Rule
$gymSaveRes = gf_admin_save_gym([
    'name' => 'Automated Test Gym',
    'address' => '999 Nguyen Trai, Q5',
    'district_id' => 1,
    'price_min' => 400000,
    'price_max' => 900000,
    'category_ids' => [1, 2],
    'amenity_ids' => [1, 3]
], null);
test_assert($gymSaveRes['ok'] === true && isset($gymSaveRes['id']), "Admin created gym with ID {$gymSaveRes['id']}");
$gymId = $gymSaveRes['id'];
$newGym = gf_admin_gym_payload($gymId);
test_assert(str_starts_with($newGym['slug'], 'automated-test-gym'), "Gym slug auto-generated: {$newGym['slug']}");

// Create trainer for this gym
$trainerSaveRes = gf_admin_save_trainer([
    'full_name' => 'Coach Automated Test',
    'gym_id' => $gymId,
    'district_id' => 1, // must match gym district
    'specialty_id' => 1,
    'years_experience' => 5
], null);
test_assert($trainerSaveRes['ok'] === true && isset($trainerSaveRes['id']), "Admin created trainer in gym {$gymId}");
$trainerId = $trainerSaveRes['id'];
$newTrainer = gf_admin_trainer_payload($trainerId);

// Test Canonical Rule: Changing gym district_id cascades to trainers.district_id
$updateGymRes = gf_admin_save_gym([
    'name' => 'Automated Test Gym',
    'district_id' => 2 // change from 1 to 2
], $gymId);
test_assert($updateGymRes['ok'] === true, "Admin updated Gym district to 2");

$stmt = $pdo->prepare("SELECT district_id FROM trainers WHERE id = ?");
$stmt->execute([$trainerId]);
$reloadedDistrict = (int)$stmt->fetchColumn();
test_assert($reloadedDistrict === 2, "Canonical Rule PASSED: Updating Gym district to 2 cascaded to Trainer district ($reloadedDistrict)");


// -----------------------------------------------------------------------------
// SPRINT 3: TRAINER, FAVORITE, REVIEW & RECALC RATING
// -----------------------------------------------------------------------------
echo "\n--- SPRINT 3: Trainers, Favorites, Reviews & Rating Recalc ---\n";

// 1. Trainer Search with Experience Buckets
$tr02 = gf_search_trainers_paged(['experience' => '0-2', 'page' => 1, 'limit' => 10]);
test_assert(is_array($tr02['items']), "Trainer search by experience '0-2' returned valid response");
foreach ($tr02['items'] as $t) {
    test_assert((int)$t['years_experience'] <= 2, "Trainer {$t['full_name']} has <= 2 years exp ({$t['years_experience']})");
}

$tr6plus = gf_search_trainers_paged(['experience' => '6plus', 'page' => 1, 'limit' => 10]);
test_assert(is_array($tr6plus['items']), "Trainer search by experience '6plus' returned valid response");
foreach ($tr6plus['items'] as $t) {
    test_assert((int)$t['years_experience'] >= 6, "Trainer {$t['full_name']} has >= 6 years exp ({$t['years_experience']})");
}

// Trainer detail
$trainerRaw = gf_get_trainer_by_key((string)$newTrainer['id']);
$trainerDetail = gf_public_trainer_detail($trainerRaw, null);
test_assert($trainerDetail !== null, "Trainer detail fetched by ID");
test_assert(isset($trainerDetail['gym']['name']), "Trainer detail includes gym object");
test_assert(isset($trainerDetail['specialty']['name']), "Trainer detail includes specialty object");

// 2. Favorites Module
$userId = (int)$testUser['id'];
$favGym = gf_add_favorite_row($userId, 'gym', $gymId);
test_assert(isset($favGym['id']), "Added gym to favorites with ID {$favGym['id']}");

// Duplicate favorite returns true with gf_is_fav
$isFav = gf_is_fav($userId, 'gym', $gymId);
test_assert($isFav === true, "Favorite exists check (gf_is_fav) returns true");

// List favorites
$myFavs = gf_favorites_paged($userId, 1, 10);
test_assert($myFavs['total'] >= 1, "User favorites listed successfully (count: {$myFavs['total']})");
test_assert($myFavs['items'][0]['target_id'] == $gymId, "Favorite target_id matches");

// Sync favorites
$syncRes = gf_sync_favorites($userId, [
    ['target_type' => 'trainer', 'target_id' => $trainerId],
    ['target_type' => 'gym', 'target_id' => $gymId] // already exists
]);
test_assert(count($syncRes['added']) === 1 && count($syncRes['skipped']) === 1, "Favorites sync added 1 new and skipped 1 existing (added: " . count($syncRes['added']) . ", skipped: " . count($syncRes['skipped']) . ")");

// Delete favorite (even if hidden)
gf_delete_favorite_item($userId, 'gym', $gymId);
test_assert(!gf_is_fav($userId, 'gym', $gymId), "Favorite item deleted successfully");

// 3. Reviews Module & Rating Recalculation
$revRes = gf_create_review($userId, 'gym', $gymId, 4, 'Phòng tập rất tốt, sạch sẽ.');
test_assert($revRes['ok'] === true && isset($revRes['review']['id']), "Created review for gym {$gymId} (ID: {$revRes['review']['id']})");
$rev1 = $revRes['review'];
test_assert($rev1['status'] === 'pending', "New review has default status 'pending'");

// Duplicate review by same user for same target
$revDup = gf_create_review($userId, 'gym', $gymId, 5, 'Review lần 2');
test_assert($revDup['ok'] === false && $revDup['code'] === 'DUPLICATE_REVIEW', "Duplicate review check caught duplicate review error");

// Verify recalcRating: Since review is pending, gym avg_rating should still be 0.0, review_count = 0
$gymBeforeApprove = gf_get_gym($gymId, false);
test_assert((int)$gymBeforeApprove['review_count'] === 0, "Gym review_count is 0 before approval");
test_assert((float)$gymBeforeApprove['avg_rating'] === 0.0, "Gym avg_rating is 0.0 before approval");

// Admin approves review
$adminUser = ['id' => 1, 'role' => 'admin'];
$modRes = gf_admin_moderate_review((int)$rev1['id'], ['status' => 'approved'], $adminUser);
test_assert($modRes !== null && $modRes['status'] === 'approved', "Admin approved review {$rev1['id']}");

// Check recalcRating after approve: avg_rating = 4.0, review_count = 1
$gymAfterApprove = gf_get_gym($gymId, false);
test_assert((int)$gymAfterApprove['review_count'] === 1, "Gym review_count updated to 1 after approval");
test_assert((float)$gymAfterApprove['avg_rating'] === 4.0, "Gym avg_rating updated to 4.0 after approval");

// Owner edits review -> status must reset to 'pending' and recalcRating must run
$patchedRev = gf_patch_review_owner((int)$rev1['id'], [
    'rating' => 5,
    'comment' => 'Phòng tập tuyệt vời hơn mong đợi sau khi sửa.'
], $testUser);
test_assert($patchedRev['status'] === 'pending', "Review status reset to 'pending' after owner edit");
test_assert((int)$patchedRev['rating'] === 5, "Review rating updated to 5");

// Check recalcRating after owner edit reset to pending: should drop back to 0.0, count = 0
$gymAfterEdit = gf_get_gym($gymId, false);
test_assert((int)$gymAfterEdit['review_count'] === 0, "Gym review_count reset to 0 because edited review is pending");
test_assert((float)$gymAfterEdit['avg_rating'] === 0.0, "Gym avg_rating reset to 0.0 because edited review is pending");

// Admin approves again
gf_admin_moderate_review((int)$rev1['id'], ['status' => 'approved'], $adminUser);
$gymAfterReApprove = gf_get_gym($gymId, false);
test_assert((int)$gymAfterReApprove['review_count'] === 1, "Gym review_count is 1 after re-approval");
test_assert((float)$gymAfterReApprove['avg_rating'] === 5.0, "Gym avg_rating is 5.0 after re-approval");

// Owner deletes review -> review deleted and rating recalculated
gf_delete_review_owner((int)$rev1['id'], $testUser);
$gymAfterDelete = gf_get_gym($gymId, false);
test_assert((int)$gymAfterDelete['review_count'] === 0, "Gym review_count reset to 0 after review deletion");
test_assert((float)$gymAfterDelete['avg_rating'] === 0.0, "Gym avg_rating reset to 0.0 after review deletion");

// Clean up test data
gf_admin_delete_trainer($trainerId);
gf_admin_delete_gym($gymId);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
test_assert(true, "Cleaned up temporary test gym, trainer, and user");

echo "\n========================================================\n";
echo "ALL TESTS PASSED SUCCESSFULLY! SPRINT 0, 1, 2, 3 VERIFIED!\n";
echo "========================================================\n";
