<?php


namespace Pressmind\Import\Mapper;


class Link implements MapperInterface
{
    public function map($pIdMediaObject,$pLanguage, $pVarName, $pObject)
    {
        if(!is_null($pObject)) {
            $mapped_object = new \stdClass();
            $mapped_object->id_media_object = $pIdMediaObject;
            $mapped_object->section_name = '';
            $mapped_object->language = $pLanguage;
            $mapped_object->var_name = $pVarName;
            $mapped_object->href = $pObject->hrefLink;
            $mapped_object->link_type = isset($pObject->link_type) ? $pObject->link_type : null;
            return ([$mapped_object]);
        } else {
            return [];
        }
    }
}
