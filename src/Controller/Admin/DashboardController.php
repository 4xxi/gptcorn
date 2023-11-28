<?php

namespace App\Controller\Admin;

use App\Entity\Collection;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator)
    {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->redirect($this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('GPTCORN Admin')
            ->generateRelativeUrls();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Users');
        yield MenuItem::linkToCrud('Users list', 'fa-solid fa-user-gear', User::class);
        yield MenuItem::linkToCrud('Add user', 'fas fa-plus', User::class)->setAction(Crud::PAGE_NEW);
        yield MenuItem::section('Collections');
        yield MenuItem::linkToCrud('Collections list', 'fas fa-layer-group', Collection::class);
        yield MenuItem::section('Application');
        yield MenuItem::linkToRoute('Application', 'fas fa-home', 'app_default');
    }
}
