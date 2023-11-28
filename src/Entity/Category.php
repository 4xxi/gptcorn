<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** @var Collection<int, Placeholder> */
    #[ORM\ManyToMany(targetEntity: Placeholder::class, mappedBy: 'categories')]
    #[ORM\OrderBy(['isFavorite' => Criteria::DESC, 'updatedAt' => Criteria::DESC])]
    private Collection $placeholders;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    public function __construct()
    {
        $this->id = null;
        $this->placeholders = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection<int, Placeholder>
     */
    public function getPlaceholders(): Collection
    {
        return $this->placeholders;
    }

    /**
     * @param Collection<int, Placeholder> $placeholders
     */
    public function setPlaceholders(Collection $placeholders): void
    {
        $this->placeholders = $placeholders;
    }

    public function addPlaceholder(Placeholder $placeholder): void
    {
        if (!$this->placeholders->contains($placeholder)) {
            $this->placeholders->add($placeholder);
            $placeholder->addCategory($this);
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

}
