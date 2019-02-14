<?php declare(strict_types = 1);

namespace Symbiote\SilverstripePHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Type;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticResolvableType;
use PHPStan\Type\VerbosityLevel;

class DataListType extends ObjectType implements StaticResolvableType
{
    public function __construct(string $dataListClassName, Type $itemType)
    {
        parent::__construct($dataListClassName);
        $this->itemType = $itemType;
    }

    public function describe(VerbosityLevel $level): string
    {
        $dataListTypeClass = count($this->getReferencedClasses()) === 1 ? $this->getReferencedClasses()[0] : '';
        $itemTypeClass = count($this->itemType->getReferencedClasses()) === 1 ? $this->itemType->getReferencedClasses()[0] : '';
        return sprintf('%s<%s>', $dataListTypeClass, $itemTypeClass);
    }

    public function getItemType(): Type
    {
        return $this->itemType;
    }

    public function getIterableValueType(): Type
    {
        return $this->itemType;
    }

    public function resolveStatic(string $className): Type
    {
        return $this;
    }

    public function changeBaseClass(string $className): StaticResolvableType
    {
        return $this;
    }

    public function isDocumentableNatively(): bool
    {
        return true;
    }

    // IterableTrait

    public function canCallMethods(): TrinaryLogic
    {
        return true;
    }

    public function hasMethod(string $methodName): bool
    {
        return parent::hasMethod($methodName);
    }

    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
    {
        return parent::getMethod($methodName, $scope);
    }

    public function isClonable(): bool
    {
        return true;
    }

    public function canAccessProperties(): TrinaryLogic
    {
        return parent::canAccessProperties();
    }

    public function hasProperty(string $propertyName): bool
    {
        return parent::hasProperty($propertyName);
    }

    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
    {
        return parent::getProperty($propertyName, $scope);
    }
}
