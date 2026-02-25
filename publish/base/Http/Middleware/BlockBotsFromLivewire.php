<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBotsFromLivewire
{
    /** @var list<string> */
    protected array $botPatterns = [
        'python-requests',
        'curl',
        'wget',
        'httpie',
        'scrapy',
        'node-fetch',
        'axios',
        'go-http-client',
        'java/',
        'libwww-perl',
        'mechanize',
        'aiohttp',
        'httpx',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isLivewireUpdateRequest($request) && $this->isBot($request)) {
            abort(403);
        }

        return $next($request);
    }

    protected function isLivewireUpdateRequest(Request $request): bool
    {
        return $request->isMethod('POST')
            && str_contains($request->path(), 'livewire')
            && str_contains($request->path(), 'update');
    }

    protected function isBot(Request $request): bool
    {
        $userAgent = str($request->userAgent())->lower();

        $containsPattern = collect($this->botPatterns)->contains(
            fn (string $pattern): bool => $userAgent->contains(str($pattern)->lower())
        );

        return $userAgent->isEmpty() || $containsPattern;
    }
}
