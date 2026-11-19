<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\AgriculturalResearchAgent;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSet;
use App\Services\Agriculture\Research\Synthesis\StableAnswerResultSetStore;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Rev 6.3 — prove page reads are stable-set slices (no search/composer flags).
 */
final class StableAnswerResultSetPaginationContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wsa_srs_page_'.uniqid('', true);
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

    public function test_page_two_is_slice_only_with_rerun_flags_false(): void
    {
        $store = new StableAnswerResultSetStore($this->root);
        $answers = [];
        for ($i = 0; $i < 12; $i++) {
            $answers[] = [
                'answer_id' => 'sha256:'.str_repeat(sprintf('%x', $i % 16), 64),
                'answer' => 'Independent answer '.$i,
                'result_id' => 'r'.$i,
                'evidence_ids' => ['e'.$i],
                'source_ids' => ['openalex'],
                'position' => $i,
                'provenance' => [],
            ];
        }
        $set = $store->create(9, 'Q taxonomy', 'fp', $answers, []);
        $this->assertSame(StableAnswerResultSet::PAGE_SIZE, 10);

        $page2 = $set->page(2);
        $this->assertSame(2, $page2['page']);
        $this->assertCount(2, $page2['answers']);
        $this->assertFalse($page2['rerun_performed']);
        $this->assertFalse($page2['observability']['search_rerun']);
        $this->assertFalse($page2['observability']['composer_rerun']);
        $this->assertFalse($page2['observability']['validation_rerun']);
        $this->assertSame('Independent answer 10', $page2['answers'][0]['answer']);
        $this->assertSame($set->resultSetId, $page2['result_set_id']);
    }

    public function test_agent_page_stable_result_set_isolates_organizations(): void
    {
        $store = new StableAnswerResultSetStore($this->root);
        $set = $store->create(3, 'Q', null, [
            [
                'answer' => 'Only org 3',
                'result_id' => 'r1',
                'evidence_ids' => ['e1'],
                'source_ids' => ['openalex'],
                'position' => 0,
                'provenance' => [],
            ],
        ]);

        $agent = $this->agentWithStore($store);
        $ok = $agent->pageStableResultSet(3, $set->resultSetId, 1);
        $this->assertSame($set->resultSetId, $ok['result_set_id']);
        $this->assertFalse($ok['rerun_performed']);
        $this->assertCount(1, $ok['answers']);

        $denied = $agent->pageStableResultSet(99, $set->resultSetId, 1);
        $this->assertSame('result_set_unavailable', $denied['status']);
        $this->assertSame('RESULT_SET_FAILURE', $denied['failure_class']);
        $this->assertFalse($denied['rerun_performed']);
    }

    private function agentWithStore(StableAnswerResultSetStore $store): AgriculturalResearchAgent
    {
        $ref = new ReflectionClass(AgriculturalResearchAgent::class);
        /** @var AgriculturalResearchAgent $agent */
        $agent = $ref->newInstanceWithoutConstructor();
        $prop = $ref->getProperty('stableResultSetStore');
        $prop->setAccessible(true);
        $prop->setValue($agent, $store);

        return $agent;
    }
}
