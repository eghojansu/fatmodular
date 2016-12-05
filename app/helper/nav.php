<?php

/**
 * Class Nav
 */
final class nav
{
    private static $activeRoute;
    private static $caret = ['suffix'=>' <b class="caret"></b>'];
    private static $list = ['list'=>['class'=>'dropdown']];
    private static $attr = ['attr'=>['class'=>'dropdown-menu']];
    private static $link = ['link'=>['class'=>'dropdown-toggle','data-toggle'=>'dropdown','role'=>'button','aria-haspopup'=>'true','aria-expanded'=>'false']];

    public static function active($route)
    {
        self::$activeRoute = $route;
    }

    public static function activeRoute()
    {
        if (!self::$activeRoute) {
            $base = Base::instance();

            self::$activeRoute = $base->get('ACTIVE')?:$base->get('ALIAS');
        }

        return self::$activeRoute;
    }

    public static function menuIcon($icon)
    {
        return '<i class="fa fa-'.$icon.'"></i> ';
    }

    public static function attr($route, $icon = null, $identifier = false, array $merge = [])
    {
        $attr = array_merge([
            'route'=>$route,
            'prefix'=>self::menuIcon($icon),
            'identifier'=>$identifier,
        ], $merge);

        return $attr;
    }

    public static function attrParent($icon = null)
    {
        $attr = [
            'url'=>'#',
            'prefix'=>self::menuIcon($icon),
        ] + self::$caret + self::$list + self::$link + self::$attr;

        return $attr;
    }

    public static function left()
    {
        $menu = new App\core\html\Menu(null, ['attr'=>['class'=>'nav navbar-nav']]);
        $menu
            ->setActiveRoute(self::activeRoute())
            ->add('Dashboard', self::attr('dashboard','dashboard'))
            ->getParent()
            ->add('Master', self::attrParent('hdd-o'))
                ->add('Sample', self::attr('sample_index','users'))
                ->getParent()
                ->add('Simple', self::attr('simple_index','users'))
        ;

        return $menu->render();
    }

    public static function right()
    {
        $menu = new App\core\html\Menu(null, ['attr'=>['class'=>'nav navbar-nav navbar-right']]);
        $menu
            ->setActiveRoute(self::activeRoute())
            ->add('Tools', self::attrParent('cogs'))
                ->add('Profile', self::attr('profile','user'))
                ->getParent()
                ->addDivider()
                ->add('Logout', self::attr('logout','power-off'))
        ;

        return $menu->render();
    }
}
