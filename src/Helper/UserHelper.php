<?php

namespace App\Helper;

use App\Entity\User;

class UserHelper
{
    public function refreshUserAuthToken(User $user): User
    {
        $user->setLastLogin(new \DateTime());
        $user->setAuthToken($this->generateAuthenticationToken());
        return $user;
    }

    private function generateAuthenticationToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}
