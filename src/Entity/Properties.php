<?php

namespace App\Entity;

use App\Repository\PropertiesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertiesRepository::class)]
class Properties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $property_address = null;

    #[ORM\Column(nullable: false)]
    private ?float $sale_price = null;

    #[ORM\Column(nullable: false)]
    private ?float $rent_price = null;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Images::class, cascade:["persist","remove"])]
    private Collection $images;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Videos::class, cascade:["persist","remove"])]
    private Collection $videos;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->videos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

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

    public function getPropertyAddress(): ?string
    {
        return $this->property_address;
    }

    public function setPropertyAddress(?string $property_address): static
    {
        $this->property_address = $property_address;

        return $this;
    }

    public function getSalePrice(): ?float
    {
        return $this->sale_price;
    }

    public function setSalePrice(?float $sale_price): static
    {
        $this->sale_price = $sale_price;

        return $this;
    }

    public function getRentPrice(): ?float
    {
        return $this->rent_price;
    }

    public function setRentPrice(?float $rent_price): static
    {
        $this->rent_price = $rent_price;

        return $this;
    }

    /**
     * @return Collection<int, Images>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Images $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProperty($this);
        }

        return $this;
    }

    public function removeImage(Images $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getProperty() === $this) {
                $image->setProperty(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Videos>
     */
    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(Videos $video): static
    {
        if (!$this->videos->contains($video)) {
            $this->videos->add($video);
            $video->setProperty($this);
        }

        return $this;
    }

    public function removeVideo(Videos $video): static
    {
        if ($this->videos->removeElement($video)) {
            // set the owning side to null (unless already changed)
            if ($video->getProperty() === $this) {
                $video->setProperty(null);
            }
        }

        return $this;
    }
}
