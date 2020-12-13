<?php
/**
 * This file is part of the ZBateson\MailMimeParser project.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace ZBateson\MailMimeParser;

use Pimple\Container as PimpleContainer;
use Pimple\Exception\UnknownIdentifierException;
use ReflectionClass;
use ReflectionParameter;

/**
 * Automatically configures classes and dependencies.
 *
 * @author Zaahid Bateson
 */
class Container extends PimpleContainer
{
    private function getParameterClass(ReflectionParameter $param)
    {
        if (method_exists($param, 'getType')) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                return '\\' . (method_exists($type, 'getName') ? $type->getName() : (string) $type);
            }
        } elseif ($param->getClass() !== null) {
            return '\\' . $param->getClass();
        }
        return null;
    }

    public function autoRegister($class)
    {
        $fn = function($c) use ($class) {
            $ref = new ReflectionClass($class);
            $cargs = ($ref->getConstructor() !== null) ? $ref->getConstructor()->getParameters() : [];
            $ap = [];
            foreach ($cargs as $arg) {
                $name = $arg->getName();
                $argClass = $this->getParameterClass($arg);
                if (!empty($c[$name])) {
                    $ap[] = $c[$name];
                } else if ($argClass !== null && !empty($c[$argClass])) {
                    $ap[] = $c[$argClass];
                } else {
                    $ap[] = null;
                }
            }
            $ret = $ref->newInstanceArgs($ap);
            return $ret;
        };
        $this[$class] = $fn;
    }

    public function offsetExists($id)
    {
        $exists = parent::offsetExists($id);
        if (!$exists && class_exists($id)) {
            $this->autoRegister($id);
            return true;
        }
        return $exists;
    }

    public function offsetGet($id)
    {
        try {
            return parent::offsetGet($id);
        } catch (UnknownIdentifierException $e) {
            if (class_exists($id)) {
                $this->autoRegister($id);
                return parent::offsetGet($id);
            }
            throw $e;
        }
    }

    public function extend($id, $callable)
    {
        $this->offsetExists($id);
        return parent::extend($id, $callable);
    }
}
