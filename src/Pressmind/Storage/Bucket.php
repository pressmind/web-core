<?php


namespace Pressmind\Storage;


use Exception;
use Pressmind\Storage\Provider\Factory;

class Bucket
{
    /**
     * @var string
     */
    public $name;

    /**
     * Bucket constructor.
     * @param string $name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * @param $file
     * @return true
     * @throws Exception
     */
    public function addFile($file)
    {
        /** @var ProviderInterface $storageProvider */
        $storageProvider = Factory::create('Filesystem');
        return $storageProvider->save($file, $this);
    }

    /**
     * @param $file
     * @return true
     * @throws Exception
     */
    public function removeFile($file)
    {
        /** @var ProviderInterface $storageProvider */
        $storageProvider = Factory::create('Filesystem');
        return $storageProvider->delete($file, $this);
    }

    /**
     * @param $file
     * @return boolean
     */
    public function fileExists($file)
    {
        /** @var ProviderInterface $storageProvider */
        $storageProvider = Factory::create('Filesystem');
        return $storageProvider->fileExists($file, $this);
    }

    /**
     * @param File $file
     * @return File
     * @throws Exception
     */
    public function readFile($file)
    {
        /** @var ProviderInterface $storageProvider */
        $storageProvider = Factory::create('Filesystem');
        return $storageProvider->readFile($file, $this);
    }

    /**
     * @param File $file
     * @return true
     * @throws Exception
     */
    public function setFileMode($file)
    {
        /** @var ProviderInterface $storageProvider */
        $storageProvider = Factory::create('Filesystem');
        return $storageProvider->setFileMode($file, $this);
    }


    /**
     * @return File[]
     * @throws Exception
     */
    public function list() {
        /** @var ProviderInterface $storageProvider */
        $storageProvider = Factory::create('Filesystem');
        return $storageProvider->listBucket($this);
    }
}
