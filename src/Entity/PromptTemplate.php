<?php

namespace App\Entity;

use App\Repository\PromptTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection as DoctrineCollection;

#[ORM\Entity(repositoryClass: PromptTemplateRepository::class)]
class PromptTemplate extends AbstractSharedEntity
{
    use FavoriteTrait;
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Category $category;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    /** @var DoctrineCollection<int, Collection> */
    #[ORM\ManyToMany(targetEntity: Collection::class, mappedBy: 'promptTemplates')]
    private DoctrineCollection $collections;

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function clone(): PromptTemplate
    {
        $newPromptTemplate = clone $this;
        $newPromptTemplate->setId(null);
        $newPromptTemplate->setCreatedAt(new \DateTimeImmutable());
        $newPromptTemplate->setUpdatedAt(new \DateTimeImmutable());
        $newPromptTemplate->setTitle("Clone of ".$this->getTitle());
        $newPromptTemplate->setIsFavorite(false);
        $newPromptTemplate->setCollections(new ArrayCollection());
        $newPromptTemplate->setCategory(null);

        return $newPromptTemplate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function __construct()
    {
        $this->id = null;
        $this->collections = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
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

    /**
     * @return DoctrineCollection<int, Collection>
     */
    public function getCollections(): DoctrineCollection
    {
        return $this->collections;
    }

    public function addCollection(Collection $collection): self
    {
        if (!$this->collections->contains($collection)) {
            $this->collections[] = $collection;
            $collection->addPromptTemplate($this);
        }

        return $this;
    }

    public function removeCollection(Collection $collection): self
    {
        if ($this->collections->removeElement($collection)) {
            $collection->removePromptTemplate($this);
        }

        return $this;
    }

    public function setCollections(DoctrineCollection $collections): void
    {
        $this->collections = $collections;
    }

    public function isSharedWith(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        foreach ($this->getCollections() as $collection) {
            foreach ($collection->getSharedCollections() as $sharedCollection) {
                if ($sharedCollection->getSharedWithUser() === $user) {
                    return true;
                }
            }
        }

        return false;
    }


    public function getPermissions(User $user): ?int
    {
        $hasWritePermission = $this->getUser()?->getId() === $user->getId();
        $hasReadPermission = $this->getUser()?->getId() === $user->getId();

        foreach ($this->getCollections() as $collection) {
            foreach ($collection->getSharedCollections() as $sharedCollection) {
                if ($sharedCollection->getSharedByUser() === $user) {
                    $hasWritePermission = true;
                }

                if ($sharedCollection->getSharedWithUser() === $user) {
                    $sharedCollectionPermissions = $sharedCollection->getPermissions();
                    $hasReadPermission = true;
                    if (false === $hasWritePermission) {
                        $hasWritePermission = $sharedCollectionPermissions === EntityWithPermissionsInterface::WRITE_PERMISSION;
                    }
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
}
