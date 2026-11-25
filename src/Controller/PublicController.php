<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Award;
use App\Entity\ContactMessage;
use App\Entity\HeroSlide;
use App\Entity\InterventionDomain;
use App\Entity\OrganizationSetting;
use App\Entity\Page;
use App\Entity\Partner;
use App\Entity\Project;
use App\Entity\PublicDocument;
use App\Entity\Testimonial;
use App\Enum\ArticleCategory;
use App\Enum\ProjectStatus;
use App\Form\DynamicContentFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function root(): Response
    {
        return $this->redirectToRoute('app_home_localized', ['_locale' => 'fr']);
    }

    #[Route('/{_locale}', name: 'app_home_localized', requirements: ['_locale' => 'fr|en'])]
    public function home(EntityManagerInterface $entityManager): Response
    {
        return $this->render('public/home.html.twig', [
            'settings' => $this->settings($entityManager),
            'slides' => $entityManager->getRepository(HeroSlide::class)->findBy(['isActive' => true], ['displayOrder' => 'ASC']),
            'domains' => $entityManager->getRepository(InterventionDomain::class)->findBy([], ['displayOrder' => 'ASC']),
            'projects' => $entityManager->getRepository(Project::class)->findBy([], ['createdAt' => 'DESC'], 4),
            'articles' => $entityManager->getRepository(Article::class)->findBy(['isPublished' => true], ['publishedAt' => 'DESC'], 3),
            'testimonials' => $entityManager->getRepository(Testimonial::class)->findBy(['isFeaturedOnHome' => true], ['id' => 'DESC'], 3),
            'partners' => $entityManager->getRepository(Partner::class)->findBy(['isVisibleOnHome' => true], ['name' => 'ASC']),
            'awards' => $entityManager->getRepository(Award::class)->findBy([], ['year' => 'DESC'], 4),
        ]);
    }

    #[Route('/{_locale}/qui-sommes-nous', name: 'app_about', requirements: ['_locale' => 'fr|en'])]
    public function about(EntityManagerInterface $entityManager): Response
    {
        return $this->render('public/about.html.twig', [
            'settings' => $this->settings($entityManager),
            'domains' => $entityManager->getRepository(InterventionDomain::class)->findBy([], ['displayOrder' => 'ASC']),
            'documents' => $entityManager->getRepository(PublicDocument::class)->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/{_locale}/projets', name: 'app_projects', requirements: ['_locale' => 'fr|en'])]
    public function projects(Request $request, EntityManagerInterface $entityManager): Response
    {
        $status = $request->query->get('status');
        $qb = $entityManager->getRepository(Project::class)->createQueryBuilder('p');
        if (is_string($status) && ProjectStatus::tryFrom($status) instanceof ProjectStatus) {
            $qb->andWhere('p.status = :status')->setParameter('status', ProjectStatus::from($status));
        }
        $qb->orderBy('p.createdAt', 'DESC');
        $pagination = $this->paginate($qb->getQuery(), $request->query->getInt('page', 1));

        return $this->render('public/projects.html.twig', [
            'projects' => $pagination['items'],
            'pagination' => $pagination,
            'paginationRoute' => 'app_projects',
            'paginationParams' => array_filter(['_locale' => $request->getLocale(), 'status' => $status]),
            'statuses' => ProjectStatus::cases(),
            'currentStatus' => $status,
        ]);
    }

    #[Route('/{_locale}/projets/{slug}', name: 'app_project_show', requirements: ['_locale' => 'fr|en'])]
    public function projectShow(string $slug, EntityManagerInterface $entityManager): Response
    {
        $project = $entityManager->getRepository(Project::class)->findOneBy(['slug' => $slug]);
        if (null === $project) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        return $this->render('public/project_show.html.twig', [
            'project' => $project,
            'documents' => $entityManager->getRepository(PublicDocument::class)->findBy(['project' => $project]),
            'testimonials' => $entityManager->getRepository(Testimonial::class)->findBy([], ['id' => 'DESC'], 3),
        ]);
    }

    #[Route('/{_locale}/actualites', name: 'app_articles', requirements: ['_locale' => 'fr|en'])]
    public function articles(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = $request->query->get('category');
        $qb = $entityManager->getRepository(Article::class)->createQueryBuilder('a')
            ->andWhere('a.isPublished = true');
        if (is_string($category) && ArticleCategory::tryFrom($category) instanceof ArticleCategory) {
            $qb->andWhere('a.category = :category')->setParameter('category', ArticleCategory::from($category));
        }
        $qb->orderBy('a.publishedAt', 'DESC');
        $pagination = $this->paginate($qb->getQuery(), $request->query->getInt('page', 1));

        return $this->render('public/articles.html.twig', [
            'articles' => $pagination['items'],
            'pagination' => $pagination,
            'paginationRoute' => 'app_articles',
            'paginationParams' => array_filter(['_locale' => $request->getLocale(), 'category' => $category]),
            'categories' => ArticleCategory::cases(),
            'currentCategory' => $category,
        ]);
    }

    #[Route('/{_locale}/temoignages', name: 'app_testimonials', requirements: ['_locale' => 'fr|en'])]
    public function testimonials(Request $request, EntityManagerInterface $entityManager): Response
    {
        $qb = $entityManager->getRepository(Testimonial::class)->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');
        $pagination = $this->paginate($qb->getQuery(), $request->query->getInt('page', 1), 9);

        return $this->render('public/testimonials.html.twig', [
            'testimonials' => $pagination['items'],
            'pagination' => $pagination,
            'paginationRoute' => 'app_testimonials',
            'paginationParams' => ['_locale' => $request->getLocale()],
        ]);
    }

    #[Route('/{_locale}/actualites/{slug}', name: 'app_article_show', requirements: ['_locale' => 'fr|en'])]
    public function articleShow(string $slug, EntityManagerInterface $entityManager): Response
    {
        $article = $entityManager->getRepository(Article::class)->findOneBy(['slug' => $slug, 'isPublished' => true]);
        if (null === $article) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        return $this->render('public/article_show.html.twig', ['article' => $article]);
    }

    #[Route('/{_locale}/partenaires', name: 'app_partners', requirements: ['_locale' => 'fr|en'])]
    public function partners(EntityManagerInterface $entityManager): Response
    {
        return $this->render('public/partners.html.twig', [
            'partners' => $entityManager->getRepository(Partner::class)->findBy([], ['name' => 'ASC']),
            'awards' => $entityManager->getRepository(Award::class)->findBy([], ['year' => 'DESC']),
        ]);
    }

    #[Route('/{_locale}/documents', name: 'app_documents', requirements: ['_locale' => 'fr|en'])]
    public function documents(EntityManagerInterface $entityManager): Response
    {
        return $this->render('public/documents.html.twig', [
            'documents' => $entityManager->getRepository(PublicDocument::class)->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/{_locale}/pages', name: 'app_pages', requirements: ['_locale' => 'fr|en'])]
    public function pages(EntityManagerInterface $entityManager): Response
    {
        return $this->render('public/pages.html.twig', [
            'pages' => $entityManager->getRepository(Page::class)->findBy(['isPublished' => true], ['displayOrder' => 'ASC']),
        ]);
    }

    #[Route('/{_locale}/pages/{slug}', name: 'app_page_show', requirements: ['_locale' => 'fr|en'])]
    public function pageShow(string $slug, EntityManagerInterface $entityManager): Response
    {
        $page = $entityManager->getRepository(Page::class)->findOneBy(['slug' => $slug, 'isPublished' => true]);
        if (null === $page) {
            throw $this->createNotFoundException('Page introuvable.');
        }

        return $this->render('public/page_show.html.twig', ['page' => $page]);
    }

    #[Route('/{_locale}/contact', name: 'app_contact', requirements: ['_locale' => 'fr|en'], methods: ['GET', 'POST'])]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        $message = new ContactMessage();
        $form = $this->createForm(DynamicContentFormType::class, $message, [
            'data_class' => ContactMessage::class,
            'fields' => [
                'senderName' => ['type' => TextType::class],
                'senderEmail' => ['type' => EmailType::class],
                'subject' => ['type' => TextType::class, 'options' => ['required' => false]],
                'message' => ['type' => TextareaType::class, 'options' => ['attr' => ['rows' => 6]]],
            ],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();
            $this->addFlash('success', 'Votre message a été envoyé. Merci !');

            return $this->redirectToRoute('app_contact', ['_locale' => $request->getLocale()]);
        }

        return $this->render('public/contact.html.twig', [
            'settings' => $this->settings($entityManager),
            'form' => $form->createView(),
        ]);
    }

    /** @return array{items: array<int, object>, currentPage: int, pages: int, total: int} */
    private function paginate(Query $query, int $page, int $limit = 9): array
    {
        $page = max(1, $page);
        $paginator = new Paginator($query, false);
        $pages = max(1, (int) ceil(count($paginator) / $limit));
        $page = min($page, $pages);
        $query->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'currentPage' => $page,
            'pages' => $pages,
            'total' => count($paginator),
        ];
    }

    private function settings(EntityManagerInterface $entityManager): OrganizationSetting
    {
        return $entityManager->getRepository(OrganizationSetting::class)->findOneBy([]) ?? new OrganizationSetting();
    }
}
