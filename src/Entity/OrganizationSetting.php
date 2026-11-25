<?php

declare(strict_types=1);

namespace App\Entity;

use App\Trait\BlameableTrait;
use App\Trait\TimestampableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class OrganizationSetting
{
    use TimestampableTrait;
    use BlameableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $organizationName = 'ASBL Bon Pasteur RDC';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $primaryColor = '#1B7A4B';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mission = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $vision = null;

    #[ORM\Column(name: 'organization_values', type: Types::TEXT, nullable: true)]
    private ?string $values = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $history = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $objectives = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $strategicPlan = null;

    #[ORM\Column(type: Types::JSON)]
    private array $keyFigures = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $phoneSecondary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gpsCoordinates = null;

    #[ORM\Column(type: Types::JSON)]
    private array $socialLinks = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganizationName(): string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(string $organizationName): static
    {
        $this->organizationName = $organizationName;

        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;

        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(?string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getMission(): ?string
    {
        return $this->mission;
    }

    public function setMission(?string $mission): static
    {
        $this->mission = $mission;

        return $this;
    }

    public function getVision(): ?string
    {
        return $this->vision;
    }

    public function setVision(?string $vision): static
    {
        $this->vision = $vision;

        return $this;
    }

    public function getValues(): ?string
    {
        return $this->values;
    }

    public function setValues(?string $values): static
    {
        $this->values = $values;

        return $this;
    }

    public function getHistory(): ?string
    {
        return $this->history;
    }

    public function setHistory(?string $history): static
    {
        $this->history = $history;

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

    public function getStrategicPlan(): ?string
    {
        return $this->strategicPlan;
    }

    public function setStrategicPlan(?string $strategicPlan): static
    {
        $this->strategicPlan = $strategicPlan;

        return $this;
    }

    public function getKeyFigures(): array
    {
        return $this->keyFigures;
    }

    public function setKeyFigures(array $keyFigures): static
    {
        $this->keyFigures = $keyFigures;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhoneSecondary(): ?string
    {
        return $this->phoneSecondary;
    }

    public function setPhoneSecondary(?string $phoneSecondary): static
    {
        $this->phoneSecondary = $phoneSecondary;

        return $this;
    }

    public function getGpsCoordinates(): ?string
    {
        return $this->gpsCoordinates;
    }

    public function setGpsCoordinates(?string $gpsCoordinates): static
    {
        $this->gpsCoordinates = $gpsCoordinates;

        return $this;
    }

    public function getSocialLinks(): array
    {
        return $this->socialLinks;
    }

    public function setSocialLinks(array $socialLinks): static
    {
        $this->socialLinks = $socialLinks;

        return $this;
    }
}
