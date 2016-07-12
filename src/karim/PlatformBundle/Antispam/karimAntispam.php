<?php

namespace karim\PlatformBundle\Antispam;

class karimAntispam
{

    public function isSpam($text)
    {
        return strlen($text) < 50;
    }

}