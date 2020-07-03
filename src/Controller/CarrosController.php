<?php

namespace App\Controller;

use App\Entity\Carros;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use TokenClass;

/**
 * @Route("/carros", name="carros")
 */
class CarrosController extends AbstractController {
    
    private $entityManager;
    private $token;
    private $request;
    private $authorization;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $request) {
        $this->entityManager = $entityManager;

        $this->request = $request->getCurrentRequest();
        $authorization = $this->request->headers->get('authorization');
        $token = new TokenClass($this->entityManager);
        $token = $token->verificaTokenByAuthorization($authorization);

        $this->token = $token;
        $this->authorization = $authorization;
    }

    /**
     * @Route("/create", name="create", methods={"POST"})
     */
    public function create(Request $request) {
        $doctrine = $this->getDoctrine()->getManager();

        $userLogado = TokenClass::getUserIdByAuthorization($this->authorization);

        $carro = new Carros();

        if(!$request->request->has('id_user')) {                                        // Se não for enviado um user, vincula ao user logado.
            $userId = $userLogado;
        } else {
            $userId = $request->request->get('id_user');
        }

        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(['id' => $userId]);

        if(!$request->request->has('marca')) {
            throw new Exception('Necessita de marca');
        } else {
            $carro->setMarca($request->request->get('marca'));
        }
        if(!$request->request->has('modelo')) {
            throw new Exception('Necessita de modelo');
        } else {
            $carro->setModelo($request->request->get('modelo'));
        }

        $carro->setIdUser($user);
        $carro->setCreatedBy($userLogado);
        $carro->setUpdatedBy($userLogado);
        // $carro->setCreatedAt(date('Y-m-d h:i:s'));


        $doctrine->persist($carro);
        $doctrine->flush();

        return $this->json([
            'token' => $this->token,
            'message' => 'Carro criado com sucesso'
        ]);
    }
    
}
