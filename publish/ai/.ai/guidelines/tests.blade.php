# Tests

- Use Pest for testing
- Don't use multiple `expect()`'s in tests, rather use `and()`
@verbatim
<code-snippet name="ExpectAnd" lang="php">
    expect($user)
        ->toBeInstanceOf(User::class)
        ->type->toBe('foo')
        ->message->toBe('bar')
        ->and($userTwo)->toBeInstanceOf(User::class);
</code-snippet>
@endverbatim
