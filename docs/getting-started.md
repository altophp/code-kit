# Getting started

After [installation](installation.md), save this as `snippet.php` beside
`vendor/` and run `php snippet.php`. The input is included, so the example does
not depend on an external source file.

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Alto\Code\Kit\CodeKit;
use Alto\Code\Slicer\CodeSource;

$code = <<<'PHP'
<?php
$total = 42;
return $total;
PHP;

$source = CodeSource::fromString($code, 'php', 'checkout.php');
$snippet = (new CodeKit())->snippet($source->lines(2, 2));

printf("%s:%d\n", $snippet->sourceName(), $snippet->startLine());
echo $snippet->code(), "\n";

foreach ($snippet->annotations() as $annotation) {
    printf(
        "%s => %s/%s\n",
        substr($snippet->code(), $annotation->offset, $annotation->length),
        $annotation->data['scope'],
        $annotation->data['tokenType'],
    );
}
```

The script prints:

```text
checkout.php:2
$total = 42;
$total => variable/unknown
= => operator/unknown
42 => number/unknown
; => punctuation/unknown
```

The result retains the source name and original line number. Its annotations
carry semantic scope and token type data; they do not contain rendered HTML.

## Create a snippet

`CodeKit::snippet()` accepts a `CodeSlice`. It parses the complete source,
creates syntax annotations, and then projects the selected byte range into a
`CodeSnippet`. Complete-source parsing preserves context when a slice starts
inside a docblock, string, heredoc, or embedded language.

```php
$snippet = (new CodeKit())->snippet(
    CodeSource::fromString($code, 'php')->lines(24, 24),
);
```

The source language controls parsing. When it is absent or unsupported, consult
the Code Highlight language documentation before changing the selected range.

## Annotate a snippet

Use `annotate()` when a `CodeSnippet` already exists:

```php
use Alto\Code\Snippet\CodeSnippet;

$snippet = CodeSnippet::fromCode('$total = array_sum($prices);', 'php');
$annotated = (new CodeKit())->annotate($snippet);
```

The method parses the snippet code and returns a new value containing `syntax`
annotations. Existing metadata, selections, and annotations remain available.
Each syntax annotation contains the semantic `scope` and `tokenType` supplied
by Code Highlight; whitespace is left unannotated.

Unlike `snippet()`, `annotate()` only has the snippet content as parsing
context. Use `snippet()` with a source slice when the selected code begins
inside context established earlier in the file.
