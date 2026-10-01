<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Cap dimension families (Capability Store Design §§14–17).
 *
 * content_about ≠ query_constrainable (CAP-INV-009).
 */
enum CapabilityDimensionFamily: string
{
    case ACCESS_METHOD = 'ACCESS_METHOD';
    case SCIENTIFIC_FACET_CONTENT_ABOUT = 'SCIENTIFIC_FACET_CONTENT_ABOUT';
    case SCIENTIFIC_FACET_QUERY_CONSTRAINABLE = 'SCIENTIFIC_FACET_QUERY_CONSTRAINABLE';
    case LICENSE_CONSTRAINT = 'LICENSE_CONSTRAINT';
}
