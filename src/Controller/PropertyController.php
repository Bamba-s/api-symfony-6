<?php

namespace App\Controller;

use App\Entity\Images;
use App\Entity\Properties;
use App\Entity\Videos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;


#[Route('/api/v1', name: 'app_property')]
class PropertyController extends AbstractController
{
    private $entityManager;
    private $propertiesRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/properties/create', methods: ["POST"], name: 'app_property_create')]
    public function create(Request $request, SluggerInterface $slugger): Response
    {
        $entityManager = $this->entityManager;
    
        // Récupérer les données envoyées sous forme JSON ou form-data
        if ($request->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($request->getContent(), true);
        } else {
            $data = $request->request->all();
        }
    
        // Vérifier si la propriété existe déjà
        $existingProperty = $entityManager->getRepository(Properties::class)
            ->findOneBy([
                'title' => $data['title'],
                'description' => $data['description'],
                'property_address' => $data['property_address'],
            ]);
    
        if ($existingProperty !== null) {
            return new JsonResponse(['error' => 'Cette propriété existe déjà.'], Response::HTTP_CONFLICT);
        }
    
        // Créer une nouvelle propriété
        $property = new Properties();
        $property->setTitle($data['title']);
        $property->setDescription($data['description']);
        $property->setPropertyAddress($data['property_address']);
        $property->setSalePrice($data['sale_price']);
        $property->setRentPrice($data['rent_price']);
        
        // Récupérer les fichiers téléchargés
        $uploadedFiles = $request->files->get('images');
    
        if (is_array($uploadedFiles)) {
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFile) {
                    $image = new Images();
    
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
    
                    try {
                        $uploadedFile->move(
                            $this->getParameter('property_images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'enregistrement de l\'image.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
    
                    $image->setNameImg('uploads/images/' . $newFilename);
                    
    
                    // Associer l'image à la propriété
                    $property->addImage($image);
                }
            }
        } else {
            // Gérer le cas où aucun fichier n'a été téléchargé avec la clé "images"
            return new JsonResponse(['error' => 'Aucun fichier d\'image n\'a été uploadé.'], Response::HTTP_BAD_REQUEST);
        }

            // Récupérer les fichiers de vidéos
    $uploadedVideos = $request->files->get('videos');

    if (is_array($uploadedVideos)) {
        foreach ($uploadedVideos as $uploadedVideo) {
            if ($uploadedVideo instanceof UploadedFile) {
                $video = new Videos();

                $originalFilename = pathinfo($uploadedVideo->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedVideo->guessExtension();

                try {
                    $uploadedVideo->move(
                        $this->getParameter('property_videos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'enregistrement de la vidéo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $video->setVideoUrl('uploads/videos/' . $newFilename);
                $video->setProperty($property);

                // Associer la vidéo à la propriété
                $property->addVideo($video);
            }
        }
    }
    
        // Persistez l'entité "Properties"
        $entityManager->persist($property);
        $entityManager->flush();
    
        return new JsonResponse(['success' => 'Propriété enregistrée avec succès !'], Response::HTTP_OK);
    }
    

    #[Route('/properties', methods: ["GET"], name: 'app_property_list', )]
    public function list(Request $request): JsonResponse
    {
        $entityManager = $this->entityManager;

        $currentPage = $request->query->getInt('current_page', 1);
        $itemsPerPage = $request->query->getInt('items_per_page', 10);

        $repository = $entityManager->getRepository(Properties::class);

        $totalItems = $repository->count([]);

        $totalPages = ceil($totalItems / $itemsPerPage);

        $offset = ($currentPage - 1) * $itemsPerPage;

        $properties = $repository->findBy([], null, $itemsPerPage, $offset);

        $responseData = [
            'current_page' => $currentPage,
            'data' => [],
            'totalItems' => $totalItems,
            'itemsPerPage' => $itemsPerPage,
            'totalPages' => $totalPages,
        ];
        foreach ($properties as $property) {
            $propertyData = [
                'id' => $property->getId(),
                'title' => $property->getTitle(),
                'description' => $property->getDescription(),
                'property_address' => $property->getPropertyAddress(),
                'sale_price' => $property->getSalePrice(),
                'rent_price' => $property->getRentPrice(),
                'images' => [],
                'videos' => [],
            ];
              // Images
            foreach ($property->getImages() as $image) {
                $propertyData['images'][] = [
                    //'id' => $image->getId(),
                    'name_img' => $image->getNameImg(),
                ];
            }
            //Vidéos
            foreach ($property->getVideos() as $video) {
                $propertyData['videos'][] = [
                    'video_url' => $video->getVideoUrl(),
                ];
            }

            $responseData['data'][] = $propertyData;

        }

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/properties/{id}', methods: ["GET"], name: 'app_property_show')]
    public function show(int $id): JsonResponse
    {
        $entityManager = $this->entityManager;

        // Recherche de la propriété
        $property = $entityManager->getRepository(Properties::class)->find($id);

        // Vérification si la propriété existe
        if ($property === null) {
            return new JsonResponse(['error' => "Aucune propriété trouvée avec l'ID: $id"], Response::HTTP_NOT_FOUND);
        }

        // Construction des données de réponse
        $responseData = [
            'id' => $property->getId(),
            'title' => $property->getTitle(),
            'description' => $property->getDescription(),
            'property_address' => $property->getPropertyAddress(),
            'sale_price' => $property->getSalePrice(),
            'rent_price' => $property->getRentPrice(),
            'images' => [],
            'videos' => [],
        ];

        foreach ($property->getImages() as $image) {
            $responseData['images'][] = [
                'name_img' => $image->getNameImg(),
            ];
        }

        foreach ($property->getVideos() as $video) {
            $responseData['videos'][] = [
                'video_url' => $video->getVideoUrl(),
            ];
        }

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/properties/{id}', methods: ["DELETE"], name: 'app_property_delete')]
    public function delete(int $id): JsonResponse
    {
        $entityManager = $this->entityManager;

        // Recherche de la propriété à supprimer
        $property = $entityManager->getRepository(Properties::class)->find($id);

        // Vérification si la propriété existe
        if ($property === null) {
            return new JsonResponse(['error' => "Aucune propriété trouvée avec ID:$id"], Response::HTTP_NOT_FOUND);
        }

        // Suppression de la propriété
        $entityManager->remove($property);
        $entityManager->flush();

        return new JsonResponse(['success' => 'La propriété a été supprimée avec succès.'], Response::HTTP_OK);
    }

    #[Route('/properties/{id}', methods: ["PUT"], name: 'app_property_update')]
    public function update(int $id, Request $request, SluggerInterface $slugger): JsonResponse
    {
        $entityManager = $this->entityManager;

        // Recherche de la propriété à mettre à jour
        $property = $entityManager->getRepository(Properties::class)->find($id);

        // Vérification si la propriété existe
        if ($property === null) {
            return new JsonResponse(['error' => "Aucune propriété trouvée avec l'ID: $id"], Response::HTTP_NOT_FOUND);
        }

        // Récupération des données de la requête
        if ($request->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($request->getContent(), true);
        } else {
            $data = $request->request->all();
        }
    
        // Mise à jour des propriétés de la propriété
        // $property = new Properties();
        // $property->setTitle($data['title']);
        // $property->setDescription($data['description']);
        // $property->setPropertyAddress($data['property_address']);
        // $property->setSalePrice($data['sale_price']);
        // $property->setRentPrice($data['rent_price']);
        $property = new Properties();
        $property->setTitle($data['title']);
        $property->setDescription($data['description']);
        $property->setPropertyAddress($data['property_address']);
        $property->setSalePrice($data['sale_price']);
        $property->setRentPrice($data['rent_price']);

        // Suppression des images existantes
        foreach ($property->getImages() as $image) {
            $entityManager->remove($image);
        }

        // Suppression des vidéos existantes
        foreach ($property->getVideos() as $video) {
            $entityManager->remove($video);
        }

        // Ajout des nouvelles images
        if (isset($data['images'])) {
            $uploadedFiles = $request->files->get('images');

            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFile) {
                    $image = new Images();

                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    try {
                        $uploadedFile->move(
                            $this->getParameter('property_images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'enregistrement de l\'image.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    $image->setNameImg('uploads/' . $newFilename);
                    $image->setProperty($property);
                    $property->addImage($image);
                }
            }
        }
       // Récupérer les fichiers de vidéos

        $uploadedVideos = $request->files->get('videos');
        if (is_array($uploadedVideos)) {
            foreach ($uploadedVideos as $uploadedVideo) {
                if ($uploadedVideo instanceof UploadedFile) {
                    $video = new Videos();

                    $originalFilename = pathinfo($uploadedVideo->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedVideo->guessExtension();

                    try {
                        $uploadedVideo->move(
                            $this->getParameter('property_videos_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'enregistrement de la vidéo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    $video->setVideoUrl('uploads/videos/' . $newFilename);
                    $video->setProperty($property);

                    // Associer la vidéo à la propriété
                    $property->addVideo($video);
                }
            }
        }

        // Persistez l'entité "Properties"
        $entityManager->persist($property);
        $entityManager->flush();

        return new JsonResponse(['success' => 'La propriété a été mise à jour avec succès !'], Response::HTTP_OK);
    }

    // public function update(Request $request, SluggerInterface $slugger, int $id): Response
    // {
    //     $entityManager = $this->entityManager;

    //     // Récupérer la propriété existante
    //     $property = $entityManager->getRepository(Properties::class)->find($id);

    //     if ($property === null) {
    //         return new JsonResponse(['error' => 'Propriété introuvable.'], Response::HTTP_NOT_FOUND);
    //     }

    //     // Récupérer les données envoyées sous forme JSON ou form-data
    //     if ($request->headers->get('Content-Type') === 'application/json') {
    //         $data = json_decode($request->getContent(), true);
    //     } else {
    //         $data = $request->request->all();
    //     }

    //     // Mettre à jour les propriétés de la propriété
    //     $property->setTitle($data['title']);
    //     $property->setDescription($data['description']);
    //     $property->setPropertyAddress($data['property_address']);
    //     $property->setSalePrice($data['sale_price']);
    //     $property->setRentPrice($data['rent_price']);

    //     // Récupérer les fichiers téléchargés
    //     $uploadedFiles = $request->files->get('images');

    //     if (is_array($uploadedFiles)) {
    //         foreach ($uploadedFiles as $uploadedFile) {
    //             if ($uploadedFile instanceof UploadedFile) {
    //                 $image = new Images();

    //                 $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
    //                 $safeFilename = $slugger->slug($originalFilename);
    //                 $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

    //                 try {
    //                     $uploadedFile->move(
    //                         $this->getParameter('property_images_directory'),
    //                         $newFilename
    //                     );
    //                 } catch (FileException $e) {
    //                     return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'enregistrement de l\'image.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //                 }

    //                 $image->setNameImg('uploads/images/' . $newFilename);

    //                 // Associer l'image à la propriété
    //                 $property->addImage($image);
    //             }
    //         }
    //     } else {
    //         // Gérer le cas où aucun fichier n'a été téléchargé avec la clé "images"
    //         return new JsonResponse(['error' => 'Aucun fichier d\'image n\'a été uploadé.'], Response::HTTP_BAD_REQUEST);
    //     }

    //     // Récupérer les fichiers de vidéos
    //     $uploadedVideos = $request->files->get('videos');

    //     if (is_array($uploadedVideos)) {
    //         foreach ($uploadedVideos as $uploadedVideo) {
    //             if ($uploadedVideo instanceof UploadedFile) {
    //                 $video = new Videos();

    //                 $originalFilename = pathinfo($uploadedVideo->getClientOriginalName(), PATHINFO_FILENAME);
    //                 $safeFilename = $slugger->slug($originalFilename);
    //                 $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedVideo->guessExtension();

    //                 try {
    //                     $uploadedVideo->move(
    //                         $this->getParameter('property_videos_directory'),
    //                         $newFilename
    //                     );
    //                 } catch (FileException $e) {
    //                     return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'enregistrement de la vidéo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //                 }

    //                 $video->setVideoUrl('uploads/videos/' . $newFilename);
    //                             $video->setProperty($property);

    //                 // Associer la vidéo à la propriété
    //                 $property->addVideo($video);
    //             }
    //         }
    //     }

    //     // Persistez les modifications de la propriété
    //     $entityManager->flush();

    //     return new JsonResponse(['success' => 'Propriété mise à jour avec succès !'], Response::HTTP_OK);
    // }

}