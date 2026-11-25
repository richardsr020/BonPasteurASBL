<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Award;
use App\Entity\HeroSlide;
use App\Entity\InterventionDomain;
use App\Entity\OrganizationSetting;
use App\Entity\Page;
use App\Entity\Partner;
use App\Entity\Project;
use App\Entity\ProjectMedia;
use App\Entity\PublicDocument;
use App\Entity\Testimonial;
use App\Entity\Article;
use App\Entity\User;
use App\Enum\ArticleCategory;
use App\Enum\PartnerType;
use App\Enum\ProjectStatus;
use App\Form\DynamicContentFormType;
use App\Service\FileStorage\FileStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class ContentController extends AbstractController
{
    private const RESOURCES = [
        'projects' => [
            'entity' => Project::class, 'label' => 'Projets publics', 'singular' => 'projet',
            'fields' => [
                'title' => ['type' => TextType::class],
                'slug' => ['type' => TextType::class],
                'context' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'objectives' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'geographicZone' => ['type' => TextType::class, 'options' => ['required' => false]],
                'startDate' => ['type' => DateType::class, 'options' => ['widget' => 'single_text', 'input' => 'datetime_immutable', 'required' => false]],
                'endDate' => ['type' => DateType::class, 'options' => ['widget' => 'single_text', 'input' => 'datetime_immutable', 'required' => false]],
                'status' => ['type' => EnumType::class, 'options' => ['class' => ProjectStatus::class]],
                'targetGroups' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'mainActivities' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'resultsObtained' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'showDonorPublicly' => ['type' => CheckboxType::class, 'options' => ['required' => false]],
                'coverImageUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
                'partners' => ['type' => EntityType::class, 'options' => ['class' => Partner::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false]],
            ],
        ],
        'articles' => [
            'entity' => Article::class, 'label' => 'Actualités', 'singular' => 'article',
            'fields' => [
                'title' => ['type' => TextType::class],
                'slug' => ['type' => TextType::class],
                'category' => ['type' => EnumType::class, 'options' => ['class' => ArticleCategory::class]],
                'content' => ['type' => TextareaType::class, 'options' => ['required' => false, 'attr' => ['rows' => 10]]],
                'coverImageUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
                'publishedAt' => ['type' => \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, 'options' => ['widget' => 'single_text', 'input' => 'datetime_immutable', 'required' => false]],
                'isPublished' => ['type' => CheckboxType::class, 'options' => ['required' => false]],
                'attachmentUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
            ],
        ],
        'testimonials' => [
            'entity' => Testimonial::class, 'label' => 'Témoignages', 'singular' => 'témoignage',
            'fields' => [
                'beneficiaryName' => ['type' => TextType::class],
                'roleOrContext' => ['type' => TextType::class, 'options' => ['required' => false]],
                'storyText' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'photoUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
                'videoUrl' => ['type' => UrlType::class, 'options' => ['required' => false]],
                'isFeaturedOnHome' => ['type' => CheckboxType::class, 'options' => ['required' => false]],
            ],
        ],
        'pages' => [
            'entity' => Page::class, 'label' => 'Pages statiques', 'singular' => 'page',
            'fields' => [
                'title' => ['type' => TextType::class],
                'slug' => ['type' => TextType::class],
                'content' => ['type' => TextareaType::class, 'options' => ['required' => false, 'attr' => ['rows' => 12]]],
                'metaDescription' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'isPublished' => ['type' => CheckboxType::class, 'options' => ['required' => false]],
                'displayOrder' => ['type' => IntegerType::class],
            ],
        ],
        'partners' => [
            'entity' => Partner::class, 'label' => 'Partenaires', 'singular' => 'partenaire',
            'fields' => [
                'name' => ['type' => TextType::class],
                'type' => ['type' => EnumType::class, 'options' => ['class' => PartnerType::class]],
                'logoUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
                'websiteUrl' => ['type' => UrlType::class, 'options' => ['required' => false]],
                'description' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'isVisibleOnHome' => ['type' => CheckboxType::class, 'options' => ['required' => false]],
            ],
        ],
        'awards' => [
            'entity' => Award::class, 'label' => 'Prix et distinctions', 'singular' => 'prix',
            'fields' => [
                'title' => ['type' => TextType::class],
                'issuer' => ['type' => TextType::class, 'options' => ['required' => false]],
                'year' => ['type' => IntegerType::class],
                'description' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'imageUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
            ],
        ],
        'slides' => [
            'entity' => HeroSlide::class, 'label' => 'Diaporama d\'accueil', 'singular' => 'diapositive',
            'fields' => [
                'title' => ['type' => TextType::class],
                'subtitle' => ['type' => TextType::class, 'options' => ['required' => false]],
                'imageUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
                'ctaLabel' => ['type' => TextType::class, 'options' => ['required' => false]],
                'ctaUrl' => ['type' => TextType::class, 'options' => ['required' => false]],
                'displayOrder' => ['type' => IntegerType::class],
                'isActive' => ['type' => CheckboxType::class, 'options' => ['required' => false]],
            ],
        ],
        'domains' => [
            'entity' => InterventionDomain::class, 'label' => 'Domaines d\'intervention', 'singular' => 'domaine',
            'fields' => [
                'title' => ['type' => TextType::class],
                'slug' => ['type' => TextType::class],
                'summary' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'description' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'displayOrder' => ['type' => IntegerType::class],
            ],
        ],
        'public_documents' => [
            'entity' => PublicDocument::class, 'label' => 'Documents publics', 'singular' => 'document public',
            'fields' => [
                'title' => ['type' => TextType::class],
                'path' => ['type' => TextType::class],
                'pathUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
                'extension' => ['type' => TextType::class, 'options' => ['required' => false]],
                'project' => ['type' => EntityType::class, 'options' => ['class' => Project::class, 'choice_label' => 'title', 'required' => false, 'placeholder' => 'Aucun projet']],
            ],
        ],
        'project_medias' => [
            'entity' => ProjectMedia::class, 'label' => 'Médias publics', 'singular' => 'média',
            'fields' => [
                'title' => ['type' => TextType::class],
                'type' => ['type' => ChoiceType::class, 'options' => ['choices' => ['Image' => 'image', 'Vidéo' => 'video']]],
                'path' => ['type' => TextType::class],
                'pathUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
                'caption' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'project' => ['type' => EntityType::class, 'options' => ['class' => Project::class, 'choice_label' => 'title', 'required' => false, 'placeholder' => 'Aucun projet']],
            ],
        ],
        'settings' => [
            'entity' => OrganizationSetting::class, 'label' => 'Identité & coordonnées', 'singular' => 'paramètre',
            'fields' => [
                'organizationName' => ['type' => TextType::class],
                'logoPathUpload' => ['type' => FileType::class, 'options' => ['mapped' => false, 'required' => false]],
                'primaryColor' => ['type' => TextType::class, 'options' => ['required' => false]],
                'shortDescription' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'mission' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'vision' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'organization_values' => ['type' => TextareaType::class, 'options' => ['required' => false, 'mapped' => false]],
                'history' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'objectives' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'strategicPlan' => ['type' => TextareaType::class, 'options' => ['required' => false]],
                'address' => ['type' => TextType::class, 'options' => ['required' => false]],
                'email' => ['type' => TextType::class, 'options' => ['required' => false]],
                'phone' => ['type' => TextType::class, 'options' => ['required' => false]],
                'phoneSecondary' => ['type' => TextType::class, 'options' => ['required' => false]],
                'gpsCoordinates' => ['type' => TextType::class, 'options' => ['required' => false]],
                'keyFiguresText' => ['type' => TextareaType::class, 'options' => ['mapped' => false, 'required' => false, 'attr' => ['rows' => 5, 'placeholder' => "Bénéficiaires touchés|12 500"]]],
                'socialLinksText' => ['type' => TextareaType::class, 'options' => ['mapped' => false, 'required' => false, 'attr' => ['rows' => 4, 'placeholder' => "facebook|https://facebook.com/..."]]],
            ],
        ],
        'users' => [
            'entity' => User::class, 'label' => 'Utilisateurs', 'singular' => 'utilisateur',
            'fields' => [
                'email' => ['type' => TextType::class],
                'fullName' => ['type' => TextType::class],
                'roles' => ['type' => ChoiceType::class, 'options' => [
                    'choices' => ['Administrateur' => 'ROLE_ADMIN', 'Communication' => 'ROLE_COMM'],
                    'multiple' => true, 'expanded' => true,
                ]],
                'isActive' => ['type' => CheckboxType::class, 'options' => ['required' => false]],
                'plainPassword' => ['type' => PasswordType::class, 'options' => ['mapped' => false, 'required' => false]],
            ],
        ],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/projects', name: 'admin_projects', defaults: ['resource' => 'projects'])]
    #[Route('/pages', name: 'admin_pages', defaults: ['resource' => 'pages'])]
    #[Route('/articles', name: 'admin_articles', defaults: ['resource' => 'articles'])]
    #[Route('/testimonials', name: 'admin_testimonials', defaults: ['resource' => 'testimonials'])]
    #[Route('/partners', name: 'admin_partners', defaults: ['resource' => 'partners'])]
    #[Route('/awards', name: 'admin_awards', defaults: ['resource' => 'awards'])]
    #[Route('/slides', name: 'admin_slides', defaults: ['resource' => 'slides'])]
    #[Route('/domains', name: 'admin_domains', defaults: ['resource' => 'domains'])]
    #[Route('/public-documents', name: 'admin_public_documents', defaults: ['resource' => 'public_documents'])]
    #[Route('/project-medias', name: 'admin_project_medias', defaults: ['resource' => 'project_medias'])]
    #[Route('/settings', name: 'admin_settings', defaults: ['resource' => 'settings'])]
    #[Route('/users', name: 'admin_users', defaults: ['resource' => 'users'])]
    public function list(string $resource, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMM');
        $resource = str_replace('-', '_', $resource);
        $config = $this->config($resource);
        if ('settings' === $resource) {
            $settings = $this->entityManager->getRepository($config['entity'])->findOneBy([]);

            return null !== $settings
                ? $this->redirectToRoute('admin_content_edit', ['resource' => $resource, 'id' => $settings->getId()])
                : $this->redirectToRoute('admin_content_new', ['resource' => $resource]);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;
        $repository = $this->entityManager->getRepository($config['entity']);
        $items = $repository->findBy([], ['id' => 'DESC'], $limit, ($page - 1) * $limit);
        $total = $repository->count([]);

        return $this->render('admin/content_list.html.twig', [
            'resource' => $resource,
            'config' => $config,
            'items' => $items,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
        ]);
    }

    #[Route('/{resource}/new', name: 'admin_content_new', requirements: ['resource' => 'projects|pages|articles|testimonials|partners|awards|slides|domains|public-documents|project-medias|public_documents|project_medias|settings|users'])]
    public function new(string $resource, Request $request, FileStorageService $storage, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMM');
        $resource = str_replace('-', '_', $resource);
        if ('users' === $resource) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $config = $this->config($resource);
        $class = $config['entity'];
        $entity = new $class();

        return $this->handleForm($resource, $entity, $config, $request, $storage, $passwordHasher, true);
    }

    #[Route('/{resource}/{id}/edit', name: 'admin_content_edit', requirements: ['resource' => 'projects|pages|articles|testimonials|partners|awards|slides|domains|public-documents|project-medias|public_documents|project_medias|settings|users', 'id' => '\d+'])]
    public function edit(string $resource, int $id, Request $request, FileStorageService $storage, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMM');
        $resource = str_replace('-', '_', $resource);
        if ('users' === $resource) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $config = $this->config($resource);
        $entity = $this->entityManager->getRepository($config['entity'])->find($id);
        if (null === $entity) {
            throw $this->createNotFoundException('Élément introuvable.');
        }

        return $this->handleForm($resource, $entity, $config, $request, $storage, $passwordHasher, false);
    }

    #[Route('/{resource}/{id}/delete', name: 'admin_content_delete', methods: ['POST'], requirements: ['resource' => 'projects|pages|articles|testimonials|partners|awards|slides|domains|public-documents|project-medias|public_documents|project_medias|users', 'id' => '\d+'])]
    public function delete(string $resource, int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMM');
        $resource = str_replace('-', '_', $resource);
        if ('users' === $resource) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $config = $this->config($resource);
        $entity = $this->entityManager->getRepository($config['entity'])->find($id);
        if (null === $entity) {
            throw $this->createNotFoundException('Élément introuvable.');
        }

        if (!$this->isCsrfTokenValid('delete_content'.$resource.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($entity instanceof User && $entity === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('admin_'.$resource);
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
        $this->addFlash('success', ucfirst($config['singular']).' supprimé.');

        return $this->redirectToRoute('admin_'.$resource);
    }

    private function handleForm(string $resource, object $entity, array $config, Request $request, FileStorageService $storage, UserPasswordHasherInterface $passwordHasher, bool $isNew): Response
    {
        $fields = [];
        foreach ($config['fields'] as $name => $definition) {
            $fields[$name] = $definition;
        }

        $form = $this->createForm(DynamicContentFormType::class, $entity, [
            'fields' => $fields,
            'data_class' => $config['entity'],
        ]);
        if ($entity instanceof OrganizationSetting) {
            $form->get('organization_values')->setData($entity->getValues());
            $form->get('keyFiguresText')->setData($this->encodePairs($entity->getKeyFigures(), 'value'));
            $form->get('socialLinksText')->setData($this->encodePairs($entity->getSocialLinks()));
        }
        if ($entity instanceof User) {
            $form->get('roles')->setData(array_values(array_intersect($entity->getRoles(), ['ROLE_ADMIN', 'ROLE_COMM'])));
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($config['fields'] as $name => $definition) {
                if (($definition['options']['mapped'] ?? true) !== false) {
                    continue;
                }
                $field = $form->get($name);
                $data = $field->getData();

                if ($data instanceof UploadedFile) {
                    $path = $storage->storePublicFile($data, 'media');
                    $setter = 'set'.str_replace('Upload', '', ucfirst($name));
                    if (method_exists($entity, $setter)) {
                        $entity->{$setter}($path);
                    }
                    if ($entity instanceof PublicDocument && !$entity->getExtension()) {
                        $entity->setExtension(strtolower(pathinfo($path, \PATHINFO_EXTENSION)));
                    }
                    if ($entity instanceof ProjectMedia) {
                        $entity->setType(str_starts_with($data->getClientMimeType() ?: '', 'video/') ? 'video' : 'image');
                    }
                } elseif ('plainPassword' === $name && is_string($data) && '' !== $data && $entity instanceof User) {
                    $entity->setPassword($passwordHasher->hashPassword($entity, $data));
                } elseif ('organization_values' === $name && is_string($data) && $entity instanceof OrganizationSetting) {
                    $entity->setValues($data);
                } elseif ('keyFiguresText' === $name && is_string($data) && $entity instanceof OrganizationSetting) {
                    $entity->setKeyFigures($this->decodePairs($data, 'value'));
                } elseif ('socialLinksText' === $name && is_string($data) && $entity instanceof OrganizationSetting) {
                    $entity->setSocialLinks($this->decodePairs($data));
                }
            }

            if ($isNew && $entity instanceof User && null === $entity->getPassword()) {
                $form->get('plainPassword')->addError(new \Symfony\Component\Form\FormError('Le mot de passe est obligatoire.'));
            } else {
                if ($isNew) {
                    $this->entityManager->persist($entity);
                }
                $this->entityManager->flush();
                $this->addFlash('success', ucfirst($config['singular']).' enregistré.');

                return $this->redirectToRoute('admin_content_edit', ['resource' => $resource, 'id' => $entity->getId()]);
            }
        }

        return $this->render('admin/content_form.html.twig', [
            'resource' => $resource,
            'config' => $config,
            'form' => $form->createView(),
            'entity' => $entity,
            'isNew' => $isNew,
        ]);
    }

    private function encodePairs(array $items, ?string $valueKey = null): string
    {
        $lines = [];
        foreach ($items as $key => $item) {
            if (\is_array($item)) {
                $label = (string) ($item['label'] ?? $key);
                $value = (string) ($item[$valueKey ?? 'value'] ?? '');
            } else {
                $label = (string) $key;
                $value = (string) $item;
            }
            $lines[] = $label.'|'.$value;
        }

        return implode("\n", $lines);
    }

    /** @return array<int|string, mixed> */
    private function decodePairs(string $text, ?string $valueKey = null): array
    {
        $items = [];
        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            [$label, $value] = array_pad(explode('|', $line, 2), 2, '');
            $label = trim($label);
            if ('' === $label) {
                continue;
            }
            $value = trim($value);
            if (null !== $valueKey) {
                $items[] = ['label' => $label, $valueKey => $value];
            } else {
                $items[$label] = $value;
            }
        }

        return $items;
    }

    private function config(string $resource): array
    {
        $resource = str_replace('-', '_', $resource);
        if (!isset(self::RESOURCES[$resource])) {
            throw $this->createNotFoundException('Ressource inconnue.');
        }

        return self::RESOURCES[$resource];
    }
}
