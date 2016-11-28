<?php
/**
 * User: delboy1978uk
 * Date: 27/11/2016
 * Time: 16:24
 */

namespace DelTesting\ProxyGenerator\Command;

use Codeception\TestCase\Test;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;

class CommandTest extends Test
{
    public function runCommand(Command $command, array $args)
    {
        $application = new Application();
        $application->add($command);
        $commandName = $command->getName();
        $args = array_merge(['command' => $commandName], $args);
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);

        return $commandTester->getDisplay();
    }
}