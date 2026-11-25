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
#[ORM\Table(indexes: [new ORM\Index(name: 'idx_archive_project_year', columns: ['archive_year_id'])])]
class ArchiveProject
{
    use TimestampableTrait;
    use BlameableTrait;

    public const ROOT_FOLDERS = [
        '01 - Identification du projet',
        '02 - Planification',
        '03 - Activités réalisées',
        '04 - Résultats et impact',
        '05 - Témoignages et médias',
        '06 - Partenaires et donateurs',
        '07 - Rapport complet et clôture',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArchiveYear $archiveYear = null;

    #[ORM\Column(length: 180)]
    private ?string $title = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToOne(inversedBy: 'archiveProject', targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Project $linkedPublicProject = null;

    /** @var Collection<int, ArchiveFolder> */
    #[ORM\OneToMany(mappedBy: 'archiveProject', targetEntity: ArchiveFolder::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $folders;

    /** @var Collection<int, ArchivePermission> */
    #[ORM\OneToMany(mappedBy: 'archiveProject', targetEntity: ArchivePermission::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $permissions;

    public function __construct()
    {
        $this->folders = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArchiveYear(): ?ArchiveYear
    {
        return $this->archiveYear;
    }

    public function setArchiveYear(?ArchiveYear $archiveYear): static
    {
        $this->archiveYear = $archiveYear;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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

    public function getLinkedPublicProject(): ?Project
    {
        return $this->linkedPublicProject;
    }

    public function setLinkedPublicProject(?Project $linkedPublicProject): static
    {
        $this->linkedPublicProject = $linkedPublicProject;

        return $this;
    }

    /** @return Collection<int, ArchiveFolder> */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    public function addFolder(ArchiveFolder $folder): static
    {
        if (!$this->folders->contains($folder)) {
            $this->folders->add($folder);
            $folder->setArchiveProject($this);
        }

        return $this;
    }

    public function removeFolder(ArchiveFolder $folder): static
    {
        if ($this->folders->removeElement($folder)) {
            if ($folder->getArchiveProject() === $this) {
                $folder->setArchiveProject(null);
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
        return $this->title ?? 'Projet d\'archive';
    }
}
