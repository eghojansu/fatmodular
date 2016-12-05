<?php

namespace App\core;

use Base;
use Dompdf\Dompdf;
use Template;
use Web;

class DownloadPdf
{
    private $layout = 'pdf/layout.html';
    private $template;
    private $tempfile;
    private $temppath;
    private $success;

    public function __construct($template)
    {
        $app = Base::instance();

        $this->template = $template;
        $this->temppath = $app->fixslashes(realpath($app->get('TEMP'))).'/';
        $this->tempfile = $this->temppath.'pdf-'.$app->hash(microtime()).strrchr($template, '.');
    }

    public function send($filename)
    {
        set_time_limit(600);
        $base = Base::instance();
        $base->set('VIEW', $this->template);
        $root = $base->get('ROOTDIR');
        $logo = $root.'asset/images/logo.png';
        $mime = mime_content_type($logo);
        $base->set('logo', $base->base64($base->read($logo), $mime));
        $html = Template::instance()->render($this->layout);

        $dompdf = new Dompdf;
        // $dompdf->set_option('defaultFont', 'Courier');
        $dompdf->loadHtml($html);
        $dompdf->setPaper('folio');
        $dompdf->render();
        $option = [
            'compress'=>1,
            'Attachment'=>0,
        ];
        $filename .= '.pdf';
        $dompdf->stream($filename, $option);
        die;
    }
}
