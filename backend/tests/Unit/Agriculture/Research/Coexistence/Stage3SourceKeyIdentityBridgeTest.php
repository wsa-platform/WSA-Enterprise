<?php

namespace Tests\Unit\Agriculture\Research\Coexistence;

use App\Services\Agriculture\Research\Coexistence\CoexistenceBindingClass;
use App\Services\Agriculture\Research\Coexistence\Stage3SourceKeyIdentityBridge;
use PHPUnit\Framework\TestCase;

/**
 * IU-09 — Stage-3 sourceKey → IU-01 identity bridge.
 */
final class Stage3SourceKeyIdentityBridgeTest extends TestCase
{
    public function test_known_stage3_keys_resolve_external_only_deterministically(): void
    {
        $bridge = new Stage3SourceKeyIdentityBridge();

        foreach (Stage3SourceKeyIdentityBridge::KNOWN_STAGE3_SOURCE_KEYS as $key) {
            $a = $bridge->resolve($key);
            $b = $bridge->resolve(strtoupper($key));

            $this->assertSame(CoexistenceBindingClass::EXTERNAL_ONLY, $a->bindingClass);
            $this->assertSame($a->toArray(), $b->toArray());
            $this->assertSame('external:'.$key, $a->externalRef?->identifier);
            $this->assertNull($a->adrId);
            $this->assertNull($a->canonicalIdentityId);
            $this->assertNotSame($key, $a->externalRef?->identifier);
        }
    }

    public function test_source_key_does_not_become_adr_id(): void
    {
        $bridge = new Stage3SourceKeyIdentityBridge();
        $resolution = $bridge->resolve('openalex');

        $this->assertNull($resolution->adrId);
        $this->assertNotSame('openalex', $resolution->externalRef?->identifier);
        $this->assertSame('external:openalex', $resolution->externalRef?->identifier);
    }

    public function test_unknown_key_is_unresolved(): void
    {
        $bridge = new Stage3SourceKeyIdentityBridge();
        $resolution = $bridge->resolve('invented_native_109');

        $this->assertSame(CoexistenceBindingClass::UNRESOLVED, $resolution->bindingClass);
        $this->assertNull($resolution->adrId);
        $this->assertNull($resolution->externalRef);
    }

    public function test_explicit_adr_binding_only_when_supplied(): void
    {
        $bridge = new Stage3SourceKeyIdentityBridge([
            'openalex' => [
                'adr_id' => 'G3-OA-01',
                'canonical_identity_id' => 'cid_openalex',
            ],
        ]);

        $bound = $bridge->resolve('openalex');
        $this->assertSame(CoexistenceBindingClass::ADR_BOUND, $bound->bindingClass);
        $this->assertSame('G3-OA-01', $bound->adrId?->value);
        $this->assertSame('cid_openalex', $bound->canonicalIdentityId?->value);
        $this->assertSame('external:openalex', $bound->externalRef?->identifier);

        $other = $bridge->resolve('crossref');
        $this->assertSame(CoexistenceBindingClass::EXTERNAL_ONLY, $other->bindingClass);
        $this->assertNull($other->adrId);
    }

    public function test_no_identity_mutation_across_calls(): void
    {
        $bridge = new Stage3SourceKeyIdentityBridge();
        $first = $bridge->resolve('fao_stat');
        $second = $bridge->resolve('fao_stat');

        $this->assertSame($first->toArray(), $second->toArray());
        $this->assertSame(CoexistenceBindingClass::EXTERNAL_ONLY, $first->bindingClass);
    }
}
