<?php

namespace App\Enums;

enum RelationshipType: string
{
    case FATHER = 'father';
    case MOTHER = 'mother';
    case COUSIN = 'spouse';
    case FRIEND = 'sibling';
    case OTHER = 'other';
}
