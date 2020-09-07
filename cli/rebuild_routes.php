<?php
namespace Pressmind;

use Pressmind\ORM\Object\MediaObject;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

/** @var MediaObject[] $mediaObjects */
$mediaObjects = MediaObject::listAll();
foreach ($mediaObjects as $mediaObject) {
    $routes = $mediaObject->routes;
    if(!empty($routes)) {
        $old_route = $routes[0];
        $new_route = $mediaObject->buildPrettyUrl();
        $old_route->route = $new_route;
        $old_route->id_object_type = $mediaObject->id_object_type;
        $old_route->update();
    }
}
