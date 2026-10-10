<?php
declare(strict_types=1);

// =============================================================================
// UPLOAD RESOURCE — xử lý upload file ảnh
// =============================================================================

/**
 * Lưu file ảnh upload từ request, validate loại file và quyền.
 * Trả về ['ok'=>true, 'url'=>'/uploads/...'] hoặc ['ok'=>false, ...].
 */
function gf_save_upload(array $file, string $type, array $user): array
{
    // Chỉ admin mới upload ảnh gym/trainer
    $allowedType = [
        'user_avatar'    => 'user',
        'trainer_avatar' => 'admin',
        'gym_cover'      => 'admin',
        'gym_image'      => 'admin',
    ];

    if (!isset($allowedType[$type])) {
        return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'type không hợp lệ.', 'details' => ['type' => 'user_avatar|trainer_avatar|gym_cover|gym_image']];
    }
    if ($allowedType[$type] === 'admin' && ($user['role'] ?? '') !== 'admin') {
        return ['ok' => false, 'http' => 403, 'code' => 'FORBIDDEN', 'error' => 'Không đủ quyền upload loại này.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'File không hợp lệ.', 'details' => ['file' => 'Upload thất bại.']];
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'Tối đa 5MB.', 'details' => ['file' => 'Tối đa 5MB.']];
    }

    // Detect định dạng ảnh bằng magic bytes
    $tmp  = $file['tmp_name'];
    $head = file_get_contents($tmp, false, null, 0, 16) ?: '';
    $ext  = null;
    $mime = '';
    if (class_exists('finfo')) {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    }
    if (str_starts_with($head, "\xFF\xD8\xFF") && ($mime === '' || $mime === 'image/jpeg')) {
        $ext = 'jpg';
    } elseif (str_starts_with($head, "\x89PNG") && ($mime === '' || $mime === 'image/png')) {
        $ext = 'png';
    } elseif ((str_starts_with($head, 'RIFF') && str_contains($head, 'WEBP')) && ($mime === '' || $mime === 'image/webp')) {
        $ext = 'webp';
    }
    if (!$ext) {
        return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'Chỉ JPG, PNG, WEBP.', 'details' => ['file' => 'Sai định dạng.']];
    }

    // Lưu file
    $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $dest) && !rename($tmp, $dest)) {
        return ['ok' => false, 'http' => 500, 'code' => 'SERVER_ERROR', 'error' => 'Không lưu được file.'];
    }

    return ['ok' => true, 'url' => '/uploads/' . $name];
}
