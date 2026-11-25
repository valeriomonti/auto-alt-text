<?php
/**
 * Post-Strauss patch: make implicitly nullable parameters explicit.
 *
 * PHP 8.4 deprecates `Type $param = null` (implicit nullable); the explicit
 * `?Type $param = null` must be used instead. PHP-DI 6.4.0 — the last release of
 * the 6.x branch, and the newest one still compatible with PHP 7.4 — is full of
 * them, and every one shows up as a Deprecated notice on the front end when
 * WP_DEBUG_DISPLAY is on.
 *
 * Upstream fixed this only in PHP-DI 7.x, which requires PHP >= 8.0, so we can't
 * upgrade without dropping the plugin's PHP 7.4 support. Instead we rewrite the
 * scoped copies in vendor-prefixed/ after Strauss runs. `?Type $x = null` is
 * valid PHP 7.4 syntax, so the minimum version is unaffected. Union types
 * (`A|B $x = null`, PHP 8.0+) get `|null` appended instead; they can only appear
 * in dependencies that already require PHP 8.
 *
 * Intersection types (`A&B $x = null`, PHP 8.1+) cannot be fixed this way: the
 * explicit form `(A&B)|null` needs PHP 8.2. They are reported, never rewritten,
 * and make the script exit non-zero so the problem cannot go unnoticed.
 *
 * Run automatically by `composer strauss`. Idempotent: already-explicit
 * signatures are left alone.
 *
 * On PHP 7.4 `#[...]` is tokenized as a line comment, so a parameter sharing a
 * line with an attribute is invisible there. Such code cannot run on PHP 7.4
 * anyway; the release workflow's PHP 8.4 lint catches anything left over.
 *
 * Usage: php bin/patch-implicit-nullable.php [--check] [directory]
 *
 * With --check nothing is written and the exit code is non-zero if any parameter
 * still needs patching — used by the release workflow to prove the patch ran.
 */

declare(strict_types=1);

$arguments = array_slice($argv, 1);
$checkOnly = in_array('--check', $arguments, true);
$positional = array_values(array_filter($arguments, static function (string $argument): bool {
    return strpos($argument, '--') !== 0;
}));

$targetDir = $positional[0] ?? __DIR__ . '/../vendor-prefixed';

if (!is_dir($targetDir)) {
    fwrite(STDERR, "patch-implicit-nullable: directory not found: {$targetDir}\n");
    exit(1);
}

$patchedFiles = 0;
$patchedParams = 0;
$unfixable = [];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(realpath($targetDir), FilesystemIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $original = file_get_contents($file->getPathname());
    if ($original === false) {
        fwrite(STDERR, "patch-implicit-nullable: cannot read {$file->getPathname()}\n");
        exit(1);
    }

    $patched = patchSource($original, $count, $problems);

    foreach ($problems as $problem) {
        $unfixable[] = "{$file->getPathname()}:{$problem}";
    }

    if ($count > 0) {
        if (!$checkOnly && file_put_contents($file->getPathname(), $patched) === false) {
            fwrite(STDERR, "patch-implicit-nullable: cannot write {$file->getPathname()}\n");
            exit(1);
        }
        $patchedFiles++;
        $patchedParams += $count;
    }
}

if ($unfixable !== []) {
    fwrite(STDERR, "patch-implicit-nullable: implicitly nullable intersection-type parameter(s) cannot be made explicit while keeping PHP 7.4 support:\n");
    foreach ($unfixable as $location) {
        fwrite(STDERR, "  - {$location}\n");
    }
}

if ($checkOnly) {
    if ($unfixable !== []) {
        exit(1);
    }

    if ($patchedParams > 0) {
        fwrite(STDERR, "patch-implicit-nullable: {$patchedParams} implicitly nullable parameter(s) still present in {$patchedFiles} file(s) — the patch did not run.\n");
        exit(1);
    }

    echo "patch-implicit-nullable: no implicitly nullable parameters left.\n";
    exit(0);
}

