<?php

namespace App\Services\Agriculture\Research\Capability;

/**
 * Controlled Cap access-method dimension codes (Capability Store Design §14).
 *
 * Declaring a method without CapVer evidence is forbidden at population time;
 * this enum only freezes the vocabulary for domain records.
 */
enum CapabilityAccessMethod: string
{
    case WEB_UI_SEARCH = 'web_ui_search';
    case MACHINE_ACCESS = 'machine_access';
    case API = 'api';
    case OAI_PMH = 'oai_pmh';
    case RSS = 'rss';
    case ATOM = 'atom';
    case BULK_DOWNLOAD = 'bulk_download';
    case STATIC_DOWNLOAD = 'static_download';
    case METADATA_ACCESS = 'metadata_access';
    case FULL_TEXT_ACCESS = 'full_text_access';
}
