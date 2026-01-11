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
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AUTHOR');

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Handle Base64 Cropped Image
            $croppedImage = $request->request->get('cropped_image');
            if ($croppedImage) {
                $filename = $this->saveBase64Image($croppedImage);
                if ($filename) {
                    $post->setImagePath($filename);
                }
            } else {
                /** @var UploadedFile $image */
                $image = $form->get('imageFile')->getData();
                if ($image) {
                    $newFilename = uniqid() . '-' . $image->getClientOriginalName();
                    try {
                        $image->move($this->getParameter('kernel.project_dir') . '/public/images', $newFilename);
                        $post->setImagePath($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image');
                    }
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
    public function edit(Post $post, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
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
                $filename = $this->saveBase64Image($croppedImage);
                if ($filename) {
                    $post->setImagePath($filename);
                }
            } else {
                /** @var UploadedFile $image */
                $image = $form->get('imageFile')->getData();
                if ($image) {
                    $newFilename = uniqid() . '-' . $image->getClientOriginalName();
                    try {
                        $image->move($this->getParameter('kernel.project_dir') . '/public/images', $newFilename);
                        $post->setImagePath($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image');
                    }
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

    private function saveBase64Image(string $base64String): ?string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
            $data = substr($base64String, strpos($base64String, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                return null;
            }

            $data = base64_decode($data);
            if ($data === false) {
                return null;
            }

            $filename = uniqid() . '.' . $type;
            $path = $this->getParameter('kernel.project_dir') . '/public/images/' . $filename;

            file_put_contents($path, $data);
            return $filename;
        }
        return null;
    }
}
