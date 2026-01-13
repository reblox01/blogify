<?php

namespace App\Controller;

use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Form\PostType;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\ImageEncryptionService;
use App\Form\CommentType;
use App\Entity\Comment;
use App\Repository\CommentRepository;

class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index')]
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findAll();

        return $this->render('blog/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show(string $slug, PostRepository $postRepository, Request $request, CommentRepository $commentRepository, EntityManagerInterface $entityManager): Response
    {
        $post = $postRepository->findOneBy(['slug' => $slug]);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        // Comments form (only processed if user is authenticated)
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user) {
                $comment->setUser($user);
                $comment->setPost($post);
                $entityManager->persist($comment);
                $entityManager->flush();

                $this->addFlash('success', 'Comment posted successfully.');
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            } else {
                $this->addFlash('error', 'You must be signed in to post comments.');
                return $this->redirectToRoute('app_login');
            }
        }

        $comments = $commentRepository->findBy(['post' => $post], ['createdAt' => 'ASC']);

        return $this->render('blog/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'comment_form' => $form->createView(),
        ]);
    }
}

