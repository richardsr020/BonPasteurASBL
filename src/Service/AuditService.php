<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ArchiveAuditLog;
use App\Entity\ArchiveDocument;
use App\Entity\ArchiveFolder;
use App\Entity\ArchiveProject;
use App\Entity\ArchiveYear;
use App\Entity\User;
use App\Enum\ArchiveAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class AuditService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function record(ArchiveAction $action, ArchiveDocument|ArchiveFolder|ArchiveProject|ArchiveYear|null $entity, array $details = []): void
    {
        $log = new ArchiveAuditLog();
        $log->setAction($action)
            ->setEntityType(null !== $entity ? $entity::class : 'system')
            ->setEntityId($entity?->getId() ?? 0)
            ->setDetails($details);

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            $log->setIpAddress($request->getClientIp());
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
