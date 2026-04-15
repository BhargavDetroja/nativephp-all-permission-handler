<?php

namespace Nativephp\AllPermissionHandler\Enums;

enum ServiceStatus: int
{
    case Disabled = 0;
    case Enabled = 1;
    case NotApplicable = 2;
}
