<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ProjectStatus;
use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Project
{
    use TimestampableTrait;
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $title = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $context = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectives = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $geographicZone = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::STRING, enumType: ProjectStatus::class)]
    private ProjectStatus $status = ProjectStatus::ONGOING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $targetGroups = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mainActivities = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultsObtained = null;

    #[ORM\Column]
    private bool $showDonorPublicly = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\ManyToMany(targetEntity: Partner::class, inversedBy: 'projects')]
    #[ORM\JoinTable(name: 'project_partner')]
    private Collection $partners;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectMedia::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $medias;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Testimonial::class)]
    private Collection $testimonials;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: PublicDocument::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $publicDocuments;

    #[ORM\OneToOne(mappedBy: 'linkedPublicProject', targetEntity: ArchiveProject::class)]
    private ?ArchiveProject $archiveProject = null;

    public function __construct()
    {
        $this->partners = new ArrayCollection();
        $this->medias = new ArrayCollection();
        $this->testimonials = new ArrayCollection();
        $this->publicDocuments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getObjectives(): ?string
    {
        return $this->objectives;
    }

    public function setObjectives(?string $objectives): static
    {
        $this->objectives = $objectives;

        return $this;
    }

    public function getGeographicZone(): ?string
    {
        return $this->geographicZone;
    }

    public function setGeographicZone(?string $geographicZone): static
    {
        $this->geographicZone = $geographicZone;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTargetGroups(): ?string
    {
        return $this->targetGroups;
    }

    public function setTargetGroups(?string $targetGroups): static
    {
        $this->targetGroups = $targetGroups;

        return $this;
    }

    public function getMainActivities(): ?string
    {
        return $this->mainActivities;
    }

    public function setMainActivities(?string $mainActivities): static
    {
        $this->mainActivities = $mainActivities;

        return $this;
    }

    public function getResultsObtained(): ?string
    {
        return $this->resultsObtained;
    }

    public function setResultsObtained(?string $resultsObtained): static
    {
        $this->resultsObtained = $resultsObtained;

        return $this;
    }

    public function isShowDonorPublicly(): bool
    {
        return $this->showDonorPublicly;
    }

    public function setShowDonorPublicly(bool $showDonorPublicly): static
    {
        $this->showDonorPublicly = $showDonorPublicly;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    /** @return Collection<int, Partner> */
    public function getPartners(): Collection
    {
        return $this->partners;
    }

    public function addPartner(Partner $partner): static
    {
        if (!$this->partners->contains($partner)) {
            $this->partners->add($partner);
            $partner->getProjects()->add($this);
        }

        return $this;
    }

    public function removePartner(Partner $partner): static
    {
        if ($this->partners->removeElement($partner)) {
            $partner->getProjects()->removeElement($this);
        }

        return $this;
    }

    /** @return Collection<int, ProjectMedia> */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(ProjectMedia $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setProject($this);
        }

        return $this;
    }

    public function removeMedia(ProjectMedia $media): static
    {
        if ($this->medias->removeElement($media)) {
            if ($media->getProject() === $this) {
                $media->setProject(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Testimonial> */
    public function getTestimonials(): Collection
    {
        return $this->testimonials;
    }

    public function addTestimonial(Testimonial $testimonial): static
    {
        if (!$this->testimonials->contains($testimonial)) {
            $this->testimonials->add($testimonial);
            $testimonial->setProject($this);
        }

        return $this;
    }

    public function removeTestimonial(Testimonial $testimonial): static
    {
        if ($this->testimonials->removeElement($testimonial)) {
            if ($testimonial->getProject() === $this) {
                $testimonial->setProject(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, PublicDocument> */
    public function getPublicDocuments(): Collection
    {
        return $this->publicDocuments;
    }

    public function addPublicDocument(PublicDocument $publicDocument): static
    {
        if (!$this->publicDocuments->contains($publicDocument)) {
            $this->publicDocuments->add($publicDocument);
            $publicDocument->setProject($this);
        }

        return $this;
    }

    public function removePublicDocument(PublicDocument $publicDocument): static
    {
        if ($this->publicDocuments->removeElement($publicDocument)) {
            if ($publicDocument->getProject() === $this) {
                $publicDocument->setProject(null);
            }
        }

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

    public function __toString(): string
    {
        return $this->title ?? 'Projet';
    }
}
