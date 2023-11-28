<?php

namespace App\Entity;

use App\Component\CollectionImport\Validator\Constraint\ImportSourceProvided;
use App\Enum\ImportCollectionFileTypeEnum;
use App\Repository\CollectionImportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: CollectionImportRepository::class)]
#[ImportSourceProvided]
class CollectionImport
{
    use TimestampableTrait;

    public const STATUS_CREATED = 'created';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $githubUrl;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $status;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $filePath;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, enumType: ImportCollectionFileTypeEnum::class)]
    private ?ImportCollectionFileTypeEnum $fileType;

    private ?File $fileUpload;

    public function __construct()
    {
        $this->id = null;
        $this->githubUrl = null;
        $this->status = self::STATUS_CREATED;
        $this->filePath = null;
        $this->fileType = null;
        $this->fileUpload = null;
        $this->user = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGithubUrl(): ?string
    {
        return $this->githubUrl;
    }

    public function setGithubUrl(?string $githubUrl): void
    {
        $this->githubUrl = $githubUrl;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getFileUpload(): ?File
    {
        return $this->fileUpload;
    }

    public function setFileUpload(?File $fileUpload): void
    {
        $this->fileUpload = $fileUpload;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getFileType(): ?ImportCollectionFileTypeEnum
    {
        return $this->fileType;
    }

    public function setFileType(?ImportCollectionFileTypeEnum $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}
