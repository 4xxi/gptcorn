<?php

declare(strict_types=1);

namespace App\Component\OpenAI\ResponseGetter;

use App\Enum\OpenAIModelEnum;
use GuzzleHttp\Client;
use OpenAI;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class OpenAResponseGetter implements OpenAIResponseGetterInterface
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function getResponse(string $promptContent, OpenAIModelEnum $openAIModelEnum): array
    {
        $apiKey = $this->parameterBag->get('openai_api_key');
        $client = OpenAI::factory()
            ->withHttpClient(new Client(['timeout' => 0, 'connect_timeout' => 0]))
            ->withApiKey($apiKey)
            ->make();

        $response =  $client->chat()->create([
            'model' => $openAIModelEnum->value,
            'messages' => [
                ['role' => 'user', 'content' => $promptContent],
            ],
        ]);

        return $response->toArray();
    }
}
