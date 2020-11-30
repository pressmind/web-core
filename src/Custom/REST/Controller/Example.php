<?php


namespace Custom\REST\Controller;


use Pressmind\REST\Controller\AbstractController;

class Example
{


    /**
     * function index will automatically be executed if no action is given in request (e.g. https://myproject/rest/example )
     * @param $parameters
     * @return array
     */
    public function index($parameters) {
        return ['action' => 'index','params' => $parameters];
    }

    public function test($parameters) {
        return ['action' => 'test','params' => $parameters];
    }

    public function foo($parameters) {
        return ['action' => 'foo','params' => $parameters];
    }
}
