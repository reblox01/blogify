<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $pendingPosts = $postRepository->count(['status' => 'draft']); // Assuming 'draft' acts as pending for now, or add 'pending' status
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
    public function posts(PostRepository $postRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/posts/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/posts/{id}/status', name: 'post_status', methods: ['POST'])]
    public function updatePostStatus(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $status = $request->request->get('status');
        if (in_array($status, ['draft', 'published', 'rejected'])) {
            $post->setStatus($status);
            $em->flush();
            $this->addFlash('success', 'Post status updated to ' . ucfirst($status));
        }

        return $this->redirectToRoute('admin_posts');
    }
}
