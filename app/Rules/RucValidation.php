<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RucValidation implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        // 1. Validar longitud y que sea numérico (ya cubierto por digits:11 pero por seguridad)
        if (strlen($value) !== 11 || !is_numeric($value)) {
            $fail('El :attribute debe tener 11 dígitos numéricos.');
            return;
        }

        // 2. Validar prefijo (10, 15, 17, 20)
        $prefix = substr($value, 0, 2);
        if (!in_array($prefix, ['10', '15', '17', '20'])) {
            $fail('El :attribute debe iniciar con 10, 15, 17 o 20.');
            return;
        }

        // 3. Validar dígito verificador (Algoritmo SUNAT)
        $factors = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $sum += $value[$i] * $factors[$i];
        }

        $calculatedDigit = 11 - ($sum % 11);

        if ($calculatedDigit === 10) {
            $calculatedDigit = 0;
        } elseif ($calculatedDigit === 11) {
            $calculatedDigit = 1;
        }

        if ($calculatedDigit !== (int) $value[10]) {
            $fail('El :attribute no es válido (dígito verificador incorrecto).');
        }
    }
}