echo "patch-implicit-nullable: {$patchedParams} parameter(s) made explicitly nullable in {$patchedFiles} file(s).\n";

exit($unfixable === [] ? 0 : 1);

/**
 * Rewrites every implicitly nullable parameter in a PHP source string.
 *
 * @param array<int, string>|null $problems Filled with "line N: description" for
 *                                         parameters that cannot be rewritten.
 */
function patchSource(string $code, ?int &$count = null, ?array &$problems = null): string
{
    $count = 0;
    $problems = [];
    $tokens = token_get_all($code);
    $total = count($tokens);

    // Token index => string to inject before/after the token.
    $prefixes = [];
    $suffixes = [];
    // Token indexes to drop from the output.
    $removals = [];

    for ($i = 0; $i < $total; $i++) {
        if (!isTokenOfType($tokens[$i], [T_FUNCTION, T_FN])) {
            continue;
        }

        // `use function Foo\bar;` is not a declaration.
        $previous = previousSignificant($tokens, $i);
        if ($previous !== null && isTokenOfType($tokens[$previous], [T_USE])) {
            continue;
        }

        $open = findParameterListStart($tokens, $i);
        if ($open === null) {
            continue;
        }

        $close = findMatching($tokens, $open, '(', ')');
        if ($close === null) {
            continue;
        }

        $parameters = splitParameters($tokens, $open + 1, $close - 1);

        foreach ($parameters as $position => $parameter) {
            $problem = null;
            $edits = nullableEdits(
                $tokens,
                $parameter[0],
                $parameter[1],
                hasLaterRequiredParameter($tokens, $parameters, $position),
                $problem
            );

            if ($problem !== null) {
                $problems[] = 'line ' . lineOf($tokens, $parameter[0], $parameter[1]) . ": {$problem}";
            }

            if ($edits === []) {
                continue;
            }

            foreach ($edits as $edit) {
                [$kind, $index, $text] = $edit;
                if ($kind === 'before') {
                    $prefixes[$index] = $text;
                } elseif ($kind === 'after') {
                    $suffixes[$index] = $text;
                } else {
                    $removals[$index] = true;
                }
            }
            $count++;
        }

        $i = $close;
    }

    if ($count === 0) {
        return $code;
    }

    $out = '';
    foreach ($tokens as $index => $token) {
        $out .= $prefixes[$index] ?? '';
        if (!isset($removals[$index])) {
            $out .= is_array($token) ? $token[1] : $token;
        }
        $out .= $suffixes[$index] ?? '';
    }

    return $out;
}

/**
 * Decides whether a single parameter needs patching.
 *
 * @param bool        $beforeRequired Whether a later parameter of the same signature is required.
 * @param string|null $problem        Set when the parameter needs patching but cannot be patched.
 * @return array<int, array{0:string,1:int,2:string}> Edits as [kind, token index, text].
 */
