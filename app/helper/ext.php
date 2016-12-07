<?php

/**
 * Template extending
 */
class ext
{
    public static function getExtensions()
    {
        return [
            'while'=>'_while',
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
}
