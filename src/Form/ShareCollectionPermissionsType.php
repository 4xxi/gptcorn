<?php

declare(strict_types = 1);

namespace App\Form;

use App\Entity\EntityWithPermissionsInterface;
use App\Entity\SharedCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShareCollectionPermissionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'mapped' => false,
                'label' => 'Email',
                'disabled' => true,
                'data' => $options['email_default'],
            ])
            ->add('permissions', ChoiceType::class, [
                'choices' => [
                    'Read only' => EntityWithPermissionsInterface::READ_PERMISSION,
                    'Edit' => EntityWithPermissionsInterface::WRITE_PERMISSION,
                ],
            ])
            ->add('Edit', SubmitType::class, ['label' => 'Edit permissions'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => SharedCollection::class,
                'email_default' => null,
            ]
        );
    }
}
