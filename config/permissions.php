<?php

return [
    'task'        => ['task.view', 'task.create', 'task.edit', 'task.delete', 'task.status.edit', 'task.internal_note.view', 'task.internal_note.edit'],
    'work'        => ['work.view', 'work.pass', 'work.fail', 'work.no_test', 'work.severity.edit', 'work.images.upload'],
    'product'     => ['product.view', 'product.pricing.view', 'product.create', 'product.edit', 'product.delete'],
    'asset'       => ['asset.view', 'asset.create', 'asset.edit', 'asset.delete'],
    'time'        => ['time.view', 'time.start', 'time.delete'],
    'report'      => ['report.view', 'report.create', 'report.send'],
    'property'    => ['property.view', 'property.create', 'property.edit', 'property.delete'],
    'client'      => ['client.view', 'client.create', 'client.edit', 'client.delete'],
    'routine'     => ['routine.view', 'routine.create', 'routine.edit', 'routine.delete'],
    'appointment' => ['appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete'],
    'settings'    => ['settings.view', 'settings.edit'],
    'user'        => ['user.view', 'user.create', 'user.edit', 'user.delete'],
    'technician'  => ['technician.view', 'technician.self', 'technician.create', 'technician.edit', 'technician.delete'],
    'quote'       => ['quote.view', 'quote.create', 'quote.edit', 'quote.delete'],
];
