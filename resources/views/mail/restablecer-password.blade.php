@component('mail::message')
# {{ __('Restablecimiento de contraseña') }}

{{ __('Hola :nombre,', ['nombre' => $usuario->name ?: $usuario->username]) }}

{{ __('Recibimos una solicitud para restablecer la contraseña de tu cuenta en :app.', ['app' => config('app.name')]) }}

{{ __('Si fuiste tú, pulsa el botón siguiente. El enlace es válido durante :minutos minutos.', ['minutos' => config('auth.passwords.users.expire', 60)]) }}

@component('mail::button', ['url' => $urlRestablecer])
{{ __('Restablecer contraseña') }}
{{ $urlRestablecer }}
@endcomponent

{{ __('Si no solicitaste este cambio, puedes ignorar este mensaje: tu contraseña no se ha modificado.') }}

{{ __('Este mensaje fue enviado automáticamente; por favor no respondas.') }}

Saludos,<br>
{{ config('app.name') }}
@endcomponent
