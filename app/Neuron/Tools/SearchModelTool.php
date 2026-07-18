<?php

declare(strict_types=1);

namespace App\Neuron\Tools;

use App\Neuron\Resources\RagResultResource;
use App\Rag\Contracts\RagSearchable;
use App\Rag\RagRetriever;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use NeuronAI\RAG\Document;

readonly class SearchModelTool
{
    /** @param class-string<Model&RagSearchable> $modelClass */
    public function __construct(
        private RagRetriever $retriever,
        private string $modelClass,
    ) {}

    /** @return list<array{record: array<string, mixed>, score: float}> */
    public function search(string $query, int $limit = 5): array
    {
        $documents = $this->retriever->search($this->modelClass, $query, $limit);
        $ids = array_values(array_unique(array_filter(array_map(
            fn (Document $document): mixed => $document->metadata[$this->metadataIdKey()] ?? null,
            $documents,
        ), is_int(...))));
        $records = $this->modelClass::loadRagRecords($ids);
        $resourceClass = $this->modelClass::ragResultResource();
        /** @var RagResultResource $resource */
        $resource = new $resourceClass;

        return array_values(array_filter(array_map(function (Document $document) use ($records, $resource): ?array {
            $id = $document->metadata[$this->metadataIdKey()] ?? null;
            if (! is_int($id) || ! $records->has($id)) {
                return null;
            }

            return [
                'record' => $resource->toArray($records[$id]),
                'score' => $document->score,
            ];
        }, $documents)));
    }

    private function metadataIdKey(): string
    {
        return Str::snake(class_basename($this->modelClass)).'_id';
    }
}
