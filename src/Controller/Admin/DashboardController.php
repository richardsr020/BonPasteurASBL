<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ArchiveDocumentRepository;
use App\Repository\ArchiveFolderRepository;
use App\Repository\ArchiveProjectRepository;
use App\Repository\ArticleRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\PageViewStatRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        ProjectRepository $projectRepository,
        ArticleRepository $articleRepository,
        ContactMessageRepository $contactMessageRepository,
        ArchiveProjectRepository $archiveProjectRepository,
        ArchiveFolderRepository $archiveFolderRepository,
        ArchiveDocumentRepository $archiveDocumentRepository,
        PageViewStatRepository $pageViewStatRepository,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'projects' => $projectRepository->count([]),
                'articles' => $articleRepository->count([]),
                'unreadMessages' => $contactMessageRepository->count(['isRead' => false]),
                'archiveProjects' => $archiveProjectRepository->count([]),
                'folders' => $archiveFolderRepository->count([]),
                'documents' => $archiveDocumentRepository->count(['deletedAt' => null]),
                'pageViewsToday' => (int) $pageViewStatRepository->createQueryBuilder('s')
                    ->select('COALESCE(SUM(s.hitsCount), 0)')
                    ->where('s.visitedDate = :today')
                    ->setParameter('today', new \DateTimeImmutable('today'))
                    ->getQuery()
                    ->getSingleScalarResult(),
            ],
        ]);
    }
}
