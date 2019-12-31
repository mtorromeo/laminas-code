<?php

/**
 * @see       https://github.com/laminas/laminas-code for the canonical source repository
 * @copyright https://github.com/laminas/laminas-code/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-code/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Code\Reflection;

use Laminas\Code\Annotation\AnnotationCollection;
use Laminas\Code\Annotation\AnnotationManager;
use Laminas\Code\Reflection\FileReflection;
use Laminas\Code\Scanner\AnnotationScanner;
use Laminas\Code\Scanner\FileScanner;
use ReflectionClass;

class ClassReflection extends ReflectionClass implements ReflectionInterface
{
    /**
     * @var AnnotationScanner
     */
    protected $annotations = null;

    /**
     * @var DocBlockReflection
     */
    protected $docBlock = null;

    /**
     * Return the reflection file of the declaring file.
     *
     * @return FileReflection
     */
    public function getDeclaringFile()
    {
        $instance = new FileReflection($this->getFileName());

        return $instance;
    }

    /**
     * Return the classes DocBlock reflection object
     *
     * @return DocBlockReflection
     * @throws Exception\ExceptionInterface for missing DocBock or invalid reflection class
     */
    public function getDocBlock()
    {
        if (isset($this->docBlock)) {
            return $this->docBlock;
        }

        if ('' == $this->getDocComment()) {
            return false;
        }

        $this->docBlock = new DocBlockReflection($this);

        return $this->docBlock;
    }

    /**
     * @param  AnnotationManager $annotationManager
     * @return AnnotationCollection
     */
    public function getAnnotations(AnnotationManager $annotationManager)
    {
        $docComment = $this->getDocComment();

        if ($docComment == '') {
            return false;
        }

        if ($this->annotations) {
            return $this->annotations;
        }

        $fileScanner       = $this->createFileScanner($this->getFileName());
        $nameInformation   = $fileScanner->getClassNameInformation($this->getName());

        if (!$nameInformation) {
            return false;
        }

        $this->annotations = new AnnotationScanner($annotationManager, $docComment, $nameInformation);

        return $this->annotations;
    }

    /**
     * Return the start line of the class
     *
     * @param  bool $includeDocComment
     * @return int
     */
    public function getStartLine($includeDocComment = false)
    {
        if ($includeDocComment && $this->getDocComment() != '') {
            return $this->getDocBlock()->getStartLine();
        }

        return parent::getStartLine();
    }

    /**
     * Return the contents of the class
     *
     * @param  bool $includeDocBlock
     * @return string
     */
    public function getContents($includeDocBlock = true)
    {
        $filename  = $this->getFileName();
        $filelines = file($filename);
        $startnum  = $this->getStartLine($includeDocBlock);
        $endnum    = $this->getEndLine() - $this->getStartLine();

        // Ensure we get between the open and close braces
        $lines = array_slice($filelines, $startnum, $endnum);
        array_unshift($lines, $filelines[$startnum-1]);

        return strstr(implode('', $lines), '{');
    }

    /**
     * Get all reflection objects of implemented interfaces
     *
     * @return ClassReflection[]
     */
    public function getInterfaces()
    {
        $phpReflections  = parent::getInterfaces();
        $laminasReflections = array();
        while ($phpReflections && ($phpReflection = array_shift($phpReflections))) {
            $instance          = new ClassReflection($phpReflection->getName());
            $laminasReflections[] = $instance;
            unset($phpReflection);
        }
        unset($phpReflections);

        return $laminasReflections;
    }

    /**
     * Return method reflection by name
     *
     * @param  string $name
     * @return MethodReflection
     */
    public function getMethod($name)
    {
        $method = new MethodReflection($this->getName(), parent::getMethod($name)->getName());

        return $method;
    }

    /**
     * Get reflection objects of all methods
     *
     * @param  string $filter
     * @return MethodReflection[]
     */
    public function getMethods($filter = -1)
    {
        $methods = array();
        foreach (parent::getMethods($filter) as $method) {
            $instance  = new MethodReflection($this->getName(), $method->getName());
            $methods[] = $instance;
        }

        return $methods;
    }

    /**
     * Get parent reflection class of reflected class
     *
     * @return ClassReflection|bool
     */
    public function getParentClass()
    {
        $phpReflection = parent::getParentClass();
        if ($phpReflection) {
            $laminasReflection = new ClassReflection($phpReflection->getName());
            unset($phpReflection);

            return $laminasReflection;
        }

        return false;
    }

    /**
     * Return reflection property of this class by name
     *
     * @param  string $name
     * @return PropertyReflection
     */
    public function getProperty($name)
    {
        $phpReflection  = parent::getProperty($name);
        $laminasReflection = new PropertyReflection($this->getName(), $phpReflection->getName());
        unset($phpReflection);

        return $laminasReflection;
    }

    /**
     * Return reflection properties of this class
     *
     * @param  int $filter
     * @return PropertyReflection[]
     */
    public function getProperties($filter = -1)
    {
        $phpReflections  = parent::getProperties($filter);
        $laminasReflections = array();
        while ($phpReflections && ($phpReflection = array_shift($phpReflections))) {
            $instance          = new PropertyReflection($this->getName(), $phpReflection->getName());
            $laminasReflections[] = $instance;
            unset($phpReflection);
        }
        unset($phpReflections);

        return $laminasReflections;
    }

    public function toString()
    {
        return parent::__toString();
    }

    public function __toString()
    {
        return parent::__toString();
    }

    /**
     * Creates a new FileScanner instance.
     *
     * By having this as a seperate method it allows the method to be overridden
     * if a different FileScanner is needed.
     *
     * @param  string $filename
     *
     * @return FileScanner
     */
    protected function createFileScanner($filename)
    {
        return new FileScanner($filename);
    }
}
