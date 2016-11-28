<?php
/**
 * User: delboy1978uk
 * Date: 27/11/2016
 * Time: 15:48
 */

namespace Del\ProxyGenerator\Service;

use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class ProxyGeneratorService
{
    const REGEX_CLASS = '#class\s(?<class>\w+)(\sextends\s(?<parentClass>\w+))?(\simplements\s(?<interface>\w+))?#';
    const REGEX_NAMESPACE = '#namespace\s(?<namespace>\w+.*)\;#';
    const PROCESS_INTERFACES = 'implements';
    const PROCESS_CHILD_CLASSES = 'extends';

    const TEMPLATE = <<<HERE
<?php

namespace REPLACE_NAMESPACE;

REPLACE_USE

REPLACE_ABSTRACTclass REPLACE_CLASS REPLACE_EXTENDS REPLACE_IMPLEMENTS
{
}
HERE;


    /** @var  Filesystem */
    private $fileSystem;

    /** @var string $scanPath  */
    private $scanPath;

    /** @var string $scanPath  */
    private $scanInterface;

    /** @var string $replaceInterface */
    private $replaceInterface;

    /** @var string $targetPath */
    private $targetPath;

    /** @var string $targetNamespace */
    private $targetNamespace;

    /** @var  $replaceNamespace */
    private $replaceNamespace;

    /** @var array $implementingClasses */
    private $implementingClasses = [];

    /** @var array $extendingClasses */
    private $extendingClasses = [];

    private $type = self::PROCESS_INTERFACES;

    /** @var array $currentFile */
    private $currentFile;

    /** @var array $currentNamespace */
    private $currentNamespace;

    /** @var string $checkClass  */
    private $checkClass;

    private $newMatches = 0;

    private $baseNamespace;

    public function __construct($projectRootPath = '.')
    {
        $adapter = new Local($projectRootPath);
        $this->fileSystem = new Filesystem($adapter);
        $this->targetPath = 'src';
    }

    /**
     * @param string $scanPath
     */
    public function setScanPath($scanPath)
    {
        $this->scanPath = $scanPath;
    }

    /**
     * @param string $scanInterface
     */
    public function setScanInterface($scanInterface)
    {
        $this->scanInterface = $scanInterface;
    }

    /**
     * @param string $replaceInterface
     */
    public function setReplaceInterface($replaceInterface)
    {
        $this->replaceInterface = $replaceInterface;
    }

    /**
     * @param string $targetPath
     * @return ProxyGeneratorService
     */
    public function setTargetPath($targetPath)
    {
        $this->targetPath = $targetPath;
        return $this;
    }

    /**
     * @param string $targetNamespace
     * @return ProxyGeneratorService
     */
    public function setTargetNamespace($targetNamespace)
    {
        $this->targetNamespace = $targetNamespace;
        return $this;
    }

    /**
     * @param mixed $replaceNamespace
     * @return ProxyGeneratorService
     */
    public function setReplaceNamespace($replaceNamespace)
    {
        $this->replaceNamespace = $replaceNamespace;
        return $this;
    }



    /**
     * @return array
     */
    public function generate()
    {
        if(empty($this->scanPath) || empty($this->scanInterface) || empty($this->replaceInterface) || empty($this->targetNamespace) || empty($this->replaceNamespace)) {
            throw new InvalidArgumentException('set scanPath, scanInterface, replaceInterface, targetNamespace and $replaceNamespace');
        }
        $ex = explode('\\', $this->scanInterface);
        $this->scanInterface = array_pop($ex);
        $this->baseNamespace = implode('\\', $ex);
        $classes = $this->getClasses();
        $classes = $this->generateFiles($classes);
        return $classes;
    }

    /**
     * @param $interface
     * @return array
     */
    private function getClasses()
    {
        // Get all the files to scan
        $contents = $this->fileSystem->listContents($this->scanPath, true);
        $classes = $this->processContents($contents);
        return $classes;
    }

    /**
     * @param $contents
     * @param $interfaceName
     * @return array
     */
    private function processContents($contents)
    {
        // Find all classes implementing $interfaceName
        $this->type = self::PROCESS_INTERFACES;
        foreach ($contents as $file) {
            $this->processFile($file);
        }
        $this->type = self::PROCESS_CHILD_CLASSES;
        foreach ($this->implementingClasses as $class) {
            $this->checkClass = $class['class'];
            foreach($contents as $file) {
                $this->processFile($file);
            }
        }
        do {
            $this->newMatches = false;
            foreach ($this->extendingClasses as $class) {
                $this->checkClass = $class['class'];
                foreach($contents as $file) {
                    $this->processFile($file);
                }
            }
            $keepGoing = $this->newMatches;
        } while ($keepGoing === true);

        return array_merge($this->implementingClasses, $this->extendingClasses);
    }

    /**
     * @param $file
     * @return bool
     */
    private function processFile($file)
    {
        $this->currentFile = $file;
        if (!isset($file['extension']) || $file['extension'] != 'php') {
            return false;
        }
        $filePath = $file['path'];
        $contents = $this->fileSystem->read($filePath);
        foreach (explode("\n", $contents) as $line) {
            $this->processLine($line);
        }
        return true;
    }

    private function processLine($line)
    {
        preg_match(self::REGEX_NAMESPACE, $line, $match);
        if (!empty($match['namespace'])) {
            $this->currentNamespace = $match['namespace'];
        }
        preg_match(self::REGEX_CLASS, $line, $match);
        if (!empty($match)) {
            $match['abstract'] = (strstr($line, 'abstract ')) ? true : false;
            $this->processMatch($match);
        }
    }

    private function processMatch(array $match)
    {
        switch ($this->type) {
            case self::PROCESS_CHILD_CLASSES:
                $this->processExtendingClass($match);
                break;
            case self::PROCESS_INTERFACES:
            default;
                $this->processImplementingClass($match);
                break;
        }
    }

    private function processImplementingClass(array $match)
    {
        if(!empty($match['interface']) && $match['interface'] == $this->scanInterface) {
            $this->currentFile['interface'] = $match['interface'];
            $this->currentFile['abstract'] = $match['abstract'];
            $this->currentFile['class'] = $match['class'];
            $this->currentFile['parentClass'] = isset($match['parentClass']) ? $match['parentClass'] : null;
            $this->currentFile['namespace'] = $this->currentNamespace;
            if(!in_array($this->implementingClasses, $this->currentFile)) {
                $this->implementingClasses[] = $this->currentFile;
            }
        }
    }

    private function processExtendingClass(array $match)
    {
        if(isset($match['parentClass']) && $this->checkClass == $match['parentClass']) {
            $this->currentFile['abstract'] = $match['abstract'];
            $this->currentFile['class'] = $match['class'];
            $this->currentFile['parentClass'] = $match['parentClass'];
            $this->currentFile['namespace'] = $this->currentNamespace;
            $key = $this->currentNamespace.'\\'.$this->currentFile['class'];
            if(!isset($this->extendingClasses[$key])) {
                $this->extendingClasses[$key] = $this->currentFile;
                $this->newMatches = true;
            }
        }
    }


    private function generateFiles($classes)
    {
        $newClasses = [];
        foreach ($classes as $class) {

            $file = self::TEMPLATE;
            $namespace = isset($class['namespace']) ? $class['namespace'] : null;
            $extraNamespace = str_replace($this->targetNamespace, '', $namespace);
            $alias = 'ThirdParty'.$class['class'];
            $use = 'use '.$class['namespace'].'\\'.$class['class'].' as '.$alias.';';
            $abstract = $class['abstract'] ? 'abstract ' : null;
            $extends = 'extends '.$alias;

            $use .= ($extraNamespace != '') ? "\n".'use '.$this->replaceInterface.';' : null;
            $interface = str_replace($this->replaceNamespace.'\\', '',$this->replaceInterface);
            $implements = 'implements ' . $interface;


            $file = str_replace('REPLACE_NAMESPACE', $this->replaceNamespace.$extraNamespace, $file);
            $file = str_replace('REPLACE_USE', $use, $file);
            $file = str_replace('REPLACE_ABSTRACT', $abstract, $file);
            $file = str_replace('REPLACE_CLASS', $class['class'], $file);
            $file = str_replace('REPLACE_EXTENDS', $extends, $file);
            $file = str_replace('REPLACE_IMPLEMENTS', $implements, $file);

            $extraPath = !empty($extraNamespace) ? str_replace('\\', '', $extraNamespace).DIRECTORY_SEPARATOR : null;
            $location = $this->targetPath.DIRECTORY_SEPARATOR.$extraPath.$class['class'].'.php';
            if(!$this->fileSystem->has($location)) {
                $this->fileSystem->write($location, $file);
            }
            $newClasses[] = $location;
        }
        return $newClasses;
    }
}