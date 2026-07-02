<?php

namespace App\Controller;

use App\Service\MusicSessionService;
use App\Service\MusicTakeoverMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/music/session')]
class MusicTakeoverController extends AbstractController
{
    public function __construct(
        private MusicSessionService $sessionService,
        private MusicTakeoverMailer $mailer
    ) {
    }

    #[Route('/takeover/request', name: 'music_takeover_request', methods: ['POST'])]
    public function request(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        $session = $this->sessionService->getActiveSession($user);

        if (!$session) {
            return $this->json(['error' => 'no_session'], 404);
        }

        // 🔥 LOCK SESSION + CODE (SERVICE)
        $code = (string) random_int(100000, 999999);
        $this->sessionService->lockSessionWithCode($session, $code);

        // 📩 EMAIL
        $this->mailer->sendTakeoverCode($user, $code);

        return $this->json([
            'success' => true,
            'message' => 'code_sent'
        ]);
    }

    #[Route('/takeover/confirm', name: 'music_takeover_confirm', methods: ['POST'])]
    public function confirm(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;

        if (!$code) {
            return $this->json(['error' => 'missing_code'], 400);
        }

        // 🔥 FULL LOGIC IN SERVICE
        $newSession = $this->sessionService->confirmTakeover($user, $code, $request);

        if (!$newSession) {
            return $this->json([
                'success' => false,
                'message' => 'invalid_code'
            ], 403);
        }

        return $this->json([
            'success' => true,
            'token' => $newSession->getToken()
        ]);
    }
}