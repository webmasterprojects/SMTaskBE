<?php

namespace App\Enums;

enum Permission: string
{
    case USER_VIEW       = 'user.view';
    case USER_CREATE     = 'user.create';
    case USER_EDIT       = 'user.edit';
    case USER_DELETE     = 'user.delete';

    case CLIENT_VIEW     = 'client.view';
    case CLIENT_CREATE   = 'client.create';
    case CLIENT_EDIT     = 'client.edit';
    case CLIENT_DELETE   = 'client.delete';

    case PROPERTY_VIEW   = 'property.view';
    case PROPERTY_CREATE = 'property.create';
    case PROPERTY_EDIT   = 'property.edit';
    case PROPERTY_DELETE = 'property.delete';

    case ASSET_VIEW      = 'asset.view';
    case ASSET_CREATE    = 'asset.create';
    case ASSET_EDIT      = 'asset.edit';
    case ASSET_DELETE    = 'asset.delete';

    case ROUTINE_VIEW    = 'routine.view';
    case ROUTINE_CREATE  = 'routine.create';
    case ROUTINE_EDIT    = 'routine.edit';
    case ROUTINE_DELETE  = 'routine.delete';

    case TASK_VIEW       = 'task.view';
    case TASK_CREATE     = 'task.create';
    case TASK_EDIT       = 'task.edit';
    case TASK_DELETE     = 'task.delete';

    case TASK_TIME_START = 'task.time.start';
    case TASK_TIME_END   = 'task.time.end';

    case REPORT_TIME_VIEW = 'report.time.view';

    // Technician visibility permissions
    case TECHNICIAN_VIEW = 'technician.view'; // can see ALL technicians
    case TECHNICIAN_SELF = 'technician.self'; // can only see themselves as technician
}
