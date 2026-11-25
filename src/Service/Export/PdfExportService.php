<?php

declare(strict_types=1);

namespace App\Service\Export;

use App\Entity\ArchiveProject;
use App\Entity\ArchiveYear;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class PdfExportService
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function exportProject(ArchiveProject $project): string
    {
        return $this->render('ged/export.html.twig', [
            'title' => 'Bordereau du projet '.$project->getTitle(),
            'meta' => [
                'Année' => (string) $project->getArchiveYear()?->getYear(),
                'Code projet' => (string) $project->getCode(),
                'Description' => (string) $project->getDescription(),
                'Créé le' => $project->getCreatedAt()?->format('d/m/Y H:i') ?? '-',
            ],
            'folders' => $project->getFolders()->toArray(),
        ]);
    }

    public function exportYear(ArchiveYear $year): string
    {
        $meta = ['Année' => (string) $year->getYear()];
        if (null !== $year->getDescription()) {
            $meta['Description'] = $year->getDescription();
        }

        return $this->render('ged/export.html.twig', [
            'title' => 'Bordereau annuel '.$year->getYear(),
            'meta' => $meta,
            'folders' => [],
            'projects' => $year->getProjects()->toArray(),
        ]);
    }

    private function render(string $template, array $context): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->twig->render($template, $context));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
