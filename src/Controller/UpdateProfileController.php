<?php
// src/Controller/UpdateProfileController.php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class UpdateProfileController extends AbstractController
{
    #[Route('/profile/update', name: 'app_update_profile')]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Store previous page in session (only if not already stored)
        if (!$request->getSession()->has('previous_page')) {
            $referer = $request->headers->get('referer');
            if ($referer) {
                $request->getSession()->set('previous_page', $referer);
            }
        }

        // Build the form
        $form = $this->createFormBuilder($user)
            ->add('firstName', TextType::class, ['label' => 'First Name'])
            ->add('lastName', TextType::class, ['label' => 'Last Name'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('previousPage', HiddenType::class, [
                'mapped' => false,
                'data' => $request->getSession()->get('previous_page', $this->generateUrl('app_home')),
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $request->request->get('currentPassword');
            $newPassword = $request->request->get('newPassword');

            if ($currentPassword) {
                if ($passwordHasher->isPasswordValid($user, $currentPassword)) {
                    if ($newPassword) {
                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $newPassword)
                        );
                    }
                } else {
                    $this->addFlash('error', 'Current password is incorrect!');
                    return $this->redirectToRoute('app_update_profile');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully!');

            // Redirect back to previous page
            $previousPage = $form->get('previousPage')->getData();
            $request->getSession()->remove('previous_page'); // clear session
            return $this->redirect($previousPage);
        }

        return $this->render('update-profile/update-profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
