<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Fails when the attribute is present in the request — including an
 * explicit `null` — and the given user lacks the given permission.
 *
 * `Rule::prohibitedIf()`/the built-in `prohibited` rule can't be used for
 * this: they're implemented as `! validateRequired()`, which treats an
 * explicit `null` the same as "attribute absent" and so lets a null value
 * straight through. A plain closure rule has the same problem from a
 * different angle: once an attribute also carries `nullable` and its
 * submitted value is null, Laravel's Validator skips every *non-implicit*
 * rule for that attribute entirely (see
 * Validator::isNotNullIfMarkedAsNullable()) — so a closure rule simply
 * never runs for `{"assignee_id": null}`.
 *
 * Implementing ValidationRule with `$implicit = true` is what makes
 * Laravel run this rule regardless of the nullable-null skip, and reading
 * presence off the full validated data array (via DataAwareRule) — rather
 * than off $value — is what lets it tell "explicit null" apart from
 * "omitted".
 */
class ProhibitedWithoutPermission implements DataAwareRule, ValidationRule
{
    public bool $implicit = true;

    /** @var array<string, mixed> */
    protected array $data = [];

    public function __construct(
        private readonly User $user,
        private readonly string $permission,
    ) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! array_key_exists($attribute, $this->data)) {
            return;
        }

        if (! $this->user->can($this->permission)) {
            $fail("You are not allowed to set {$attribute}.");
        }
    }
}
