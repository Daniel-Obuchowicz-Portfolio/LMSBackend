<?php
// src/Service/JwtTokenDecoder.php

namespace App\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class JwtTokenDecoder
{
    private $tokenStorage;
    private $jwtManager;

    public function __construct(TokenStorageInterface $tokenStorage, JWTTokenManagerInterface $jwtManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->jwtManager = $jwtManager;
    }

    public function getUserIdFromToken(): ?int
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $data = $this->jwtManager->decode($token);

        return $data['id'] ?? null;
    }
}
