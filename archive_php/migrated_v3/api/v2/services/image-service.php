<?php

declare(strict_types=1);

/**
 * Image Service for INFOTESS
 * Production-ready image upload, validation, and processing
 */

class ImageService {
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png', 
        'image/gif',
        'image/webp'
    ];
    private int $maxFileSize = 5 * 1024 * 1024; // 5MB
    private int $maxWidth = 2000;
    private int $maxHeight = 2000;
    private string $uploadDir;
    
    public function __construct() {
        $this->uploadDir = __DIR__ . '/../../../../uploads/';
        $this->ensureUploadDirectories();
    }
    
    /**
     * Process profile picture upload
     */
    public function processProfilePicture(array $file, int $userId): array {
        try {
            // Validate file upload
            $validation = $this->validateImageUpload($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            // Get image info
            $imageInfo = getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                return [
                    'success' => false,
                    'error' => 'Invalid image file'
                ];
            }
            
            // Check image dimensions
            list($width, $height) = $imageInfo;
            if ($width > $this->maxWidth || $height > $this->maxHeight) {
                return [
                    'success' => false,
                    'error' => "Image dimensions too large. Maximum: {$this->maxWidth}x{$this->maxHeight}px"
                ];
            }
            
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file['name'], $userId);
            $uploadPath = $this->uploadDir . 'profile_pictures/' . $filename;
            
            // Process and save image
            $processResult = $this->processAndSaveImage($file['tmp_name'], $uploadPath, $imageInfo);
            if (!$processResult['success']) {
                return $processResult;
            }
            
            // Generate thumbnails
            $thumbnailPaths = $this->generateThumbnails($uploadPath, $filename, $userId);
            
            // Clean up old profile pictures
            $this->cleanupOldProfilePictures($userId);
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => '/uploads/profile_pictures/' . $filename,
                'thumbnails' => $thumbnailPaths,
                'size' => filesize($uploadPath),
                'dimensions' => ['width' => $width, 'height' => $height]
            ];
            
        } catch (Throwable $e) {
            error_log('Image processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Image processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate image upload
     */
    private function validateImageUpload(array $file): array {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return [
                'success' => false,
                'error' => 'No file uploaded or invalid upload'
            ];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            
            $errorMessage = $errorMessages[$file['error']] ?? 'Unknown upload error';
            
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => 'File too large. Maximum size: ' . ($this->maxFileSize / 1024 / 1024) . 'MB'
            ];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'success' => false,
                'error' => 'Invalid file type. Allowed: ' . implode(', ', $this->allowedExtensions)
            ];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            return [
                'success' => false,
                'error' => 'Invalid MIME type. File appears to be: ' . $mimeType
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $originalName, int $userId): string {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        
        return "profile_{$userId}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Process and save image with optimization
     */
    private function processAndSaveImage(string $tempPath, string $savePath, array $imageInfo): array {
        try {
            $mimeType = $imageInfo['mime'];
            
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($tempPath);
                    $quality = 85; // Good quality with compression
                    imagejpeg($image, $savePath, $quality);
                    break;
                    
                case 'image/png':
                    $image = imagecreatefrompng($tempPath);
                    // PNG compression (0-9, where 0 is no compression)
                    $compression = 6;
                    imagepng($image, $savePath, $compression);
                    break;
                    
                case 'image/gif':
                    $image = imagecreatefromgif($tempPath);
                    imagegif($image, $savePath);
                    break;
                    
                case 'image/webp':
                    $image = imagecreatefromwebp($tempPath);
                    imagewebp($image, $savePath, 85);
                    break;
                    
                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported image format'
                    ];
            }
            
            imagedestroy($image);
            
            return ['success' => true];
            
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => 'Image processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate thumbnails
     */
    private function generateThumbnails(string $originalPath, string $filename, int $userId): array {
        $thumbnails = [];
        $sizes = [
            'small' => [150, 150],
            'medium' => [300, 300]
        ];
        
        foreach ($sizes as $sizeName => [$width, $height]) {
            $thumbnailDir = $this->uploadDir . "profile_pictures/thumbnails/{$sizeName}/";
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            $thumbnailPath = $thumbnailDir . $filename;
            
            if ($this->createThumbnail($originalPath, $thumbnailPath, $width, $height)) {
                $thumbnails[$sizeName] = "/uploads/profile_pictures/thumbnails/{$sizeName}/{$filename}";
            }
        }
        
        return $thumbnails;
    }
    
    /**
     * Create thumbnail
     */
    private function createThumbnail(string $originalPath, string $thumbnailPath, int $width, int $height): bool {
        try {
            // Get original image info
            $imageInfo = getimagesize($originalPath);
            if (!$imageInfo) return false;
            
            list($origWidth, $origHeight) = $imageInfo;
            $mimeType = $imageInfo['mime'];
            
            // Create image resource
            switch ($mimeType) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($originalPath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($originalPath);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($originalPath);
                    break;
                case 'image/webp':
                    $source = imagecreatefromwebp($originalPath);
                    break;
                default:
                    return false;
            }
            
            // Calculate aspect ratio
            $aspectRatio = $origWidth / $origHeight;
            
            // Calculate dimensions maintaining aspect ratio
            if ($width / $height > $aspectRatio) {
                $newWidth = (int)($height * $aspectRatio);
                $newHeight = $height;
            } else {
                $newWidth = $width;
                $newHeight = (int)($width / $aspectRatio);
            }
            
            // Create thumbnail
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency for PNG and GIF
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }
            
            // Resize and save
            imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            
            // Save thumbnail
            switch ($mimeType) {
                case 'image/jpeg':
                    imagejpeg($thumbnail, $thumbnailPath, 85);
                    break;
                case 'image/png':
                    imagepng($thumbnail, $thumbnailPath, 6);
                    break;
                case 'image/gif':
                    imagegif($thumbnail, $thumbnailPath);
                    break;
                case 'image/webp':
                    imagewebp($thumbnail, $thumbnailPath, 85);
                    break;
            }
            
            imagedestroy($source);
            imagedestroy($thumbnail);
            
            return true;
            
        } catch (Throwable $e) {
            error_log('Thumbnail creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up old profile pictures
     */
    private function cleanupOldProfilePictures(int $userId): void {
        $profileDir = $this->uploadDir . 'profile_pictures/';
        $pattern = "profile_{$userId}_*";
        
        $files = glob($profileDir . $pattern);
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Clean up thumbnails
        $thumbnailDirs = [
            $profileDir . 'thumbnails/small/',
            $profileDir . 'thumbnails/medium/'
        ];
        
        foreach ($thumbnailDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . $pattern);
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }
    
    /**
     * Ensure upload directories exist with proper permissions
     */
    private function ensureUploadDirectories(): void {
        $directories = [
            $this->uploadDir,
            $this->uploadDir . 'profile_pictures/',
            $this->uploadDir . 'profile_pictures/thumbnails/small/',
            $this->uploadDir . 'profile_pictures/thumbnails/medium/'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Check and fix permissions
            if (file_exists($dir)) {
                $currentPerms = fileperms($dir) & 0777;
                if ($currentPerms !== 0755) {
                    chmod($dir, 0755);
                }
            }
        }
    }
    
    /**
     * Check upload directory permissions
     */
    public function checkPermissions(): array {
        $issues = [];
        
        $directories = [
            $this->uploadDir => 'Main uploads directory',
            $this->uploadDir . 'profile_pictures/' => 'Profile pictures directory',
            $this->uploadDir . 'profile_pictures/thumbnails/' => 'Thumbnails directory'
        ];
        
        foreach ($directories as $dir => $description) {
            if (!file_exists($dir)) {
                $issues[] = "{$description} does not exist";
            } elseif (!is_dir($dir)) {
                $issues[] = "{$description} is not a directory";
            } elseif (!is_writable($dir)) {
                $issues[] = "{$description} is not writable";
            } else {
                $perms = fileperms($dir) & 0777;
                if ($perms !== 0755) {
                    $issues[] = "{$description} has incorrect permissions (current: " . decoct($perms) . ", should be 0755)";
                }
            }
        }
        
        return [
            'success' => empty($issues),
            'issues' => $issues,
            'base_upload_dir' => $this->uploadDir
        ];
    }
}
