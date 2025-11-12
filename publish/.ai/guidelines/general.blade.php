# General

## Spelling
- Use American spelling, like "color" instead of "colour"

## Dates
- Use `UTC` in database and `Europe/Amsterdam` for showing dates
- Use `d-m-Y H:i` format for datetime
- Use `d-m-Y` format for date
- Use `H:i` format for time

## Code
- Use simplified FQNs whenever possible. If the name is already taken, create an alias.
@verbatim
<code-snippet name="FQN" lang="php">
    use App\Models\User;
    use App\Factories\User as UserFactory;

    class FooBar
    {
        public function __invoke()
        {
            $user = User::find(1);

            $factory = UserFactory::find(1);
        }
    }
</code-snippet>
@endverbatim
- Always include a blank line between new statements
@verbatim
<code-snippet name="Whitespace" lang="php">
    $foo = 'bar';

    $john = 'doe';
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Whitespace" lang="php">
    $this->foo();

    $this->bar();
</code-snippet>
@endverbatim
- Always include a blank line before a `return` statement
@verbatim
<code-snippet name="Whitespace" lang="php">
    $foo = $this->bar();

    return $foo;
</code-snippet>
@endverbatim
- When a `Collection` or `array` is used in a method and needs to be returned, prefer returning the `Collection`
@verbatim
<code-snippet name="Collection" lang="php">
    public function __invoke(): Collection
    {
        return collect($array)->filter();
    }
</code-snippet>
@endverbatim
