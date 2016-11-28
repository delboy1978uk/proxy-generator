<?php

namespace DelTesting\ProxyGenerator;

use DelTesting\ProxyGenerator\Command\CommandTest;
use Del\ProxyGenerator\Command\ProxyGenerator;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class ProxyGeneratorTest extends CommandTest
{
   /**
    * @var \UnitTester
    */
    protected $tester;

    /**
     * @var ProxyGenerator
     */
    protected $proxyGenerator;

    /** @var  Filesystem */
    private $fileSystem;

    protected function _before()
    {
        $adapter = new Local(getcwd());
        $this->fileSystem = new Filesystem($adapter);
        $this->proxyGenerator = new ProxyGenerator();
    }

    protected function _after()
    {
        $gitIgnore = $this->fileSystem->read('tests/_output/generation/.gitignore');
        $this->fileSystem->deleteDir('tests/_output/generation');
        $this->fileSystem->createDir('tests/_output/generation');
        $this->fileSystem->write('tests/_output/generation/.gitignore', $gitIgnore);
        unset($this->proxyGenerator);
    }

    /**
     * Check tests are working
     */
    public function testGenerate()
    {
        $command = new ProxyGenerator('.');
        $output = $this->runCommand($command, [
            'scanInterface' => 'Zend\Filter\FilterInterface',
            'replaceInterface' => 'My\Awesome\Filter\FilterInterface',
            'scanDirectory' => 'vendor/zendframework/zend-filter/src',
            'targetDirectory' => 'tests/_output/generation',
            'projectDirectory' => '.',
            'targetNamespace' => 'Zend\Filter',
            'replaceNamespace' => 'My\Awesome\Filter',
        ]);
        $this->assertEquals('Classes generated in tests/_output/generation'."\n",$output);
    }


}
