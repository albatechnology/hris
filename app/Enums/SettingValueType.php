<?php

namespace App\Enums;

enum SettingValueType: string
{
    case STRING = 'string';
    case INT = 'int';
    case BOOL = 'bool';
    case OPTIONS = 'options';
    case ARRAY = 'array';
    case MODEL = 'model';
    // case JSON = 'json';
    // case FLOAT = 'float';
    // case DATE = 'date';
    // case DATETIME = 'datetime';
}
