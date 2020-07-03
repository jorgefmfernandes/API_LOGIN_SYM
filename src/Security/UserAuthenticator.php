<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use ReallySimpleJWT\Encode;
use ReallySimpleJWT\Jwt as ReallySimpleJWTJwt;
use ReallySimpleJWT\Parse;
use ReallySimpleJWT\Token;
use ReallySimpleJWT\Validate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Guard\Token\GuardTokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class UserAuthenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private $entityManager;
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function supports(Request $request) {
        // return $request->headers->has('authorization');
        return true;
    }

    public function getCredentials(Request $request) {
        $authorization = $request->headers->get('authorization');

        if($authorization) {
            $token = explode(" ", $authorization);
            $token = $token[1];

            $credentials = [
                "token" => $token,
                "request" => $request
            ];
    
            return $credentials;

        } else {
            throw new CustomUserMessageAuthenticationException('Nenhum token enviado.');
        }

    }

    public function getUser($credentials, UserProviderInterface $userProvider) {

        if($credentials['token'] == null) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['token' => $credentials['token']]);
        
        if(!$user) {
            throw new CustomUserMessageAuthenticationException('Token Inválido.');
        }

        $payload = Token::getPayload($credentials['token'], $_ENV['JWT_SECRET']);
        $expiracyDate = $payload['exp'];

        $dateDiff = $expiracyDate - time();
        if($dateDiff < 0) {                                     // Token expirado
            throw new CustomUserMessageAuthenticationException('Token expirado.');
            // return null;
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user) {
        return true;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string {
        return $credentials['password'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse ($data, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null) {
        $data = [
            // you might translate this message
            'message' => 'Necessita de autenticação'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);

    }

    public function supportsRememberMe() {
        return false;
    }
}
