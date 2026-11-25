<?php

declare(strict_types=1);

namespace App\Entity;

use App\Trait\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(indexes: [new ORM\Index(name: 'idx_permission_user', columns: ['user_id'])])]
class ArchivePermission
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ArchiveFolder $archiveFolder = null;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ArchiveProject $archiveProject = null;

    #[ORM\Column(type: Types::JSON)]
    private array $permissions = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getArchiveFolder(): ?ArchiveFolder
    {
        return $this->archiveFolder;
    }

    public function setArchiveFolder(?ArchiveFolder $archiveFolder): static
    {
        $this->archiveFolder = $archiveFolder;

        return $this;
    }

    public function getArchiveProject(): ?ArchiveProject
    {
        return $this->archiveProject;
    }

    public function setArchiveProject(?ArchiveProject $archiveProject): static
    {
        $this->archiveProject = $archiveProject;

        return $this;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function allows(string $action): bool
    {
        return in_array($action, $this->permissions, true);
    }
}
