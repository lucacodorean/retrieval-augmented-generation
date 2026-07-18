<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\VehicleAgentSearchRequest;
use App\Neuron\Agents\VehicleAgent;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleAgent $vehicleAgent,
    ) {
        // Empty on purpose
    }

    public function search(
        VehicleAgentSearchRequest $request
    ): JsonResponse {
        $query = $request->validated()['query'];

        return new JsonResponse(
            $this->vehicleAgent->ask($query),
            Response::HTTP_OK
        );
    }
}
