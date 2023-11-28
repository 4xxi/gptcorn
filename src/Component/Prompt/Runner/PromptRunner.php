<?php

declare(strict_types = 1);

namespace App\Component\Prompt\Runner;

use App\Component\OpenAI\ResponseGetter\OpenAIResponseGetterInterface;
use App\Component\Placeholder\Replacer\PlaceholderReplacerInterface;
use App\Entity\Collection;
use App\Entity\Prompt;
use App\Enum\OpenAIModelEnum;
use App\Message\RunPromptMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class PromptRunner implements PromptRunnerInterface
{
    public function __construct(
        private OpenAIResponseGetterInterface $openAIResponseGetter,
        private PlaceholderReplacerInterface $placeholderReplacer,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function run(
        Prompt $prompt,
        OpenAIModelEnum $openAIModelEnum,
        ?Collection $collection = null,
    ): void {
        $contentWithoutPlaceholders = $this->placeholderReplacer->replace($prompt, $collection);
        $openaiResponse = $this->openAIResponseGetter->getResponse($contentWithoutPlaceholders, $openAIModelEnum);

        $user = $prompt->getUser();
        if (null === $user) {
            throw new \LogicException(sprintf('Prompt with id = %s must have a user', $prompt->getId()));
        }

        $prompt->setStatus(Prompt::STATUS_COMPLETED);
        $prompt->setContent($contentWithoutPlaceholders);
        $prompt->setContentWithoutPlaceholders($contentWithoutPlaceholders);
        $prompt->setOpenaiRawResponse($openaiResponse);
        $prompt->setOpenaiResponse($openaiResponse['choices'][0]['message']['content'] ?? '');

        $this->entityManager->flush();
    }

    public function initiatePromptRun(Prompt $prompt, ?Collection $collection = null): void
    {
        $prompt->setStatus(Prompt::STATUS_IN_RPOGRESS);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new RunPromptMessage((int) $prompt->getId(), $collection?->getId()));
    }
}
