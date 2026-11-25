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
class ArchiveYear
{
    use TimestampableTrait;
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    private int $year;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isLocked = false;

    /** @var Collection<int, ArchiveProject> */
    #[ORM\OneToMany(mappedBy: 'archiveYear', targetEntity: ArchiveProject::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $projects;

    public function __construct(int $year = 0)
    {
        $this->year = $year ?: (int) date('Y');
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

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

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): static
    {
        $this->isLocked = $isLocked;

        return $this;
    }

    /** @return Collection<int, ArchiveProject> */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(ArchiveProject $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setArchiveYear($this);
        }

        return $this;
    }

    public function removeProject(ArchiveProject $project): static
    {
        if ($this->projects->removeElement($project)) {
            if ($project->getArchiveYear() === $this) {
                $project->setArchiveYear(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->year;
    }
}
