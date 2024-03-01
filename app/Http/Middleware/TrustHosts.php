<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        return [
            '127.0.0.1', '127.0.0.1:8000', '127.0.0.1:3000',
            $this->allSubdomainsOfApplicationUrl(),
        ];
    }
}
