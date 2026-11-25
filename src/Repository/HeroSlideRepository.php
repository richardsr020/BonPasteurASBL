<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\HeroSlide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<HeroSlide> */
final class HeroSlideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroSlide::class);
    }
}
