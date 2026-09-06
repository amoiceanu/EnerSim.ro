<?php

namespace App\Enums;

enum GridMode: string
{
    case Prosumer = 'prosumer';
    case ZeroExport = 'zero_export';
    case Offline = 'offline';
    case Limited = 'limited';
}
