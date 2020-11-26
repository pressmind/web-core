<?php


namespace Pressmind\Storage;


use Exception;

class File
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $tags;

    /**
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $mimetype;

    /**
     * @var string
     */
    public $content;

    /**
     * @var integer
     */
    public $mode = 644;

    /**
     * @var Bucket
     */
    private $_bucket;

    /**
     * File constructor.
     * @param Bucket $bucket
     */
    public function __construct($bucket)
    {
        $this->_bucket = $bucket;
    }

    /**
     * @param string $tag
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return true
     * @throws Exception
     */
    public function save()
    {
        $this->hash = md5($this->content);
        return $this->_bucket->addFile($this);
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->_bucket->fileExists($this);
    }

    /**
     * @return File
     * @throws Exception
     */
    public function read()
    {
        return $this->_bucket->readFile($this);
    }

    /**
     * @param integer $mode
     * @return true
     * @throws Exception
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this->_bucket->setFileMode($this);
    }

    /**
     * @return true
     * @throws Exception
     */
    public function delete()
    {
        return $this->_bucket->removeFile($this);
    }
}
