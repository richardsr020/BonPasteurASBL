<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PageViewStat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PageViewStat> */
final class PageViewStatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageViewStat::class);
    }
}
