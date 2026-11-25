<?php

declare(strict_types=1);

namespace App\Entity;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(indexes: [
    new ORM\Index(name: 'idx_document_folder', columns: ['folder_id']),
    new ORM\Index(name: 'idx_document_checksum', columns: ['checksum_sha256']),
    new ORM\Index(name: 'idx_document_title', columns: ['title', 'original_filename']),
])]
class ArchiveDocument
{
    use TimestampableTrait;
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArchiveFolder $folder = null;

    #[ORM\Column(length: 180)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $originalFilename = null;

    #[ORM\Column(length: 255)]
    private ?string $storedFilename = null;

    #[ORM\Column(length: 512)]
    private ?string $storagePath = null;

    #[ORM\Column(length: 150)]
    private ?string $mimeType = null;

    #[ORM\Column(length: 20)]
    private ?string $fileExtension = null;

    #[ORM\Column(type: Types::BIGINT)]
    private int $fileSize = 0;

    #[ORM\Column(length: 64)]
    private ?string $checksumSha256 = null;

    #[ORM\Column]
    private int $version = 1;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $uploadedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $deletedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFolder(): ?ArchiveFolder
    {
        return $this->folder;
    }

    public function setFolder(?ArchiveFolder $folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getStoredFilename(): ?string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(string $storedFilename): static
    {
        $this->storedFilename = $storedFilename;

        return $this;
    }

    public function getStoragePath(): ?string
    {
        return $this->storagePath;
    }

    public function setStoragePath(string $storagePath): static
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileExtension(): ?string
    {
        return $this->fileExtension;
    }

    public function setFileExtension(string $fileExtension): static
    {
        $this->fileExtension = $fileExtension;

        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getChecksumSha256(): ?string
    {
        return $this->checksumSha256;
    }

    public function setChecksumSha256(string $checksumSha256): static
    {
        $this->checksumSha256 = $checksumSha256;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedBy(): ?User
    {
        return $this->deletedBy;
    }

    public function setDeletedBy(?User $deletedBy): static
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    public function isTrashed(): bool
    {
        return null !== $this->deletedAt;
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mimeType, 'image/');
    }

    public function isPdf(): bool
    {
        return 'application/pdf' === $this->mimeType;
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mimeType, 'video/');
    }

    public function isOffice(): bool
    {
        return (bool) preg_match('/office|msword|spreadsheet|presentation/', (string) $this->mimeType);
    }

    public function getHumanFileSize(): string
    {
        $size = (float) $this->fileSize;
        foreach (['o', 'Ko', 'Mo', 'Go'] as $unit) {
            if ($size < 1024 || 'Go' === $unit) {
                return sprintf('%.1f %s', $size, $unit);
            }
            $size /= 1024;
        }

        return '';
    }

    public function __toString(): string
    {
        return $this->title ?? $this->originalFilename ?? 'Document';
    }
}
