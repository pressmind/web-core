<?php


namespace Pressmind\REST\Controller;


class MediaObject extends AbstractController
{
    public function getByRoute($route)
    {
        return \Pressmind\ORM\Object\MediaObject::getByPrettyUrl($route);
    }

    public function getByCode($code)
    {
        return \Pressmind\ORM\Object\MediaObject::getByCode($code);
    }
}
