<?php

namespace App\command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test;

class TestCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('app:test')
            ->setDescription('Performing test')
            ->configureOther()
        ;
    }

    protected function configureOther()
    {
        $this
            ->addArgument('test', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Test to execute', [])
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $tests = $input->getArgument('test') ?: $this->getAllTest();
        $tester = new Test(Test::FLAG_False);
        foreach ($tests as $test) {
            $test = 'test'.$test;
            call_user_func([$this, $test], $tester);
        }

        if ($tester->passed()) {
            $this->io->success('Test passed');
        }
        else {
            $this->io->caution('Test contains error!');

            $errors = [];
            foreach ($tester->results() as $result) {
                $errors[] = [$result['text'],'False'];
            }

            $this->io->table(['Test','Result'], $errors);
        }

        $this->reallyDone('Testing done');
    }

    private function getAllTest()
    {
        $tests = [];
        $ref = new \ReflectionClass($this);
        foreach ($ref->getMethods() as $method) {
            if ($method->isPrivate() && 'test'===substr($method->name, 0, 4)) {
                $tests[] = substr($method->name, 4);
            }
        }

        return $tests;
    }

    private function testFaMicrotime(Test $tester)
    {
        $tests = [
            '1,25s'=>1.2534,
            '1s'=>1,
            '2s'=>2,
            '10s'=>10,
            '1m'=>60,
            '1m 3s'=>63,
            '1h'=>3600,
            '1h 30m 5s'=>5405,
            '2h 30m 5s'=>9005,
            '5d 6s'=>432006,
            '1w 2h'=>612000,
            '1w 2h 3s'=>612003,
            '1mo 3d 5m'=>18403500,
        ];

        foreach ($tests as $result => $value) {
            $r = \fa::microtime($value);
            $m = $result.' = '.$r;
            $tester->expect($r==$result, $m);
        }
    }
}
