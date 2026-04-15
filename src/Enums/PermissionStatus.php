<?php

namespace Nativephp\AllPermissionHandler\Enums;

enum PermissionStatus: int
{
    case Denied = 0;
    case Granted = 1;
    case Restricted = 2;
    case Limited = 3;
    case PermanentlyDenied = 4;
    case Provisional = 5;
}
