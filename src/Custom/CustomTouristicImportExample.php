<?php


namespace Custom;


use Pressmind\Import\ImportInterface;
use Pressmind\ORM\Object\Touristic;

class CustomTouristicImportExample implements ImportInterface
{

    private $_fake_data = '"ABC";"12.12.2020";"16.12.2020";6;"Doppelzimmer";"14.80";"2"
    "ABC";"12.12.2020";"16.12.2020";6;"Einzelzimmer";"16.80";"1"
    "ABC";"12.12.2020";"16.12.2020";6;"Studio";"12.80";"3"
    "DEF";"12.12.2020";"16.12.2020";6;"Doppelzimmer";"20.80";"2"
    "DEF";"12.12.2020";"16.12.2020";6;"Einzelzimmer";"24.80";"1"
    "DEF";"12.12.2020";"16.12.2020";6;"Studio";"18.80";"3"';

    private $_data;

    public function __construct($data)
    {
        $this->_data = $data;
    }

    public function import() {
        $array_of_lines = explode("\n", $this->_fake_data);
        $booking_packages = [];
        $bp = null;
        foreach ($array_of_lines as $line) {
            $array = str_getcsv($line, ";");
            $booking_packages[$array[0]][] = $array;
        }
        $i = 0;
        foreach ($booking_packages as $code => $booking_package_data) {
            if($code = $this->_data->import_id) {
                $id = 100 + $i . '12345';
                $date = new Touristic\Date();
                $date->season = $code;
                $date->id = $id;
                $date->id_booking_package = $id;
                $hp = new Touristic\Housing\Package();
                $hp->id = $id;
                $hp->options = [];
                $hp->room_type = 'room';
                $bp = new Touristic\Booking\Package();
                $bp->id = $id;
                $bp->id_origin = $this->_data->id_my_content;
                $bp->id_media_object = $this->_data->id_media_object;
                $bp->price_mix = 'date_housing';
                $hp->id_media_object = $this->_data->id_media_object;
                $hp->id_booking_package = $bp->id;
                $oi=0;
                foreach ($booking_package_data as $option_info) {
                    $option = new Touristic\Option();
                    $option->id = $id . $oi;
                    $option->id_booking_package = $bp->id;
                    $option->id_housing_package = $hp->id;
                    $option->id_media_object = $this->_data->id_media_object;
                    $option->price = $option_info[5];
                    $option->occupancy = $option_info[6];
                    $option->name = $option_info[4];
                    $option->season = $code;
                    $option->type = 'housing_option';
                    $hp->options[] = $option;
                    $date->departure = \DateTime::createFromFormat('d.m.Y', $option_info[2]);
                    $date->arrival = \DateTime::createFromFormat('d.m.Y', $option_info[1]);
                    $bp->duration = $option_info[3];
                    $oi++;
                }
                $bp->housing_packages = [$hp];
                $bp->dates = [$date];
            }
        }
        $bp->create();
        return $bp->toStdClass();
    }

    public function getLog()
    {
        // TODO: Implement getLog() method.
    }

    public function getErrors()
    {
        // TODO: Implement getErrors() method.
    }
}
