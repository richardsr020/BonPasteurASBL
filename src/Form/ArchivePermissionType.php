<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ArchiveFolder;
use App\Entity\ArchivePermission;
use App\Entity\ArchiveProject;
use App\Entity\User;
use App\Enum\ArchiveFolderPermission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ArchivePermissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach (ArchiveFolderPermission::cases() as $permission) {
            $choices[ucfirst(strtolower(str_replace('_', ' ', $permission->value)))] = $permission->value;
        }

        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'placeholder' => 'Choisir un utilisateur',
            ])
            ->add('archiveFolder', EntityType::class, [
                'class' => ArchiveFolder::class,
                'choice_label' => 'name',
                'placeholder' => 'Aucun dossier',
                'required' => false,
            ])
            ->add('archiveProject', EntityType::class, [
                'class' => ArchiveProject::class,
                'choice_label' => 'title',
                'placeholder' => 'Aucun projet',
                'required' => false,
            ])
            ->add('permissions', ChoiceType::class, [
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArchivePermission::class,
        ]);
    }
}
