<?php declare(strict_types = 1);

namespace Symbiote\SilverstripePHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use Symbiote\SilverstripePHPStan\ClassHelper;
use Symbiote\SilverstripePHPStan\Utility;

class SingletonReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    /** @var string[] */
    private $functionNames = [
        'singleton' => '',
    ];

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return isset($this->functionNames[strtolower($functionReflection->getName())]);
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        $name = $functionReflection->getName();
        $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $functionCall->args, $functionReflection->getVariants());
        switch ($name) {
            case 'singleton':
                if (count($functionCall->args) === 0) {
                    return $parametersAcceptor->getReturnType();
                }
                // Handle singleton('HTMLText')
                $arg = $functionCall->args[0]->value;
                $type = Utility::getTypeFromInjectorVariable($arg, $parametersAcceptor->getReturnType());
                return $type;
            break;
        }
        return $functionReflection->getReturnType();
    }
}
