<?php

declare(strict_types=1);

namespace App\Rag\Contracts;

use Illuminate\Database\Eloquent\Model;
use NeuronAI\RAG\Document;

interface DocumentTransformer
{
    public static function build(Model $model): Document;
}
