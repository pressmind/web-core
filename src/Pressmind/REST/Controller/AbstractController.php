<?php


namespace Pressmind\REST\Controller;


use Exception;
use Pressmind\ORM\Object\AbstractObject;
use ReflectionClass;
use ReflectionException;

abstract class AbstractController
{
    protected $orm_class_name = null;

    /** @var AbstractObject */
    private $orm_class = null;

    /**
     * AbstractController constructor.
     * @throws ReflectionException
     */
    public function __construct()
    {
        if(is_null($this->orm_class_name)) {
            $this->orm_class_name = '\\Pressmind\\ORM\\Object\\' . (new ReflectionClass($this))->getShortName();
        }
        $this->orm_class = new $this->orm_class_name();
    }

    /**
     * @param $parameters
     * @return array|mixed|AbstractObject
     * @throws Exception
     */
    public function listAll($parameters) {
        $readRelations = false;
        $apiTemplate = null;
        if(isset($parameters['readRelations'])) {
            $readRelations = boolval($parameters['readRelations']);
            unset($parameters['readRelations']);
        }
        if(isset($parameters['apiTemplate'])) {
            $apiTemplate = $parameters['apiTemplate'];
            unset($parameters['apiTemplate']);
        }
        if(count($parameters) == 1 && isset($parameters['id'])) {
            return $this->read($parameters['id'], $readRelations, $apiTemplate);
        }
        $this->orm_class->setReadRelations($readRelations);
        if(count($parameters) == 0) $parameters = null;
        if(!is_null($apiTemplate)) {
            $result = [];
            foreach($this->orm_class->loadAll($parameters) as $object) {
                $result[] = $object->renderApiOutputTemplate($apiTemplate);
            }
            return $result;
        }
        return $this->orm_class->loadAll($parameters);
    }

    /**
     * @param $id
     * @param bool $readRelations
     * @param bool $apiTemplate
     * @return mixed|AbstractObject
     * @throws Exception
     */
    public function read($id, $readRelations = false, $apiTemplate = false) {
        $this->orm_class->read($id);
        if(!is_null($apiTemplate)) {
            return $this->orm_class->renderApiOutputTemplate($apiTemplate);
        }
        $this->orm_class->setReadRelations($readRelations);
        $this->orm_class->readRelations();
        return $this->orm_class;
    }
}
