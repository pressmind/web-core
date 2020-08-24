<?php
namespace Pressmind\Image\Processor\Adapter;


use Pressmind\Image\Processor\AdapterInterface;
use Pressmind\Log\Writer;

class ImageMagickCLI implements AdapterInterface
{
    public function process($config, $file, $derivative_name)
    {
        if(!empty(exec('which convert'))) {
            $path_info = pathinfo($file);
            $path = $path_info['dirname'];
            $new_name = $path_info['filename'] . '_' . $derivative_name . '.' . $path_info['extension'];
            if ($config->crop == true) {
                $command = 'convert ' . $file . ' -resize ' . $config->max_width . '^x' . $config->max_height . '^ -gravity ' . $config->horizontal_crop . ' -crop ' . $config->max_width . 'x' . $config->max_height . '+0+0 ' . $path . DIRECTORY_SEPARATOR . $new_name;
                Writer::write($command, WRITER::OUTPUT_FILE, 'image_processor.log');
                exec($command);
            } else {
                $command = 'convert ' . $file . ' -resize ' . $config->max_width . '^x' . $config->max_height . '^ ' . $path . DIRECTORY_SEPARATOR . $new_name;
                Writer::write($command, WRITER::OUTPUT_FILE, 'image_processor.log');
                exec($command);
            }
            return $path . DIRECTORY_SEPARATOR . $new_name;
        } else {
            Writer::write('convert (imagemagick) is not installed on system: "which convert" returned null', WRITER::OUTPUT_FILE, 'image_processor_error.log');
        }
    }
}
