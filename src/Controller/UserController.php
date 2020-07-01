<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Schema\View;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ReallySimpleJWT\Token;

/**
 * @Route("/user", name="user")
 */
class UserController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(Request $request, UserPasswordEncoderInterface $passEncoder) {
        $data = json_decode($request->getContent(), true);
        $doctrine = $this->getDoctrine();

        // request->request->get('username');
        // passar o body do json para aqui
        // dd($request->request->get('username'));

        $entityManager = $doctrine->getManager()->getRepository(User::class);
        $user = $entityManager->findOneBy(['username' => $request->request->get('username')]);

        // var_dump($user);
        // die();

        if($user && $passEncoder->isPasswordValid($user, $request->request->get('password'))) {

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
                'token' => $token,
                'message' => 'Login efetuado com sucesso.',
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                ],
            ]);
        } else {
            return $this->json([
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Username ou password incorretos.',
            ]);
        }
    }

    /**
     * @Route("/", name="getAll", methods={"get"})
     */
    public function getAll(Request $request) {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        return $this->json([
            'users' => $users,
        ]);
    }

    /**
     * @Route("/", name="create", methods={"POST"})
     */
    public  function create(Request $request, UserPasswordEncoderInterface $passwordEncoder) {
        $data = $request->request->all();

        $user = new User();
        $user->setUsername($data['username']);
        $user->setPassword($passwordEncoder->encodePassword($user, $data['password']));

        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->persist($user);
        $doctrine->flush();

        return $this->json([
            'data' => 'User criado com sucesso'
        ]);
    }

    /** @var EncoderFactoryInterface */
    private $encoderFactory;

    public function generatePassword(string $plainPassword): string {
        $encoder = $this->encoderFactory->getEncoder(new User());

        return $encoder->encodePassword($plainPassword, null);
    }

    public function isPasswordValid(string $plainPassword, string $password): bool {
        $encoder = $this->encoderFactory->getEncoder(new User());

        return $encoder->isPasswordValid($password, $plainPassword, null);
    }
}
