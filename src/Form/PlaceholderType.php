<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Placeholder;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PlaceholderType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'headline',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'SMM post LinkedIn act as proofreader',
                    ],
                    'help' => 'A brief description used when you create or edit a prompt template'
                ]
            )
            ->add('key', TextType::class, [
                'help' => 'This key will be used in prompt templates as a reference. Please use only lowercase letters, numbers, and underscores. Spaces and curly braces {} are not allowed.',
                'attr' => [
                    'placeholder' => 'smm_post_linkedin_act_as_proofreader',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[a-z0-9_]+$/',
                        'message' => 'The key must consist of lowercase letters, numbers, and underscores only.',
                    ]),
                ],
            ])
            ->add('value', TextareaType::class, ['attr' => ['rows' => 15]])
            ->add('description', TextareaType::class, ['required' => false])
            ->add(
                'categories',
                EntityType::class,
                [
                    'class' => Category::class,
                    'multiple' => true,
                    'query_builder' => function (CategoryRepository $categoryRepository) {
                        return $categoryRepository->getQueryBulderByUser($this->security->getUser());
                    },
                    'required' => false,
                ]
            )
            ->add(
                'isFavorite',
                CheckboxType::class,
                [
                    'label' => 'Favorite?',
                    'required' => false,
                    'disabled' => !$options['is_owner'],
                ]
            )
            ->add('Save', SubmitType::class, ['label' => 'Save'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Placeholder::class,
                'is_owner' => false,
            ]
        );
    }
}
