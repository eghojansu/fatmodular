<?php

namespace App\command;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class DistCommand extends AbstractCommand
{
    protected $distDir;
    protected $tempDir;
    protected $baseDir;
    protected $zipname;
    protected $fs;

    public function configure()
    {
        $this->baseDir = $this->base()->get('ROOT');
        $this->distDir = $this->baseDir.'dist/';
        $this->tempDir = $this->baseDir.'dist/tmp/';
        $this->zipname = basename($this->baseDir);
        $this->fs = new Filesystem;

        $this
            ->setName('dist:build')
            ->setDescription('Build distributable')
            ->addArgument('tag', InputArgument::OPTIONAL, 'Version tag', 'unstable')
            ->addOption('patch', 'p', InputOption::VALUE_NONE, 'Build patch instead')
            ->addOption('no-dev', 'd', InputOption::VALUE_NONE, 'Do not include dev component')
            ->addOption('no-vendor', 'e', InputOption::VALUE_NONE, 'Do not install vendor')
            ->addOption('no-compress', 's', InputOption::VALUE_NONE, 'Do not perform compression')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->configureIO($input, $output)
            ->deployInitialize()
            ->deployCopyScript()
            ->deployInstallVendor()
            ->deployPrepareCompress()
            ->deployCompress()
            ->reallyDone('Building dist package complete')
        ;
    }

    protected function deployCompress()
    {
        $this->info('compressing script', 0);

        if ($this->input->getOption('no-compress')) {
            $this->error('skipped');

            return $this;
        }

        $tag = str_replace('..', 'to', $this->input->getArgument('tag'));
        $saveAs = $this->zipname.'-'.$tag.'.zip';
        $path = $this->distDir.$saveAs;
        $this->fs->remove([$path]);

        $zip = new ZipArchive();
        $ret = $zip->open($path, ZipArchive::CREATE);
        if ($ret === ZipArchive::ER_EXISTS) {
            $error = "File already exists.";
        }
        elseif ($ret === ZipArchive::ER_INCONS) {
            $error = "Zip archive inconsistent.";
        }
        elseif ($ret === ZipArchive::ER_INVAL) {
            $error = "Invalid argument.";
        }
        elseif ($ret === ZipArchive::ER_MEMORY) {
            $error = "Malloc failure.";
        }
        elseif ($ret === ZipArchive::ER_NOENT) {
            $error = "No such file.";
        }
        elseif ($ret === ZipArchive::ER_NOZIP) {
            $error = "Not a zip archive.";
        }
        elseif ($ret === ZipArchive::ER_OPEN) {
            $error = "Can't open file.";
        }
        elseif ($ret === ZipArchive::ER_READ) {
            $error = "Read error.";
        }
        elseif ($ret === ZipArchive::ER_SEEK) {
            $error = "Seek error.";
        }
        else {
            $error = false;
        }

        if ($error) {
            $error .= ' ('.$path.')';
            $this->error($error);

            throw new RuntimeException($error);
        }
        else {
            $it = new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS);
            $entries = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

            $zip->addEmptyDir($this->zipname);
            foreach($entries as $entry) {
                $full = strtr($entry->getRealPath(), '\\', '/');
                $rel = $this->zipname.str_replace($this->tempDir, '/', $full);
                if ($entry->isDir()){
                    $zip->addEmptyDir($rel);
                } else {
                    $zip->addFile($full, $rel);
                }
            }

            $time = date('d/m/Y H:i:s');
            $zip->setArchiveComment("$saveAs\nby Eko Kurniawan <ekokurniawanbs@gmail.com>\\on $time");
            $zip->close();

            $this->done();
        }

        return $this;
    }

    protected function deployPrepareCompress()
    {
        $this->info('preparing compression', 0);

        if ($this->input->getOption('no-compress')) {
            $this->error('skipped');

            return $this;
        }

        $paths = [
            $this->tempDir.'dev.ini',
            $this->tempDir.'composer.json',
            $this->tempDir.'composer.lock',
        ];
        if ($this->input->getOption('no-dev')) {
            array_push($paths, $this->tempDir.'app/command', $this->tempDir.'app/console');
        }
        $this->fs->remove($paths);

        $this->done();

        return $this;
    }

    protected function deployInstallVendor()
    {
        $this->info('installing vendor', 0);

        if (file_exists($this->tempDir.'composer.json') && !$this->input->getOption('no-vendor')) {
            $command = [
                'composer',
                'install',
                '--quiet ',
                '--optimize-autoloader',
            ];
            if ($this->input->getOption('no-dev')) {
                array_push($command, '--no-dev');
            }
            $this->process(implode(' ', $command), $this->tempDir, true);

            $this->done();
        } else {
            $this->error('skipped');
        }

        return $this;
    }

    protected function deployCopyScript()
    {
        $this->info('copying script', 0);

        $base = $this->base();

        $files = [
            '.htaccess',
            'index.php',
            'composer.json',
            'favicon.ico',
            'LICENSE',
            'README.md',
        ];

        if ($this->input->getOption('patch')) {
            $tag = $this->input->getArgument('tag');

            if (!$tag) {
                throw new RuntimeException('You must supply tag if use patch option');
            }

            $command = [
                'git',
                'diff',
                '--name-only',
                '--diff-filter=duxb',
                $tag,
            ];

            $process = $this->process(implode(' ', $command), $this->tempDir);
            $files = array_filter(explode("\n", str_replace(["\n","\r"], "\n", $process->getOutput())));
        }
        else {
            $finder = new Finder;
            $sources = $finder->files()->in([
                $this->baseDir.'app',
                $this->baseDir.'asset',
            ]);
            foreach ($sources as $file) {
                $realpath = $base->fixslashes($file->getRealPath());
                $newpath = str_replace($this->baseDir, $this->tempDir, $realpath);
                if ($newpath === $realpath) {
                    continue;
                }
                $this->fs->copy($realpath, $newpath);
            }
        }

        foreach ($files as $file) {
            if ($this->fs->exists($this->baseDir.$file)) {
                $this->fs->copy($this->baseDir.$file, $this->tempDir.$file);
            }
        }

        // modify files
        $finder = new Finder;
        $layouts = $finder->files()->in([$this->tempDir.'app/view/layout']);
        foreach ($layouts as $layout) {
            $path = $layout->getRealPath();
            $contents = explode("\n", $base->read($path, true));
            foreach ($contents as $no=>$line) {
                if (preg_match('/(bower:|inject:|endinject)/', $line)) {
                    unset($contents[$no]);
                }
            }
            $base->write($path, implode("\n", $contents));
            unset($contents);
        }

        $this->done();

        return $this;
    }

    protected function deployInitialize()
    {
        $this->output->writeln("<fg=yellow>building dist package...</> <fg=cyan>(please wait until process complete)</>\n");

        $this->info('initializing', 0);

        $base = $this->base();
        $this->fs->mkdir($this->distDir, $base::MODE, true);
        $this->fs->remove([$this->tempDir]);
        $this->fs->mkdir($this->tempDir, $base::MODE, true);

        $this->done();

        return $this;
    }
}
