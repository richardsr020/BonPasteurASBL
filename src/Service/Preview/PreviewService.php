<?php

declare(strict_types=1);

namespace App\Service\Preview;

use App\Entity\ArchiveDocument;
use App\Service\FileStorage\FileStorageService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

final class PreviewService
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
        private readonly FileStorageService $storage,
    ) {
    }

    public function supports(ArchiveDocument $document): bool
    {
        return $document->isImage() || $document->isPdf() || $document->isVideo() || $document->isOffice();
    }

    public function getPreviewFile(ArchiveDocument $document): ?string
    {
        if (!$document->isOffice()) {
            return $this->storage->absolutePrivatePath((string) $document->getStoragePath());
        }

        $cacheDir = $this->projectDir.'/var/cache/previews/'.$document->getId();
        $pdf = $cacheDir.'/document.pdf';
        if (is_file($pdf)) {
            return $pdf;
        }

        $source = $this->storage->absolutePrivatePath((string) $document->getStoragePath());
        if (!is_file($source)) {
            return null;
        }

        $soffice = $this->findLibreOffice();
        if (null === $soffice) {
            return null;
        }

        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
            return null;
        }

        $process = new Process([$soffice, '--headless', '--convert-to', 'pdf', '--outdir', $cacheDir, $source]);
        $process->setTimeout(120);
        $process->run();

        return is_file($pdf) ? $pdf : null;
    }

    private function findLibreOffice(): ?string
    {
        foreach (['/usr/bin/soffice', '/usr/bin/libreoffice', '/usr/local/bin/soffice'] as $binary) {
            if (is_executable($binary)) {
                return $binary;
            }
        }

        return null;
    }
}
