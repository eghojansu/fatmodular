<?php

namespace App\core;

use Symfony\Component\Finder\Finder;

abstract class AbstractModule
{
    public function init()
    {}

    public function getCommands()
    {
        return [];
    }

    protected function findFiles($dir, $suffix = null)
    {
        $files = (new Finder)->files()->in($dir)->name($suffix);

        $result = [];
        foreach ($files as $file) {
            $result[] = $file->getRealpath();
        }

        return $result;
    }

    protected function findCommands($dir, $suffix = '*Command.php')
    {
        $commands = [];

        $commandFiles = (new Finder)->files()->in($dir)->name($suffix);
        foreach ($commandFiles as $file) {
            $content = $file->getContents();

            // skip abstract
            if (preg_match('/abstract class/i', $content)) {
                continue;
            }

            // get namespace
            $namespace = null;
            if (preg_match('/namespace ([\w\\\\]+)/', $content, $matches)) {
                $namespace = $matches[1].'\\';
            }

            // check first
            $command = $namespace . basename($file, '.php');
            if (false === class_exists($command)) {
                continue;
            }

            $commands[] = $command;
        }

        return $commands;
    }
}
