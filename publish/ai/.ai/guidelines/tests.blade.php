# Tests

## Pest
- Don't use multiple `expect()`'s in tests, rather use `and()`
@verbatim
<code-snippet name="Expect" lang="php">
    expect($user)
        ->toBeInstanceOf(User::class)
        ->type->toBe('foo')
        ->message->toBe('bar')
        ->and($userTwo)->toBeInstanceOf(User::class);
</code-snippet>
@endverbatim
- Place each attribute assertion on a new line
@verbatim
    <code-snippet name="Assertions" lang="php">
        expect($user)
            ->toBeInstanceOf(User::class)
            ->type->toBe('foo')
            ->message->toBe('bar')
    </code-snippet>
@endverbatim
- Keep `->not` on the same line as the attribute assertion
@verbatim
    <code-snippet name="Assertions" lang="php">
        expect($user)
            ->not->toBeInstanceOf(OtherUser::class)
            ->type->not->toBe('foo')
            ->message->not->toBe('bar')
    </code-snippet>
@endverbatim
- Keep `->and()` and the subsequent assertion on the same line. When multiple assertions will follow, put them on a new line
@verbatim
    <code-snippet name="Assertions" lang="php">
        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($post)->toBeInstanceOf(User::class);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($post)
            ->toBeInstanceOf(User::class)
            ->message->toBe('foo')
            ->id->toBe(1);
    </code-snippet>
@endverbatim

