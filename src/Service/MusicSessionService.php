<?php

namespace App\Service;

use App\Entity\MusicSession;
use App\Entity\User;
use App\Repository\MusicSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

class MusicSessionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MusicSessionRepository $repository
    ) {
    }

    /**
     * Retourne la session active de l'utilisateur.
     */
    public function getActiveSession(User $user): ?MusicSession
    {
        return $this->repository->findActiveByUser($user);
    }

    /**
     * Vérifie si une autre session est déjà active.
     */
    public function hasAnotherActiveSession(User $user, ?string $token = null): bool
    {
        $session = $this->repository->findActiveByUser($user);

        if (!$session) {
            return false;
        }

        if ($token !== null && $session->getToken() === $token) {
            return false;
        }

        return true;
    }

    /**
     * Crée une nouvelle session de lecture.
     */
    public function create(User $user, Request $request): MusicSession
    {
        $this->repository->deactivateAll($user);

        $session = new MusicSession();

        $session->setUser($user);
        $session->setToken(Uuid::v4()->toRfc4122());

        $session->setDeviceName(
            $request->headers->get('Sec-CH-UA-Platform')
            ?? 'Unknown'
        );

        $session->setUserAgent(
            $request->headers->get('User-Agent')
        );

        $session->setIpAddress(
            $request->getClientIp()
        );

        $session->setCreatedAt(new \DateTimeImmutable());
        $session->setLastActivity(new \DateTimeImmutable());
        $session->setIsActive(true);

        $this->em->persist($session);
        $this->em->flush();

        dd('CREATE SESSION CALLED');

        return $session;
    }

    /**
     * Met à jour l'activité de la session.
     */
    public function touch(MusicSession $session): void
    {
        $session->setLastActivity(new \DateTimeImmutable());

        $this->em->flush();
    }

    /**
     * Désactive une session.
     */
    public function close(MusicSession $session): void
    {
        $session->setIsActive(false);

        $this->em->flush();
    }

    /**
     * Désactive toutes les sessions de l'utilisateur.
     */
    public function closeAll(User $user): void
    {
        $this->repository->deactivateAll($user);
    }

    /**
     * Recherche une session par son token.
     */
    public function findByToken(string $token): ?MusicSession
    {
        return $this->repository->findByToken($token);
    }

    /**
     * Vérifie si le token est toujours valide.
     */
    public function isTokenActive(string $token): bool
    {
        $session = $this->repository->findActiveByToken($token);

        if (!$session) {
            return false;
        }

        $this->touch($session);

        return true;
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

    public function isRequestActive(Request $request): bool
    {
        $session = $this->getSessionFromRequest($request);

        if (!$session) {
            return false;
        }

        if (!$session->isActive()) {
            return false;
        }

        $this->touch($session);

        return true;
    }
}