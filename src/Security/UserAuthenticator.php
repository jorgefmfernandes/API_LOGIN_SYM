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
        // return self::LOGIN_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST');

        return $request->headers->has('authorization');
    }

    public function getCredentials(Request $request) {
        // $credentials = [
        //     'username' => $request->request->get('username'),
        //     'password' => $request->request->get('password'),
        // ];

        // return $credentials;

        $authorization = $request->headers->get('authorization');

        if($authorization) {
            
            $token = explode(" ", $authorization);
            $token = $token[1];
    
            return $token;
        } else {
            return null;
        }

    }

    public function getUser($credentials, UserProviderInterface $userProvider) {


        if($credentials == null) {
            return null;
        }

        // dd($credentials);

        return $this->entityManager->getRepository(User::class)->findOneBy(['token' => $credentials]);

        // $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);

        // if (!$user) {
        //     // fail authentication with a custom error
        //     throw new CustomUserMessageAuthenticationException('Username e/ou password incorretos.');
        // }

        // return $user;
    }

    public function checkCredentials($credentials, UserInterface $user) {
        // return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);

        return true;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
        //     // dd($targetPath);

        //     return new RedirectResponse($targetPath);
        // }

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

    // public function createAuthenticatedToken(UserInterface $user, string $providerKey) {

    //     $userId = $user->getId();
    //     $secret = $_ENV['JWT_SECRET'];
    //     $expiration = time() + 3600;
    //     $issuer = $_ENV['CFG_PATH'];

    //     $payload = [
    //         'cat' => time(),                // Created At
    //         'uid' => $userId,               // User Id
    //         'exp' => $expiration,           // Expiracy Date
    //         'iss' => $issuer                // Issuer
    //     ];

    //     $token = Token::customPayload($payload, $secret);
    //     $user->setToken($token);

    //     $this->entityManager->persist($user);
    //     $this->entityManager->flush();

    //     return $token;

    // }

    public function start(Request $request, AuthenticationException $authException = null) {
        // $data = $request->headers->all();
        // $authorization = $data['authorization'][0];

        // $token = explode(" ", $authorization);
        // $token = $token[1];

        // $payload = Token::getPayload($token, $_ENV['JWT_SECRET']);
        // $expiracyDate = $payload['exp'];

        // $dateDiff = $expiracyDate - time();
        // if($dateDiff < 0) {
        //     $data = [
        //         'dateDiff' => $dateDiff,
        //         'token' => $token,
        //         'code' => Response::HTTP_UNAUTHORIZED,
        //         'message' => 'Necessária Autenticação',
        //     ];

        //     return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
        // } else {



        //     $data = [
        //         'rota' => $request->attributes->get('_route'),
        //         'dateDiff' => $dateDiff,
        //         'token' => $token,
        //         'code' => Response::HTTP_OK,
        //         // 'data' => $this->redirectToRoute($request->attributes->get('_route'), $request->query->all())
        //     ];
        // }

        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_OK);

    }

    public function supportsRememberMe() {
        return false;
    }

    // protected function getLoginUrl() {
    //     // return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    // }
}
