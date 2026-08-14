<?php

namespace App\Enums;

enum BillCategory: string
{
    case AIRTIME = 'airtime';
    case DATA = 'data';
    case ELECTRICITY = 'electricity';
    case CABLE_TV = 'cable_tv';
    case INTERNET = 'internet';
    case UTILITIES = 'utilities';
}
