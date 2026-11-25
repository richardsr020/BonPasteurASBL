<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class BackupService
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
        #[Autowire(env: 'BACKUP_DIR')] private readonly string $backupDir = 'var/backups',
        #[Autowire(env: 'ARCHIVE_STORAGE_DIR')] private readonly string $archiveDir = 'var/storage/archives',
        #[Autowire(env: 'PUBLIC_UPLOAD_DIR')] private readonly string $publicDir = 'public/uploads',
        #[Autowire(env: 'DATABASE_URL')] private readonly string $databaseUrl = '',
    ) {
    }

    /** @return array{directory: string, tables: int, rows: int, files: int, size: int} */
    public function run(): array
    {
        $root = $this->absoluteRoot();
        $stamp = (new \DateTimeImmutable())->format('Ymd_His');
        $target = $root.'/'.$stamp;
        if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
            throw new \RuntimeException(sprintf('Impossible de créer "%s".', $target));
        }

        $tables = $this->connection->createSchemaManager()->listTableNames();
        $rows = 0;
        foreach ($tables as $table) {
            $rows += (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.$this->connection->quoteIdentifier($table));
        }

        $sqlFile = $target.'/database.sql';
        $this->dumpDatabase($sqlFile, $tables);
        $this->gzipFile($sqlFile, $sqlFile.'.gz');

        $staging = $target.'/staging';
        if (!is_dir($staging) && !mkdir($staging, 0775, true) && !is_dir($staging)) {
            throw new \RuntimeException(sprintf('Impossible de créer "%s".', $staging));
        }

        $files = 0;
        $files += $this->copyTree($this->absolutePath($this->archiveDir), $staging.'/archives');
        $files += $this->copyTree($this->absolutePath($this->publicDir), $staging.'/uploads');
        $this->tarDirectory($staging.'/archives', $target.'/archives.tar.gz');
        $this->tarDirectory($staging.'/uploads', $target.'/uploads.tar.gz');
        $this->deleteTree($staging);

        $manifest = [
            'created_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'tables' => \count($tables),
            'rows' => $rows,
            'files' => $files,
            'size_bytes' => $this->directorySize($target),
            'database_file' => 'database.sql.gz',
            'archives_file' => 'archives.tar.gz',
            'uploads_file' => 'uploads.tar.gz',
            'database_gz_sha256' => hash_file('sha256', $sqlFile.'.gz') ?: '',
        ];
        file_put_contents($target.'/manifest.json', json_encode($manifest, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
        $this->prune($root);

        return [
            'directory' => $stamp,
            'tables' => \count($tables),
            'rows' => $rows,
            'files' => $files,
            'size' => (int) $manifest['size_bytes'],
        ];
    }

    /** @return list<array{name: string, date: \DateTimeImmutable, size: int, rows: int, files: int}> */
    public function list(): array
    {
        $root = $this->absoluteRoot();
        if (!is_dir($root)) {
            return [];
        }

        $backups = [];
        foreach (glob($root.'/*', \GLOB_ONLYDIR) ?: [] as $dir) {
            $manifestPath = $dir.'/manifest.json';
            if (!is_file($manifestPath)) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, \JSON_THROW_ON_ERROR);
            $backups[] = [
                'name' => basename($dir),
                'date' => new \DateTimeImmutable((string) ($manifest['created_at'] ?? 'now')),
                'size' => (int) ($manifest['size_bytes'] ?? 0),
                'rows' => (int) ($manifest['rows'] ?? 0),
                'files' => (int) ($manifest['files'] ?? 0),
            ];
        }
        usort($backups, fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return $backups;
    }

    public function absoluteRoot(): string
    {
        return $this->absolutePath($this->backupDir);
    }

    /** @param list<string> $tables */
    private function dumpDatabase(string $target, array $tables): void
    {
        $mysqldump = [];
        exec('command -v mysqldump 2>/dev/null', $mysqldump, $status);
        if (0 === $status && isset($mysqldump[0])) {
            $this->dumpWithMysqldump($mysqldump[0], $target);

            return;
        }

        $this->dumpWithDbal($target, $tables);
    }

    private function dumpWithMysqldump(string $binary, string $target): void
    {
        $parts = parse_url($this->databaseUrl);
        if (false === $parts || !isset($parts['host'], $parts['path'])) {
            throw new \RuntimeException('DATABASE_URL invalide pour la sauvegarde.');
        }

        $defaults = tempnam(sys_get_temp_dir(), 'bp_mysql_');
        if (false === $defaults) {
            throw new \RuntimeException('Impossible de créer le fichier de configuration MySQL temporaire.');
        }

        $configuration = sprintf(
            "[client]\nuser=%s\npassword=%s\nhost=%s\nport=%d\n",
            urldecode((string) ($parts['user'] ?? 'root')),
            urldecode((string) ($parts['pass'] ?? '')),
            (string) $parts['host'],
            (int) ($parts['port'] ?? 3306),
        );
        file_put_contents($defaults, $configuration);
        chmod($defaults, 0600);

        $command = sprintf(
            '%s --defaults-extra-file=%s --single-transaction --quick --routines --triggers %s > %s',
            escapeshellarg($binary),
            escapeshellarg($defaults),
            escapeshellarg(ltrim((string) $parts['path'], '/')),
            escapeshellarg($target),
        );
        exec($command, $output, $status);
        unlink($defaults);

        if (0 !== $status) {
            throw new \RuntimeException('Échec de mysqldump.');
        }
    }

    /** @param list<string> $tables */
    private function dumpWithDbal(string $target, array $tables): void
    {
        $sql = "-- Sauvegarde ASBL Bon Pasteur RDC - ".(new \DateTimeImmutable())->format('Ymd_His')."\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        foreach ($tables as $table) {
            $columns = $this->connection->createSchemaManager()->listTableColumns($table);
            $names = array_keys($columns);
            $records = $this->connection->fetchAllAssociative('SELECT * FROM '.$this->connection->quoteIdentifier($table));
            $sql .= sprintf("\n-- Table %s (%d lignes)\n", $table, \count($records));
            foreach ($records as $record) {
                $values = [];
                foreach ($names as $name) {
                    $value = $record[$name] ?? null;
                    $values[] = null === $value ? 'NULL' : $this->connection->quote((string) $value);
                }
                $sql .= sprintf('INSERT INTO %s (%s) VALUES (%s);'."\n",
                    $this->connection->quoteIdentifier($table),
                    implode(', ', array_map(fn (string $n): string => $this->connection->quoteIdentifier($n), $names)),
                    implode(', ', $values),
                );
            }
        }
        $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($target, $sql);
    }

    private function gzipFile(string $source, string $target): void
    {
        $input = fopen($source, 'rb');
        $output = gzopen($target, 'wb');
        if (false === $input || false === $output) {
            throw new \RuntimeException(sprintf('Impossible de compresser "%s".', $source));
        }

        while (!feof($input)) {
            $chunk = fread($input, 1048576);
            if (false === $chunk || false === gzwrite($output, $chunk)) {
                throw new \RuntimeException(sprintf('Erreur durant la compression de "%s".', $source));
            }
        }
        fclose($input);
        gzclose($output);
        unlink($source);
    }

    private function tarDirectory(string $source, string $target): void
    {
        $command = sprintf(
            'tar -czf %s -C %s .',
            escapeshellarg($target),
            escapeshellarg($source),
        );
        exec($command, $output, $status);
        if (0 !== $status) {
            throw new \RuntimeException(sprintf('Impossible de créer "%s".', $target));
        }
    }

    private function absolutePath(string $relative): string
    {
        return str_starts_with($relative, '/') ? $relative : $this->projectDir.'/'.$relative;
    }

    private function copyTree(string $source, string $target): int
    {
        if (!is_dir($source)) {
            return 0;
        }
        if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
            throw new \RuntimeException(sprintf('Impossible de créer "%s".', $target));
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = substr($file->getPathname(), \strlen($source));
            $destination = $target.$relative;
            $directory = \dirname($destination);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Impossible de créer "%s".', $directory));
            }
            copy($file->getPathname(), $destination);
            ++$count;
        }

        return $count;
    }

    private function directorySize(string $directory): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function prune(string $root): void
    {
        $backups = [];
        foreach (glob($root.'/*', \GLOB_ONLYDIR) ?: [] as $dir) {
            $manifestPath = $dir.'/manifest.json';
            if (!is_file($manifestPath)) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, \JSON_THROW_ON_ERROR);
            $backups[] = [
                'dir' => $dir,
                'date' => new \DateTimeImmutable((string) ($manifest['created_at'] ?? 'now')),
            ];
        }
        usort($backups, fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        $daily = $weekly = $monthly = [];
        $seenDays = $seenWeeks = $seenMonths = [];
        foreach ($backups as $backup) {
            /** @var \DateTimeImmutable $date */
            $date = $backup['date'];
            $day = $date->format('Y-m-d');
            $week = $date->format('o-W');
            $month = $date->format('Y-m');
            $keep = false;

            if (\count($daily) < 7 && !isset($seenDays[$day])) {
                $daily[] = $day;
                $seenDays[$day] = true;
                $keep = true;
            }
            if (\count($weekly) < 4 && !isset($seenWeeks[$week])) {
                $weekly[] = $week;
                $seenWeeks[$week] = true;
                $keep = true;
            }
            if (\count($monthly) < 12 && !isset($seenMonths[$month])) {
                $monthly[] = $month;
                $seenMonths[$month] = true;
                $keep = true;
            }

            if (!$keep) {
                $this->deleteTree((string) $backup['dir']);
            }
        }
    }

    private function deleteTree(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
}
