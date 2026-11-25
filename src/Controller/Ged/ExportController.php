<?php

declare(strict_types=1);

namespace App\Controller\Ged;

use App\Entity\ArchiveProject;
use App\Entity\ArchiveYear;
use App\Enum\ArchiveAction;
use App\Service\AuditService;
use App\Service\Export\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExportController extends AbstractController
{
    #[Route('/admin/ged/export/project/{id}', name: 'admin_ged_export_project', requirements: ['id' => '\d+'])]
    public function project(ArchiveProject $project, PdfExportService $exportService, AuditService $audit): Response
    {
        $this->denyAccessUnlessGranted('EXPORT', $project);
        $audit->record(ArchiveAction::EXPORT_PDF, $project, ['title' => $project->getTitle()]);

        return $this->pdfResponse(
            $exportService->exportProject($project),
            'bordereau-projet-'.$project->getId().'.pdf',
        );
    }

    #[Route('/admin/ged/export/year/{id}', name: 'admin_ged_export_year', requirements: ['id' => '\d+'])]
    public function year(ArchiveYear $year, PdfExportService $exportService, AuditService $audit): Response
    {
        $audit->record(ArchiveAction::EXPORT_PDF, $year, ['year' => $year->getYear()]);

        return $this->pdfResponse(
            $exportService->exportYear($year),
            'bordereau-annuel-'.$year->getYear().'.pdf',
        );
    }

    private function pdfResponse(string $content, string $filename): Response
    {
        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
