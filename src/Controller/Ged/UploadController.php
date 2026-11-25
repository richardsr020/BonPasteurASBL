<?php

declare(strict_types=1);

namespace App\Controller\Ged;

use App\Entity\ArchiveDocument;
use App\Entity\ArchiveFolder;
use App\Service\FileStorage\FileStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ged/folder/{id}')]
final class UploadController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/upload', name: 'admin_ged_upload', methods: ['POST'])]
    public function upload(ArchiveFolder $folder, Request $request, FileStorageService $storage): Response
    {
        $this->denyAccessUnlessGranted('UPLOAD', $folder);
        if (!$this->isCsrfTokenValid('upload'.$folder->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $files = $request->files->all('files');
        if ([] === $files) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');

            return $this->redirectToRoute('admin_ged_folder', ['id' => $folder->getId()]);
        }

        $uploaded = 0;
        $errors = [];
        foreach ($files as $file) {
            if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                continue;
            }

            try {
                $data = $storage->storeArchiveFile(
                    $file,
                    (int) $folder->getArchiveProject()?->getId(),
                    (int) $folder->getId(),
                );

                $document = new ArchiveDocument();
                $document->setFolder($folder)
                    ->setTitle(pathinfo($data['originalFilename'], PATHINFO_FILENAME))
                    ->setOriginalFilename($data['originalFilename'])
                    ->setStoredFilename($data['storedFilename'])
                    ->setStoragePath($data['storagePath'])
                    ->setMimeType($data['mimeType'])
                    ->setFileExtension($data['fileExtension'])
                    ->setFileSize($data['fileSize'])
                    ->setChecksumSha256($data['checksumSha256'])
                    ->setUploadedBy($this->getUser());
                $folder->addDocument($document);
                $this->entityManager->persist($document);
                ++$uploaded;
            } catch (\InvalidArgumentException $exception) {
                $errors[] = $file->getClientOriginalName().' : '.$exception->getMessage();
            }
        }

        $this->entityManager->flush();
        if ($uploaded > 0) {
            $this->addFlash('success', $uploaded.' fichier(s) archivé(s).');
        }
        foreach ($errors as $error) {
            $this->addFlash('error', $error);
        }

        return $this->redirectToRoute('admin_ged_folder', ['id' => $folder->getId()]);
    }
}
