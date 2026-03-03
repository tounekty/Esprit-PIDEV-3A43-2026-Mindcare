<?php
// src/Controller/AuthController.php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthController extends AbstractController
{
    // ---------------- ROOT REDIRECT ----------------
    #[Route('/', name: 'app_root')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    // ---------------- LOGIN ----------------
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'recaptcha_site_key' => $_ENV['RECAPTCHA_SITE_KEY'] ?? '',
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method will be intercepted by Symfony.');
    }

    // ---------------- REGISTER ----------------
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, MailerInterface $mailer): Response
    {
        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $confirmPassword = $request->request->get('confirmPassword');

        if ($password !== $confirmPassword) {
            $this->addFlash('error', 'Passwords do not match!');
            return $this->redirectToRoute('app_login');
        }

        if (strlen($password) < 6) {
            $this->addFlash('error', 'Password must be at least 6 characters!');
            return $this->redirectToRoute('app_login');
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $this->addFlash('error', 'Email already registered!');
            return $this->redirectToRoute('app_login');
        }

        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        
        // User account not verified by default
        $user->setIsVerified(false);
        
        // Generate unique verification token - simple format for URL compatibility
        $randomBytes = random_bytes(32);
        $verificationToken = bin2hex($randomBytes);
        $user->setVerificationToken($verificationToken);
        
        $user->setRole('etudiant');
        $user->setCreatedAt(new \DateTime());

        $em->persist($user);
        $em->flush();

        // Send verification email with link
        try {
            $verificationLink = $this->generateUrl('app_verify_email_link', [
                'token' => $verificationToken
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            
            $mailer->send(
                (new Email())
                    ->from('no-reply@mindcare.tn')
                    ->to($user->getEmail())
                    ->subject('✉️ Verify Your Email - MindCare')
                    ->html($this->renderView('emails/verify_email.html.twig', [
                        'name' => $user->getFirstName() ?: 'User',
                        'verificationLink' => $verificationLink
                    ]))
            );
        } catch (\Exception $e) {
            error_log('Mailer Error: ' . $e->getMessage());
            $this->addFlash('error', 'Failed to send verification email. Please try again.');
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Registration successful! A verification link has been sent to your email. Please click it to activate your account.');
        return $this->redirectToRoute('app_login');
    }

    // ---------------- FORGOT PASSWORD (SEND CODE) ----------------
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Generate 6-digit code
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                
                $user->setResetCode($code);
                // FIX: Use DateTime not DateTimeImmutable to match your entity
                $user->setResetCodeExpiresAt(new \DateTime('+15 minutes'));
                $em->flush();

                // Send the email
                try {
                    $mailer->send(
                        (new Email())
                            ->from('no-reply@mindcare.tn')
                            ->to($user->getEmail())
                            ->subject('🔐 Your Password Reset Code - MindCare')
                            ->html($this->renderView('emails/reset_code.html.twig', [
                                'code' => $code,
                                'name' => $user->getFirstName() ?: 'User'
                            ]))
                    );
                } catch (\Exception $e) {
                    error_log('Mailer Error: ' . $e->getMessage());
                    $this->addFlash('error', 'Failed to send email. Please try again.');
                    return $this->redirectToRoute('app_forgot_password');
                }

                // Store email in session for verification
                $request->getSession()->set('reset_email', $email);
                $this->addFlash('success', 'A 6-digit code has been sent to your email.');
                
                // FIX: This MUST redirect to verify-code
                return $this->redirectToRoute('app_verify_code');
            }

            // Always show same message to prevent email enumeration
            $this->addFlash('success', 'If an account exists with this email, a reset code will be sent.');
            // FIX: Add redirect here too
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('login/send_code.html.twig', [
            'recaptcha_site_key' => $_ENV['RECAPTCHA_SITE_KEY'] ?? ''
        ]);
    }

    // ---------------- VERIFY CODE ----------------
    #[Route('/verify-code', name: 'app_verify_code', methods: ['GET', 'POST'])]
    public function verifyCode(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $email = $session->get('reset_email');

        // If no email in session, redirect to forgot password
        if (!$email) {
            $this->addFlash('error', 'Please start the password reset process first.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');
            
            // Remove spaces and trim
            $code = preg_replace('/\s+/', '', $code);

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'User not found.');
                $session->remove('reset_email');
                return $this->redirectToRoute('app_forgot_password');
            }

            // FIX: Use DateTime not DateTimeImmutable
            $now = new \DateTime();
            $expiresAt = $user->getResetCodeExpiresAt();

            // Check if code exists and is valid
            if ($user->getResetCode() === $code && $expiresAt && $expiresAt > $now) {
                // Clear the code but keep email in session
                $user->setResetCode(null);
                $user->setResetCodeExpiresAt(null);
                $em->flush();

                $this->addFlash('success', 'Code verified successfully! Please enter your new password.');
                return $this->redirectToRoute('app_reset_password');
            }

            $this->addFlash('error', 'Invalid or expired code. Please request a new one.');
            
            // If code expired, clear it
            if ($expiresAt && $expiresAt <= $now) {
                $user->setResetCode(null);
                $user->setResetCodeExpiresAt(null);
                $em->flush();
            }
        }

        return $this->render('login/verify_code.html.twig', [
            'email' => $email
        ]);
    }

    // ---------------- RESET PASSWORD ----------------
    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $session = $request->getSession();
        $email = $session->get('reset_email');

        if (!$email) {
            $this->addFlash('error', 'Invalid reset request. Please start over.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirmPassword');

            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->redirectToRoute('app_reset_password');
            }

            if (strlen($password) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters.');
                return $this->redirectToRoute('app_reset_password');
            }

            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'User not found.');
                $session->remove('reset_email');
                return $this->redirectToRoute('app_forgot_password');
            }

            // Update password
            $user->setPassword($hasher->hashPassword($user, $password));
            
            // Clear reset fields
            $user->setResetCode(null);
            $user->setResetCodeExpiresAt(null);
            
            $em->flush();

            // Clear session
            $session->remove('reset_email');

            $this->addFlash('success', '✅ Password reset successful! You can now login with your new password.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('login/reset_password.html.twig');
    }

    // ---------------- VERIFY EMAIL LINK ----------------
    #[Route('/verify-email/{token}', name: 'app_verify_email_link', methods: ['GET'], requirements: ['token' => '.+'])]
    public function verifyEmailLink(string $token, EntityManagerInterface $em): Response
    {
        // Find user by verification token
        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', '❌ Invalid verification link.');
            return $this->redirectToRoute('app_login');
        }

        // Check if already verified
        if ($user->isVerified()) {
            $this->addFlash('success', '✅ Your account is already verified!');
            return $this->redirectToRoute('app_login');
        }

        // Mark account as verified
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $em->flush();

        $this->addFlash('success', '✅ Email verified successfully! You can now login.');
        return $this->redirectToRoute('app_login');
    }

    // ---------------- RESEND CODE ----------------
    #[Route('/resend-code', name: 'app_resend_code', methods: ['POST'])]
    public function resendCode(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $session = $request->getSession();
        $email = $session->get('reset_email');

        if (!$email) {
            return new JsonResponse(['error' => 'No email in session'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            // Generate new 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $user->setResetCode($code);
            // FIX: Use DateTime not DateTimeImmutable
            $user->setResetCodeExpiresAt(new \DateTime('+15 minutes'));
            $em->flush();

            // Send the email
            try {
                $mailer->send(
                    (new Email())
                        ->from('no-reply@mindcare.tn')
                        ->to($user->getEmail())
                        ->subject('🔄 New Password Reset Code - MindCare')
                        ->html($this->renderView('emails/reset_code.html.twig', [
                            'code' => $code,
                            'name' => $user->getFirstName() ?: 'User'
                        ]))
                );
                
                return new JsonResponse(['success' => 'New code sent!']);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Failed to send email'], 500);
            }
        }

        return new JsonResponse(['error' => 'User not found'], 404);
    }
}