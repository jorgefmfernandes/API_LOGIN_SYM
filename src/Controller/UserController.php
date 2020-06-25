<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/user", name="user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(Request $request, UserPasswordEncoderInterface $passEncoder) {
        $data = $request->request->all();
        $doctrine = $this->getDoctrine();
        

        // try {
            $entityManager = $doctrine->getManager()->getRepository(User::class);
            $userAux = $entityManager->getUserByUsername($data['username']);

            $user = new User();
            $user->setUsername($data['username']);

            $storedPassword = $userAux[0]->getPassword();
            // $passwordEncoded = $passEncoder->encodePassword($user, $data['password']);
            $passwordEncoded = $data['password'];
            // $passEncoder->isPasswordValid();
            
            // return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);

            // return $this->json([
            //     'storedPassword' => $storedPassword,
            //     'passwordEncoded' => $passwordEncoded,
            // ]);

            if($passEncoder->isPasswordValid($user, $data['password'])) {
            // if($passwordEncoded == $storedPassword) {
                return $this->json([
                    'code' => 200,
                    'username' => $data['username'],
                    'password' => $passwordEncoded,
                    'message' => 'Login efetuado com sucesso.',
                ]);
            } else {
                return $this->json([
                    'code' => 401,
                    'username' => $data['username'],
                    'passwordEncoded' => $passwordEncoded,
                    'storedPassword' => $storedPassword,
                    'message' => 'Username ou password incorretos.',
                    'user' => $userAux
                ]);
            }
        // } catch(Exception $e) {
        //     return $this->json([
        //         'code' => 401,
        //         'username' => $data['username'],
        //         'password' => $data['password'],
        //         'message' => 'Algo de errado não está certo.',
        //     ]);
        // }
    }

    /**
     * @Route("/", name="getAll", methods={"get"})
     */
    public function getAll() {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        return $this->json([
            'data' => $users
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
