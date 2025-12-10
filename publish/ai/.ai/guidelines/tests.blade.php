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
    <code-snippet name="Not" lang="php">
        expect($user)
            ->not->toBeInstanceOf(OtherUser::class)
            ->type->not->toBe('foo')
            ->message->not->toBe('bar')
    </code-snippet>
@endverbatim
- Keep assertion of an `expect()` on the same line when the `expect()` has only one assertion. Put it on a new line when it has more assertions
@verbatim
    <code-snippet name="Expect" lang="php">
        expect($user)->toBeInstanceOf(User::class)
            ->and($post)->toBeInstanceOf(Post::class)

        expect($user)
            ->toBeInstanceOf(User::class)
            ->id->toBe(1)
            ->and($post)->toBeInstanceOf(Post::class)
    </code-snippet>
@endverbatim
- Keep `->and()` and the subsequent assertion on the same line. When multiple assertions will follow, put them on a new line
@verbatim
    <code-snippet name="And" lang="php">
        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($post)->toBeInstanceOf(Post::class);

        expect($user)
            ->toBeInstanceOf(User::class)
            ->and($post)
            ->toBeInstanceOf(Post::class)
            ->message->toBe('foo')
            ->id->toBe(1);
    </code-snippet>
@endverbatim

