<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ArchiveDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ArchiveDocument> */
final class ArchiveDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArchiveDocument::class);
    }

    /** @param array<string, mixed> $filters */
    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $qb = $this->searchQuery($filters)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /** @param array<string, mixed> $filters */
    public function countSearch(array $filters = []): int
    {
        return (int) $this->searchQuery($filters)
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @param array<string, mixed> $filters */
    private function searchQuery(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->addSelect('f', 'p', 'y')
            ->join('d.folder', 'f')
            ->join('f.archiveProject', 'p')
            ->join('p.archiveYear', 'y');

        if (empty($filters['includeTrashed'])) {
            $qb->andWhere('d.deletedAt IS NULL');
        } elseif (($filters['scope'] ?? 'trashed') === 'trashed') {
            $qb->andWhere('d.deletedAt IS NOT NULL');
        }

        if (!empty($filters['keywords'])) {
            $qb->andWhere('(d.title LIKE :keywords OR d.originalFilename LIKE :keywords OR f.name LIKE :keywords OR p.title LIKE :keywords)')
                ->setParameter('keywords', '%'.$filters['keywords'].'%');
        }

        if (!empty($filters['year'])) {
            $qb->andWhere('y.year = :year')->setParameter('year', (int) $filters['year']);
        }

        if (!empty($filters['project'])) {
            $qb->andWhere('p.id = :project')->setParameter('project', (int) $filters['project']);
        }

        if (!empty($filters['folder'])) {
            $qb->andWhere('f.id = :folder')->setParameter('folder', (int) $filters['folder']);
        }

        if (!empty($filters['type'])) {
            $mime = match ($filters['type']) {
                'pdf' => 'application/pdf',
                'image' => 'image/%',
                'video' => 'video/%',
                'office' => '%office%',
                default => null,
            };

            if (null !== $mime) {
                $qb->andWhere('d.mimeType LIKE :mime')->setParameter('mime', $mime);
            }
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('d.createdAt >= :dateFrom')->setParameter('dateFrom', new \DateTimeImmutable($filters['dateFrom'].' 00:00:00'));
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('d.createdAt <= :dateTo')->setParameter('dateTo', new \DateTimeImmutable($filters['dateTo'].' 23:59:59'));
        }

        return $qb;
    }
}
