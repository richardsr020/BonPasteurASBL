<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\BackupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:backup:run', description: 'Crée une sauvegarde compressée (base gzipée, archives et uploads tar.gz).')]
final class BackupRunCommand extends Command
{
    public function __construct(private readonly BackupService $backupService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->backupService->run();

        $io->success(sprintf(
            'Sauvegarde %s terminée : %d tables, %d lignes, %d fichiers, %s.',
            $result['directory'],
            $result['tables'],
            $result['rows'],
            $result['files'],
            number_format($result['size'] / 1048576, 2).' Mo',
        ));

        return Command::SUCCESS;
    }
}
