<?php

declare(strict_types=1);

namespace App\Rag\Contracts;

interface Documentable
{
    /** @return class-string<DocumentTransformer> */
    public static function documentTransformer(): string;

    /** @return list<string> */
    public static function documentRelations(): array;

    public function documentKey(): string;

    public static function ragCollection(): string;
}
