<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportedContent extends Model
{
    use HasFactory;

    public const CATEGORY_VIDEO = 'video';
    public const CATEGORY_AUDIO = 'audio';
    public const CATEGORY_DOCUMENT = 'document';
    public const CATEGORY_IMAGE = 'image';
    public const CATEGORY_ARCHIVE = 'archive';
    public const CATEGORY_OTHER = 'other';

    public const SCAN_PENDING = 'pending';
    public const SCAN_CLEAN = 'clean';
    public const SCAN_INFECTED = 'infected';
    public const SCAN_SKIPPED = 'skipped';
    public const SCAN_ERROR = 'error';

    protected $fillable = [
        'original_name',
        'stored_name',
        'relative_path',
        'category',
        'extension',
        'mime_type',
        'size_bytes',
        'hash_sha256',
        'source_drive',
        'scan_status',
        'scan_message',
        'imported_by',
        'imported_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'imported_at' => 'datetime',
    ];

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Public URL for serving the file (Nginx serves directly from /storage symlink).
     */
    public function getPublicUrlAttribute(): string
    {
        return asset('storage/library/' . $this->relative_path);
    }

    /**
     * Human-friendly file size.
     */
    public function getSizeHumanAttribute(): string
    {
        $bytes = (float) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return sprintf('%.1f %s', $bytes, $units[$i]);
    }

    public static function categoryFromExtension(string $ext): string
    {
        $ext = strtolower(ltrim($ext, '.'));
        $map = [
            // video
            'mp4' => self::CATEGORY_VIDEO, 'mkv' => self::CATEGORY_VIDEO, 'webm' => self::CATEGORY_VIDEO,
            'mov' => self::CATEGORY_VIDEO, 'avi' => self::CATEGORY_VIDEO, 'wmv' => self::CATEGORY_VIDEO,
            'flv' => self::CATEGORY_VIDEO, 'm4v' => self::CATEGORY_VIDEO, '3gp' => self::CATEGORY_VIDEO,
            // audio
            'mp3' => self::CATEGORY_AUDIO, 'wav' => self::CATEGORY_AUDIO, 'ogg' => self::CATEGORY_AUDIO,
            'm4a' => self::CATEGORY_AUDIO, 'flac' => self::CATEGORY_AUDIO, 'aac' => self::CATEGORY_AUDIO,
            'wma' => self::CATEGORY_AUDIO, 'opus' => self::CATEGORY_AUDIO,
            // documents
            'pdf' => self::CATEGORY_DOCUMENT, 'doc' => self::CATEGORY_DOCUMENT, 'docx' => self::CATEGORY_DOCUMENT,
            'xls' => self::CATEGORY_DOCUMENT, 'xlsx' => self::CATEGORY_DOCUMENT, 'ppt' => self::CATEGORY_DOCUMENT,
            'pptx' => self::CATEGORY_DOCUMENT, 'txt' => self::CATEGORY_DOCUMENT, 'rtf' => self::CATEGORY_DOCUMENT,
            'odt' => self::CATEGORY_DOCUMENT, 'ods' => self::CATEGORY_DOCUMENT, 'odp' => self::CATEGORY_DOCUMENT,
            'csv' => self::CATEGORY_DOCUMENT, 'md' => self::CATEGORY_DOCUMENT, 'epub' => self::CATEGORY_DOCUMENT,
            // images
            'jpg' => self::CATEGORY_IMAGE, 'jpeg' => self::CATEGORY_IMAGE, 'png' => self::CATEGORY_IMAGE,
            'gif' => self::CATEGORY_IMAGE, 'webp' => self::CATEGORY_IMAGE, 'bmp' => self::CATEGORY_IMAGE,
            'svg' => self::CATEGORY_IMAGE, 'tiff' => self::CATEGORY_IMAGE,
            // archives
            'zip' => self::CATEGORY_ARCHIVE, 'rar' => self::CATEGORY_ARCHIVE, '7z' => self::CATEGORY_ARCHIVE,
            'tar' => self::CATEGORY_ARCHIVE, 'gz' => self::CATEGORY_ARCHIVE,
        ];
        return $map[$ext] ?? self::CATEGORY_OTHER;
    }
}
