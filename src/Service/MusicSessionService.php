<?php

namespace App\Service;

use App\Entity\MusicSession;
use App\Entity\User;
use App\Repository\MusicSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;
use App\Service\DeviceFingerprintService;


class MusicSessionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MusicSessionRepository $repository,
        private DeviceFingerprintService $fingerprintService
    ) {
    }

    // =========================
    // GETTERS
    // =========================

    public function getActiveSession(User $user): ?MusicSession
    {
        return $this->repository->findActiveByUser($user);
    }

    public function findByToken(string $token): ?MusicSession
    {
        return $this->repository->findByToken($token);
    }

    public function getTokenFromRequest(Request $request): ?string
    {
        return $request->headers->get('X-Music-Token');
    }

    public function getSessionFromRequest(Request $request): ?MusicSession
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return null;
        }

        return $this->findByToken($token);
    }

    // =========================
    // SESSION CREATION (SAFE)
    // =========================

    /**
     * Crée une nouvelle session SANS tuer les autres automatiquement.
     * (takeover controlé)
     */
    public function create(User $user, Request $request, ?object $context = null): MusicSession
    {
        $session = new MusicSession();

        $session->setUser($user);
        $session->setToken(Uuid::v4()->toRfc4122());

        // Album optionnel
        if ($context && method_exists($context, 'getAlbum')) {
            $session->setAlbum($context->getAlbum());
        }

        $session->setDeviceName(
            $request->headers->get('Sec-CH-UA-Platform') ?? 'Unknown'
        );

        $session->setUserAgent($request->headers->get('User-Agent'));
        $session->setIpAddress($request->getClientIp());

        $session->setCreatedAt(new \DateTimeImmutable());
        $session->setLastActivity(new \DateTimeImmutable());

        $session->setIsActive(true);
        $session->setIsLocked(false);

        // fingerprint sécurité
        $session->setDeviceFingerprint(
            $this->fingerprintService->generate($request)
        );

        $this->em->persist($session);
        $this->em->flush();

        return $session;
    }

    // =========================
    // TAKEOVER SYSTEM (SAFE)
    // =========================

    /**
     * Demande takeover (NE ferme pas les sessions existantes)
     */
    public function requestTakeover(User $user, MusicSession $session): MusicSession
    {
        $code = (string) random_int(100000, 999999);

        $session->setIsLocked(true);
        $session->setTakeoverCode($code);
        $session->setTakeoverRequestedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $session;
    }

    /**
     * Confirme takeover + remplace session proprement
     */
    public function confirmTakeover(User $user, string $code, Request $request): ?MusicSession
    {
        $session = $this->repository->findOneBy([
            'user' => $user,
            'takeoverCode' => $code,
        ]);

        if (!$session) {
            return null;
        }

        // Code expiré
        if (
            !$session->getTakeoverRequestedAt() ||
            $session->getTakeoverRequestedAt() < new \DateTimeImmutable('-10 minutes')
        ) {
            $session->setTakeoverCode(null);
            $session->setTakeoverRequestedAt(null);

            $this->em->flush();

            return null;
        }

        // Désactiver toutes les anciennes sessions
        $sessions = $this->repository->findAllByUser($user);

        foreach ($sessions as $s) {
            $s->setIsActive(false);
            $s->setIsLocked(false); // facultatif si tu n'utilises plus isLocked
            $s->setTakeoverCode(null);
            $s->setTakeoverRequestedAt(null);
        }

        $this->em->flush();

        // Créer une nouvelle session
        return $this->create($user, $request);
    }

    // =========================
    // LIFECYCLE
    // =========================

    public function touch(MusicSession $session): void
    {
        $session->setLastActivity(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function close(MusicSession $session): void
    {
        $session->setIsActive(false);
        $this->em->flush();
    }

    // =========================
    // VALIDATION
    // =========================

    public function isTokenActive(string $token): bool
    {
        $session = $this->repository->findActiveByToken($token);

        if (!$session) {
            return false;
        }

        if ($session->isLocked()) {
            return false;
        }

        $this->touch($session);

        return true;
    }

    public function isRequestActive(Request $request): bool
    {
        $session = $this->getSessionFromRequest($request);

        if (!$session) {
            return false;
        }

        // if (!$session->isActive() || $session->isLocked()) {
        //     return false;
        // }

        if (!$session->isActive()) {
            return false;
        }

        $this->touch($session);

        return true;
    }

    // =========================
    // SECURITY CHECK
    // =========================

    public function isSuspicious(Request $request, User $user): bool
    {
        $fingerprint = $this->fingerprintService->generate($request);

        $sessions = $this->repository->findAllByUser($user);

        foreach ($sessions as $s) {
            if ($s->getDeviceFingerprint() === $fingerprint) {
                return false;
            }
        }

        return true;
    }

    // =========================
    // UTILITIES
    // =========================

    public function flush(): void
    {
        $this->em->flush();
    }

    public function getRepository(): MusicSessionRepository
    {
        return $this->repository;
    }

    public function lockSessionWithCode(MusicSession $session, string $code): void
    {
        // NE PAS verrouiller ici
        //$session->setIsLocked(true);

        $session->setTakeoverCode($code);
        $session->setTakeoverRequestedAt(new \DateTimeImmutable());

        $this->em->flush();
    }
}