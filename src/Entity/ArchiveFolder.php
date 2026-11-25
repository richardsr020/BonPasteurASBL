<?php

declare(strict_types=1);

namespace App\Entity;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(indexes: [
    new ORM\Index(name: 'idx_folder_project', columns: ['archive_project_id']),
    new ORM\Index(name: 'idx_folder_parent', columns: ['parent_id']),
])]
class ArchiveFolder
{
    use TimestampableTrait;
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'folders')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArchiveProject $archiveProject = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[ORM\Column(length: 180)]
    private ?string $name = null;

    #[ORM\Column(length: 180)]
    private ?string $slug = null;

    #[ORM\Column]
    private bool $isSystemFolder = false;

    #[ORM\Column]
    private bool $restrictedToAdmin = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $displayOrder = 0;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $children;

    /** @var Collection<int, ArchiveDocument> */
    #[ORM\OneToMany(mappedBy: 'folder', targetEntity: ArchiveDocument::class)]
    private Collection $documents;

    /** @var Collection<int, ArchivePermission> */
    #[ORM\OneToMany(mappedBy: 'archiveFolder', targetEntity: ArchivePermission::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $permissions;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function isSystemFolder(): bool
    {
        return $this->isSystemFolder;
    }

    public function setIsSystemFolder(bool $isSystemFolder): static
    {
        $this->isSystemFolder = $isSystemFolder;

        return $this;
    }

    public function isRestrictedToAdmin(): bool
    {
        return $this->restrictedToAdmin;
    }

    public function setRestrictedToAdmin(bool $restrictedToAdmin): static
    {
        $this->restrictedToAdmin = $restrictedToAdmin;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /** @return Collection<int, self> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, ArchiveDocument> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(ArchiveDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setFolder($this);
        }

        return $this;
    }

    public function removeDocument(ArchiveDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getFolder() === $this) {
                $document->setFolder(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, ArchivePermission> */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Dossier';
    }
}
