<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArchiveAction;
use App\Trait\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(indexes: [
    new ORM\Index(name: 'idx_audit_entity', columns: ['entity_type', 'entity_id']),
    new ORM\Index(name: 'idx_audit_created', columns: ['created_at']),
])]
class ArchiveAuditLog
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, enumType: ArchiveAction::class)]
    private ArchiveAction $action;

    #[ORM\Column(length: 180)]
    private string $entityType;

    #[ORM\Column]
    private int $entityId;

    #[ORM\Column(type: Types::JSON)]
    private array $details = [];

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

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

    public function getAction(): ArchiveAction
    {
        return $this->action;
    }

    public function setAction(ArchiveAction $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }
}
