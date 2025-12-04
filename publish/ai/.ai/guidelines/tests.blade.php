# Tests

## Pest
- Don't use multiple `expect()`'s in tests, rather use `and()`
@verbatim
<code-snippet name="Expect" lang="php">
    expect($user)
        ->toBeInstanceOf(User::class)
        ->type->toBe('foo')
        ->message->toBe('bar')
        ->and($userTwo)
        ->toBeInstanceOf(User::class);
</code-snippet>
@endverbatim
- Place each assertion on a new line
@verbatim
<code-snippet name="Assertions" lang="php">
    expect($user)
        ->toBeInstanceOf(User::class)
        ->type->toBe('foo')
        ->message->toBe('bar')
</code-snippet>
@endverbatim
