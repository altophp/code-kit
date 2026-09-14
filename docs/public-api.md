# Public API

## `CodeKit`

`CodeKit` accepts an optional `CodeParser` in its constructor.

### `snippet(CodeSlice $slice): CodeSnippet`

Parses the complete source, creates syntax annotations, then projects the selected byte range into
a `CodeSnippet`.

### `annotate(CodeSnippet $snippet): CodeSnippet`

Parses the snippet code and returns a new value containing `syntax` annotations. Existing snippet
metadata, selections, and annotations remain available.

Syntax annotation data contains the semantic `scope` and `tokenType` supplied by CodeHighlight.
Whitespace tokens are not annotated.
