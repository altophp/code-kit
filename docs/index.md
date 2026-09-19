# Alto Code Kit

ALTO Code Kit combines source selection, semantic parsing, and immutable
snippet data. It parses a complete source before projecting the selected range,
so a slice retains the context required to recognize comments, strings,
heredocs, and embedded languages.

```php
use Alto\Code\Kit\CodeKit;
use Alto\Code\Slicer\CodeSource;

$source = CodeSource::fromString("<?php\nreturn 42;\n", 'php', 'answer.php');
$snippet = (new CodeKit())->snippet($source->lines(2, 2));
echo $snippet->code();
```

The example prints `return 42;` and the returned `CodeSnippet` carries syntax
annotations for its tokens.

## Documentation

- [Installation](installation.md): install Code Kit and its three component packages.
- [Getting started](getting-started.md): create or annotate a portable code snippet.

## Boundaries

Code Slicer owns source selection, Code Highlight owns syntax parsing, and Code
Snippet owns the immutable result. Code Kit composes them. Rendering to HTML,
SVG, PNG, terminals, or slides remains the responsibility of consumers.
