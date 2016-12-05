<?php

namespace App\core;

use Base;

class User
{
    const ERROR_METHOD = 'Method "%s" not found neither in User or map class';
    const KEY_FLASH = 'SESSION.flash';
    const KEY_ID = 'SESSION.user';

    public $map;

    public function __construct(UserInterface $map)
    {
        $this->map = $map;
        $id = $this->getId();
        $this->map->loadByKey($id);
        if ($id && $this->map->dry()) {
            $this->logout();
        }
    }

    public function getId()
    {
        return Base::instance()->get(static::KEY_ID);
    }

    public function hasLogin()
    {
        return !empty($this->getId());
    }

    public function loginWithTokenPassword($token, $password)
    {
        $constant = get_class($this->map).'::TOKEN_NAME';
        $tokenName = constant($constant);
        $ttl = 0;
        $this->map->loadBy([$tokenName=>$token], ['limit'=>1], $ttl);

        $base = Base::instance();
        if ($this->map->valid()) {
            if ($this->map->validatePassword($password)) {
                if ($this->map->active()) {
                    $base->set(static::KEY_ID, $this->map->getId());

                    return true;
                }

                $this->addMessage('error', $base['login.inactive']);

                return false;
            }

            $this->addMessage('error', $base['login.invalid_password']);

            return false;
        }

        $this->addMessage('error', $base['login.notfound']);

        return false;
    }

    public function logout()
    {
        $base = Base::instance();
        $base->clear(static::KEY_ID);
        $base->reroute('@homepage');

        return $this;
    }

    public function getRoles()
    {
        $userRoles = array_filter($this->map->getRoles());
        if ($this->hasLogin()) {
            array_push($userRoles, 'user');
        } else {
            array_push($userRoles, 'anon');
        }

        return $userRoles;
    }

    public function hasRoles($role)
    {
        $roles = array_filter(is_array($role)?$role:[$role]);
        $userRoles = $this->getRoles();
        $hasRoles = array_intersect($userRoles, $roles);

        return !empty($hasRoles);
    }

    public function denyUnlessGranted($role)
    {
        if ($this->hasRoles($role)) {
            return true;
        }

        $base = Base::instance();
        switch ($role) {
            case 'anon':
                $constant = get_class($this->map).'::ROUTE_DASHBOARD';
                $route = constant($constant);
                $base->reroute($route);
                break;
            case 'user':
                $constant = get_class($this->map).'::ROUTE_LOGIN';
                $route = constant($constant);
                $base->reroute($route);
                break;
            default:
                $base->error(405, $base['error.access']);
                break;
        }
    }

    public function getMessage($key = null, $keep = false)
    {
        $key = static::KEY_FLASH.($key?'.'.$key:'');
        $base = Base::instance();
        $messages = $base->get($key);
        if (!$keep) {
            $base->clear($key);
        }

        return $messages;
    }

    public function addMessage($key, $value)
    {
        Base::instance()->push(static::KEY_FLASH.'.'.$key, $value);

        return $this;
    }

    public function __call($method, array $args)
    {
        if (method_exists($this->map, $method)) {
            return call_user_func_array([$this->map, $method], $args);
        }

        throw new \Exception(sprintf(self::ERROR_METHOD, $method));
    }
}
