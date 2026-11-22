<?php

namespace Tests\Unit\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Synthesis\AnswerExpressionAccuracyGate;
use Tests\TestCase;

/**
 * Mission 75193: quantity CSQ family must accept yield-language evidence (surface mapping).
 */
final class QuantityPropertyAddressTermsTest extends TestCase
{
    public function test_quantity_family_address_terms_include_yield_language(): void
    {
        $gate = new AnswerExpressionAccuracyGate;
        $terms = $gate->propertyAddressTerms('quantity');

        $this->assertContains('yield', $terms);
        $this->assertTrue(
            $gate->haystackAddressesPropertyTerms(
                'effect of deficit irrigation on wheat yield under arid conditions',
                $terms,
            ),
        );
    }

    public function test_irrigation_address_terms_match_deficit_irrigation_titles(): void
    {
        $gate = new AnswerExpressionAccuracyGate;
        $terms = $gate->propertyAddressTerms('irrigation');

        $this->assertTrue(
            $gate->haystackAddressesPropertyTerms(
                'effects of irrigation water qualities and iron sulphate on wheat',
                $terms,
            ),
        );
    }
}
