<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserProfileType extends AbstractType
{
    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxSize = $this->parameterBag->get('avatar_max_file_size');

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'disabled' => true,
                'attr' => ['autocomplete' => 'username'],
            ])
            ->add('name', TextType::class, ['label' => 'Name', 'attr' => ['autocomplete' => 'name']])
            ->add('avatarFile', FileType::class, [
                'label' => 'Avatar',
                'mapped' => false,
                'required' => false,
                'help' => sprintf('Upload an avatar image file. Maximum size: %s.', $maxSize),
                'constraints' => [
                    new File(maxSize: $maxSize),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'New Password',
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('Save', SubmitType::class, ['label' => 'Save'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );
    }
}
