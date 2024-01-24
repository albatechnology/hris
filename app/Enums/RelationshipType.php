<?php

namespace App\Enums;

enum RelationshipType: string
{
    use BaseEnum;

    case AYAH = 'ayah';
    case IBU = 'ibu';
    case ADIK = 'adik';
    case KAKAK = 'kakak';
    case KAKEK = 'kakek';
    case NENEK = 'nenek';
    case PAMAN = 'paman';
    case BIBI = 'bibi';
    case SEPUPU = 'sepupu';
    case TEMAN = 'teman';
    case OTHER = 'other';
}
