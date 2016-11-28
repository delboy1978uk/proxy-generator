<?php
/**
 * User: delboy1978uk
 * Date: 14/08/15
 * Time: 15:56
 */

namespace Del\ProxyGenerator\Command;

use Del\ProxyGenerator\Service\ProxyGeneratorService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProxyGenerator extends Command
{
    private $proxyGeneratorService;

    protected function configure()
    {
        $this->setName('create')
             ->setDescription('Creates new classes extending vendor classes with a certain interface, and implementing a different given interface.')
             ->setHelp("Pass the 3rd party interface to replace, the new interface, and the folder to check")
             ->addArgument('scanInterface', InputArgument::REQUIRED, 'The interface to scan for and list in an array of classes to extend in the scanDirectory')
             ->addArgument('replaceInterface', InputArgument::REQUIRED, 'The interface your extending class will implement')
             ->addArgument('scanDirectory', InputArgument::REQUIRED, 'The relative directory from the projectDirectory of classes to scan through and extend.')
             ->addArgument('targetNamespace', InputArgument::REQUIRED, 'The base namespace of the classes you are scanning through')
             ->addArgument('replaceNamespace', InputArgument::REQUIRED, 'The new base namespace.')
             ->addArgument('targetDirectory', InputArgument::OPTIONAL, 'The folder to generate the classes in.')
             ->addArgument('projectDirectory', InputArgument::OPTIONAL, 'The project\'s root directory.')
        ;
        $this->proxyGeneratorService = new ProxyGeneratorService();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentDir = realpath(getcwd().DIRECTORY_SEPARATOR.'..');
        $projectDirectory = $input->getArgument('projectDirectory');
        $targetDirectory = !is_null($input->getArgument('targetDirectory')) ? $input->getArgument('targetDirectory') : 'src';
        $scanDirectory = $input->getArgument('scanDirectory');
        $scanInterface = $input->getArgument('scanInterface');
        $replaceInterface = $input->getArgument('replaceInterface');
        $targetNamespace = $input->getArgument('targetNamespace');
        $replaceNamespace = $input->getArgument('replaceNamespace');

        $dir = empty($projectDirectory) ? $currentDir : realpath($projectDirectory);
        $svc = new ProxyGeneratorService($dir);
        $svc->setTargetPath($targetDirectory);
        $svc->setScanPath($scanDirectory);
        $svc->setScanInterface($scanInterface);
        $svc->setReplaceInterface($replaceInterface);
        $svc->setTargetNamespace($targetNamespace);
        $svc->setReplaceNamespace($replaceNamespace);
        
        $classes = $svc->generate();
        $output->writeln('Classes generated in '.$targetDirectory);
        foreach ($classes as $class) {
            $output->writeln('Generated '.$class.'.');
        }
    }
}