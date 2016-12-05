<?php

/**
 * Template extending
 */
class ext
{
    private static $collection = [];

    public static function getExtensions()
    {
        return [
            'while'=>'_while',
            'collection'=>'_collection',
        ];
    }

    public static function _while(array $node)
    {
        $template = Template::instance();
        $attrib=$node['@attrib'];
        unset($node['@attrib']);
        return
            '<?php '.
                (isset($attrib['counter'])?
                    (($ctr=$template->token($attrib['counter'])).'=0; '):'').
                'while (('.$template->token($attrib['true']).')):'.
                (isset($ctr)?(' '.$ctr.'++;'):'').' ?>'.
                $template->build($node).
                '<?php '.$template->token($attrib['then']).'; ?>'.
            '<?php endwhile; ?>';
    }

    public static function _collection(array $node)
    {
        $template = Template::instance();
        $attrib=$node['@attrib'];
        unset($node['@attrib']);

        if ($attrib['flush']) {
            $content = static::$collection[$attrib['name']];
            unset(static::$collection[$attrib['name']]);

            return $content;
        }
        else {
            static::$collection[$attrib['name']] = $template->build($node);

            return '';
        }
    }
}
