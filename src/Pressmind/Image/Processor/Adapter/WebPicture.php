<?php
namespace Pressmind\Image\Processor\Adapter;


use Pressmind\Image\Processor\AdapterInterface;
use Pressmind\Registry;

class WebPicture implements AdapterInterface
{
    /**
     * @param \Pressmind\Image\Processor\Config $config
     * @param string $file
     * @param string $derivativeName
     * @return mixed|void
     */
    public function process($config, $file, $derivativeName)
    {
        $conf = Registry::getInstance()->get('config');
        if(isset($conf['imageprocessor']['webp_library']) && !empty(exec('which ' . $conf['imageprocessor']['webp_library'])) && true === $config->webp_create) {
            $path_info = pathinfo($file);
            $path = $path_info['dirname'];
            $new_name = $path_info['filename'] . '_' . $derivativeName . '.' . $path_info['extension'];
            $webp_name = $path_info['filename'] . '_' . $derivativeName . '.webp';
            $command = $conf['imageprocessor']['webp_library'] . ' -quiet -q ' . $config->webp_quality . ' ' . $path . DIRECTORY_SEPARATOR . $new_name . ' -o ' . $path . DIRECTORY_SEPARATOR . $webp_name;
            exec($command);
        }
    }
}
