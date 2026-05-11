<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\FaceLoginAuthenticator;
use App\Service\CompreFaceClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class FaceIdController extends AbstractController
{
    #[Route('/profile/face/enable', name: 'app_face_enable', methods: ['POST'])]
    public function enable(
        Request $request,
        CompreFaceClient $client,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->getJsonBody($request);
        if (!$this->isCsrfTokenValid('face_id', $data['_token'] ?? '')) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_BAD_REQUEST);
        }

        $base64 = $this->normalizeBase64($data['image'] ?? '');
        if (!$base64) {
            return new JsonResponse(['error' => 'Image is required.'], Response::HTTP_BAD_REQUEST);
        }

        $subject = $user->getFaceIdSubject() ?: 'user-' . $user->getId();

        try {
            $client->deleteFacesBySubject($subject);
        } catch (\Throwable $e) {
            // Best-effort cleanup to avoid mixing old examples.
        }

        try {
            $client->addFaceExample($subject, $base64);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Face enrollment failed: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $user->setFaceIdSubject($subject);
        $user->setFaceIdEnabled(true);
        $em->flush();

        return new JsonResponse(['enabled' => true, 'subject' => $subject]);
    }

    #[Route('/profile/face/disable', name: 'app_face_disable', methods: ['POST'])]
    public function disable(
        Request $request,
        CompreFaceClient $client,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->getJsonBody($request);
        if (!$this->isCsrfTokenValid('face_id', $data['_token'] ?? '')) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_BAD_REQUEST);
        }

        $subject = $user->getFaceIdSubject();
        if ($subject) {
            try {
                $client->deleteFacesBySubject($subject);
            } catch (\Throwable $e) {
                // Ignore delete failures; we still disable locally.
            }
        }

        $user->setFaceIdEnabled(false);
        $em->flush();

        return new JsonResponse(['enabled' => false]);
    }

    #[Route('/login/face', name: 'app_face_login', methods: ['POST'])]
    public function login(
        Request $request,
        CompreFaceClient $client,
        EntityManagerInterface $em,
        UserAuthenticatorInterface $userAuthenticator,
        FaceLoginAuthenticator $authenticator
    ): JsonResponse {
        $data = $this->getJsonBody($request);
        
        // CSRF validation is optional for public endpoints with no session
        // Try to validate if token is provided, but don't fail if it's missing
        $token = $data['_token'] ?? '';
        if ($token && !$this->isCsrfTokenValid('face_login', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_BAD_REQUEST);
        }

        $base64 = $this->normalizeBase64($data['image'] ?? '');
        if (!$base64) {
            return new JsonResponse(['error' => 'Image is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $client->recognize($base64);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Face recognition failed.'], Response::HTTP_BAD_REQUEST);
        }

        $match = $client->extractBestMatch($result);
        if (!$match || $match['similarity'] < $client->getMinSimilarity()) {
            return new JsonResponse(['error' => 'Face not recognized.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->findUserBySubject($em, $match['subject']);
        if (!$user || !$user->isFaceIdEnabled()) {
            return new JsonResponse(['error' => 'Face ID is not enabled for this user.'], Response::HTTP_UNAUTHORIZED);
        }

        $userAuthenticator->authenticateUser($user, $authenticator, $request);

        return new JsonResponse([
            'redirect' => $this->generateUrl('app_redirect'),
        ]);
    }

    private function getJsonBody(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        return is_array($data) ? $data : [];
    }

    private function normalizeBase64(string $image): ?string
    {
        $image = trim($image);
        if ($image === '') {
            return null;
        }

        if (str_contains($image, ',')) {
            $parts = explode(',', $image, 2);
            $image = $parts[1] ?? '';
        }

        return $image !== '' ? $image : null;
    }

    private function findUserBySubject(EntityManagerInterface $em, string $subject): ?User
    {
        $user = $em->getRepository(User::class)->findOneBy(['faceIdSubject' => $subject]);
        if ($user) {
            return $user;
        }

        if (preg_match('/^user-(\d+)$/', $subject, $matches)) {
            return $em->getRepository(User::class)->find((int) $matches[1]);
        }

        return null;
    }
}
