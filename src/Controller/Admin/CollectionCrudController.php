<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\File\AvatarFileHandlerInterface;
use App\Entity\Collection;
use App\Entity\EntityWithPermissionsInterface;
use App\Entity\SharedCollection;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class CollectionCrudController extends AbstractCrudController
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Collection::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['id' => Criteria::ASC]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('id')
            ->add('title')
            ->add(EntityFilter::new('user'))
            ->add('isPublic')
            ->add(EntityFilter::new('madePublicByUser'))
            ->add('createdAt')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->setLabel('Id'),
            TextField::new('title')->setLabel('Title')->setRequired(false),
            AssociationField::new('user')->setLabel('Owner')->autocomplete(),
            TextField::new('description')->setLabel('Description')->hideOnIndex()->setRequired(false),
            BooleanField::new('isPublic')->setLabel('Is public')->setRequired(false),
            AssociationField::new('madePublicByUser')->setLabel('Made public by')->autocomplete(),
            DateTimeField::new('createdAt')->hideOnForm()->setLabel('Created At'),
            DateTimeField::new('updatedAt')->onlyOnDetail()->setLabel('Updated At'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static function (Action $action) {
                return $action->setIcon('fa fa-eye');
            })

            ->reorder(Crud::PAGE_DETAIL, [Action::EDIT, Action::DELETE, Action::INDEX])
            ->update(Crud::PAGE_DETAIL, Action::EDIT, static function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, static function (Action $action) {
                return $action->setIcon('fa fa-list');
            })

            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
        ;
    }

    /**
     * @param mixed $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Collection $entityInstance */
        if (true === $entityInstance->isPublic()) {
            $alreadySharedWithUserIds = [];
            foreach ($entityInstance->getSharedCollections() as $sharedCollection) {
                $alreadySharedWithUserIds[] = $sharedCollection->getSharedWithUser()?->getId();
            }

            $users = $this->userRepository->findAll();
            foreach ($users as $user) {
                if (in_array($user->getId(), $alreadySharedWithUserIds, true)) {
                    continue;
                }

                $sharedCollection = new SharedCollection();
                $sharedCollection->setCollection($entityInstance);
                $sharedCollection->setSharedWithUser($user);
                $sharedCollection->setSharedByUser($this->getUser());
                $sharedCollection->setPermissions(EntityWithPermissionsInterface::READ_PERMISSION);
                $entityManager->persist($sharedCollection);
            }

            $entityInstance->setMadePublicByUser($this->getUser());
        }

        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}
