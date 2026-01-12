<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
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

    #[Route('/avatar/{id}', name: 'user_avatar')]
    public function showAvatar(User $user, ImageEncryptionService $encryptionService): Response
    {
        if (!$user->getAvatarData()) {
            throw $this->createNotFoundException('Avatar not found');
        }

        $decryptedData = $encryptionService->decrypt($user->getAvatarData());

        if (!$decryptedData) {
            throw $this->createNotFoundException('Failed to decrypt avatar');
        }

        return new Response($decryptedData, 200, [
            'Content-Type' => $user->getAvatarMimeType() ?: 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
