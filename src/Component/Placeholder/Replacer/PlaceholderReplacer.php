<?php

declare(strict_types = 1);

namespace App\Component\Placeholder\Replacer;

use App\Entity\Collection;
use App\Entity\Placeholder;
use App\Entity\Prompt;
use App\Repository\PlaceholderRepository;
use Psr\Log\LoggerInterface;

final readonly class PlaceholderReplacer implements PlaceholderReplacerInterface
{
    public function __construct(private LoggerInterface $logger, private PlaceholderRepository $placeholderRepository)
    {
    }

    public function replace(Prompt $prompt, ?Collection $collection = null): string
    {
        $placeholders = $this->getPlaceholders($prompt, $collection);

        $placeholdersKeyValueMap = [];
        foreach ($placeholders as $placeholder) {
            $placeholdersKeyValueMap[sprintf('{%s}', $placeholder->getKey())] = $placeholder->getValue();
        }

        $this->logger->debug(sprintf('Prompt text with placeholders: %s', $prompt->getContent()));

        $contentWithoutPlaceholders = str_replace(
            array_keys($placeholdersKeyValueMap),
            array_values($placeholdersKeyValueMap),
            $prompt->getContent()
        );

        $this->logger->debug(sprintf('Prompt text with replaced placeholders: %s', $contentWithoutPlaceholders));

        return $contentWithoutPlaceholders;
    }

    /**
     * @return Placeholder[]
     */
    private function getPlaceholders(Prompt $prompt, ?Collection $collection = null): array
    {
        if (null === $collection) {
            return $this->placeholderRepository->getByPrompt($prompt);
        }

        return $collection->getPlaceholders()->toArray();
    }
}
