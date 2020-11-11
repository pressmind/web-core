<?php
namespace Custom\MediaType;

use Custom\AbstractMediaType;
use Pressmind\Registry;

class Factory {
    /**
     * @param $pMediaTypeName
     * @return AbstractMediaType
     */
    public static function create($pMediaTypeName, $pReadRelations = false) {
        $class_name = 'Custom\MediaType\\' . $pMediaTypeName;
        $object = new $class_name(null, $pReadRelations);
        return $object;
    }

    /**
     * @param $pMediaTypeId
     * @return AbstractMediaType
     */
    public static function createById($pMediaTypeId, $pReadRelations = false) {
        $config = Registry::getInstance()->get('config');
        $media_type_name = $config['data']['media_types'][$pMediaTypeId];
        return self::create($media_type_name, $pReadRelations);
    }
}
