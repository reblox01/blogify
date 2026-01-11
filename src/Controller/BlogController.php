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

    #[Route('/blog/create', name: 'blog_create')]
    public function create(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $form = $this->createForm(PostType::class);
        return $this->render('blog/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/blog/store', name: 'blog_store', methods: ['POST'])]
    public function store(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, ImageEncryptionService $encryptionService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $form = $this->createForm(PostType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $image */
            $image = $form->get('imageFile')->getData();

            $post = $form->getData();
            // generate unique slug
            $baseSlug = strtolower($slugger->slug($post->getTitle()));
            $slug = $baseSlug;
            $i = 1;
            while ($em->getRepository(Post::class)->findOneBy(['slug' => $slug])) {
                $slug = $baseSlug . '-' . $i++;
            }
            $post->setSlug($slug);
            if ($image) {
                $data = file_get_contents($image->getPathname());
                $post->setImageData($encryptionService->encrypt($data));
                $post->setImageMimeType($image->getClientMimeType());
            }
            $post->setUser($this->getUser());
            $em->persist($post);
            $em->flush();
        }

        return $this->redirectToRoute('blog_index');
    }

    #[Route('/blog/{slug}/edit', name: 'blog_edit')]
    public function edit(string $slug, PostRepository $postRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $post = $postRepository->findOneBy(['slug' => $slug]);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        $form = $this->createForm(PostType::class, $post);
        return $this->render('blog/edit.html.twig', ['post' => $post, 'form' => $form->createView()]);
    }

    #[Route('/blog/{slug}/update', name: 'blog_update', methods: ['POST'])]
    public function update(string $slug, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, PostRepository $postRepository, ImageEncryptionService $encryptionService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $post = $postRepository->findOneBy(['slug' => $slug]);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $image */
            $image = $form->get('imageFile')->getData();
            $post->setSlug(strtolower($slugger->slug($post->getTitle())));
            if ($image) {
                $data = file_get_contents($image->getPathname());
                $post->setImageData($encryptionService->encrypt($data));
                $post->setImageMimeType($image->getClientMimeType());
            }
            $em->flush();
        }

        return $this->redirectToRoute('blog_index');
    }

    #[Route('/blog/{slug}/delete', name: 'blog_delete', methods: ['POST'])]
    public function delete(string $slug, EntityManagerInterface $em, PostRepository $postRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $post = $postRepository->findOneBy(['slug' => $slug]);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }
        $em->remove($post);
        $em->flush();

        return $this->redirectToRoute('blog_index');
    }
}

