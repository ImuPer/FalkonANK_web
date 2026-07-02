<?php

namespace App\Controller;

use App\Service\MusicSessionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/music/session')]
class MusicSessionController extends AbstractController
{
    public function __construct(
        private readonly MusicSessionService $musicSessionService,
        private readonly Security $security
    ) {
    }

    /**
     * Démarre une session de lecture.
     */
    #[Route('/start', name: 'app_music_session_start', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        $user = $this->getUser();
        dd($user);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $currentToken = $this->musicSessionService->getTokenFromRequest($request);

        $oldSession = $this->musicSessionService->getActiveSession($user);

        $alreadyActive = false;
        $device = null;
        $lastActivity = null;

        // Une autre session est active ?
        if (
            $oldSession &&
            $oldSession->isActive() &&
            $oldSession->getToken() !== $currentToken
        ) {
            $alreadyActive = true;
            $device = $oldSession->getDeviceName();
            $lastActivity = $oldSession->getLastActivity()?->format('d/m/Y H:i:s');

            // On ferme immédiatement l'ancienne session
            $this->musicSessionService->close($oldSession);
        }

        // Création de la nouvelle session
        $session = $this->musicSessionService->create($user, $request);

        dd('ROUTE START CALLED');

        return $this->json([
            'success' => true,
            'token' => $session->getToken(),
            'already_active' => $alreadyActive,
            'device' => $device,
            'lastActivity' => $lastActivity
        ]);
    }

    /**
     * Force la prise de contrôle de la lecture.
     */
    #[Route('/force', name: 'app_music_session_force', methods: ['POST'])]
    public function force(Request $request): JsonResponse
    {
        $user = $this->getUser();
        dd($user);
        if (!$user) {
            return $this->json([], 401);
        }

        $this->musicSessionService->closeAll($user);

        $session = $this->musicSessionService->create($user, $request);

        return $this->json([
            'success' => true,
            'token' => $session->getToken()
        ]);
    }

    /**
     * Vérifie que la session est toujours active.
     */
    #[Route('/check', name: 'app_music_session_check', methods: ['GET'])]
    public function check(Request $request): JsonResponse
    {
        return $this->json([
            'active' => $this->musicSessionService->isRequestActive($request)
        ]);
    }

    /**
     * Ferme la session.
     */
    #[Route('/stop', name: 'app_music_session_stop', methods: ['POST'])]
    public function stop(Request $request): JsonResponse
    {
        $session = $this->musicSessionService->getSessionFromRequest($request);

        if (!$session) {
            return $this->json([
                'success' => false
            ], 404);
        }

        $this->musicSessionService->close($session);

        return $this->json([
            'success' => true
        ]);
    }
}