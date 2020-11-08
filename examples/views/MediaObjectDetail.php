<?php
use Pressmind\Search\AbstractTemplate;

/**
 * Class Reise_SearchTeaser
 * @property \Pressmind\ORM\Object\MediaObject $_object
 */
class MediaObjectDetail extends AbstractTemplate {
    public function render() {
        return [
            'code' => $this->_object->code,
            'id' => $this->_object->getId(),
            'name' => $this->_object->name,
            'cheapest_price' => $this->_object->getCheapestPrice()
        ];
    }
}
