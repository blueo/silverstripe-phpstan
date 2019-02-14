<?php declare(strict_types = 1);

namespace Symbiote\SilverstripePHPStan\Tests;

use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Cache\Cache;
use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;
use PHPStan\PhpDoc;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;
use ReflectionProperty;

abstract class ResolverTest extends \PHPStan\Testing\TestCase
{
    /** @var \PHPStan\Broker\Broker */
    private $broker;

    protected function assertTypes(
        string $file,
        string $description,
        string $expression,
        array $dynamicMethodReturnTypeExtensions = [],
        array $dynamicStaticMethodReturnTypeExtensions = [],
        array $dynamicFunctionReturnTypeExtensions = [],
        string $evaluatedPointExpression = 'die;'
    ) {

        // NOTE(Jake): 2018-04-21
        //
        // If I don't do this, I get a class not found error.
        //
        // This is due to PHPStan using a classmap for loading files in its
        // 'data' folder.
        //
        include_once($file);

        // NOTE(Jake): 2018-04-21
        //
        // Taken from:
        // - phpstan\tests\PHPStan\Analyser\NodeScopeResolverTest.php
        //
        $this->processFile($file, function (\PhpParser\Node $node, Scope $scope) use ($description, $expression, $evaluatedPointExpression) {
            $printer = new \PhpParser\PrettyPrinter\Standard();
            $printedNode = $printer->prettyPrint([$node]);
            if ($printedNode === $evaluatedPointExpression) {
                /** @var \PhpParser\Node\Expr $expressionNode */
                $expressionNode = $this->getParser()->parseString(sprintf('<?php %s;', $expression))[0];
                $type = $scope->getType($expressionNode->expr);
                $this->assertTypeDescribe(
                    $description,
                    $type->describe(VerbosityLevel::precise()),
                    sprintf('%s at %s', $expression, $evaluatedPointExpression)
                );
            }
        }, $dynamicMethodReturnTypeExtensions, $dynamicStaticMethodReturnTypeExtensions, $dynamicFunctionReturnTypeExtensions);
    }

    private function processFile(
        string $file,
        \Closure $callback,
        array $dynamicMethodReturnTypeExtensions = [],
        array $dynamicStaticMethodReturnTypeExtensions = [],
        array $dynamicFunctionReturnTypeExtensions = [],
        array $dynamicConstantNames = []
    ) {
        // NOTE(Jake): 2018-04-21
        //
        // Taken from:
        // - phpstan\tests\PHPStan\Analyser\NodeScopeResolverTest.php
        //
        $phpDocStringResolver = $this->getContainer()->getByType(PhpDocStringResolver::class);

        $printer = new \PhpParser\PrettyPrinter\Standard();
        $broker = $this->createBroker();

        // NOTE(Jake): 2018-04-22
        //
        // Hack in DynamicFunctionReturnType support
        //
        if ($dynamicFunctionReturnTypeExtensions) {
            $hack = $broker->getDynamicFunctionReturnTypeExtensions();
            $hack = array_merge($hack, $dynamicFunctionReturnTypeExtensions);
            foreach ($dynamicFunctionReturnTypeExtensions as $extension) {
                if ($extension instanceof BrokerAwareExtension) {
                    $extension->setBroker($broker);
                }
            }
            $refProperty = new \ReflectionProperty($broker, 'dynamicFunctionReturnTypeExtensions');
            $refProperty->setAccessible(true);
            $refProperty->setValue($broker, $hack);
        }

        $currentWorkingDirectory = $this->getCurrentWorkingDirectory();
        $anonymousClassNameHelper = new AnonymousClassNameHelper(new FileHelper($currentWorkingDirectory), new RelativePathHelper($currentWorkingDirectory, DIRECTORY_SEPARATOR, []));
        $typeNodeResolver = self::getContainer()->getByType(TypeNodeResolver::class);
        $typeSpecifier = $this->createTypeSpecifier($printer, $broker);

        $resolver = new NodeScopeResolver(
            $broker,
            $this->getParser(),
            new FileTypeMapper(
                $this->getParser(),
                $phpDocStringResolver,
                $this->createMock(Cache::class),
                $anonymousClassNameHelper,
                $typeNodeResolver
            ),
            new FileHelper('/'),
            $typeSpecifier,
            true,
            true,
            [
                //\EarlyTermination\Foo::class => [
                //    'doFoo',
                //],
            ]
        );
        $broker = $this->createBroker(
            $dynamicMethodReturnTypeExtensions,
            $dynamicStaticMethodReturnTypeExtensions
        );

        // NOTE(Jake): 2018-04-22
        //
        // Hack in DynamicFunctionReturnType support
        // -DUPLICATE CODE-
        //
        if ($dynamicFunctionReturnTypeExtensions) {
            $hack = $broker->getDynamicFunctionReturnTypeExtensions();
            $hack = array_merge($hack, $dynamicFunctionReturnTypeExtensions);
            foreach ($dynamicFunctionReturnTypeExtensions as $extension) {
                if ($extension instanceof BrokerAwareExtension) {
                    $extension->setBroker($broker);
                }
            }
            $refProperty = new \ReflectionProperty($broker, 'dynamicFunctionReturnTypeExtensions');
            $refProperty->setAccessible(true);
            $refProperty->setValue($broker, $hack);
        }

        $resolver->processNodes(
            $this->getParser()->parseFile($file),
            $this->createScopeFactory($broker, $typeSpecifier, $dynamicConstantNames)->create(ScopeContext::create($file)),
            $callback
        );
    }

    private function assertTypeDescribe(string $expectedDescription, string $actualDescription, string $label = '')
    {
        $this->assertSame(
            $expectedDescription,
            $actualDescription,
            $label
        );
    }
}
