<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ArchiveAuditLog;
use App\Entity\ArchivePermission;
use App\Enum\ArchiveAction;
use App\Form\ArchivePermissionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ged')]
final class GedAdministrationController extends AbstractController
{
    #[Route('/permissions', name: 'admin_ged_permissions')]
    public function permissions(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $permission = new ArchivePermission();
        $form = $this->createForm(ArchivePermissionType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $permission->getArchiveFolder() && null === $permission->getArchiveProject()) {
                $form->addError(new \Symfony\Component\Form\FormError('Sélectionnez un dossier ou un projet.'));
            } else {
                $entityManager->persist($permission);
                $entityManager->flush();
                $this->addFlash('success', 'Permission enregistrée.');

                return $this->redirectToRoute('admin_ged_permissions');
            }
        }

        return $this->render('ged/permissions.html.twig', [
            'permissions' => $entityManager->getRepository(ArchivePermission::class)->findBy([], ['id' => 'DESC']),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/permissions/{id}/edit', name: 'admin_ged_permission_edit', requirements: ['id' => '\d+'])]
    public function editPermission(ArchivePermission $permission, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createForm(ArchivePermissionType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $permission->getArchiveFolder() && null === $permission->getArchiveProject()) {
                $form->addError(new \Symfony\Component\Form\FormError('Sélectionnez un dossier ou un projet.'));
            } else {
                $entityManager->flush();
                $this->addFlash('success', 'Permission mise à jour.');

                return $this->redirectToRoute('admin_ged_permissions');
            }
        }

        return $this->render('ged/permissions.html.twig', [
            'permissions' => $entityManager->getRepository(ArchivePermission::class)->findBy([], ['id' => 'DESC']),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/permissions/{id}/delete', name: 'admin_ged_permission_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deletePermission(ArchivePermission $permission, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('delete_permission'.$permission->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($permission);
        $entityManager->flush();
        $this->addFlash('success', 'Permission supprimée.');

        return $this->redirectToRoute('admin_ged_permissions');
    }

    #[Route('/audit', name: 'admin_ged_audit')]
    public function audit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $action = $request->query->get('action');
        $qb = $entityManager->getRepository(ArchiveAuditLog::class)->createQueryBuilder('log')
            ->leftJoin('log.user', 'user')
            ->addSelect('user')
            ->orderBy('log.createdAt', 'DESC')
            ->setMaxResults(100);
        if (is_string($action) && ArchiveAction::tryFrom($action) instanceof ArchiveAction) {
            $qb->andWhere('log.action = :action')->setParameter('action', ArchiveAction::from($action));
        }

        return $this->render('ged/audit.html.twig', [
            'logs' => $qb->getQuery()->getResult(),
            'actions' => ArchiveAction::cases(),
            'currentAction' => $action,
        ]);
    }
}
