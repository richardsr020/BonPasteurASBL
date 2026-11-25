<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_stat_route_date', columns: ['route', 'visited_date'])])]
class PageViewStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $route = null;

    #[ORM\Column(length: 255)]
    private ?string $urlPath = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $visitedDate = null;

    #[ORM\Column]
    private int $hitsCount = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(string $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getUrlPath(): ?string
    {
        return $this->urlPath;
    }

    public function setUrlPath(string $urlPath): static
    {
        $this->urlPath = $urlPath;

        return $this;
    }

    public function getVisitedDate(): ?\DateTimeImmutable
    {
        return $this->visitedDate;
    }

    public function setVisitedDate(\DateTimeImmutable $visitedDate): static
    {
        $this->visitedDate = $visitedDate;

        return $this;
    }

    public function getHitsCount(): int
    {
        return $this->hitsCount;
    }

    public function setHitsCount(int $hitsCount): static
    {
        $this->hitsCount = $hitsCount;

        return $this;
    }

    public function incrementHits(): static
    {
        ++$this->hitsCount;

        return $this;
    }
}
