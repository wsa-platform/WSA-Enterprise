<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

/**
 * Lifecycle state of one locally stored source record.
 *
 * DELETED: the source announced the deletion (OAI-PMH deleted header).
 * WITHDRAWN: the record left a complete dataset snapshot or the dataset flags it withdrawn.
 * A tombstone keeps identity and provenance but no content, and is never searchable.
 */
enum LocalRecordState: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
    case WITHDRAWN = 'withdrawn';
}
