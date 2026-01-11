<?php

namespace App\Controller;

use App\Entity\Post;
use App\Service\ImageEncryptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/image/{id}', name: 'post_image')]
    public function show(Post $post, ImageEncryptionService $encryptionService): Response
    {
        if (!$post->getImageData()) {
            throw $this->createNotFoundException('Image not found');
        }

        $decryptedData = $encryptionService->decrypt($post->getImageData());

        if (!$decryptedData) {
            throw $this->createNotFoundException('Failed to decrypt image');
        }

        return new Response($decryptedData, 200, [
            'Content-Type' => $post->getImageMimeType() ?: 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