function nullableEdits(array $tokens, int $start, int $end, bool $beforeRequired, ?string &$problem = null): array
{
    $i = skipParameterPrefix($tokens, $start, $end);
    if ($i === null) {
        return [];
    }

    // Collect the type declaration, which ends at the variable name (possibly
    // preceded by a by-ref `&` or a variadic `...`).
    $typeStart = null;
    $typeEnd = null;

    while ($i <= $end) {
        $token = $tokens[$i];

        if (isTokenOfType($token, [T_VARIABLE]) || isTokenOfType($token, [T_ELLIPSIS])) {
            break;
        }

        // PHP 8.1+ tokenizes the by-reference `&` of `A &$x` separately from the
        // `&` of an intersection type `A&B $x`.
        if (defined('T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG')
            && isTokenOfType($token, [T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG])
        ) {
            break;
        }

        if ($token === '&') {
            // PHP < 8.1: `A&B $x` is an intersection type; `A &$x` is by-reference.
            $next = nextSignificant($tokens, $i, $end);
            if ($next === null || isTokenOfType($tokens[$next], [T_VARIABLE, T_ELLIPSIS])) {
                break;
            }
        }

        if (!isTokenOfType($token, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
            $typeStart = $typeStart ?? $i;
            $typeEnd = $i;
        }

        $i++;
    }

    if ($typeStart === null) {
        return []; // Untyped parameter — nothing to make nullable.
    }

    $defaultTokens = nullDefaultTokens($tokens, $i, $end);
    if ($defaultTokens === null) {
        return [];
    }

    $type = '';
    for ($j = $typeStart; $j <= $typeEnd; $j++) {
        $type .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
    }
    $type = trim($type);

    if ($type === '' || $type[0] === '?') {
        return []; // Already explicitly nullable.
    }

    // `mixed` already includes null; `null` on its own is fine too.
    if (preg_match('/(^|\|)\s*\\\\?(mixed|null)\s*($|\|)/i', $type)) {
        return [];
    }

    if (strpos($type, '&') !== false) {
        // Only `(A&B)|null` (PHP 8.2+) is valid here — report, don't rewrite.
        $problem = "intersection type `{$type}` with a null default";
        return [];
    }

    $edits = strpos($type, '|') !== false
        ? [['after', $typeEnd, '|null']]
        : [['before', $typeStart, '?']];

    // An implicitly nullable parameter sitting before a required one is exempt
    // from the "optional before required" deprecation — making it explicit would
    // trigger that other deprecation instead. PHP already treats it as required,
    // so dropping `= null` keeps the signature semantically identical.
    if ($beforeRequired) {
        foreach ($defaultTokens as $index) {
            $edits[] = ['remove', $index, ''];
        }
    }

    return $edits;
}

/**
 * Whether any parameter after $position is required (no default, not variadic).
 *
 * @param array<int, array{0:int,1:int}> $parameters
 */
function hasLaterRequiredParameter(array $tokens, array $parameters, int $position): bool
{
    $count = count($parameters);

    for ($i = $position + 1; $i < $count; $i++) {
        [$start, $end] = $parameters[$i];

        $isRequired = true;
        for ($j = $start; $j <= $end; $j++) {
            if ($tokens[$j] === '=' || isTokenOfType($tokens[$j], [T_ELLIPSIS])) {
                $isRequired = false;
                break;
            }
        }

        if ($isRequired) {
            return true;
        }
    }

    return false;
}

/**
 * Skips attributes, visibility modifiers and `readonly` at the start of a parameter.
 */
function skipParameterPrefix(array $tokens, int $start, int $end): ?int
{
    $modifiers = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
    if (defined('T_READONLY')) {
        $modifiers[] = T_READONLY;
    }

    $i = $start;
    while ($i <= $end) {
        if (defined('T_ATTRIBUTE') && isTokenOfType($tokens[$i], [T_ATTRIBUTE])) {
            $closing = findMatching($tokens, $i, '[', ']');
            if ($closing === null) {
                return null;
            }
            $i = $closing + 1;
            continue;
        }

        if (isTokenOfType($tokens[$i], $modifiers)) {
            $i++;
            continue;
        }

        return $i;
    }

    return null;
}

/**
 * Returns the token indexes making up a `= null` default, or null when the
 * remainder of the parameter is anything else.
 *
 * @return array<int, int>|null
 */
function nullDefaultTokens(array $tokens, int $start, int $end): ?array
{
    $equals = null;
    $value = '';
    $last = null;

    for ($i = $start; $i <= $end; $i++) {
        $token = $tokens[$i];

        if (isTokenOfType($token, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
            continue;
        }

        if ($equals === null) {
            if ($token === '=') {
                $equals = $i;
                continue;
            }
            // The variable name, optionally passed by reference (`&$x`).
            if ($token === '&' || isTokenOfType($token, [T_VARIABLE])
                || (defined('T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG')
                    && isTokenOfType($token, [T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG]))
            ) {
                continue;
            }
            return null;
        }

        $value .= is_array($token) ? $token[1] : $token;
        $last = $i;
    }

    // After `=` we only accept the constant `null`, optionally fully qualified
    // (`\null` is one token on PHP 8, two on PHP 7.4).
    if ($equals === null || !in_array(strtolower($value), ['null', '\\null'], true)) {
        return null;
    }

    // Swallow the whitespace before `=` too, so removing the default doesn't
    // leave a dangling space after the variable name.
    if ($equals > $start && isTokenOfType($tokens[$equals - 1], [T_WHITESPACE])) {
        $equals--;
    }

    return range($equals, $last);
}

/**
 * Line number of the first token of a parameter, for error messages.
 */
function lineOf(array $tokens, int $start, int $end): int
{
    for ($i = $start; $i <= $end; $i++) {
        if (is_array($tokens[$i]) && !isTokenOfType($tokens[$i], [T_WHITESPACE])) {
            return $tokens[$i][2];
        }
    }

    return is_array($tokens[$start]) ? $tokens[$start][2] : 0;
}

/**
 * @return array<int, array{0:int,1:int}> Start/end token index of each parameter.
 */
function splitParameters(array $tokens, int $start, int $end): array
{
    $parameters = [];
    $depth = 0;
    $current = $start;

    for ($i = $start; $i <= $end; $i++) {
        $token = $tokens[$i];

        if (in_array($token, ['(', '[', '{'], true)) {
            $depth++;
        } elseif (in_array($token, [')', ']', '}'], true)) {
            $depth--;
        } elseif (defined('T_ATTRIBUTE') && isTokenOfType($token, [T_ATTRIBUTE])) {
            $depth++;
        } elseif ($token === ',' && $depth === 0) {
            $parameters[] = [$current, $i - 1];
            $current = $i + 1;
        }
    }

    if ($current <= $end) {
        $parameters[] = [$current, $end];
    }

    return array_values(array_filter($parameters, static function (array $range) use ($tokens): bool {
        for ($i = $range[0]; $i <= $range[1]; $i++) {
            if (!isTokenOfType($tokens[$i], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                return true;
            }
        }
        return false;
    }));
}

function findParameterListStart(array $tokens, int $functionIndex): ?int
{
    $total = count($tokens);

    for ($i = $functionIndex + 1; $i < $total; $i++) {
        if ($tokens[$i] === '(') {
            return $i;
        }
        if ($tokens[$i] === '{' || $tokens[$i] === ';') {
            return null;
        }
    }

    return null;
}

function findMatching(array $tokens, int $openIndex, string $open, string $close): ?int
{
    $total = count($tokens);
    $depth = 0;

    for ($i = $openIndex; $i < $total; $i++) {
        $token = $tokens[$i];

        // `#[` opens a bracket pair that closes with a plain `]`.
        if (defined('T_ATTRIBUTE') && isTokenOfType($token, [T_ATTRIBUTE]) && $open === '[') {
            $depth++;
            continue;
        }

        if ($token === $open) {
            $depth++;
        } elseif ($token === $close) {
            $depth--;
            if ($depth === 0) {
                return $i;
            }
        }
    }

    return null;
}

function nextSignificant(array $tokens, int $index, ?int $end = null): ?int
{
    $end = $end ?? count($tokens) - 1;

    for ($i = $index + 1; $i <= $end; $i++) {
        if (!isTokenOfType($tokens[$i], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
            return $i;
        }
    }

    return null;
}

function previousSignificant(array $tokens, int $index): ?int
{
    for ($i = $index - 1; $i >= 0; $i--) {
        if (!isTokenOfType($tokens[$i], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
            return $i;
        }
    }

    return null;
}

/**
 * @param array{0:int,1:string,2:int}|string $token
 * @param array<int, int> $types
 */
function isTokenOfType($token, array $types): bool
{
    return is_array($token) && in_array($token[0], $types, true);
}
