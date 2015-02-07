<?php
namespace Corley\Middleware;

use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Config\FileLocator;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Corley\Middleware\Loader\FrankieAnnotationClassLoader;

class RouteAnnotationTest extends \PHPUnit_Framework_TestCase
{
    private $annotClassLoader;

    public function setUp()
    {
        AnnotationRegistry::registerFile(__DIR__ . "/../vendor/symfony/routing/Symfony/Component/Routing/Annotation/Route.php");
        $reader = new AnnotationReader();

        $this->annotClassLoader = new FrankieAnnotationClassLoader($reader);
    }

    public function testCollectRoutes()
    {
        $loader = new AnnotationDirectoryLoader(new FileLocator([__DIR__ . "/Stub"]), $this->annotClassLoader);
        $collections = $loader->load(__DIR__ . '/Stub');

        $this->assertCount(1, $collections);
        $collections = $collections->all();
        $route = array_pop($collections);

        $this->assertEquals("Corley\\Middleware\\Stub\\Sut", $route->getOption("_controller"));
        $this->assertEquals("anAction", $route->getOption("_method"));
    }
}

