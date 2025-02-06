<?php

return [
  'rate_limits' => [
    'api' => env('API_RATE_LIMIT', '20000,1'), // Permite 20,000 solicitudes por minuto
    'sign_in' => env('SIGN_IN_RATE_LIMIT', '50,1'), // Límite más bajo para inicios de sesión
  ],
];
