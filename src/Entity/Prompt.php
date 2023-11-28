<?php

namespace App\Entity;

use App\Repository\PromptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use function Symfony\Component\String\u;

#[ORM\Entity(repositoryClass: PromptRepository::class)]
class Prompt
{
    public const STATUS_CREATED = 'created';
    public const STATUS_IN_RPOGRESS = 'in_progress';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMPLETED = 'completed';

    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: PromptTemplate::class)]
    #[ORM\JoinColumn(name: 'prompt_template_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?PromptTemplate $promptTemplate;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $openaiResponse;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $openaiRawResponse = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contentWithoutPlaceholders;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['default' => self::STATUS_CREATED])]
    private ?string $status;

    public function __construct()
    {
        $this->id = null;
        $this->title = '';
        $this->content = '';
        $this->openaiResponse = '';
        $this->contentWithoutPlaceholders = '';
        $this->status = self::STATUS_CREATED;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function isCreated(): bool
    {
        return self::STATUS_CREATED === $this->getStatus();
    }

    public function isInProgress(): bool
    {
        return self::STATUS_IN_RPOGRESS === $this->getStatus();
    }

    public function isCompleted(): bool
    {
        return self::STATUS_COMPLETED === $this->getStatus();
    }

    public function isFailed(): bool
    {
        return self::STATUS_FAILED === $this->getStatus();
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            self::STATUS_CREATED => 'Created',
            self::STATUS_IN_RPOGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
        };
    }

    public function clone(): Prompt
    {
        $newPrompt = clone $this;
        $newPrompt->setId(null);
        $newPrompt->setCreatedAt(new \DateTimeImmutable());
        $newPrompt->setUpdatedAt(new \DateTimeImmutable());
        $newPrompt->setPromptTemplate(null);
        $newPrompt->setOpenaiResponse(null);
        $newPrompt->setOpenaiRawResponse([]);
        $newPrompt->setContentWithoutPlaceholders(null);
        $newPrompt->setTitle("Clone of ".$this->getTitle());
        $newPrompt->setStatus(self::STATUS_CREATED);

        return $newPrompt;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getTitleOrPieceOfContent(int $length = 100): string
    {
        $title = $this->getTitle();
        if (strlen($title) > 0) {
            return $title;
        }

        return u($this->getContent())->truncate($length, '…', false);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function hasUserAccess(?User $user): bool
    {
        if (null === $user) {
            return false;
        }

        return $user->getId() === $this->getUser()?->getId();
    }

    public function getPromptTemplate(): ?PromptTemplate
    {
        return $this->promptTemplate;
    }

    public function setPromptTemplate(?PromptTemplate $promptTemplate): void
    {
        $this->promptTemplate = $promptTemplate;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getOpenaiResponse(): ?string
    {
        return $this->openaiResponse;
    }

    public function setOpenaiResponse(?string $openaiResponse): void
    {
        $this->openaiResponse = $openaiResponse;
    }

    public function getOpenaiRawResponse(): array
    {
        return $this->openaiRawResponse;
    }

    public function setOpenaiRawResponse(array $openaiRawResponse): void
    {
        $this->openaiRawResponse = $openaiRawResponse;
    }

    public function getContentWithoutPlaceholders(): ?string
    {
        return $this->contentWithoutPlaceholders;
    }

    public function setContentWithoutPlaceholders(?string $contentWithoutPlaceholders): void
    {
        $this->contentWithoutPlaceholders = $contentWithoutPlaceholders;
    }
}
