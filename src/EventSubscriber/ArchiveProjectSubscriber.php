<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\ArchiveFolder;
use App\Entity\ArchiveProject;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postFlush)]
final class ArchiveProjectSubscriber
{
    /** @var list<ArchiveFolder> */
    private array $pendingFolders = [];

    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $project = $args->getObject();
        if (!$project instanceof ArchiveProject || [] !== $project->getFolders()->toArray()) {
            return;
        }

        foreach (ArchiveProject::ROOT_FOLDERS as $index => $name) {
            $folder = new ArchiveFolder();
            $folder->setName($name)
                ->setSlug($this->slugger->slug($name)->lower()->toString())
                ->setIsSystemFolder(true)
                ->setDisplayOrder($index + 1)
                ->setArchiveProject($project);
            $project->addFolder($folder);
            $this->pendingFolders[] = $folder;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ([] === $this->pendingFolders) {
            return;
        }

        $folders = $this->pendingFolders;
        $this->pendingFolders = [];

        foreach ($folders as $folder) {
            $args->getObjectManager()->persist($folder);
        }
        $args->getObjectManager()->flush();
    }
}
