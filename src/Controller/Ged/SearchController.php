<?php

declare(strict_types=1);

namespace App\Controller\Ged;

use App\Repository\ArchiveDocumentRepository;
use App\Repository\ArchiveProjectRepository;
use App\Repository\ArchiveYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/admin/ged/search', name: 'ged_search')]
    public function index(
        Request $request,
        ArchiveDocumentRepository $documentRepository,
        ArchiveYearRepository $yearRepository,
        ArchiveProjectRepository $projectRepository,
    ): Response {
        $filters = $this->filters($request);
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        return $this->render('ged/search.html.twig', [
            'documents' => $documentRepository->search($filters, $limit, ($page - 1) * $limit),
            'total' => $documentRepository->countSearch($filters),
            'totalPages' => max(1, (int) ceil($documentRepository->countSearch($filters) / $limit)),
            'page' => $page,
            'filters' => $filters,
            'years' => $yearRepository->findBy([], ['year' => 'DESC']),
            'projects' => $projectRepository->findBy([], ['title' => 'ASC']),
        ]);
    }

    #[Route('/admin/ged/search.json', name: 'ged_search_json')]
    public function jsonResults(Request $request, ArchiveDocumentRepository $documentRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COMM');
        $filters = $this->filters($request);
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $documents = $documentRepository->search($filters, $limit, ($page - 1) * $limit);

        $results = array_map(static function ($document): array {
            $folder = $document->getFolder();
            $project = $folder?->getArchiveProject();

            return [
                'id' => $document->getId(),
                'title' => $document->getTitle(),
                'originalFilename' => $document->getOriginalFilename(),
                'type' => $document->getFileExtension(),
                'mimeType' => $document->getMimeType(),
                'size' => $document->getHumanFileSize(),
                'createdAt' => $document->getCreatedAt()?->format('d/m/Y H:i'),
                'version' => $document->getVersion(),
                'folder' => $folder?->getName(),
                'project' => $project?->getTitle(),
                'year' => $project?->getArchiveYear()?->getYear(),
                'previewUrl' => '/admin/ged/document/'.$document->getId().'/preview',
                'downloadUrl' => '/admin/ged/document/'.$document->getId().'/download',
            ];
        }, $documents);

        return new JsonResponse([
            'results' => $results,
            'total' => $documentRepository->countSearch($filters),
            'page' => $page,
        ]);
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return [
            'keywords' => trim((string) $request->query->get('q', '')),
            'year' => $request->query->get('year'),
            'project' => $request->query->get('project'),
            'type' => $request->query->get('type'),
            'dateFrom' => $this->dateFilter($request->query->get('dateFrom')),
            'dateTo' => $this->dateFilter($request->query->get('dateTo')),
        ];
    }

    private function dateFilter(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}
