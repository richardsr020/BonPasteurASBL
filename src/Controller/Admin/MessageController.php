<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/messages')]
final class MessageController extends AbstractController
{
    #[Route('', name: 'admin_messages')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMM');

        return $this->render('admin/messages.html.twig', [
            'messages' => $entityManager->getRepository(ContactMessage::class)->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'admin_messages_show', requirements: ['id' => '\d+'])]
    public function show(ContactMessage $message, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMM');
        $message->setIsRead(true);
        $entityManager->flush();

        return $this->render('admin/message_show.html.twig', ['message' => $message]);
    }

    #[Route('/{id}/delete', name: 'admin_messages_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(ContactMessage $message, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMM');
        if (!$this->isCsrfTokenValid('delete_message'.$message->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($message);
        $entityManager->flush();
        $this->addFlash('success', 'Message supprimé.');

        return $this->redirectToRoute('admin_messages');
    }
}
