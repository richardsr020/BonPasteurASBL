<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Award;
use App\Entity\HeroSlide;
use App\Entity\InterventionDomain;
use App\Entity\OrganizationSetting;
use App\Entity\Page;
use App\Entity\Partner;
use App\Entity\Project;
use App\Entity\Testimonial;
use App\Entity\User;
use App\Entity\ArchiveProject;
use App\Entity\ArchiveYear;
use App\Enum\ArticleCategory;
use App\Enum\PartnerType;
use App\Enum\ProjectStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@bonpasteur-rdc.org')
            ->setFullName('Administrateur Bon Pasteur')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        $comm = new User();
        $comm->setEmail('communication@bonpasteur-rdc.org')
            ->setFullName('Responsable communication')
            ->setRoles(['ROLE_COMM'])
            ->setPassword($this->passwordHasher->hashPassword($comm, 'Comm123!'));
        $manager->persist($comm);

        $settings = new OrganizationSetting();
        $settings->setOrganizationName('ASBL Bon Pasteur RDC')
            ->setShortDescription('Accompagner les communautés vulnérables de la province du Lualaba vers une autonomie durable.')
            ->setMission('Protéger l\'enfant et la femme, promouvoir l\'éducation, la formation professionnelle et l\'agriculture durable.')
            ->setVision('Une société lualabaise résiliente, inclusive et prospère.')
            ->setValues('Dignité humaine · Solidarité · Transparence · Engagement communautaire')
            ->setHistory('Fondée à Kolwezi, l\'ASBL Bon Pasteur accompagne depuis plusieurs années les populations du Lualaba.')
            ->setObjectives('Renforcer les capacités locales et améliorer les conditions de vie des bénéficiaires.')
            ->setStrategicPlan('Plan stratégique 2024-2028 : éducation, protection, économie locale et gouvernance.')
            ->setKeyFigures([
                ['label' => 'Bénéficiaires touchés', 'value' => '12 500'],
                ['label' => 'Zones d\'intervention', 'value' => '8'],
                ['label' => 'Projets réalisés', 'value' => '34'],
                ['label' => 'Taux d\'impact', 'value' => '87 %'],
            ])
            ->setAddress('Kolwezi, province du Lualaba, République Démocratique du Congo')
            ->setEmail('contact@bonpasteur-rdc.org')
            ->setPhone('+243 97 000 0000')
            ->setGpsCoordinates('-10.7167,25.4681')
            ->setSocialLinks(['facebook' => 'https://facebook.com', 'linkedin' => 'https://linkedin.com']);
        $manager->persist($settings);

        foreach ([
            ['Charte éthique', 'charte-ethique', 'La dignité humaine guide toutes nos interventions...', 1],
            ['Politique de protection des données', 'politique-de-confidentialite', 'Les données collectées via le formulaire de contact servent uniquement à répondre aux demandes.', 2],
        ] as [$title, $slug, $content, $order]) {
            $page = new Page();
            $page->setTitle($title)->setSlug($slug)->setContent($content)
                ->setMetaDescription($content)->setIsPublished(true)->setDisplayOrder($order);
            $manager->persist($page);
        }

        foreach ([
            ['Un avenir pour chaque enfant', 'Éduquer, protéger et accompagner.', '/uploads/hero-1.svg', 'Découvrir nos actions'],
            ['Autonomiser les femmes', 'Formation et microcrédit pour un impact durable.', '/uploads/hero-2.svg', 'Voir les projets'],
        ] as $index => [$title, $subtitle, $image, $cta]) {
            $slide = new HeroSlide();
            $slide->setTitle($title)->setSubtitle($subtitle)->setImagePath($image)
                ->setCtaLabel($cta)->setCtaUrl('/fr/projets')->setDisplayOrder($index + 1);
            $manager->persist($slide);
        }

        foreach ([
            ['Protection de l\'enfant et de la femme', 'protection-enfant-femme'],
            ['Éducation informelle', 'education-informelle'],
            ['Formation professionnelle', 'formation-professionnelle'],
            ['Agriculture durable', 'agriculture-durable'],
            ['Microcrédit', 'microcredit'],
        ] as $index => [$title, $slug]) {
            $domain = new InterventionDomain();
            $domain->setTitle($title)->setSlug($slug)
                ->setSummary('Un axe d\'intervention prioritaire de l\'ASBL Bon Pasteur.')
                ->setDescription('Description détaillée de l\'axe d\'intervention '.$title.'.')
                ->setDisplayOrder($index + 1);
            $manager->persist($domain);
        }

        $partner1 = new Partner();
        $partner1->setName('UNICEF RDC')->setType(PartnerType::TECHNICAL)
            ->setDescription('Appui technique à la protection de l\'enfant.')->setIsVisibleOnHome(true);
        $manager->persist($partner1);

        $partner2 = new Partner();
        $partner2->setName('Fondation Colombo')->setType(PartnerType::FINANCIAL)
            ->setDescription('Financement de programmes éducatifs.')->setIsVisibleOnHome(true);
        $manager->persist($partner2);

        $project = new Project();
        $project->setTitle('École pour tous à Kolwezi')
            ->setSlug('ecole-pour-tous-a-kolwezi')
            ->setContext('Accès limité à l\'éducation dans les quartiers périphériques.')
            ->setObjectives('Scolariser 300 enfants et former 20 enseignants.')
            ->setGeographicZone('Kolwezi, Lualaba')
            ->setStartDate(new \DateTimeImmutable('2024-01-15'))
            ->setStatus(ProjectStatus::ONGOING)
            ->setTargetGroups('Enfants de 6 à 15 ans')
            ->setMainActivities('Construction de salles de classe, distribution de matériel.')
            ->setResultsObtained('250 enfants scolarisés à ce jour.')
            ->setShowDonorPublicly(true)
            ->addPartner($partner1);
        $manager->persist($project);

        $article = new Article();
        $article->setTitle('Lancement du plan stratégique 2024-2028')
            ->setSlug('lancement-plan-strategique-2024-2028')
            ->setCategory(ArticleCategory::NEWS)
            ->setContent('L\'ASBL Bon Pasteur a officiellement lancé son plan stratégique 2024-2028.')
            ->setPublishedAt(new \DateTimeImmutable('2026-01-20'))
            ->setIsPublished(true);
        $manager->persist($article);

        $testimonial = new Testimonial();
        $testimonial->setBeneficiaryName('Marie K.')
            ->setRoleOrContext('Bénéficiaire du programme de formation')
            ->setStoryText('Grâce à la formation, j\'ai pu ouvrir ma petite boutique.')
            ->setProject($project)
            ->setFeaturedOnHome(true);
        $manager->persist($testimonial);

        $award = new Award();
        $award->setTitle('Prix de l\'impact communautaire')
            ->setIssuer('Gouvernement provincial du Lualaba')
            ->setYear(2025)
            ->setDescription('Reconnaissance de l\'action sociale de l\'ASBL.');
        $manager->persist($award);

        foreach ([2024, 2025, 2026] as $year) {
            $archiveYear = new ArchiveYear($year);
            $archiveYear->setDescription('Archives de l\'année '.$year);
            $manager->persist($archiveYear);

            $archiveProject = new ArchiveProject();
            $archiveProject->setArchiveYear($archiveYear)
                ->setTitle('Programme éducatif '.$year)
                ->setCode('BP-EDU-'.$year)
                ->setDescription('Suivi administratif et financier du programme éducatif '.$year.'.')
                ->setLinkedPublicProject(2026 === $year ? $project : null);
            $manager->persist($archiveProject);
        }

        $manager->flush();
    }
}
