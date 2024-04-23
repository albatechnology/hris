<?php

namespace App\Enums;

enum RelationshipType: string
{
    use BaseEnum;

    case FATHER = 'father';
    case MOTHER = 'mother';
    case BROTHER = 'brother';
    case GRANDFATHER = 'grandfather';
    case GRANDMOTHER = 'grandmother';
    case UNCLE = 'uncle';
    case AUNT = 'aunt';
    case COUSIN = 'cousin';
    case FRIEND = 'friend';
    case OTHER = 'other';
}
