<?php

namespace App\core;

interface UserInterface extends CursorInterface
{
    const TOKEN_NAME = 'username';
    const ROUTE_LOGIN = '@login';
    const ROUTE_DASHBOARD = '@dashboard';

    /**
     * Get user roles
     *
     * @return array
     */
    public function getRoles();

    /**
     * Validate password
     *
     * @param string $plainPassword
     * @return  boolean
     */
    public function validatePassword($plainPassword);

    /**
     * Hash password
     *
     * @param string $plainPassword
     * @return  string
     */
    public function hashPassword($plainPassword);

    /**
     * Get user active status
     *
     * @return boolean
     */
    public function active();

    /**
     * Get user id
     *
     * @return string|int
     */
    public function getId();
}
