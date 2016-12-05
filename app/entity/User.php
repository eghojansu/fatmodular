<?php

namespace App\entity;

use Base;
use Bcrypt;
use App\core\SQLMapper;
use App\core\UserInterface;

class User extends SQLMapper implements UserInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->beforesave(function() {
            if ($plain = $this->get('new_password')) {
                $this->set('password', $this->hashPassword($plain));
            }
            $this->set('plain_password', null);
            $this->set('new_password', null);
        });
    }

    public function getRoles()
    {
        return explode(',', $this->roles);
    }

    public function hashPassword($plainPassword)
    {
        return Bcrypt::instance()->hash($plainPassword);
    }

    public function validatePassword($plainPassword)
    {
        return Bcrypt::instance()->verify($plainPassword, $this->password);
    }

    public function active()
    {
        return $this->get('active');
    }

    public function getId()
    {
        return $this->get('id');
    }

    public function listing()
    {
        $filter = [self::TS_DELETE.' is null and id <> :sid', ':sid'=>Base::instance()->get('user')->getId()];
        if (isset($_GET['keyword']) && $_GET['keyword']) {
            $filter[0] .= ' and username like :keyword';
            $filter[':keyword'] = '%'.$_GET['keyword'].'%';
        }
        $option = ['order'=>'id'];

        return $this->apaginate($filter, $option);
    }

    public function isEmployee()
    {
        return $this->employee()->valid();
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id=id');
    }
}
