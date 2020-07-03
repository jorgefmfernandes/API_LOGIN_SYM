<?php

namespace App\Controller;

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

    public function __construct(EntityManagerInterface $entityManager, RequestStack $request) {
        $this->entityManager = $entityManager;

        $this->request = $request->getCurrentRequest();
        $authorization = $this->request->headers->get('authorization');
        $token = new TokenClass($this->entityManager);
        $token = $token->verificaTokenByAuthorization($authorization);
        $this->token = $token;
    }

    /**
     * @Route("/create", name="create", methods={"POST"})
     */
    public function create(Request $request) {
        $data = $request->request->all();
        $doctrine = $this->getDoctrine()->getManager();

        if(!$request->request->has('id_user')) {
            throw new Exception('O carro precisa de ser vinculado a um utilizador');
        }
        
    }
    
}
