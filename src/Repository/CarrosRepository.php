<?php

namespace App\Repository;

use App\Entity\Carros;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Carros|null find($id, $lockMode = null, $lockVersion = null)
 * @method Carros|null findOneBy(array $criteria, array $orderBy = null)
 * @method Carros[]    findAll()
 * @method Carros[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarrosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carros::class);
    }

    public function findCarros($params = array()) {
        $defaultParams = array(
            'page' => null,
            'filters' => null,
            'paginator' => null,
            'order' => null,
            'limit' => null
        );

        array_merge($defaultParams, $params);

        $query = $this->createQueryBuilder('c');
        $query->innerJoin('c.idUser', 'ci')
            ->addSelect('ci');

        if ($params['filters']) {
            if ($params['filters']['marca']) {
                $query->andWhere('c.marca LIKE :marca')
                    ->setParameter('marca', '%' . $params['filters']['marca'] . '%');
            }
            if ($params['filters']['modelo']) {
                $query->andWhere('c.modelo LIKE :modelo')
                    ->setParameter('modelo', '%' . $params['filters']['modelo'] . '%');
            }
        }
        if ($params['paginator']) {
            $pagination = $params['paginator']->paginate(
                $query->getQuery(),
                $params['page'],
                $params['limit']
            );

            return $pagination;
        } else {
            return $query->getQuery()->getResult();
        }
    }
}
