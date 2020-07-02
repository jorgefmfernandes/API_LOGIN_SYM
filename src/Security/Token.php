<?php

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use ReallySimpleJWT\Token;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class TokenClass {
    private $token;
    private $user;
    private $entityManager;


    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }
    

    // Se qualquer pedido chega aqui, é porque o token tem validade -> UserAuthenticator

    public function verificaTokenByAuthorization($authorization) {
        // Retornar um novo token e inseri-lo na base de dados associado ao user.

        if($authorization) {
            try {
                $token = explode(" ", $authorization);
                $token = $token[1];
        
                $payload = Token::getPayload($token, $_ENV['JWT_SECRET']);
                $userId = $payload['uid'];
        
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['token' => $token]);
        
                if($user->getId() == $userId) {     // Token está associado a este user.
                    $token = $this->criaToken($user);
        
                    return $token;
        
                } else {
                    return false;
                }
            } catch (CustomUserMessageAuthenticationException $e) {
                throw new CustomUserMessageAuthenticationException('Token Inválido.');
            }
        } else {
            return false;
        }
    }

    public function criaToken(User $user) {
        $issuer = $_ENV['CFG_PATH'];
        $secret = $_ENV['JWT_SECRET'];

        $payload = [
            'cat' => time(),                // Created At
            'uid' => $user->getId(),        // User Id
            'exp' => time() + 3600,         // Expiracy Date
            'iss' => $issuer                // Issuer
        ];


        $token = Token::customPayload($payload, $secret);
        $user->setToken($token);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $token;
    }
}