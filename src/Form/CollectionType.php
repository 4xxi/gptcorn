<?php

namespace App\Form;

use App\Entity\Collection;
use App\Entity\Placeholder;
use App\Entity\PromptTemplate;
use App\Entity\User;
use App\Repository\PlaceholderRepository;
use App\Repository\PromptTemplateRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly PromptTemplateRepository $promptTemplateRepository,
        private readonly PlaceholderRepository $placeholderRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Collection $collection */
        $collection = $options['data'];

        /** @var User $user */
        $user = $this->security->getUser();

        $builder
            ->add('title')
            ->add('description')
            ->add(
                'promptTemplates',
                EntityType::class,
                [
                    'class' => PromptTemplate::class,
                    'multiple' => true,
                    'query_builder' => function () use ($user, $collection) {
                        return $this->getPromptTemplateQueryBuilder($user, $collection);
                    },
                    'required' => false,
                ]
            )
            ->add(
                'placeholders',
                EntityType::class,
                [
                    'class' => Placeholder::class,
                    'multiple' => true,
                    'query_builder' => function () use ($user, $collection) {
                        return $this->getPlaceholderQueryBuilder($user, $collection);
                    },
                    'required' => false,
                ]
            )
            ->add('isFavorite')
            ->add(
                'isFavorite',
                CheckboxType::class,
                [
                    'label' => 'Favorite?',
                    'required' => false,
                    'disabled' => !$collection->isOwner($user),
                ]
            )
            ->add('Save', SubmitType::class, ['label' => 'Save'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Collection::class,
            ]
        );
    }

    private function getPromptTemplateQueryBuilder(User $user, Collection $collection): QueryBuilder
    {
        if (null === $collection->getId()) {
            return $this->promptTemplateRepository->getQueryBuilderByUser($user);
        }

        $isOwner = $collection->isOwner($user);
        if (true === $isOwner) {
            return $this->promptTemplateRepository->getQueryBuilderByUser($user);
        }

        return $this->promptTemplateRepository->filterByUserAndSortByTitle($user, $collection);
    }

    private function getPlaceholderQueryBuilder(User $user, Collection $collection): QueryBuilder
    {
        if (null === $collection->getId()) {
            return $this->placeholderRepository->getQueryBuilderByUser($user);
        }

        $isOwner = $collection->isOwner($user);
        if (true === $isOwner) {
            return $this->placeholderRepository->getQueryBuilderByUser($user);
        }

        return $this->placeholderRepository->filterByUserAndSortByTitle($user, $collection);
    }
}
