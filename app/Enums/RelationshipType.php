<?php

namespace App\Enums;

enum RelationshipType: string
{
    case FATHER = 'father';
    case MOTHER = 'mother';
    case COUSIN = 'cousin';
    case FRIEND = 'sibling';
    case SPOUSE = 'spouse';
    case OTHER = 'other';
}
