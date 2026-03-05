<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LoginCaptchaListener
{
    private RouterInterface $router;
    private HttpClientInterface $client;
    private string $secret;

    public function __construct(RouterInterface $router, HttpClientInterface $client, string $recaptchaSecret)
    {
        $this->router = $router;
        $this->client = $client;
        $this->secret = $recaptchaSecret;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // Only check POST on /login
        if ($request->getPathInfo() === '/login' && $request->isMethod('POST')) {
            $recaptchaToken = $request->request->get('g-recaptcha-response');

            if (!$recaptchaToken || !$this->verifyRecaptcha($recaptchaToken)) {
                // Redirect back with flash message
                $session = $request->getSession();
                if ($session instanceof FlashBagAwareSessionInterface) {
                    $session->getFlashBag()->add('error', 'Please confirm you are not a robot (Captcha required).');
                }
                $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
            }
        }
    }

    private function verifyRecaptcha(string $token): bool
    {
        $response = $this->client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->secret,
                'response' => $token,
            ],
        ]);

        $data = $response->toArray();
        return $data['success'] ?? false;
    }
}
