<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DynamicContentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['fields'] as $name => $definition) {
            $builder->add($name, $definition['type'], $definition['options'] ?? []);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'fields' => [],
            'attr' => ['class' => 'space-y-4'],
        ]);
        $resolver->setAllowedTypes('fields', 'array');
    }
}
