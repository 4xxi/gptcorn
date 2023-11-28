<?php

namespace App\Controller;

use App\Component\Placeholder\Cloner\PromptTemplatePlaceholderClonerInterface;
use App\Component\Prompt\Runner\PromptRunnerInterface;
use App\Component\SharedEntity\PermissionsResolver\SharedEntityPermissionResolverInterface;
use App\Entity\Collection;
use App\Entity\Prompt;
use App\Entity\PromptTemplate;
use App\Entity\User;
use App\Form\PromptTemplateType;
use App\Form\RunPromptFromTemplateType;
use App\Repository\PlaceholderRepository;
use App\Repository\PromptTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/prompt_template')]
class PromptTemplateController extends AbstractController
{
    #[Route('/{id}/toggleFavorite', name: 'app_prompt_template_favorite', methods: ['GET'])]
    public function toggleFavorite(
        PromptTemplate $promptTemplate,
        EntityManagerInterface $em,
        Request $request,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $promptTemplate)) {
            return $this->redirectToRoute('app_default');
        }

        $promptTemplate->toggleFavorite();
        $em->persist($promptTemplate);
        $em->flush();

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_prompt_template_index');
    }

    #[Route('/{id}/clone', name: 'app_prompt_template_clone', methods: ['GET'])]
    public function clone(
        PromptTemplate $promptTemplate,
        EntityManagerInterface $em,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
        PromptTemplatePlaceholderClonerInterface $promptTemplatePlaceholderCloner,
    ): Response {
        if (false === $sharedEntityPermissionResolver->canRead($this->getUser(), $promptTemplate)) {
            return $this->redirectToRoute('app_default');
        }

        $newPromptTemplate = $promptTemplate->clone();
        $newPromptTemplate->setUser($this->getUser());

        $em->persist($newPromptTemplate);
        $em->flush();

        $promptTemplatePlaceholderCloner->clone($promptTemplate);

        return $this->redirectToRoute('app_prompt_template_edit', ['id' => $newPromptTemplate->getId()]);
    }

    #[Route('/{id}/collection/{collectionId}/run', name: 'app_prompt_template_in_collection_run', methods: ['GET', 'POST'])]
    #[ParamConverter('collection', class: Collection::class, options: ['id' => 'collectionId'])]
    public function runInCollection(
        Request $request,
        PromptTemplate $promptTemplate,
        Collection $collection,
        EntityManagerInterface $entityManager,
        PromptRunnerInterface $promptRunner,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (false === $sharedEntityPermissionResolver->canRead($user, $promptTemplate)) {
            return $this->redirectToRoute('app_default');
        }

        $prompt = new Prompt();
        $prompt->setUser($user);
        $prompt->setPromptTemplate($promptTemplate);
        $prompt->setContent($promptTemplate->getContent());

        $form = $this->createForm(RunPromptFromTemplateType::class, $prompt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($prompt);
            $entityManager->flush();

            if ('Run' === $form->getClickedButton()?->getName()) {
                $promptRunner->initiatePromptRun($prompt, $collection);
            }

            return $this->redirectToRoute('app_prompt_show', ['id' => $prompt->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'prompt/new.html.twig',
            [
                'placeholder' => $prompt,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}/run', name: 'app_prompt_template_run', methods: ['GET', 'POST'])]
    public function runTemplate(
        Request $request,
        PromptTemplate $promptTemplate,
        EntityManagerInterface $entityManager,
        PromptRunnerInterface $promptRunner,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (false === $sharedEntityPermissionResolver->canRead($user, $promptTemplate)) {
            return $this->redirectToRoute('app_default');
        }

        $prompt = new Prompt();
        $prompt->setUser($user);
        $prompt->setPromptTemplate($promptTemplate);
        $prompt->setContent($promptTemplate->getContent());

        $form = $this->createForm(RunPromptFromTemplateType::class, $prompt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($prompt);
            $entityManager->flush();

            if ('Run' === $form->getClickedButton()?->getName()) {
                $promptRunner->initiatePromptRun($prompt);
            }

            return $this->redirectToRoute('app_prompt_show', ['id' => $prompt->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'prompt/new.html.twig',
            [
                'placeholder' => $prompt,
                'form' => $form,
            ]
        );
    }

    #[Route('/', name: 'app_prompt_template_index', methods: ['GET'])]
    public function index(
        Request $request,
        PromptTemplateRepository $promptTemplateRepository,
        PaginatorInterface $paginator,
    ): Response {
        $query = $promptTemplateRepository->getQueryByUser($this->getUser());
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
        );

        return $this->render(
            'prompt_template/index.html.twig',
            [
                'prompt_templates' => $pagination,
            ]
        );
    }

    #[Route('/new', name: 'app_prompt_template_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        PlaceholderRepository $placeholderRepository,
    ): Response {
        $promptTemplate = new PromptTemplate();
        $form = $this->createForm(PromptTemplateType::class, $promptTemplate, ['is_owner' => true]);
        $form->handleRequest($request);

        $placeholders = $placeholderRepository->getAvailable($this->getUser());

        if ($form->isSubmitted() && $form->isValid()) {
            $promptTemplate->setUser($this->getUser());
            $entityManager->persist($promptTemplate);
            $entityManager->flush();

            return $this->redirectToRoute('app_prompt_template_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'prompt_template/new.html.twig',
            [
                'prompt_template' => $promptTemplate,
                'form' => $form,
                'placeholders' => $placeholders,
            ]
        );
    }

    #[Route('/{id}', name: 'app_prompt_template_show', methods: ['GET'])]
    public function show(
        PromptTemplate $promptTemplate,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canRead($this->getUser(), $promptTemplate)) {
            return $this->redirectToRoute('app_default');
        }

        return $this->render(
            'prompt_template/show.html.twig',
            [
                'prompt_template' => $promptTemplate,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_prompt_template_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        PromptTemplate $promptTemplate,
        EntityManagerInterface $entityManager,
        PlaceholderRepository $placeholderRepository,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $promptTemplate)) {
            return $this->redirectToRoute('app_default');
        }

        $placeholders = $placeholderRepository->getAvailable($this->getUser());
        $form = $this->createForm(PromptTemplateType::class, $promptTemplate, [
            'is_owner' => $promptTemplate->isOwner($this->getUser()),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_prompt_template_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'prompt_template/edit.html.twig',
            [
                'prompt_template' => $promptTemplate,
                'form' => $form,
                'placeholders' => $placeholders,
            ]
        );
    }

    #[Route('/{id}/collection/{collectionId}/edit', name: 'app_prompt_template_in_collection_edit', methods: ['GET', 'POST'])]
    #[ParamConverter('collection', class: Collection::class, options: ['id' => 'collectionId'])]
    public function editInCollection(
        Request $request,
        PromptTemplate $promptTemplate,
        Collection $collection,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $promptTemplate)) {
            return $this->redirectToRoute('app_default');
        }

        $placeholders = $collection->getPlaceholders();
        $isOwner = $promptTemplate->isOwner($this->getUser());
        $form = $this->createForm(PromptTemplateType::class, $promptTemplate, [
            'is_owner' => $isOwner,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                $isOwner ? 'app_collection_show' : 'app_shared_collection_show',
                ['id' => $collection->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render(
            'prompt_template/edit.html.twig',
            [
                'prompt_template' => $promptTemplate,
                'form' => $form,
                'placeholders' => $placeholders,
                'collection' => $collection,
                'isOwner' => $isOwner,
            ]
        );
    }

    #[Route('/{id}', name: 'app_prompt_template_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        PromptTemplate $promptTemplate,
        EntityManagerInterface $entityManager,
        SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver
    ): Response {
        if (false === $sharedEntityPermissionResolver->canWrite($this->getUser(), $promptTemplate)) {
            return $this->redirectToRoute('app_default');
        }

        if ($this->isCsrfTokenValid('delete'.$promptTemplate->getId(), $request->request->get('_token'))) {
            $entityManager->remove($promptTemplate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_prompt_template_index', [], Response::HTTP_SEE_OTHER);
    }
}
