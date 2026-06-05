<?php

namespace Infira\Klahvik\Machine\Config\Concerns;

use Infira\Klahvik\Config\Klahvik;
use Wolo\File\Path;

trait ConfigDefaults
{
    public function getTempPath(): string
    {
        return $this->get('tempPath', Path::join($this->getKlahvikPath(), 'tmp'));
    }

    public function getKlahvikPath(): string
    {
        return $this->get('klahvikPath', Klahvik::getKlahvikPath());
    }
}