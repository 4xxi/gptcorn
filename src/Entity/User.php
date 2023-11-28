<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $name;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::JSON)]
    private array $roles;

    #[ORM\Column(type: Types::STRING)]
    private ?string $password;

    private string $plainPassword = '';

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Collection::class, orphanRemoval: true)]
    private DoctrineCollection $collections;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $avatar;

    /** @var DoctrineCollection<int, Prompt> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Prompt::class, orphanRemoval: true)]
    #[ORM\OrderBy(['updatedAt' => Criteria::DESC])]
    private DoctrineCollection $prompts;

    public function __construct()
    {
        $this->id = null;
        $this->name = null;
        $this->roles = [self::ROLE_USER];
        $this->password = null;
        $this->avatar = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->collections = new ArrayCollection();
        $this->prompts = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getEmail();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function hasRole(string $role): bool
    {
        return true === in_array($role, $this->getRoles(), true);
    }

    public function addRole(string $role): void
    {
        if (true === $this->hasRole($role)) {
            return;
        }

        $this->roles[] = $role;
    }

    public function removeRole(string $role): void
    {
        if (false === $this->hasRole($role)) {
            return;
        }

        foreach ($this->getRoles() as $key => $value) {
            if ($value === $role) {
                unset($this->roles[$key]);
            }
        }

        $this->roles = array_values($this->roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = '';
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $password): void
    {
        $this->plainPassword = (string)$password;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    /**
     * @return DoctrineCollection<int, Collection>
     */
    public function getCollections(): DoctrineCollection
    {
        return $this->collections;
    }

    public function addCollection(Collection $collection): static
    {
        if (!$this->collections->contains($collection)) {
            $this->collections->add($collection);
            $collection->setUser($this);
        }

        return $this;
    }

    public function removeCollection(Collection $collection): static
    {
        if ($this->collections->removeElement($collection)) {
            // set the owning side to null (unless already changed)
            if ($collection->getUser() === $this) {
                $collection->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return DoctrineCollection<int, Prompt>
     */
    public function getPrompts(): DoctrineCollection
    {
        return $this->prompts;
    }

    public function addPrompt(Prompt $prompt): self
    {
        if (!$this->prompts->contains($prompt)) {
            $this->prompts[] = $prompt;
            $prompt->setUser($this);
        }

        return $this;
    }

    public function removePrompt(Prompt $prompt): self
    {
        if ($this->prompts->removeElement($prompt)) {
            // set the owning side to null (unless already changed)
            if ($prompt->getUser() === $this) {
                $prompt->setUser(null);
            }
        }

        return $this;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getUserIdentifier() !== $user->getUserIdentifier()) {
            return false;
        }

        return true;
    }
}
