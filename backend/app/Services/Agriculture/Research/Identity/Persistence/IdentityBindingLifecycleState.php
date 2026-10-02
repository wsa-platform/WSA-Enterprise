<?php

namespace App\Services\Agriculture\Research\Identity\Persistence;

/**
 * Persistence lifecycle for Identity Binding records (storage control only).
 */
enum IdentityBindingLifecycleState: string
{
    case ACTIVE = 'ACTIVE';
    case SUPERSEDED = 'SUPERSEDED';
    case INVALIDATED = 'INVALIDATED';
}
