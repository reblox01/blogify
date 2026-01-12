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
    public function show(string $slug, PostRepository $postRepository): Response
    {
        $post = $postRepository->findOneBy(['slug' => $slug]);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        return $this->render('blog/show.html.twig', [
            'post' => $post,
        ]);
    }
}

