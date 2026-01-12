<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Annotation;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Repository\AnnotationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(UserRepository $userRepository, PostRepository $postRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Statistics
        $totalUsers = $userRepository->count([]);
        $totalPosts = $postRepository->count([]);
        $pendingPosts = $postRepository->count(['status' => 'draft']);
        $publishedPosts = $postRepository->count(['status' => 'published']);

        // Recent posts
        $recentPosts = $postRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalPosts' => $totalPosts,
            'pendingPosts' => $pendingPosts,
            'publishedPosts' => $publishedPosts,
            'recentPosts' => $recentPosts,
        ]);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user->setName($request->request->get('name'));
            $user->setBiography($request->request->get('biography'));

            if ($request->request->get('password')) {
                $user->setPassword($hasher->hashPassword($user, $request->request->get('password')));
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully.');
        }

        return $this->render('admin/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/settings', name: 'settings')]
    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/settings.html.twig');
    }

    #[Route('/users', name: 'users')]
    public function users(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/{id}/role', name: 'user_role', methods: ['POST'])]
    public function updateUserRole(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $role = $request->request->get('role');
        if (in_array($role, ['ROLE_USER', 'ROLE_AUTHOR', 'ROLE_ADMIN'])) {
            $user->setRoles([$role]);
            $em->flush();
            $this->addFlash('success', 'User role updated successfully.');
        }

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('admin_users');
        }

        $em->remove($user);
        $em->flush();
        $this->addFlash('success', 'User deleted successfully.');

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/posts', name: 'posts')]
    public function posts(PostRepository $postRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $paginator = $postRepository->getPaginatedPosts($page, $limit);
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $limit);

        return $this->render('admin/posts/index.html.twig', [
            'posts' => $paginator,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
        ]);
    }

    #[Route('/posts/{id}/status', name: 'post_status', methods: ['POST'])]
    public function updatePostStatus(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $status = $request->request->get('status');

        if ($status === 'deleted') {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Post deleted successfully.');
            return $this->redirectToRoute('admin_posts');
        }

        if (in_array($status, ['draft', 'published', 'rejected'])) {
            $post->setStatus($status);
            $em->flush();
            $this->addFlash('success', 'Post status updated to ' . ucfirst($status));
        }

        return $this->redirectToRoute('admin_posts');
    }

    #[Route('/posts/{id}/preview', name: 'post_preview')]
    public function preview(Post $post): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/posts/preview.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/posts/{id}/annotate', name: 'post_annotate', methods: ['POST'])]
    public function addAnnotation(Post $post, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        $annotation = new Annotation();
        $annotation->setPost($post);
        $annotation->setAuthor($this->getUser());
        $annotation->setContent($data['content'] ?? '');
        $annotation->setSelectedText($data['selectedText'] ?? null);
        $annotation->setContextData($data['contextData'] ?? []);

        $em->persist($annotation);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'annotation' => [
                'id' => $annotation->getId(),
                'content' => $annotation->getContent(),
                'authorName' => $annotation->getAuthor()->getName(),
                'createdAt' => $annotation->getCreatedAt()->format('M d, Y H:i'),
            ]
        ]);
    }

    #[Route('/annotations/{id}/resolve', name: 'annotation_resolve', methods: ['POST'])]
    public function resolveAnnotation(Annotation $annotation, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $annotation->setResolved(true);
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }
}
