<?php
// src/Security/AccessTokenHandler.php
namespace App\Security;

use App\Repository\ReaderRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly ReaderRepository $repository,
    ) {
    }

    public function getUserBadgeFrom(string $token): UserBadge
    {
        $accessToken = $this->repository->findOneByValue($token);
        if (null === $accessToken || !$accessToken->isValid()) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return new UserBadge($accessToken->getUserId());
    }
}