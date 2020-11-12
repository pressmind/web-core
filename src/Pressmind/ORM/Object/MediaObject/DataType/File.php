<?php

namespace Pressmind\ORM\Object\MediaObject\DataType;
use Pressmind\ORM\Object\AbstractObject;

/**
 * Class File
 * @package Pressmind\ORM\Object\MediaObject\DataType
 * @property integer $id
 * @property integer $id_media_object
 * @property string $section_name
 * @property string $language
 * @property string $var_name
 * @property integer $id_file
 * @property integer $file_size
 * @property string $file_name
 * @property string $file_path
 * @property string $description
 * @property string $extension
 * @property string $tmp_url
 * @property string $download_url
 */
class File extends AbstractObject
{
    protected $_definitions = [
        'class' => [
            'name' => 'File',
            'namespace' => '\Pressmind\ORM\MediaObject\DataType',
        ],
        'database' => [
            'table_name' => 'pmt2core_media_object_files',
            'primary_key' => 'id',
        ],
        'properties' => [
            'id' => [
                'title' => 'id',
                'name' => 'id',
                'type' => 'integer',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'id_media_object' => [
                'title' => 'id_media_object',
                'name' => 'id_media_object',
                'type' => 'integer',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'section_name' => [
                'title' => 'section_name',
                'name' => 'section_name',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'language' => [
                'title' => 'language',
                'name' => 'language',
                'type' => 'string',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'var_name'  => [
                'title' => 'var_name',
                'name' => 'var_name',
                'type' => 'string',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'id_file' => [
                'title' => 'id_file',
                'name' => 'id_file',
                'type' => 'string',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'file_name'  => [
                'title' => 'file_name',
                'name' => 'file_name',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'file_size' => [
                'title' => 'file_size',
                'name' => 'file_size',
                'type' => 'integer',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'file_path'  => [
                'title' => 'file_path',
                'name' => 'file_path',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'description'  => [
                'title' => 'description',
                'name' => 'description',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'extension'  => [
                'title' => 'extension',
                'name' => 'extension',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'tmp_url'  => [
                'title' => 'tmp_url',
                'name' => 'tmp_url',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'download_url'  => [
                'title' => 'download',
                'name' => 'download_url',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ]
        ]
    ];
}
