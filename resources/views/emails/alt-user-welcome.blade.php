<!DOCTYPE html>
<html>

<head>
    <title>Bienvenido a O.A. Distribuciones</title>
</head>

<body>
    <h1>¡Bienvenido a O.A. Distribuciones!</h1>
    <p>Hola {{ $user->name }},</p>
    <p>Tu cuenta de invitado ha sido autorizada. Ahora puedes ingresar a nuestro sistema con las siguientes
        credenciales:</p>
    <ul>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Contraseña:</strong> {{ $password }}</li>
    </ul>
    <p><strong>Nota:</strong> Guarde estas credenciales en un lugar seguro.</p>
    <p>Tu período de prueba es de 10 días.</p>
    <p>Puedes comenzar de inmediato, algunas funciones pueden estar desactivadas.</p>
    <p>¡Gracias por unirte a nosotros!</p>
</body>

</html>