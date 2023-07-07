<?php

namespace App\Entity;

use App\Repository\ImagesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImagesRepository::class)]
class Images
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name_img = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Properties $property = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameImg(): ?string
    {
        return $this->name_img;
    }

    public function setNameImg(string $name_img): static
    {
        $this->name_img = $name_img;

        return $this;
    }

    public function getProperty(): ?Properties
    {
        return $this->property;
    }

    public function setProperty(?Properties $property): static
    {
        $this->property = $property;

        return $this;
    }
}
