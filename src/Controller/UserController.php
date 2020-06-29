<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Schema\View;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/user", name="user")
 */
class UserController extends AbstractController
{
    // Este método de login não gera token. Serve para efeito de testes.
    /**
     * @Route("/loginSemToken", name="login", methods={"POST"})
     */
    public function login(Request $request, UserPasswordEncoderInterface $passEncoder) {
        $data = $request->request->all();
        $doctrine = $this->getDoctrine();
            
        $entityManager = $doctrine->getManager()->getRepository(User::class);
        $userAux = $entityManager->getUserByUsername($data['username']);

        $user = new User();
        $user = $userAux['0'];

        if($passEncoder->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'code' => Response::HTTP_OK,
                'username' => $data['username'],
                'message' => 'Login efetuado com sucesso.',
                'user' => $user
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
        // $data = $request->headers->all();
        // $request = Request::createFromGlobals();

        $data = $request->headers->all();

        $token = substr($data['authorization'][0], 7);




        return $this->json([
            'data' => $users,
            'authorization' => $data['authorization'],
            'token' => $token
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
