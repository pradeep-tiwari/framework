<?php

namespace Lightpack;

use Lightpack\Http\Response;
use Lightpack\Routing\Dispatcher;
use Lightpack\Container\Container;

final class App
{
    public static function run(Container $container): Response 
    {
        /**
         * Prepare variables. 
         */
        $response = $container->get('response');
        $filter = $container->get('filter');
        $dispatcher = new Dispatcher($container);
        $route = $container->get('router')->route();

        /**
         * Boot app filters.
         */
        require_once DIR_BOOTSTRAP . '/filters.php';

        /**
         * Process before filters.
         */
        $result = $filter->processBeforeFilters($route);
        
        if($result instanceof Response) {
            // $result->send();
            return $result;
        }

        /**
         * Dispatch app request.
         */
        $result = $dispatcher->dispatch();

        if($result instanceof Response) {
            $response = $result;
        }

        /**
         * Process after filters.
         */
        $filter->setResponse($response);
        $response = $filter->processAfterFilters($route);

        return $response;
    }
}