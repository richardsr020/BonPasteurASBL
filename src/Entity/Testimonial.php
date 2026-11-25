<?php

declare(strict_types=1);

namespace App\Entity;

use App\Trait\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Testimonial
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $beneficiaryName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $roleOrContext = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $storyText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\ManyToOne(inversedBy: 'testimonials')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Project $project = null;

    #[ORM\Column]
    private bool $isFeaturedOnHome = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBeneficiaryName(): ?string
    {
        return $this->beneficiaryName;
    }

    public function setBeneficiaryName(string $beneficiaryName): static
    {
        $this->beneficiaryName = $beneficiaryName;

        return $this;
    }

    public function getRoleOrContext(): ?string
    {
        return $this->roleOrContext;
    }

    public function setRoleOrContext(?string $roleOrContext): static
    {
        $this->roleOrContext = $roleOrContext;

        return $this;
    }

    public function getStoryText(): ?string
    {
        return $this->storyText;
    }

    public function setStoryText(?string $storyText): static
    {
        $this->storyText = $storyText;

        return $this;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function setPhotoPath(?string $photoPath): static
    {
        $this->photoPath = $photoPath;

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function isFeaturedOnHome(): bool
    {
        return $this->isFeaturedOnHome;
    }

    public function getIsFeaturedOnHome(): bool
    {
        return $this->isFeaturedOnHome;
    }

    public function setFeaturedOnHome(bool $isFeaturedOnHome): static
    {
        $this->isFeaturedOnHome = $isFeaturedOnHome;

        return $this;
    }
}
