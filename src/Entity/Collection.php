<?php

namespace App\Entity;

use App\Repository\CollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionRepository::class)]
class Collection extends AbstractSharedEntity
{
    use FavoriteTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var DoctrineCollection<int, PromptTemplate> */
    #[ORM\ManyToMany(targetEntity: PromptTemplate::class, inversedBy: 'collections')]
    private DoctrineCollection $promptTemplates;

    /** @var DoctrineCollection<int, Placeholder> */
    #[ORM\ManyToMany(targetEntity: Placeholder::class, inversedBy: 'collections')]
    private DoctrineCollection $placeholders;

    /** @var DoctrineCollection<int, SharedCollection> */
     #[ORM\OneToMany(mappedBy: "collection", targetEntity: SharedCollection::class)]
    private DoctrineCollection $sharedCollections;

    #[ORM\Column(name: 'is_public', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPublic = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'made_public_by_user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?User $madePublicByUser = null;

    public function __construct()
    {
        $this->promptTemplates = new ArrayCollection();
        $this->placeholders = new ArrayCollection();
        $this->sharedCollections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return DoctrineCollection<int, PromptTemplate>
     */
    public function getPromptTemplates(): DoctrineCollection
    {
        return $this->promptTemplates;
    }

    public function addPromptTemplate(PromptTemplate $promptTemplate): static
    {
        if (!$this->promptTemplates->contains($promptTemplate)) {
            $this->promptTemplates->add($promptTemplate);
        }

        return $this;
    }

    public function removePromptTemplate(PromptTemplate $promptTemplate): static
    {
        $this->promptTemplates->removeElement($promptTemplate);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, Placeholder>
     */
    public function getPlaceholders(): DoctrineCollection
    {
        return $this->placeholders;
    }

    public function addPlaceholder(Placeholder $placeholder): static
    {
        if (!$this->placeholders->contains($placeholder)) {
            $this->placeholders->add($placeholder);
        }

        return $this;
    }

    public function removePlaceholder(Placeholder $placeholder): static
    {
        $this->placeholders->removeElement($placeholder);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, SharedCollection>
     */
    public function getSharedCollections(): DoctrineCollection
    {
        return $this->sharedCollections;
    }

    public function addSharedCollection(SharedCollection $sharedCollection): self
    {
        if (!$this->sharedCollections->contains($sharedCollection)) {
            $this->sharedCollections->add($sharedCollection);
            $sharedCollection->setCollection($this);
        }

        return $this;
    }

    public function removeSharedCollection(SharedCollection $sharedCollection): self
    {
        if ($this->sharedCollections->removeElement($sharedCollection)) {
            if ($sharedCollection->getCollection() === $this) {
                $sharedCollection->setCollection(null);
            }
        }

        return $this;
    }

    public function isSharedWith(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        foreach ($this->getSharedCollections() as $sharedCollection) {
            if ($sharedCollection->getSharedWithUser() === $user) {
                return true;
            }
        }

        return false;
    }

    public function getPermissions(User $user): ?int
    {
        $hasWritePermission = $this->getUser()?->getId() === $user->getId();
        $hasReadPermission = $this->getUser()?->getId() === $user->getId();

        foreach ($this->getSharedCollections() as $sharedCollection) {
            if ($sharedCollection->getSharedWithUser() === $user) {
                $sharedCollectionPermissions = $sharedCollection->getPermissions();
                $hasReadPermission = true;
                if (false === $hasWritePermission) {
                    $hasWritePermission = $sharedCollectionPermissions === EntityWithPermissionsInterface::WRITE_PERMISSION;
                }
            }
        }

        if (true === $hasWritePermission) {
            return EntityWithPermissionsInterface::WRITE_PERMISSION;
        }

        if (true === $hasReadPermission) {
            return EntityWithPermissionsInterface::READ_PERMISSION;
        }

        return null;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(?bool $isPublic): void
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function getMadePublicByUser(): ?User
    {
        return $this->madePublicByUser;
    }

    public function setMadePublicByUser(?User $madePublicByUser): void
    {
        $this->madePublicByUser = $madePublicByUser;
    }
}
