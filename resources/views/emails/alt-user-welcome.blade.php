<!DOCTYPE html>
<html>
<head>
    <title>Bienvenido a O.A. Distribuciones</title>
    <style>
        .button {
            background-color: #b91c1c;
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
        <h1 style="color: #b91c1c; text-align: center;">¡Bienvenido a O.A. Distribuciones!</h1>
        
        <p>Hola <strong>{{ $user->name }}</strong>,</p>
        
        <p>Gracias por tu solicitud para ser cliente. Para garantizar la seguridad de tu cuenta, necesitamos que confirmes tu dirección de correo electrónico.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/activate-account/' . $token) }}" class="button" style="color: #ffffff;">ACTIVAR MI CUENTA AHORA</a>
        </div>

        <p>Una vez activada, podrás ingresar a nuestro sistema con las siguientes credenciales temporales:</p>
        
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #b91c1c;">
            <p style="margin: 0;"><strong>Email:</strong> {{ $user->email }}</p>
            <p style="margin: 0;"><strong>Contraseña:</strong> {{ $password }}</p>
        </div>

        <p style="font-size: 14px; color: #666; margin-top: 20px;">
            <strong>Nota importante:</strong> Tu cuenta de cortesía tendrá una vigencia de {{ \App\Helpers\SettingsHelper::settings('guest_access_ttl_days', 10) }} días. Durante este tiempo, un asesor se contactará con vos para formalizar tu cuenta definitiva.
        </p>

        <p>¡Te esperamos!</p>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="text-align: center; font-size: 12px; color: #999;">
            Agostini Distribuidor Mayorista<br>
            Av. José Gorriti 3014, Santa Fe.
        </p>
    </div>
</body>
</html>