<?php
declare(strict_types=1);

/** Escape a value for safe output inside HTML. Use on EVERY dynamic value. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect to a path inside the app (e.g. '/doctors/index.php') and stop. */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** Read a trimmed string from an array like $_POST; arrays/garbage become ''. */
function input_string(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Read a positive integer from $_GET / $_POST, or null if missing/invalid. */
function int_param(array $source, string $key): ?int
{
    $value = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false ? null : $value;
}

// ---- Flash messages (shown once after a redirect) ---------------------------

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ---- CSRF protection --------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Call at the top of every POST handler. Stops the request if the token is wrong. */
function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('This form expired or is invalid. Go back, reload the page, and try again.');
    }
}

/** Reject anything that is not a POST request (used by action-only endpoints). */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed.');
    }
}

// ---- Errors -----------------------------------------------------------------

/** True if a PDOException was caused by a UNIQUE constraint violation (MySQL 1062). */
function is_duplicate_key(PDOException $e): bool
{
    return (int) ($e->errorInfo[1] ?? 0) === 1062;
}

function abort_404(): void
{
    http_response_code(404);
    $pageTitle = 'Not found';
    $activeNav = '';
    require __DIR__ . '/header.php';
    echo '<h1>Page not found</h1><p>The record you asked for does not exist.</p>';
    require __DIR__ . '/footer.php';
    exit;
}

// ---- Shared validation helpers ----------------------------------------------

/** Strip spaces and dashes: "98765 43210" -> "9876543210". */
function normalize_phone(string $raw): string
{
    return (string) preg_replace('/[\s\-]+/', '', $raw);
}

/** Expects an already-normalized phone: 7-15 digits, optional leading +. */
function is_valid_phone(string $normalized): bool
{
    return preg_match('/^\+?\d{7,15}$/', $normalized) === 1;
}

/**
 * Parse a strict YYYY-MM-DD string. Returns null for anything else, including
 * impossible dates like 2020-02-30 (PHP alone would roll those into March).
 */
function parse_ymd(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return ($date !== false && $date->format('Y-m-d') === $value) ? $date : null;
}
