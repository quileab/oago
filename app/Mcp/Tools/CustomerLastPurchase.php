<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Obtiene el nombre del cliente y el importe de su última compra a partir de su código o ID.')]
class CustomerLastPurchase extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        try {
            $code = $request->input('customer_code');

            $customer = User::where('id', $code)->orWhere('code', $code)->first();

            if (! $customer) {
                return Response::text("No se encontró ningún cliente con el código o ID: {$code}");
            }

            $lastOrder = $customer->orders()->latest()->first();
            $name = $customer->fullName;

            if (! $lastOrder) {
                return Response::text("El cliente {$name} no tiene ninguna compra registrada.");
            }

            return Response::text(
                sprintf('El cliente %s realizó su última compra el %s por un importe total de $%s.',
                    $name,
                    $lastOrder->created_at->format('d/m/Y'),
                    number_format($lastOrder->total_price, 2, ',', '.')
                )
            );
        } catch (\Exception $e) {
            return Response::text('Error al consultar la base de datos: '.$e->getMessage());
        }
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_code' => $schema->string('El ID o código del cliente (ej. 100007).')->required(),
        ];
    }
}
