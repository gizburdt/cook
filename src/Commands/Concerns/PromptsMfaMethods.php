<?php

namespace Gizburdt\Cook\Commands\Concerns;

use Gizburdt\Cook\Enums\MfaMethod;

use function Laravel\Prompts\multiselect;

trait PromptsMfaMethods
{
    /**
     * @return array<int, MfaMethod>
     */
    protected function promptMfaMethods(): array
    {
        $selected = multiselect(
            label: 'Which MFA methods?',
            options: MfaMethod::options(),
            default: [MfaMethod::App->value],
            required: false,
        );

        return array_map(
            fn (string $value): MfaMethod => MfaMethod::from($value),
            $selected
        );
    }
}
