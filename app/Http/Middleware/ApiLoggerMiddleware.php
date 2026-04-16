<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->logActivity($request, $response);

        return $response;
    }

    protected function logActivity(Request $request, Response $response)
    {
        $status = $response->getStatusCode();
        $method = $request->getMethod();
        $url = $request->fullUrl();

        if ($response->isSuccessful()) {
            // Registro básico para llamadas exitosas (200-299)
            Log::channel('api')->info("API Success: {$method} {$url} [{$status}]", [
                'user' => $request->user()?->id ?? 'guest',
                'ip' => $request->ip(),
            ]);
        } else {
            // Registro detallado para fallos (4xx, 5xx, etc.)
            $data = [
                'method' => $method,
                'url'    => $url,
                'status' => $status,
                'ip'     => $request->ip(),
                'user'   => $request->user()?->id ?? 'guest',
                'request_payload' => $this->maskSensitiveData($request->all()),
                'response_payload' => $this->maskSensitiveData(json_decode($response->getContent(), true) ?? []),
            ];

            Log::channel('api')->error("API Failure: {$method} {$url} [{$status}]", $data);
        }
    }

    protected function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'access_token', 'refresh_token', 'card_number', 'cvv'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '********';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }

        return $data;
    }
}
