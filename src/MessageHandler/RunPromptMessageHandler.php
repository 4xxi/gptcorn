<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Component\Prompt\Runner\PromptRunnerInterface;
use App\Entity\Collection;
use App\Entity\Prompt;
use App\Enum\OpenAIModelEnum;
use App\Message\RunPromptMessage;
use App\Repository\CollectionRepository;
use App\Repository\PromptRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RunPromptMessageHandler
{
    public function __construct(
        private PromptRepository $promptRepository,
        private CollectionRepository $collectionRepository,
        private PromptRunnerInterface $promptRunner,
        private HubInterface $hub,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RunPromptMessage $runPromptMessage): void
    {
        $promptId = $runPromptMessage->getPromptId();
        $collectionId = $runPromptMessage->getCollectionId();

        /** @var Prompt|null $prompt */
        $prompt = $this->promptRepository->find($promptId);
        if (null === $prompt) {
            $this->handleError($prompt, sprintf('Could not find prompt with id = %d to run.', $promptId));

            return;
        }

        $collection = null;
        if (null !== $collectionId) {
            /** @var Collection|null $collection */
            $collection = $this->collectionRepository->find($collectionId);
        }

        try {
            $openaiModel = $this->parameterBag->get('openai_model');
            $openaiModelEnum = OpenAIModelEnum::tryFrom($openaiModel) ?? OpenAIModelEnum::GPT4;

            $this->promptRunner->run($prompt, $openaiModelEnum, $collection);
        } catch (\Throwable $throwable) {
            $this->handleError($prompt, sprintf('Failed to run the prompt with id = %d.', $promptId), [
                'data' => ['promptId' => $promptId],
                'error' => $throwable->getMessage(),
            ]);
        }

        try {
            $update = new Update(
                sprintf('/prompt-updated-%d', $promptId),
                json_encode(['id' => $promptId, 'status' => $prompt->getStatus()], JSON_THROW_ON_ERROR)
            );
            $this->hub->publish($update);
        } catch (\Throwable $throwable) {
            $this->logger->error(sprintf('Failed to send update via mercure for the prompt with id = %d.', $promptId), [
                'data' => ['promptId' => $promptId],
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    private function handleError(?Prompt $prompt, string $errorMessage, array $context = []): void
    {
        if (null !== $prompt) {
            $prompt->setStatus(Prompt::STATUS_FAILED);
            $this->promptRepository->save($prompt);
        }

        $this->logger->error($errorMessage, $context);
    }
}
