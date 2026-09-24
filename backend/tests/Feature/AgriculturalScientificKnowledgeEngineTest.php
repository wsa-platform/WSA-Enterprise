<?php

namespace Tests\Feature;

use Tests\TestCase;

class AgriculturalScientificKnowledgeEngineTest extends TestCase
{
    public function test_library_search_engine_is_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('Services/Agriculture/Research/AgriculturalScientificKnowledgeEngine.php'));
        $this->assertFalse(class_exists(
            'App\\Services\\Agriculture\\Research\\AgriculturalScientificKnowledgeEngine',
            false,
        ));
    }
}
