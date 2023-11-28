<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait FavoriteTrait
{
    #[ORM\Column(options: ['default' => false])]
    private bool $isFavorite = false;

    public function toggleFavorite(): void
    {
        if ($this->isFavorite()) {
            $this->setIsFavorite(false);
        } else {
            $this->setIsFavorite(true);
        }
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function getIsFavorite(): bool
    {
        return $this->isFavorite();
    }

    public function setIsFavorite(bool $isFavorite): static
    {
        $this->isFavorite = $isFavorite;

        return $this;
    }
}
