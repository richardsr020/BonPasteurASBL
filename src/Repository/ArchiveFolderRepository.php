<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArchiveFolder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ArchiveFolder> */
final class ArchiveFolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArchiveFolder::class);
    }
}
