<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('App')]
#[Version('0.0.1')]
#[Instructions('Instructions describing how to use the server and its features.')]
class AppServer extends Server
{
    public int $defaultPaginationLength = 200;

    public int $maxPaginationLength = 200;

    protected array $tools = [
        //
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
