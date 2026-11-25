<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\PageViewStat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class PageViewSubscriber
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route');
        if (!str_starts_with($route, 'app_') || 'app_contact' === $route) {
            return;
        }

        try {
            $date = new \DateTimeImmutable('today');
            $stat = $this->entityManager->getRepository(PageViewStat::class)->findOneBy([
                'route' => $route,
                'visitedDate' => $date,
            ]);
            if (null === $stat) {
                $stat = new PageViewStat();
                $stat->setRoute($route)
                    ->setUrlPath($request->getPathInfo())
                    ->setVisitedDate($date)
                    ->setHitsCount(1);
                $this->entityManager->persist($stat);
            } else {
                $stat->incrementHits();
            }
            $this->entityManager->flush();
        } catch (\Throwable) {
        }
    }
}
