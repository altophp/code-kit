# Getting started

Select a source region, then turn it into an annotated snippet:

```php
use Alto\Code\Kit\CodeKit;
use Alto\Code\Slicer\CodeSource;

$slice = CodeSource::fromFile('src/Command/BuildCommand.php')
    ->slice()
    ->method('execute');

$snippet = (new CodeKit())
    ->snippet($slice)
    ->dedent();
```

CodeKit parses the complete source before projecting the selected range. Context-sensitive syntax
therefore remains correct when a slice starts inside a docblock, string, heredoc, or embedded
language.

Use `annotate()` when a `CodeSnippet` already exists:

```php
$annotated = (new CodeKit())->annotate($snippet);
```

Pass a language slug directly and use the same start and end line to return one line without
adjacent context:

```php
$line = (new CodeKit())->snippet(
    CodeSource::fromString($code, 'php')->lines(24, 24),
);
```
