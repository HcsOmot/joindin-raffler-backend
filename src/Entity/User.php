<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $id;

    /**
     * @var bool
     *
     * Flags this user as admin (adds ROLE_SUPER_ADMIN)
     */
    private $administrator;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        parent::__construct();
        // your own logic
    }

    public function isAdministrator(): bool
    {
        return $this->isSuperAdmin();
    }

    public function setAdministrator($boolean)
    {
        $this->setSuperAdmin($boolean);
        $this->administrator = $boolean;
    }
}
