<?php
class ImageUpload
{
    private const MAX_BYTES = 2097152;
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public static function save(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed. Each image must be 2 MB or smaller.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('Each image must be 2 MB or smaller.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime])) {
            throw new RuntimeException('Images must be JPG, PNG or WEBP.');
        }

        $dir = BASE_PATH . '/public/uploads/inventory';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Could not create the upload folder.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('Could not save the image.');
        }
        return $name;
    }

    public static function missing(string $field): bool
    {
        return ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;
    }

    public static function delete(?string $name): void
    {
        if ($name === null || $name === '') {
            return;
        }
        $path = BASE_PATH . '/public/uploads/inventory/' . basename($name);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
