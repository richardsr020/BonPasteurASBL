<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class BlameableSubscriber
{
    public function __construct(private readonly Security $security)
    {
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user instanceof User || !method_exists($entity, 'setCreatedBy')) {
            return;
        }

        $entity->setCreatedBy($user);
        $entity->setUpdatedBy($user);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $user = $this->security->getUser();

        if (!$user instanceof User || !method_exists($entity, 'setUpdatedBy')) {
            return;
        }

        $entity->setUpdatedBy($user);
        $manager = $args->getObjectManager();
        $classMetadata = $manager->getClassMetadata($entity::class);
        $manager->getUnitOfWork()->recomputeSingleEntityChangeSet($classMetadata, $entity);
    }
}
