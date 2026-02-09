<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users', name: 'admin_users_')]
class UserController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $search = $request->query->get('search', '');
        $rolesFilter = $request->query->all('roles');
        $statusFilter = $request->query->get('status', 'all');
        
        // Build query for filtered users
        $qb = $em->getRepository(User::class)->createQueryBuilder('u');
        
        // Search filter
        if (!empty($search)) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Role filter
        if (!empty($rolesFilter)) {
            $qb->andWhere('u.role IN (:roles)')
               ->setParameter('roles', $rolesFilter);
        }
        
        // Status filter
        if ($statusFilter !== 'all') {
            $now = new \DateTime();
            if ($statusFilter === 'banned') {
                $qb->andWhere('u.bannedUntil IS NOT NULL AND u.bannedUntil > :now')
                   ->setParameter('now', $now);
            } elseif ($statusFilter === 'active') {
                $qb->orWhere('u.bannedUntil IS NULL')
                   ->orWhere('u.bannedUntil <= :now');
                $qb->setParameter('now', $now);
            }
        }
        
        // Order by
        $qb->orderBy('u.id', 'DESC');
        
        $users = $qb->getQuery()->getResult();
        
        // Get total users (all users in database)
        $totalUsers = $em->getRepository(User::class)->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Determine filter type for display
        $filterType = 'All Users';
        $filterColor = 'primary'; // Default color
        
        if (!empty($rolesFilter)) {
            if (count($rolesFilter) === 1) {
                $role = $rolesFilter[0];
                $filterType = ucfirst($role) . ' Users';
                
                // Set colors based on role
                if ($role === 'etudiant') {
                    $filterColor = 'info';
                } elseif ($role === 'psychologue') {
                    $filterColor = 'warning';
                } elseif ($role === 'admin') {
                    $filterColor = 'danger';
                }
            } else {
                $filterType = 'Multi-role Users';
                $filterColor = 'secondary';
            }
        } elseif ($statusFilter === 'banned') {
            $filterType = 'Banned Users';
            $filterColor = 'danger';
        } elseif ($statusFilter === 'active') {
            $filterType = 'Active Users';
            $filterColor = 'success';
        } elseif (!empty($search)) {
            $filterType = 'Search Results';
            $filterColor = 'info';
        }

        return $this->render('admin/gestion_user/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'selected_roles' => $rolesFilter,
            'selected_status' => $statusFilter,
            'stats' => [
                'filtered' => count($users), // Number of users after filtering
                'total' => $totalUsers,      // Total users in database
                'filter_type' => $filterType,
                'filter_color' => $filterColor,
            ]
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password before saving
            $user->setPassword($hasher->hashPassword($user, $user->getPassword()));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User created successfully!');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/gestion_user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

   #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Only hash password if a new one was entered
            $newPassword = $form->get('password')->getData();
            if ($newPassword) {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
            }

            $em->flush();

            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('admin_users_index');
        }

        return $this->render('admin/gestion_user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();

            $this->addFlash('success', 'User deleted successfully!');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/ban', name: 'ban', methods: ['POST'])]
    public function ban(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('ban'.$user->getId(), $request->request->get('_token'))) {
            $banUntilStr = $request->request->get('banned_until');
            if ($banUntilStr) {
                $bannedUntil = new \DateTime($banUntilStr);
                $user->setBannedUntil($bannedUntil);
                $em->flush();

                $this->addFlash('success', sprintf(
                    'User %s has been banned until %s.',
                    $user->getEmail(),
                    $bannedUntil->format('Y-m-d H:i')
                ));
            }
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/unban', name: 'unban', methods: ['POST'])]
    public function unban(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('unban'.$user->getId(), $request->request->get('_token'))) {
            $user->setBannedUntil(null);
            $em->flush();

            $this->addFlash('success', sprintf(
                'User %s has been unbanned.',
                $user->getEmail()
            ));
        }

        return $this->redirectToRoute('admin_users_index');
    }
}