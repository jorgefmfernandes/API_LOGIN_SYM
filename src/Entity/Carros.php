<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Carros
 *
 * @ORM\Table(name="carros", indexes={@ORM\Index(name="FK_carros_user", columns={"id_user"})})
 * @ORM\Entity
 */
class Carros
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="marca", type="string", length=50, nullable=false)
     */
    private $marca;

    /**
     * @var string
     *
     * @ORM\Column(name="modelo", type="string", length=50, nullable=false)
     */
    private $modelo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt = 'CURRENT_TIMESTAMP';

    /**
     * @var int
     *
     * @ORM\Column(name="created_by", type="integer", nullable=false)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt = 'CURRENT_TIMESTAMP';

    /**
     * @var int
     *
     * @ORM\Column(name="updated_by", type="integer", nullable=false)
     */
    private $updatedBy;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     * })
     */
    private $idUser;


}
