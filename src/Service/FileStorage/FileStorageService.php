<?php

declare(strict_types=1);

namespace App\Service\FileStorage;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Uid\Uuid;

final class FileStorageService
{
    public const MAX_FILE_SIZE = 262144000;

    /** @var array<string, list<string>> */
    public const ALLOWED_TYPES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
    ];

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
        #[Autowire(env: 'ARCHIVE_STORAGE_DIR')] private readonly string $archiveDir = 'var/storage/archives',
        #[Autowire(env: 'PUBLIC_UPLOAD_DIR')] private readonly string $publicDir = 'public/uploads',
    ) {
    }

    public function validate(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Le fichier téléversé est invalide.');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('La taille maximale autorisée est de 250 Mo.');
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
        if (!isset(self::ALLOWED_TYPES[$extension])) {
            throw new \InvalidArgumentException(sprintf('Extension "%s" non autorisée.', $extension));
        }

        $mimeType = MimeTypes::getDefault()->guessMimeType($file->getPathname()) ?: '';
        if (!in_array($mimeType, self::ALLOWED_TYPES[$extension], true)) {
            throw new \InvalidArgumentException('Le type réel du fichier ne correspond pas à son extension.');
        }
    }

    /** @return array{originalFilename: string, storedFilename: string, storagePath: string, mimeType: string, fileExtension: string, fileSize: int, checksumSha256: string, previewPath: ?string} */
    public function storeArchiveFile(UploadedFile $file, int $projectId, int $folderId): array
    {
        $this->validate($file);

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
        $mimeType = MimeTypes::getDefault()->guessMimeType($file->getPathname()) ?: $file->getMimeType() ?: 'application/octet-stream';
        $storedFilename = Uuid::v4()->toRfc4122().'.'.$extension;
        $relativeDir = sprintf('%d/%d', $projectId, $folderId);
        $this->ensureDirectory($this->absolutePrivatePath($relativeDir));

        $target = $this->absolutePrivatePath($relativeDir.'/'.$storedFilename);
        $file->move(\dirname($target), $storedFilename);

        $previewPath = null;
        if (str_starts_with($mimeType, 'image/')) {
            $previewFilename = pathinfo($storedFilename, PATHINFO_FILENAME).'-thumb.webp';
            $previewPath = $relativeDir.'/'.$previewFilename;
            $this->createWebpVariant($target, $this->absolutePrivatePath($previewPath), 600);
        }

        return [
            'originalFilename' => $file->getClientOriginalName(),
            'storedFilename' => $storedFilename,
            'storagePath' => $relativeDir.'/'.$storedFilename,
            'mimeType' => $mimeType,
            'fileExtension' => $extension,
            'fileSize' => filesize($target) ?: 0,
            'checksumSha256' => hash_file('sha256', $target) ?: '',
            'previewPath' => $previewPath,
        ];
    }

    /** @return array{storedFilename: string, storagePath: string, fileExtension: string, fileSize: int, checksumSha256: string} */
    public function storeNewVersion(UploadedFile $file, string $currentStoragePath, string $currentFilename, int $documentId, int $version, int $projectId, int $folderId): array
    {
        $this->validate($file);

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
        $relativeDir = \dirname($currentStoragePath);
        $versionDir = $relativeDir.'/versions/'.$documentId;
        $this->ensureDirectory($this->absolutePrivatePath($versionDir));

        $archivedName = sprintf('v%d_%s', $version, basename($currentStoragePath));
        $currentAbsolute = $this->absolutePrivatePath($currentStoragePath);
        if (is_file($currentAbsolute)) {
            copy($currentAbsolute, $this->absolutePrivatePath($versionDir.'/'.$archivedName));
        }

        $storedFilename = Uuid::v4()->toRfc4122().'.'.$extension;
        $target = $this->absolutePrivatePath($relativeDir.'/'.$storedFilename);
        $file->move(\dirname($target), $storedFilename);

        return [
            'storedFilename' => $storedFilename,
            'storagePath' => $relativeDir.'/'.$storedFilename,
            'fileExtension' => $extension,
            'fileSize' => filesize($target) ?: 0,
            'checksumSha256' => hash_file('sha256', $target) ?: '',
        ];
    }

    public function storePublicFile(UploadedFile $file, string $subdir = 'media'): string
    {
        $this->validate($file);

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->guessExtension());
        $relativeDir = sprintf('%s/%s/%s', trim($subdir, '/'), date('Y'), date('m'));
        $this->ensureDirectory($this->absolutePublicPath($relativeDir));

        $mimeType = MimeTypes::getDefault()->guessMimeType($file->getPathname()) ?: '';
        $storedFilename = Uuid::v4()->toRfc4122().'.'.$extension;
        $target = $this->absolutePublicPath($relativeDir.'/'.$storedFilename);
        $file->move(\dirname($target), $storedFilename);

        if (str_starts_with($mimeType, 'image/')) {
            $webpName = pathinfo($storedFilename, PATHINFO_FILENAME).'.webp';
            $webpPath = $relativeDir.'/'.$webpName;
            if ($this->createWebpVariant($target, $this->absolutePublicPath($webpPath), 1600)) {
                unlink($target);

                return $webpPath;
            }

            return $relativeDir.'/'.$storedFilename;
        }

        return $relativeDir.'/'.$storedFilename;
    }

    public function promoteToPublic(string $privateRelativePath, string $mimeType): string
    {
        $source = $this->absolutePrivatePath($privateRelativePath);
        if (!is_file($source)) {
            throw new \RuntimeException('Fichier source introuvable.');
        }

        $relativeDir = 'gallery/'.date('Y').'/'.date('m');
        $this->ensureDirectory($this->absolutePublicPath($relativeDir));

        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $filename = Uuid::v4()->toRfc4122().'.'.$extension;
        $target = $this->absolutePublicPath($relativeDir.'/'.$filename);
        copy($source, $target);

        if (str_starts_with($mimeType, 'image/')) {
            $webpName = pathinfo($filename, PATHINFO_FILENAME).'.webp';
            if ($this->createWebpVariant($target, $this->absolutePublicPath($relativeDir.'/'.$webpName), 1600)) {
                unlink($target);

                return $relativeDir.'/'.$webpName;
            }
        }

        return $relativeDir.'/'.$filename;
    }

    public function absolutePrivatePath(string $relativePath = ''): string
    {
        return rtrim($this->projectDir.'/'.$this->archiveDir, '/').($relativePath ? '/'.ltrim($relativePath, '/') : '');
    }

    public function absolutePublicPath(string $relativePath = ''): string
    {
        return rtrim($this->projectDir.'/'.$this->publicDir, '/').($relativePath ? '/'.ltrim($relativePath, '/') : '');
    }

    public function deletePrivate(string $relativePath): void
    {
        $path = $this->absolutePrivatePath($relativePath);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function purgeDocument(string $relativePath, int $documentId): void
    {
        $this->deletePrivate($relativePath);
        $versionDir = \dirname($relativePath).'/versions/'.$documentId;
        $absoluteVersionDir = $this->absolutePrivatePath($versionDir);
        if (is_dir($absoluteVersionDir)) {
            foreach (glob($absoluteVersionDir.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($absoluteVersionDir);
        }
    }

    public function createWebpVariant(string $source, string $target, int $maxWidth): bool
    {
        $info = @getimagesize($source);
        if (false === $info) {
            return false;
        }

        [$width, $height, $type] = $info;
        $sourceImage = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            default => false,
        };

        if (false === $sourceImage) {
            return false;
        }

        $ratio = min(1, $maxWidth / max(1, $width));
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        if (false === $destination) {
            imagedestroy($sourceImage);

            return false;
        }

        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
        imagefill($destination, 0, 0, $transparent);
        imagecopyresampled($destination, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $result = imagewebp($destination, $target, 80);

        imagedestroy($sourceImage);
        imagedestroy($destination);

        return $result;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Impossible de créer le répertoire "%s".', $path));
        }
    }
}
