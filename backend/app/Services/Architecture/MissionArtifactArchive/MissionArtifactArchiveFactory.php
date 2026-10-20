<?php

namespace App\Services\Architecture\MissionArtifactArchive;

final class MissionArtifactArchiveFactory
{
    public static function default(): FilesystemMissionArtifactArchive
    {
        $sqlite = (string) config(
            'mission_artifact_archive.sqlite_path',
            storage_path('app/mission_artifact_archive/archive.sqlite')
        );
        $blobs = (string) config(
            'mission_artifact_archive.blob_root',
            storage_path('app/mission_artifact_archive/blobs')
        );

        return new FilesystemMissionArtifactArchive($sqlite, $blobs);
    }

    public static function forTesting(string $baseDir): FilesystemMissionArtifactArchive
    {
        return new FilesystemMissionArtifactArchive(
            $baseDir.DIRECTORY_SEPARATOR.'archive.sqlite',
            $baseDir.DIRECTORY_SEPARATOR.'blobs',
        );
    }
}
