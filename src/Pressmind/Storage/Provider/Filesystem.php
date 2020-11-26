<?php


namespace Pressmind\Storage\Provider;


use DirectoryIterator;
use Exception;
use Pressmind\Storage\AbstractProvider;
use Pressmind\Storage\Bucket;
use Pressmind\Storage\File;
use Pressmind\Storage\ProviderInterface;

class Filesystem extends AbstractProvider implements ProviderInterface
{

    /**
     * @param File $file
     * @param Bucket $bucket
     * @return bool|true
     * @throws Exception
     */
    public function save($file, $bucket)
    {
        if(false === file_put_contents(BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name, $file->content)) {
            throw new Exception('Failed to save file: ' . BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name);
        }
        return true;
    }

    /**
     * @param File $file
     * @param Bucket $bucket
     * @return bool|true
     * @throws Exception
     */
    public function delete($file, $bucket)
    {
        if($this->fileExists($file, $bucket)) {
            if(false === unlink(BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name)) {
                throw new Exception('Failed to unlink file: ' . BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name);
            }
        }
        return true;
    }

    /**
     * @param File $file
     * @param Bucket $bucket
     * @return bool
     */
    public function fileExists($file, $bucket)
    {
        return file_exists(BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name);
    }
    /**
     * @param File $file
     * @param Bucket $bucket
     * @return File
     * @throws Exception
     */
    public function readFile($file, $bucket)
    {
        if($this->fileExists($file, $bucket)) {
            $content = file_get_contents(BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name);
            if($content !== false) {
                $file->content = $content;
                $file->hash = md5($content);
                return $file;
            } else {
                throw new Exception('Failed to read file: ' . BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name);
            }
        } else {
            throw new Exception('Failed to read file: ' . BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name . '. File does not exist.');
        }
    }

    /**
     * @param File $file
     * @param Bucket $bucket
     * @return bool|true
     * @throws Exception
     */
    public function setFileMode($file, $bucket)
    {
        if($this->fileExists($file, $bucket)) {
            if(chmod (BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name, $file->mode) !== false) {
                return true;
            } else {
                throw new Exception('Failed to chmod file: ' . BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name);
            }
        } else {
            throw new Exception('Failed to chmod file: ' . BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name . DIRECTORY_SEPARATOR . $file->name . '. File does not exist.');
        }
    }

    /**
     * @param Bucket $bucket
     * @return File[]
     */
    public function listBucket($bucket)
    {
        $files = [];
        foreach (new DirectoryIterator(BASE_PATH . DIRECTORY_SEPARATOR . $bucket->name) as $item) {
            if($item->isFile()) {
                $file = new File($bucket);
                $file->name = $item->getFilename();
                $files[] = $file;
            }
        }
        return $files;
    }
}
