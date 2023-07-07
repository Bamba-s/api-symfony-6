<?php

namespace App\Entity;

use App\Repository\VideosRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideosRepository::class)]
class Videos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $video_url = null;

    #[ORM\ManyToOne(inversedBy: 'videos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Properties $property = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVideoUrl(): ?string
    {
        return $this->video_url;
    }

    public function setVideoUrl(string $video_url): static
    {
        $this->video_url = $video_url;

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
