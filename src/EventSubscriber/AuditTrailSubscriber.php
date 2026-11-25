<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\ArchiveAuditLog;
use App\Entity\ArchiveDocument;
use App\Entity\User;
use App\Enum\ArchiveAction;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
final class AuditTrailSubscriber
{
    /** @var list<ArchiveAuditLog> */
    private array $pendingLogs = [];

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof ArchiveDocument) {
            $this->pendingLogs[] = $this->createLog(ArchiveAction::UPLOAD, $entity->getId() ?? 0, [
                'title' => $entity->getTitle(),
                'originalFilename' => $entity->getOriginalFilename(),
                'version' => $entity->getVersion(),
            ]);
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ArchiveDocument) {
            return;
        }

        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        if (isset($changeSet['deletedAt'])) {
            return;
        }

        $this->pendingLogs[] = $this->createLog(ArchiveAction::UPDATE_FILE, $entity->getId() ?? 0, [
            'title' => $entity->getTitle(),
            'originalFilename' => $entity->getOriginalFilename(),
            'version' => $entity->getVersion(),
        ]);
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof ArchiveDocument) {
            return;
        }

        $this->pendingLogs[] = $this->createLog(ArchiveAction::PURGE, $entity->getId() ?? 0, [
            'title' => $entity->getTitle(),
            'originalFilename' => $entity->getOriginalFilename(),
        ]);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pendingLogs) {
            return;
        }

        $logs = $this->pendingLogs;
        $this->pendingLogs = [];

        foreach ($logs as $log) {
            $args->getObjectManager()->persist($log);
        }
        $args->getObjectManager()->flush();
    }

    private function createLog(ArchiveAction $action, int $entityId, array $details): ArchiveAuditLog
    {
        $log = new ArchiveAuditLog();
        $log->setAction($action)
            ->setEntityType(ArchiveDocument::class)
            ->setEntityId($entityId)
            ->setDetails($details);

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            $log->setIpAddress($request->getClientIp());
        }

        return $log;
    }
}
