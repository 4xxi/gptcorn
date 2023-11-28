<?php

namespace App\Controller;

use App\Component\Prompt\Runner\PromptRunnerInterface;
use App\Entity\Prompt;
use App\Entity\User;
use App\Form\PromptType;
use App\Repository\PromptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/prompt')]
class PromptController extends AbstractController
{
    #[Route('/{id}/clone', name: 'app_prompt_clone', methods: ['GET'])]
    public function clone(Prompt $prompt, EntityManagerInterface $entityManager): Response
    {
        if (!$prompt->hasUserAccess($this->getUser())) {
            return $this->redirectToRoute('app_default');
        }

        $newPrompt = $prompt->clone();
        $newPrompt->setUser($this->getUser());

        $entityManager->persist($newPrompt);
        $entityManager->flush();

        return $this->redirectToRoute('app_prompt_edit', ['id' => $newPrompt->getId()]);
    }

    #[Route('/{id}/run', name: 'app_prompt_run', methods: ['GET', 'POST'])]
    public function run(
        Prompt $prompt,
        PromptRunnerInterface $promptRunner,
        PromptRepository $promptRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$prompt->hasUserAccess($user)) {
            return $this->redirectToRoute('app_default');
        }

        $promptToRun = $prompt;
        if ($prompt->getStatus() !== Prompt::STATUS_CREATED) {
            $newPrompt = $prompt->clone();
            $newPrompt->setUser($user);
            $newPrompt->setTitle($prompt->getTitle());
            $promptRepository->save($newPrompt);

            $promptToRun = $newPrompt;
        }

        $promptRunner->initiatePromptRun($promptToRun);

        return $this->redirectToRoute('app_prompt_show', ['id' => $promptToRun->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/', name: 'app_prompt_index', methods: ['GET'])]
    public function index(Request $request, PromptRepository $promptRepository, PaginatorInterface $paginator): Response
    {
        $query = $promptRepository->getQueryLatestByUser($this->getUser());
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
        );

        return $this->render(
            'prompt/index.html.twig',
            [
                'prompts' => $pagination,
            ]
        );
    }

    #[Route('/new', name: 'app_prompt_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        PromptRunnerInterface $promptRunner,
    ): Response {
        $prompt = new Prompt();
        $form = $this->createForm(PromptType::class, $prompt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $prompt->setUser($user);
            $prompt->setStatus(Prompt::STATUS_CREATED);
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
                'prompt' => $prompt,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_prompt_show', methods: ['GET'])]
    public function show(Prompt $prompt): Response
    {
        if (!$prompt->hasUserAccess($this->getUser())) {
            return $this->redirectToRoute('app_default');
        }

        return $this->render(
            'prompt/show.html.twig',
            [
                'prompt' => $prompt,
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_prompt_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Prompt $prompt,
        PromptRepository $promptRepository,
        PromptRunnerInterface $promptRunner,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$prompt->hasUserAccess($user)) {
            return $this->redirectToRoute('app_default');
        }

        $form = $this->createForm(PromptType::class, $prompt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $promptRepository->save($prompt);

            if ('Run' === $form->getClickedButton()?->getName()) {
                $newPrompt = $prompt->clone();
                $newPrompt->setStatus(Prompt::STATUS_CREATED);
                $newPrompt->setUser($user);
                $newPrompt->setTitle($prompt->getTitle());
                $promptRepository->save($newPrompt);

                $promptRunner->initiatePromptRun($newPrompt);

                return $this->redirectToRoute('app_prompt_show', ['id' => $newPrompt->getId()], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_prompt_show', ['id' => $prompt->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render(
            'prompt/edit.html.twig',
            [
                'prompt' => $prompt,
                'form' => $form,
            ]
        );
    }

    #[Route('/{id}', name: 'app_prompt_delete', methods: ['POST'])]
    public function delete(Request $request, Prompt $prompt, EntityManagerInterface $entityManager): Response
    {
        if (!$prompt->hasUserAccess($this->getUser())) {
            return $this->redirectToRoute('app_default');
        }

        if ($this->isCsrfTokenValid('delete'.$prompt->getId(), $request->request->get('_token'))) {
            $entityManager->remove($prompt);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_prompt_index', [], Response::HTTP_SEE_OTHER);
    }
}
