<?php

namespace App\Entity;

use App\Repository\PlaceholderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection as DoctrineCollection;

#[ORM\Entity(repositoryClass: PlaceholderRepository::class)]
class Placeholder extends AbstractSharedEntity
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

    /** @var DoctrineCollection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'placeholders')]
    #[ORM\OrderBy(['title' => Criteria::ASC])]
    private DoctrineCollection $categories;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $key;

    #[ORM\Column(type: Types::TEXT)]
    private string $value;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['default' => ''])]
    private string $headline;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['default' => ''])]
    private ?string $description;

    #[ORM\ManyToMany(targetEntity: Collection::class, mappedBy: 'placeholders')]
    private DoctrineCollection $collections;

    public function __construct()
    {
        $this->id = null;
        $this->categories = new ArrayCollection();
        $this->headline = '';
        $this->description = '';
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->collections = new ArrayCollection();
    }

    public function clone(): Placeholder
    {
        $newPlaceholder = clone $this;
        $newPlaceholder->setId(null);
        $newPlaceholder->setCreatedAt(new \DateTimeImmutable());
        $newPlaceholder->setUpdatedAt(new \DateTimeImmutable());
        $newPlaceholder->setCollections(new ArrayCollection());
        $newPlaceholder->setCategories(new ArrayCollection());
        $newPlaceholder->setIsFavorite(false);

        return $newPlaceholder;
    }

    public function __toString(): string
    {
        return $this->getKey();
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return DoctrineCollection<int, Category>
     */
    public function getCategories(): DoctrineCollection
    {
        return $this->categories;
    }

    /**
     * @param DoctrineCollection<int, Category> $categories
     */
    public function setCategories(DoctrineCollection $categories): void
    {
        $this->categories = $categories;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getHeadline(): string
    {
        return $this->headline;
    }

    public function setHeadline(string $headline): void
    {
        $this->headline = $headline;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCollections(): DoctrineCollection
    {
        return $this->collections;
    }

    public function addCollection(Collection $collection): static
    {
        if (!$this->collections->contains($collection)) {
            $this->collections->add($collection);
            $collection->addPlaceholder($this);
        }

        return $this;
    }

    public function removeCollection(Collection $collection): static
    {
        if ($this->collections->removeElement($collection)) {
            $collection->removePlaceholder($this);
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
