<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Component\File\AvatarFileHandlerInterface;
use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private readonly AvatarFileHandlerInterface $fileHandler)
    {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['id' => Criteria::ASC]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('id')
            ->add('name')
            ->add('email')
            ->add('createdAt')
        ;
    }

    /**
     * @param User $entityInstance
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->fileHandler->deleteFile($entityInstance->getAvatar());

        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->setLabel('Id'),
            TextField::new('name')->setLabel('Name')->setRequired($pageName === Crud::PAGE_NEW),
            TextField::new('email')->setLabel('Email')->setRequired($pageName === Crud::PAGE_NEW),
            TextField::new('avatar')->setLabel('Avatar')->onlyOnDetail(),
            ArrayField::new('roles')->setLabel('Roles'),
            TextField::new('plainPassword')->setFormType(PasswordType::class)->hideOnIndex()->hideOnDetail()->setRequired($pageName === Crud::PAGE_NEW),
            DateTimeField::new('createdAt')->hideOnForm()->setLabel('Created At'),
            DateTimeField::new('updatedAt')->onlyOnDetail()->setLabel('Updated At'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE])
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static function (Action $action) {
                return $action->setIcon('fa fa-eye');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, static function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, static function (Action $action) {
                return $action->setIcon('fa fa-trash');
            })

            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->reorder(Crud::PAGE_NEW, [Action::SAVE_AND_RETURN, Action::SAVE_AND_ADD_ANOTHER, Action::INDEX])
            ->update(Crud::PAGE_NEW, Action::INDEX, static function (Action $action) {
                return $action->setIcon('fa fa-list');
            })
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, static function (Action $action) {
                return $action->setIcon('fa fa-plus');
            })
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, static function (Action $action) {
                return $action->setIcon('fa fa-plus');
            })

            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->reorder(Crud::PAGE_EDIT, [Action::SAVE_AND_RETURN, Action::SAVE_AND_CONTINUE, Action::INDEX])
            ->update(Crud::PAGE_EDIT, Action::INDEX, static function (Action $action) {
                return $action->setIcon('fa fa-list');
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, static function (Action $action) {
                return $action->setIcon('fa fa-floppy-disk');
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, static function (Action $action) {
                return $action->setIcon('fa fa-floppy-disk');
            })

            ->reorder(Crud::PAGE_DETAIL, [Action::EDIT, Action::DELETE, Action::INDEX])
            ->update(Crud::PAGE_DETAIL, Action::EDIT, static function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, static function (Action $action) {
                return $action->setIcon('fa fa-list');
            })
        ;
    }
}
