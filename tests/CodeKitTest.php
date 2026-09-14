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

namespace Alto\Code\Kit\Tests;

use Alto\Code\Highlight\CodeParser;
use Alto\Code\Highlight\Language\LanguageInterface;
use Alto\Code\Highlight\Parser\ParsedStream;
use Alto\Code\Highlight\Parser\ParsedToken;
use Alto\Code\Highlight\Scope;
use Alto\Code\Kit\CodeKit;
use Alto\Code\Slicer\CodeSource;
use Alto\Code\Snippet\CodeAnnotation;
use Alto\Code\Snippet\CodeSnippet;
use Alto\Language\Languages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeKit::class)]
final class CodeKitTest extends TestCase
{
    public function testAnnotatesSnippetAndPreservesItsData(): void
    {
        $language = Languages::get('php');
        self::assertNotNull($language);

        $focus = new CodeAnnotation(0, 6, 'focus');
        $snippet = CodeSnippet::fromCode(
            '$total = array_sum($prices);',
            $language,
            'Example.php',
            12,
        )->selectLines(1)->annotate($focus);

        $annotated = (new CodeKit())->annotate($snippet);

        self::assertSame($snippet->code(), $annotated->code());
        self::assertSame($language, $annotated->language());
        self::assertSame('Example.php', $annotated->sourceName());
        self::assertSame(12, $annotated->startLine());
        self::assertSame([1], $annotated->selectedLines());
        self::assertContains($focus, $annotated->annotations());
        self::assertTrue($this->hasSyntaxAnnotation($annotated, '$total', Scope::Variable));
        self::assertFalse($this->hasWhitespaceAnnotation($annotated));
    }

    public function testSnippetParsesCompleteSourceBeforeSlicing(): void
    {
        $language = Languages::get('php');
        self::assertNotNull($language);

        $source = CodeSource::fromString(<<<'PHP'
<?php

final class Example
{
    /**
     * Explain the method.
     *
     * @return void
     */
    public function run(): void {}
}
PHP, $language, 'Example.php');

        $snippet = (new CodeKit())->snippet($source->lines(6, 9));

        self::assertSame(6, $snippet->startLine());
        self::assertSame("     * Explain the method.\n     *\n     * @return void\n     */", $snippet->code());
        self::assertTrue($this->hasScope($snippet, Scope::CommentDocblock));

        $dedented = $snippet->dedent();
        self::assertSame("* Explain the method.\n*\n* @return void\n*/", $dedented->code());
        self::assertTrue($this->hasScope($dedented, Scope::CommentDocblock));
    }

    public function testCreatesASingleLinePhpSnippetWithoutOpeningTag(): void
    {
        $source = CodeSource::fromString(<<<'PHP'
$subtotal = array_sum($prices);
$total = $subtotal * 1.2;
return $total;
PHP, 'php');

        $snippet = (new CodeKit())->snippet($source->lines(2, 2));

        self::assertSame('$total = $subtotal * 1.2;', $snippet->code());
        self::assertSame(2, $snippet->startLine());
        self::assertSame('php', $snippet->language()?->slug);
        self::assertTrue($this->hasSyntaxAnnotation($snippet, '$total', Scope::Variable));
        self::assertTrue($this->hasSyntaxAnnotation($snippet, '$subtotal', Scope::Variable));
    }

    public function testLeavesSnippetWithoutLanguageUnchanged(): void
    {
        $snippet = CodeSnippet::fromCode('plain text');

        self::assertSame($snippet, (new CodeKit())->annotate($snippet));
    }

    public function testRejectsAParserThatChangesTheSource(): void
    {
        $language = Languages::get('php');
        self::assertNotNull($language);

        $parser = new CodeParser(languages: [new class implements LanguageInterface {
            public function parse(string $code): ParsedStream
            {
                return new ParsedStream([new ParsedToken('changed', Scope::String)]);
            }

            public function getIdentifier(): string
            {
                return 'php';
            }
        }]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The parser did not preserve the snippet source.');

        (new CodeKit($parser))->annotate(CodeSnippet::fromCode('original', $language));
    }

    public function testIgnoresEmptyParsedTokens(): void
    {
        $language = Languages::get('php');
        self::assertNotNull($language);

        $parser = new CodeParser(languages: [new class implements LanguageInterface {
            public function parse(string $code): ParsedStream
            {
                return new ParsedStream([new ParsedToken('', Scope::Keyword)]);
            }

            public function getIdentifier(): string
            {
                return 'php';
            }
        }]);

        $snippet = (new CodeKit($parser))->annotate(CodeSnippet::fromCode('', $language));

        self::assertSame([], $snippet->annotations());
    }

    private function hasSyntaxAnnotation(CodeSnippet $snippet, string $text, Scope $scope): bool
    {
        foreach ($snippet->annotations() as $annotation) {
            if (
                'syntax' === $annotation->type
                && $text === substr($snippet->code(), $annotation->offset, $annotation->length)
                && $scope->value === ($annotation->data['scope'] ?? null)
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasWhitespaceAnnotation(CodeSnippet $snippet): bool
    {
        foreach ($snippet->annotations() as $annotation) {
            if ('syntax' === $annotation->type && '' === trim(substr(
                $snippet->code(),
                $annotation->offset,
                $annotation->length,
            ))) {
                return true;
            }
        }

        return false;
    }

    private function hasScope(CodeSnippet $snippet, Scope $scope): bool
    {
        foreach ($snippet->annotations() as $annotation) {
            if ('syntax' === $annotation->type && $scope->value === ($annotation->data['scope'] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
