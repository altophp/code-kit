<?php

declare(strict_types=1);

/*
 * This file is part of the ALTO library.
 *
 * © 2026-present Simon André
 *
 * For full copyright and license information, please see
 * the LICENSE file distributed with this source code.
 */

namespace Alto\Code\Kit;

use Alto\Code\Highlight\CodeParser;
use Alto\Code\Slicer\CodeSlice;
use Alto\Code\Snippet\CodeAnnotation;
use Alto\Code\Snippet\CodeSnippet;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class CodeKit
{
    private CodeParser $parser;

    public function __construct(?CodeParser $parser = null)
    {
        $this->parser = $parser ?? new CodeParser();
    }

    /**
     * Add syntax annotations to a snippet while preserving its other data.
     */
    public function annotate(CodeSnippet $snippet): CodeSnippet
    {
        $language = $snippet->language();

        if (null === $language) {
            return $snippet;
        }

        $stream = $this->parser->parse($snippet->code(), $language->slug);

        if ($stream->toString() !== $snippet->code()) {
            throw new \LogicException('The parser did not preserve the snippet source.');
        }

        $offset = 0;
        $annotations = [];

        foreach ($stream as $token) {
            $length = strlen($token->text);

            if ($length > 0 && !$token->isWhitespace()) {
                $annotations[] = new CodeAnnotation(
                    offset: $offset,
                    length: $length,
                    type: 'syntax',
                    data: [
                        'scope' => $token->scope->value,
                        'tokenType' => $token->type->value,
                    ],
                );
            }

            $offset += $length;
        }

        return $snippet->annotate(...$annotations);
    }

    /**
     * Parse the complete source, then project the selected slice.
     */
    public function snippet(CodeSlice $slice): CodeSnippet
    {
        $source = $slice->source();
        $snippet = CodeSnippet::fromCode(
            code: $source->content(),
            language: $source->language(),
            sourceName: $source->name(),
        );
        $range = $slice->range();

        return $this->annotate($snippet)->slice($range->start, $range->end);
    }
}
