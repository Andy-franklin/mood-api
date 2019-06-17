<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Class LoginAuthenticator
 *
 * @package App\Security
 */
class LoginAuthenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * LoginAuthenticator constructor.
     *
     * @param EntityManagerInterface       $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    )
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        return 'register' !== $request->attributes->get('_route');
    }

    /**
     * @param Request $request
     *
     * @return array|mixed
     */
    public function getCredentials(Request $request)
    {
        $credentials = [
            'email'      => $request->request->get('email'),
            'password'   => $request->request->get('password'),
            'token' => $request->headers->get('x-auth-token'),
        ];

        return $credentials;
    }

    /**
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return User|null|object|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        if (isset($credentials['token']) && null !== $credentials['token']) {
            /** @var User $user */
            $user = $userRepository->findOneBy(['authToken' => $credentials['token']]);

            if (!$user) {
                throw new CustomUserMessageAuthenticationException('Invalid authentication token.');
            }

            return $user;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Email could not be found.');
        }

        return $user;
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        if (isset($credentials['token']) && null !== $credentials['token']) {
            //expire token after 30mins
            $authTokenExpiry = new \DateTimeImmutable('-1 hour');
            if ($user->getLastLogin()->getTimestamp() < $authTokenExpiry->getTimestamp()) {
                throw new CustomUserMessageAuthenticationException('User token has expired.');
            }
        }

        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse([
            'message' => 'Authentication Required'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
