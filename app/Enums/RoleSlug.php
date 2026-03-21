<?php

namespace App\Enums;

enum RoleSlug: string
{
    case SuperAdmin = 'super_admin';
    case AcademyDirector = 'academy_director';
    case Coach = 'coach';
    case Parent = 'parent';
    case Player = 'player';
}
