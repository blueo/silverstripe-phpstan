<?php declare(strict_types = 1);

namespace Symbiote\SilverstripePHPStan\Type;

use Exception;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use Symbiote\SilverstripePHPStan\ClassHelper;
use Symbiote\SilverstripePHPStan\Utility;

class InjectorReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /** @var string[] */
    private $methodNames = [
        'get' => '',
    ];

    public function getClass(): string
    {
        return ClassHelper::Injector;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return isset($this->methodNames[$methodReflection->getName()]);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $name = $methodReflection->getName();
        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->args, $methodReflection->getVariants());
        switch ($name) {
            case 'get':
                if (count($methodCall->args) === 0) {
                    return $parametersAcceptor->getReturnType();
                }
                $arg = $methodCall->args[0]->value;
                $type = Utility::getTypeFromInjectorVariable($arg, $parametersAcceptor->getReturnType());
                return $type;
            break;

            default:
                throw new Exception('Unhandled method call: '.$name);
            break;
        }
        return $parametersAcceptor->getReturnType();
    }
}
