<?php

/**
 * Mission / engineering artifact archive (ADR-023 D-08 continuity).
 * Distinct from scientific LocalIndex and Capability Store.
 */
return [
    'sqlite_path' => env(
        'MISSION_ARTIFACT_ARCHIVE_SQLITE',
        storage_path('app/mission_artifact_archive/archive.sqlite')
    ),
    'blob_root' => env(
        'MISSION_ARTIFACT_ARCHIVE_BLOB_ROOT',
        storage_path('app/mission_artifact_archive/blobs')
    ),
];
