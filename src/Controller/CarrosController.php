<?php

namespace App\Controller;

use App\Entity\Carros;
use App\Entity\User;
use Countable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use TokenClass;
use Knp\Component\Pager\PaginatorInterface;

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
     * @Route("/getAll", name="getAll", methods={"GET"})
    */
    public function getAllCarros(Request $request, PaginatorInterface $paginator) {
        try {
            $data = json_decode($request->getContent(), true);

            $page = $data['page'];
            $filters = $data['filters'];
            $order = $data['order'];
            $limit = $data['limit'];

            $page = 1;
            $limit = 2;

            // $carros = $this->getDoctrine()->getRepository(Carros::class)->findBy([], [$order_attr => $order], $limite, $page);
            $carros = $this->getDoctrine()->getRepository(Carros::class)->findCarros([
                'page' => $page,
                'filters' => $filters,
                'order' => $order,
                'paginator' => $paginator,
                'limit' => $limit
            ]);

            $dataCarros = [];

            foreach($carros as $carro) {
                array_push($dataCarros, [
                    'id' => $carro->getId(),
                    'marca' => $carro->getMarca(),
                    'modelo' => $carro->getModelo(),
                    'user' => [
                        'id' => $carro->getIdUser()->getId(),
                        'username' => $carro->getIdUser()->getUsername(),
                        'roles' => $carro->getIdUser()->getRoles(),
                    ]
                ]);
            }

            return $this->json([
                'success' => true,
                'token' => $this->token,
                'data' => $dataCarros
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'token' => $this->token,
                'data' => 'Erro ao recuperar informação'
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * @Route("/delete/{carroId}", name="delete", methods={"DELETE"})
    */
    public function delete($carroId) {
        try {
            $doctrine = $this->getDoctrine();
            $carro = $doctrine->getRepository(Carros::class)->find($carroId);
    
            $manager = $doctrine->getManager();
            $manager->remove($carro);
            $manager->flush();
    
            return $this->json([
                'success' => true,
                'token' => $this->token,
                'data' => 'Carro removido com sucesso'
            ], Response::HTTP_OK);

        } catch(Exception $e) {
            return $this->json([
                'success' => false,
                'token' => $this->token,
                'data' => 'Erro ao eliminar carro'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/update/{carroId}", name="update", methods={"PUT", "PATCH"})
    */
    public function update($carroId, Request $request) {
        try {
            $doctrine = $this->getDoctrine();
            $carro = $doctrine->getRepository(Carros::class)->find($carroId);
            $userLogado = TokenClass::getUserIdByAuthorization($this->authorization);

            if(!$request->request->has('id_user')) {                                        // Se não for enviado um user, vincula ao user logado.
                $userId = $userLogado;
            } else {
                $userId = $request->request->get('id_user');
            }
    
            $user = $doctrine->getManager()->getRepository(User::class)->findOneBy(['id' => $userId]);
    
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
            $carro->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Lisbon')));
            $carro->setUpdatedBy($userId);

            $doctrine->getManager()->flush();

            return $this->json([
                'success' => true,
                'token' => $this->token,
                'message' => 'Carro atualizado com sucesso.'
            ], Response::HTTP_OK);
            
        } catch(Exception $e) {
            return $this->json([
                'success' => false,
                'token' => $this->token,
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }


    /**
    * @Route("/create", name="create", methods={"POST"})
    */
    public function create(Request $request) {
        try {
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
            $carro->setCreatedAt(new \DateTime("now", new \DateTimeZone('Europe/Lisbon')));
            $carro->setUpdatedAt(new \DateTime("now", new \DateTimeZone('Europe/Lisbon')));
    
            $doctrine->persist($carro);
            $doctrine->flush();
    
            return $this->json([
                'success' => true,
                'token' => $this->token,
                'message' => 'Carro criado com sucesso'
            ], Response::HTTP_CREATED);

        } catch(Exception $e) {
            return $this->json([
                'success' => false,
                'token' => $this->token,
                'message' => 'Erro ao criar carro'
            ], Response::HTTP_BAD_REQUEST);

        }
    }
    
}
