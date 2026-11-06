<?php

namespace App\Http\Controllers;

use CourierHub\Facades\Courier;
use Illuminate\Http\JsonResponse;
use Throwable;

class PathaoLocationController extends Controller
{
    public function cities(): JsonResponse
    {
        return $this->lookup(fn (): array => Courier::driver('pathao')->getCities());
    }

    public function zones(int $cityId): JsonResponse
    {
        abort_unless($cityId > 0, 404);

        return $this->lookup(fn (): array => Courier::driver('pathao')->getZones($cityId));
    }

    public function areas(int $zoneId): JsonResponse
    {
        abort_unless($zoneId > 0, 404);

        return $this->lookup(fn (): array => Courier::driver('pathao')->getAreas($zoneId));
    }

    private function lookup(callable $callback): JsonResponse
    {
        abort_unless(config('courierhub.couriers.pathao.enabled'), 404);

        try {
            return response()->json(array_map(
                fn ($area): array => ['id' => $area->id, 'name' => $area->name],
                $callback(),
            ));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Pathao location data is temporarily unavailable.'], 503);
        }
    }
}
