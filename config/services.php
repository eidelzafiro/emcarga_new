<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Push móvil (FCM / Expo)
    |--------------------------------------------------------------------------
    |
    | El envío push es best-effort: en Cuba FCM/Expo puede estar bloqueado, así
    | que por defecto está DESACTIVADO y el cliente recibe la notificación
    | in-app (canal database). El cliente móvil (Capacitor
    | `@capacitor/push-notifications`) genera tokens FCM, por lo que el
    | proveedor real es Firebase. Elegir con `PUSH_PROVIDER`:
    |   - `off`  (default): NullPushSender, solo notificación in-app.
    |   - `fcm`  : FcmPushSender (HTTP v1 con service account de Firebase).
    |   - `expo` : ExpoPushSender (legacy, token ExponentPushToken).
    |
    */

    'push' => [
        'provider' => env('PUSH_PROVIDER', 'off'),
    ],

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'client_email' => env('FCM_CLIENT_EMAIL'),
        'private_key' => env('FCM_PRIVATE_KEY'),
        'token_uri' => env('FCM_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
        'send_uri' => env('FCM_SEND_URI'),
    ],

    'expo' => [
        'access_token' => env('EXPO_ACCESS_TOKEN'),
        'endpoint' => env('EXPO_PUSH_ENDPOINT', 'https://exp.host/--/api/v2/push/send'),
    ],

];
