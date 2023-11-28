<?php

namespace App\Entity;

use App\Repository\SharedCollectionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SharedCollectionRepository::class)]
class SharedCollection implements EntityWithPermissionsInterface
{
    use TimestampableTrait;
    use SharedPermissionsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Collection::class, inversedBy: 'sharedCollections')]
    #[ORM\JoinColumn(name: 'collection_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Collection $collection = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'shared_by_user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $sharedByUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'shared_with_user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $sharedWithUser = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $permissions = EntityWithPermissionsInterface::READ_PERMISSION;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollection(): ?Collection
    {
        return $this->collection;
    }

    public function setCollection(?Collection $collection): void
    {
        $this->collection = $collection;
    }

    public function getSharedByUser(): ?User
    {
        return $this->sharedByUser;
    }

    public function setSharedByUser(?User $sharedByUser): void
    {
        $this->sharedByUser = $sharedByUser;
    }

    public function getSharedWithUser(): ?User
    {
        return $this->sharedWithUser;
    }

    public function setSharedWithUser(?User $sharedWithUser): void
    {
        $this->sharedWithUser = $sharedWithUser;
    }

    public function getPermissions(): ?int
    {
        return $this->permissions;
    }

    public function setPermissions(?int $permissions): void
    {
        $this->permissions = $permissions;
    }
}
