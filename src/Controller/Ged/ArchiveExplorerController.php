<?php

declare(strict_types=1);

namespace App\Controller\Ged;

use App\Entity\ArchiveFolder;
use App\Entity\ArchiveProject;
use App\Entity\ArchiveYear;
use App\Repository\ArchiveDocumentRepository;
use App\Repository\ArchiveFolderRepository;
use App\Repository\ArchiveYearRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/ged')]
final class ArchiveExplorerController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'admin_ged_years')]
    public function years(ArchiveYearRepository $yearRepository): Response
    {
        return $this->render('ged/years.html.twig', [
            'years' => $yearRepository->findBy([], ['year' => 'DESC']),
        ]);
    }

    #[Route('/year/new', name: 'admin_ged_year_new', methods: ['GET', 'POST'])]
    public function newYear(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('new_year', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $year = new ArchiveYear((int) $request->request->get('year', date('Y')));
            $year->setDescription($request->request->get('description'));
            $this->entityManager->persist($year);
            $this->entityManager->flush();
            $this->addFlash('success', 'Année d\'archives créée.');

            return $this->redirectToRoute('admin_ged_years');
        }

        return $this->render('ged/year_new.html.twig');
    }

    #[Route('/year/{id}', name: 'admin_ged_year', requirements: ['id' => '\d+'])]
    public function year(ArchiveYear $year): Response
    {
        return $this->render('ged/year.html.twig', [
            'year' => $year,
            'projects' => $year->getProjects(),
        ]);
    }

    #[Route('/year/{id}/lock', name: 'admin_ged_year_lock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleLock(ArchiveYear $year, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('lock_year'.$year->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $year->setIsLocked(!$year->isLocked());
        $this->entityManager->flush();
        $this->addFlash('success', $year->isLocked() ? 'Année verrouillée.' : 'Année déverrouillée.');

        return $this->redirectToRoute('admin_ged_year', ['id' => $year->getId()]);
    }

    #[Route('/year/{id}/project/new', name: 'admin_ged_project_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function newProject(ArchiveYear $year, Request $request, SluggerInterface $slugger, ProjectRepository $projectRepository): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $year);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('new_project', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $title = trim((string) $request->request->get('title'));
            if ('' === $title) {
                $this->addFlash('error', 'Le titre est obligatoire.');

                return $this->redirectToRoute('admin_ged_project_new', ['id' => $year->getId()]);
            }

            $project = new ArchiveProject();
            $project->setArchiveYear($year)
                ->setTitle($title)
                ->setCode(strtoupper((string) $slugger->slug($title)->toString()))
                ->setDescription($request->request->get('description'));
            $this->entityManager->persist($project);
            $this->entityManager->flush();
            $this->addFlash('success', 'Projet d\'archives créé avec ses 7 dossiers standardisés.');

            return $this->redirectToRoute('admin_ged_project', ['id' => $project->getId()]);
        }

        return $this->render('ged/project_new.html.twig', [
            'year' => $year,
            'publicProjects' => $projectRepository->findAll(),
        ]);
    }

    #[Route('/project/{id}', name: 'admin_ged_project', requirements: ['id' => '\d+'])]
    public function project(ArchiveProject $project, ArchiveDocumentRepository $documentRepository): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $project);

        return $this->render('ged/project.html.twig', [
            'project' => $project,
            'documentCount' => $documentRepository->countSearch(['project' => $project->getId()]),
        ]);
    }

    #[Route('/folder/{id}', name: 'admin_ged_folder', requirements: ['id' => '\d+'])]
    public function folder(
        ArchiveFolder $folder,
        Request $request,
        ArchiveFolderRepository $folderRepository,
        ArchiveDocumentRepository $documentRepository,
        SluggerInterface $slugger,
    ): Response {
        $this->denyAccessUnlessGranted('VIEW', $folder);

        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('EDIT', $folder);
            $token = (string) $request->request->get('_token');

            if ($this->isCsrfTokenValid('create_folder'.$folder->getId(), $token)) {
                $name = trim((string) $request->request->get('name'));
                if ('' !== $name) {
                    $child = new ArchiveFolder();
                    $child->setArchiveProject($folder->getArchiveProject())
                        ->setParent($folder)
                        ->setName($name)
                        ->setSlug($slugger->slug($name)->lower()->toString());
                    $this->entityManager->persist($child);
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Sous-dossier créé.');
                }

                return $this->redirectToRoute('admin_ged_folder', ['id' => $folder->getId()]);
            }

            if ($this->isCsrfTokenValid('rename_folder'.$folder->getId(), $token)) {
                if ($folder->isSystemFolder()) {
                    $this->addFlash('error', 'Les dossiers racines standardisés ne peuvent pas être renommés.');
                } else {
                    $name = trim((string) $request->request->get('name'));
                    if ('' !== $name) {
                        $folder->setName($name)->setSlug($slugger->slug($name)->lower()->toString());
                        $this->entityManager->flush();
                        $this->addFlash('success', 'Dossier renommé.');
                    }
                }

                return $this->redirectToRoute('admin_ged_folder', ['id' => $folder->getId()]);
            }

            if ($this->isCsrfTokenValid('delete_folder'.$folder->getId(), $token)) {
                if ($folder->isSystemFolder()) {
                    $this->addFlash('error', 'Les dossiers racines standardisés ne peuvent pas être supprimés.');
                } else {
                    $this->entityManager->remove($folder);
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Dossier supprimé.');

                    return $this->redirectToRoute('admin_ged_project', ['id' => $folder->getArchiveProject()?->getId()]);
                }
            }
        }

        return $this->render('ged/folder.html.twig', [
            'folder' => $folder,
            'children' => $folderRepository->findBy(['parent' => $folder], ['displayOrder' => 'ASC', 'name' => 'ASC']),
            'documents' => $documentRepository->findBy(['folder' => $folder, 'deletedAt' => null], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/trash', name: 'admin_ged_trash')]
    public function trash(ArchiveDocumentRepository $documentRepository, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $filters = ['includeTrashed' => true, 'scope' => 'trashed'];

        return $this->render('ged/trash.html.twig', [
            'documents' => $documentRepository->search($filters, $limit, ($page - 1) * $limit),
            'totalPages' => max(1, (int) ceil($documentRepository->countSearch($filters) / $limit)),
            'page' => $page,
        ]);
    }
}
