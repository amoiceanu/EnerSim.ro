<?php

namespace App\Enums;

enum WeatherType: string
{
    case Clear = 'clear';
    case PartlyCloudy = 'partly_cloudy';
    case Cloudy = 'cloudy';
    case Rain = 'rain';
    case Fog = 'fog';
    case Snow = 'snow';
    case Random = 'random';
}
