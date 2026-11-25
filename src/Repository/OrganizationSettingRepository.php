<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OrganizationSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<OrganizationSetting> */
final class OrganizationSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationSetting::class);
    }
}
