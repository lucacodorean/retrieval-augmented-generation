<?php

namespace App\Providers;

use App\Rag\QdrantDocumentStoreResolver;
use Illuminate\Support\ServiceProvider;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EmbeddingsProviderInterface::class, fn () => new OllamaEmbeddingsProvider(
            config('rag.ollama.model'),
            config('rag.ollama.url'),
        ));

        $this->app->singleton(QdrantDocumentStoreResolver::class, fn () => new QdrantDocumentStoreResolver(
            config('rag.qdrant.base_url'),
            config('rag.qdrant.key'),
            config('rag.qdrant.dimension'),
            config('rag.qdrant.point_id_namespace'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
