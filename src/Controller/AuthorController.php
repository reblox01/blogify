<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\ImageEncryptionService;
use App\Entity\User;

#[Route('/author', name: 'author_')]
class AuthorController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(PostRepository $postRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUTHOR');

        $user = $this->getUser();
        $posts = $postRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        // Stats
        $draftCount = 0;
        $publishedCount = 0;
        $rejectedCount = 0;

        foreach ($posts as $post) {
            match ($post->getStatus()) {
                'draft' => $draftCount++,
                'published' => $publishedCount++,
                'rejected' => $rejectedCount++,
                default => null,
            };
        }

        return $this->render('author/dashboard.html.twig', [
            'posts' => $posts,
            'draftCount' => $draftCount,
            'publishedCount' => $publishedCount,
            'rejectedCount' => $rejectedCount,
        ]);
    }

    #[Route('/post/new', name: 'post_new')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, ImageEncryptionService $encryptionService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUTHOR');

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Handle Base64 Cropped Image
            $croppedImage = $request->request->get('cropped_image');
            if ($croppedImage) {
                $imageData = $this->processBase64Image($croppedImage);
                if ($imageData) {
                    $post->setImageData($encryptionService->encrypt($imageData['data']));
                    $post->setImageMimeType($imageData['mimeType']);
                }
            } else {
                /** @var UploadedFile $image */
                $image = $form->get('imageFile')->getData();
                if ($image) {
                    $data = file_get_contents($image->getPathname());
                    $post->setImageData($encryptionService->encrypt($data));
                    $post->setImageMimeType($image->getClientMimeType());
                }
            }

            $post->setSlug(strtolower($slugger->slug($post->getTitle())) . '-' . uniqid());
            $post->setUser($this->getUser());
            $post->setStatus('draft');

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('author_dashboard');
        }

        return $this->render('author/post/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/{id}/edit', name: 'post_edit')]
    public function edit(Post $post, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, ImageEncryptionService $encryptionService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUTHOR');

        if ($post->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own posts.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Base64 Cropped Image
            $croppedImage = $request->request->get('cropped_image');
            if ($croppedImage) {
                $imageData = $this->processBase64Image($croppedImage);
                if ($imageData) {
                    $post->setImageData($encryptionService->encrypt($imageData['data']));
                    $post->setImageMimeType($imageData['mimeType']);
                }
            } else {
                /** @var UploadedFile $image */
                $image = $form->get('imageFile')->getData();
                if ($image) {
                    $data = file_get_contents($image->getPathname());
                    $post->setImageData($encryptionService->encrypt($data));
                    $post->setImageMimeType($image->getClientMimeType());
                }
            }

            $em->flush();

            $this->addFlash('success', 'Post updated successfully!');
            return $this->redirectToRoute('author_dashboard');
        }

        return $this->render('author/post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post
        ]);
    }

    #[Route('/post/{id}/delete', name: 'post_delete', methods: ['POST'])]
    public function delete(Post $post, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUTHOR');

        if ($post->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own posts.');
        }

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Post deleted successfully.');
        return $this->redirectToRoute('author_dashboard');
    }

    #[Route('/profile', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $em, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher, ImageEncryptionService $encryptionService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUTHOR');
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user->setName($request->request->get('name'));
            $user->setBiography($request->request->get('biography'));

            if ($request->request->get('password')) {
                $user->setPassword($hasher->hashPassword($user, $request->request->get('password')));
            }

            // Handle Base64 Cropped Avatar
            $croppedAvatar = $request->request->get('cropped_avatar');
            if ($croppedAvatar) {
                $imageData = $this->processBase64Image($croppedAvatar);
                if ($imageData) {
                    $user->setAvatarData($encryptionService->encrypt($imageData['data']));
                    $user->setAvatarMimeType($imageData['mimeType']);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully.');
        }

        return $this->render('author/profile.html.twig', [
            'user' => $user,
        ]);
    }

    private function processBase64Image(string $base64String): ?array
    {
        if (preg_match('/^data:(image\/\w+);base64,/', $base64String, $matches)) {
            $mimeType = $matches[1];
            $data = substr($base64String, strpos($base64String, ',') + 1);
            $decodedData = base64_decode($data);

            if ($decodedData === false) {
                return null;
            }

            return [
                'data' => $decodedData,
                'mimeType' => $mimeType
            ];
        }
        return null;
    }
}
