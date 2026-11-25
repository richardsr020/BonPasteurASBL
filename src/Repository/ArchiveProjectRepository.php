<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArchiveProject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ArchiveProject> */
final class ArchiveProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArchiveProject::class);
    }
}
