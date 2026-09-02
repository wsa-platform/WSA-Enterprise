<?php

return [
  'public_organization_slug' => env('WSA_PUBLIC_ORG_SLUG', 'wsa-demo'),
  'openalex_mailto' => env('OPENALEX_MAILTO', 'wsa-platform@example.com'),
  'research_agent' => [
    'enabled' => filter_var(env('WSA_RESEARCH_AGENT_ENABLED', true), FILTER_VALIDATE_BOOL),
  ],
];
