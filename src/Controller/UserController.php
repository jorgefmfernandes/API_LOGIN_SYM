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
use Symfony\Component\HttpFoundation\RequestStack;
use TokenClass;

/**
 * @Route("/user", name="user")
 */
class UserController extends AbstractController {

    private $entityManager;
    private $token;
    private $request;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $request) {
        $this->entityManager = $entityManager;

        $this->request = $request->getCurrentRequest();
        $authorization = $this->request->headers->get('authorization');
        $token = new TokenClass($this->entityManager);
        $token = $token->verificaTokenByAuthorization($authorization);
        $this->token = $token;
    }

    /**
     * @Route("/login", name="app_login", methods={"POST"})
     */
    public function login(Request $request, UserPasswordEncoderInterface $passEncoder) {
        $entityManager = $this->getDoctrine()->getManager()->getRepository(User::class);
        $user = $entityManager->findOneBy(['username' => $request->request->get('username')]);

        if($user && $passEncoder->isPasswordValid($user, $request->request->get('password'))) {
            $token = new TokenClass($this->entityManager);
            $token = $token->criaToken($user);

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
        $users = $this->getDoctrine()->getRepository(User::class)->getAllUsers();
        
        // $users = $this->getDoctrine()->getRepository(User::class)->findBy([], ['username' => 'ASC'], 5, 1);
        // foreach($users as $user) {
        //     unset($user['password']);
        // }


        return $this->json([
            'code' => Response::HTTP_OK,
            'token' => $this->token,
            'data' => [
                'users' => $users,
            ]
        ]);
    }

    /**
     * @Route("/create", name="create", methods={"POST"})
     */
    public  function create(Request $request, UserPasswordEncoderInterface $passwordEncoder) {
        $data = $request->request->all();
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        // dd($this->request);

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($passwordEncoder->encodePassword($user, $password));
        $user->setRoles(['ROLE_USER']);

        $doctrine = $this->getDoctrine()->getManager();
        $doctrine->persist($user);
        $doctrine->flush();

        return $this->json([
            'username' => $username,
            'password' => $password,
            'token' => $this->token,
            'message' => 'User criado com sucesso'
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
