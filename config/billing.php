<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prezzo unitario della lezione (default del giorno)
    |--------------------------------------------------------------------------
    |
    | Questo valore viene letto quando si registra un pagamento
    | e “fotografato” dentro lesson_users.lesson_price.
    | Puoi cambiarlo in .env e poi fare config:cache.
    |
    */
    'lesson_price' => (float) env('LESSON_PRICE', 20.00),
];
