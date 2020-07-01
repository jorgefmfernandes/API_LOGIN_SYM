<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use ReallySimpleJWT\Token;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, UserPasswordEncoderInterface $passEncoder): Response {

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $userAuthenticator = new UserAuthenticator($this->entityManager, $passEncoder);
        $credentials = $userAuthenticator->getCredentials($authenticationUtils->getRequest());

        $entityManager = $this->getDoctrine()->getManager()->getRepository(User::class);
        $user = $entityManager->findOneBy(['username' => $credentials['username']]);

        

        if($user) {
            $userId = $user->getId();
            $secret = $_ENV['JWT_SECRET'];
            $expiration = time() + 3600;
            $issuer = $_ENV['CFG_PATH'];
    
            $payload = [
                'cat' => time(),                // Created At
                'uid' => $userId,               // User Id
                'exp' => $expiration,           // Expiracy Date
                'iss' => $issuer                // Issuer
            ];
    
            $token = Token::customPayload($payload, $secret);
            $user->setToken($token);
    
            $this->entityManager->persist($user);
            $this->entityManager->flush();
    
            return $this->json([
                'code' => Response::HTTP_OK,
                'message' => 'Login efetuado com sucesso.',
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                ],
                'token' => $token
            ]);
        } else {
            return $this->json([
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Username e/ou password incorretos',
            ]);
        }

    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
