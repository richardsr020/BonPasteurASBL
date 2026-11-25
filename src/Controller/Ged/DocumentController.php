<?php

declare(strict_types=1);

namespace App\Controller\Ged;

use App\Entity\ArchiveDocument;
use App\Entity\ArchiveFolder;
use App\Entity\ProjectMedia;
use App\Enum\ArchiveAction;
use App\Repository\ArchiveFolderRepository;
use App\Service\AuditService;
use App\Service\FileStorage\FileStorageService;
use App\Service\Preview\PreviewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ged/document/{id}')]
final class DocumentController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/preview', name: 'admin_ged_document_preview', requirements: ['id' => '\d+'])]
    public function preview(ArchiveDocument $document, PreviewService $previewService, AuditService $audit): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $document);
        $audit->record(ArchiveAction::VIEW_PREVIEW, $document, ['title' => $document->getTitle()]);

        $path = $previewService->getPreviewFile($document);
        if (null === $path || !is_file($path)) {
            return $this->render('ged/preview_unavailable.html.twig', [
                'document' => $document,
                'reason' => $document->isOffice()
                    ? 'La conversion bureautique nécessite LibreOffice sur le serveur.'
                    : 'Le fichier d\'aperçu est introuvable.',
            ]);
        }

        $mimeType = $document->isOffice() ? 'application/pdf' : (string) $document->getMimeType();
        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $mimeType);
        $response->setAutoLastModified();
        $response->setAutoEtag();
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $document->getOriginalFilename());

        return $response;
    }

    #[Route('/download', name: 'admin_ged_document_download', requirements: ['id' => '\d+'])]
    public function download(ArchiveDocument $document, FileStorageService $storage, AuditService $audit): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $document);
        $path = $storage->absolutePrivatePath((string) $document->getStoragePath());
        if (!is_file($path)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $audit->record(ArchiveAction::DOWNLOAD, $document, ['title' => $document->getTitle()]);

        $response = new BinaryFileResponse($path);
        $response->setAutoLastModified();
        $response->setAutoEtag();
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, (string) $document->getOriginalFilename());

        return $response;
    }

    #[Route('/replace', name: 'admin_ged_document_replace', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function replace(ArchiveDocument $document, Request $request, FileStorageService $storage): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $document);
        if (!$this->isCsrfTokenValid('replace'.$document->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $file = $request->files->get('replacement');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $this->addFlash('error', 'Aucun fichier de remplacement fourni.');

            return $this->redirectToRoute('admin_ged_folder', ['id' => $document->getFolder()?->getId()]);
        }

        try {
            $data = $storage->storeNewVersion(
                $file,
                (string) $document->getStoragePath(),
                (string) $document->getStoredFilename(),
                (int) $document->getId(),
                $document->getVersion(),
                (int) $document->getFolder()?->getArchiveProject()?->getId(),
                (int) $document->getFolder()?->getId(),
            );
        } catch (\InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('admin_ged_folder', ['id' => $document->getFolder()?->getId()]);
        }

        $document->setStoredFilename($data['storedFilename'])
            ->setStoragePath($data['storagePath'])
            ->setFileExtension($data['fileExtension'])
            ->setFileSize($data['fileSize'])
            ->setChecksumSha256($data['checksumSha256'])
            ->setMimeType($file->getClientMimeType() ?: $document->getMimeType())
            ->setVersion($document->getVersion() + 1);
        $this->entityManager->flush();
        $this->addFlash('success', 'Nouvelle version enregistrée (v'.$document->getVersion().').');

        return $this->redirectToRoute('admin_ged_folder', ['id' => $document->getFolder()?->getId()]);
    }

    #[Route('/delete', name: 'admin_ged_document_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function softDelete(ArchiveDocument $document, Request $request, AuditService $audit): Response
    {
        $this->denyAccessUnlessGranted('SOFT_DELETE', $document);
        $this->validateCsrf($request, 'delete'.$document->getId());

        $document->setDeletedAt(new \DateTimeImmutable());
        $document->setDeletedBy($this->getUser());
        $this->entityManager->flush();
        $audit->record(ArchiveAction::SOFT_DELETE, $document, ['title' => $document->getTitle()]);
        $this->addFlash('success', 'Document placé dans la corbeille.');

        return $this->redirectToRoute('admin_ged_folder', ['id' => $document->getFolder()?->getId()]);
    }

    #[Route('/restore', name: 'admin_ged_document_restore', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function restore(ArchiveDocument $document, Request $request, AuditService $audit): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $document);
        $this->validateCsrf($request, 'restore'.$document->getId());

        $document->setDeletedAt(null)->setDeletedBy(null);
        $this->entityManager->flush();
        $audit->record(ArchiveAction::RESTORE, $document, ['title' => $document->getTitle()]);
        $this->addFlash('success', 'Document restauré.');

        return $this->redirectToRoute('admin_ged_folder', ['id' => $document->getFolder()?->getId()]);
    }

    #[Route('/purge', name: 'admin_ged_document_purge', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function purge(ArchiveDocument $document, Request $request, FileStorageService $storage): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $document);
        $this->validateCsrf($request, 'purge'.$document->getId());

        $path = (string) $document->getStoragePath();
        $documentId = (int) $document->getId();
        $folderId = $document->getFolder()?->getId();
        $this->entityManager->remove($document);
        $this->entityManager->flush();
        $storage->purgeDocument($path, $documentId);
        $this->addFlash('success', 'Document supprimé définitivement.');

        return $this->redirectToRoute('admin_ged_trash');
    }

    #[Route('/promote', name: 'admin_ged_document_promote', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function promote(ArchiveDocument $document, Request $request, FileStorageService $storage, AuditService $audit): Response
    {
        $this->denyAccessUnlessGranted('EXPORT', $document);
        $this->validateCsrf($request, 'promote'.$document->getId());

        $publicPath = $storage->promoteToPublic(
            (string) $document->getStoragePath(),
            (string) $document->getMimeType(),
        );

        $media = new ProjectMedia();
        $media->setTitle((string) $document->getTitle())
            ->setPath($publicPath)
            ->setType($document->isVideo() ? 'video' : 'image')
            ->setCaption('Média promu depuis les archives internes');
        $media->setProject($document->getFolder()?->getArchiveProject()?->getLinkedPublicProject());
        $this->entityManager->persist($media);
        $this->entityManager->flush();

        $audit->record(ArchiveAction::UPLOAD, $document, ['promotedTo' => $publicPath]);
        $this->addFlash('success', 'Média publié dans la galerie publique.');

        return $this->redirectToRoute('admin_ged_folder', ['id' => $document->getFolder()?->getId()]);
    }

    #[Route('/move', name: 'admin_ged_document_move', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function move(ArchiveDocument $document, Request $request, ArchiveFolderRepository $folderRepository, AuditService $audit): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $document);
        $this->validateCsrf($request, 'move'.$document->getId());

        $target = $folderRepository->find((int) $request->request->get('targetFolderId'));
        if (null === $target) {
            $this->addFlash('error', 'Dossier cible introuvable.');

            return $this->redirectToRoute('admin_ged_folder', ['id' => $document->getFolder()?->getId()]);
        }

        $this->denyAccessUnlessGranted('UPLOAD', $target);
        $source = $document->getFolder();
        $document->setFolder($target);
        $this->entityManager->flush();
        $audit->record(ArchiveAction::MOVE, $document, [
            'from' => $source?->getName(),
            'to' => $target->getName(),
        ]);
        $this->addFlash('success', 'Document déplacé.');

        return $this->redirectToRoute('admin_ged_folder', ['id' => $target->getId()]);
    }

    private function validateCsrf(Request $request, string $tokenId): void
    {
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
