<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSet;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSetStore;
use PHPUnit\Framework\TestCase;

final class StableAnswerResultSetStoreTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wsa_srs_'.uniqid('', true);
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->root);
        }
        parent::tearDown();
    }

    public function test_create_and_paginate_without_rerun_flags(): void
    {
        $store = new StableAnswerResultSetStore($this->root);
        $answers = [];
        for ($i = 0; $i < 12; $i++) {
            $answers[] = [
                'answer_id' => 'sha256:'.str_repeat(dechex($i % 16), 64),
                'answer' => 'Independent answer '.$i,
                'result_id' => 'r'.$i,
                'evidence_ids' => ['e'.$i],
                'source_ids' => ['openalex'],
                'position' => $i,
                'provenance' => [],
            ];
        }

        $set = $store->create(
            organizationId: 7,
            originalQuestion: 'Q',
            csqFingerprint: 'fp',
            answers: $answers,
            references: [['title' => 'Ref']],
        );

        $this->assertSame(12, $set->totalAnswers());
        $this->assertSame(2, $set->totalPages());
        $this->assertSame(StableAnswerResultSet::PAGE_SIZE, 10);

        $page1 = $set->page(1);
        $page2 = $set->page(2);
        $this->assertCount(10, $page1['answers']);
        $this->assertCount(2, $page2['answers']);
        $this->assertFalse($page1['rerun_performed']);
        $this->assertFalse($page1['observability']['search_rerun']);
        $this->assertFalse($page1['observability']['composer_rerun']);

        $loaded = $store->get(7, $set->resultSetId);
        $this->assertNotNull($loaded);
        $this->assertSame($set->resultSetId, $loaded->resultSetId);

        $this->assertNull($store->get(8, $set->resultSetId));
    }
}
