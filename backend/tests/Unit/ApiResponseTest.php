<?php

namespace Tests\Unit;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_wraps_payload_in_data_envelope(): void
    {
        $response = ApiResponse::success(['status' => 'ok']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['data' => ['status' => 'ok']], $response->getData(true));
    }

    public function test_paginated_response_includes_meta(): void
    {
        $paginator = new LengthAwarePaginator(
            [['id' => 1]],
            1,
            15,
            1,
        );

        $response = ApiResponse::paginated($paginator);
        $payload = $response->getData(true);

        $this->assertSame([['id' => 1]], $payload['data']);
        $this->assertSame(1, $payload['meta']['total']);
        $this->assertSame(1, $payload['meta']['current_page']);
    }

    public function test_error_helpers_return_expected_status_codes(): void
    {
        $this->assertSame(401, ApiResponse::unauthorized()->getStatusCode());
        $this->assertSame(403, ApiResponse::forbidden()->getStatusCode());
        $this->assertSame(404, ApiResponse::notFound()->getStatusCode());
        $this->assertSame(422, ApiResponse::validationError('Invalid', ['email' => ['Required']])->getStatusCode());
    }
}
