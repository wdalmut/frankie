<?php
namespace Corley\Middleware;

use ReflectionClass;
use ReflectionMethod;
use DI\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Doctrine\Common\Annotations\AnnotationReader;
use Corley\Middleware\Annotations\Before;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class App
{
    private $container;
    private $router;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function setRouter(UrlMatcherInterface $router)
    {
        $this->router = $router;

        return $this;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function run(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;

        try {
            $matched = $this->getRouter()->match($request->getPathInfo());

            $action     = $matched["action"];
            $controller = $matched["controller"];
            $this->executeBeforeActions($controller, $action);
            $controller = $this->getContainer()->get($controller);
            $actionReturn = call_user_func_array([$controller, $action], [$request, $response]);
        } catch (ResourceNotFoundException $e) {
            $response->setStatusCode(404);
        }

        $response->send();
    }

    private function executeBeforeActions($controller, $action)
    {
        $request = $this->request;
        $response = $this->response;

        $reader = new AnnotationReader();

        $reflClass = new ReflectionMethod($controller, $action);
        $annotations = $reader->getMethodAnnotations($reflClass);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Before) {
                $this->executeBeforeActions($annotation->targetClass, $annotation->targetMethod);
                $newController = $this->getContainer()->get($annotation->targetClass);
                call_user_func_array([$newController, $annotation->targetMethod], [
                    $request, $response
                ]);
            }
        }

        $reflClass = new ReflectionClass($controller);
        $annotations = $reader->getClassAnnotations($reflClass);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Before) {
                $this->executeBeforeActions($annotation->targetClass, $annotation->targetMethod);
                $newController = $this->getContainer()->get($annotation->targetClass);
                call_user_func_array([$newController, $annotation->targetMethod], [
                    $request, $response
                ]);
            }
        }
    }
}