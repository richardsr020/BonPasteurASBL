<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BackupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/backups')]
final class BackupController extends AbstractController
{
    #[Route('', name: 'admin_backups')]
    public function index(BackupService $backupService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/backups.html.twig', [
            'backups' => $backupService->list(),
        ]);
    }

    #[Route('/run', name: 'admin_backups_run', methods: ['POST'])]
    public function run(Request $request, BackupService $backupService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('backup_run', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $result = $backupService->run();
        $this->addFlash('success', sprintf('Sauvegarde %s créée.', $result['directory']));

        return $this->redirectToRoute('admin_backups');
    }
}
