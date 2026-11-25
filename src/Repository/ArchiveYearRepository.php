<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArchiveYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ArchiveYear> */
final class ArchiveYearRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArchiveYear::class);
    }
}
