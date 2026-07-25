<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\VehicleAgentSearchRequest;
use App\Http\Requests\WorkflowCheckRequest;
use App\Models\WorkflowInterrupt;
use App\Neuron\Agents\FrenchTranslationAgent;
use App\Neuron\Agents\RomanianTranslationAgent;
use App\Neuron\Agents\VehicleAgent;
use App\Neuron\Workflows\EmailQuery\Data\Translation;
use App\Neuron\Workflows\EmailQuery\EmailQueryWorkflow;
use Exception;
use Illuminate\Http\JsonResponse;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Workflow\Persistence\EloquentPersistence;
use Symfony\Component\HttpFoundation\Response;

class EmailSendController extends Controller
{
    public function __construct(
        private readonly VehicleAgent $vehicleAgent,
        private readonly RomanianTranslationAgent $romanianAgent,
        private readonly FrenchTranslationAgent $frenchAgent,
    ) {}

    /**
     * @throws WorkflowException
     */
    public function startWorkflow(
        VehicleAgentSearchRequest $request
    ): JsonResponse {

        $workflow = new EmailQueryWorkflow(
            query: $request->validated()['query'],
            vehicleAgent: $this->vehicleAgent,
            romanianAgent: $this->romanianAgent,
            frenchAgent: $this->frenchAgent,
            persistence: new EloquentPersistence(WorkflowInterrupt::class)
        );

        $finalState = $workflow->init()->run();

        return new JsonResponse(
            [
                'workflow_id' => $workflow->getWorkflowId(),
                'final_state' => [
                    'original_response' => $finalState->originalResponse(),
                    'translations' => array_map(
                        static fn (Translation $translation): array => [
                            'language' => $translation->language->value,
                            'text' => $translation->text,
                        ],
                        $finalState->translations(),
                    ),
                ],
            ],
            Response::HTTP_OK
        );
    }

    public function checkWorkflowInterrupts(WorkflowCheckRequest $request): JsonResponse
    {
        $workflowId = $request->validated()['workflow_id'];

        try {
            $workflowDetails = WorkflowInterrupt::where('workflow_id', $workflowId)->firstOrFail();

            return new JsonResponse(
                ['workflow_details' => $workflowDetails],
                Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return new JsonResponse(
                [
                    'workflow_id' => $workflowId,
                    'message' => $exception->getMessage(),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

    }
}
