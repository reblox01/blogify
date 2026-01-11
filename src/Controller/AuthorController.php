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
            /** @var UploadedFile $image */
            $image = $form->get('imageFile')->getData();

            $post->setSlug(strtolower($slugger->slug($post->getTitle())) . '-' . uniqid());

            if ($image) {
                $newFilename = uniqid() . '-' . $image->getClientOriginalName();
                try {
                    $image->move($this->getParameter('kernel.project_dir') . '/public/images', $newFilename);
                    $post->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image');
                }
            }

            $post->setUser($this->getUser());
            $post->setStatus('draft'); // Default to draft

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
}
