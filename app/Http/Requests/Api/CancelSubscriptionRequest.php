<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CancelSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'string', 'max:500'],
            'cancel_at_period_end' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.max' => 'La razón de cancelación no puede exceder 500 caracteres.',
            'cancel_at_period_end.boolean' => 'El valor debe ser verdadero o falso.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();

            // Validar que el usuario tenga suscripción activa
            if (!$user->hasActiveSubscription()) {
                $validator->errors()->add('subscription', 'No tienes una suscripción activa para cancelar.');
            }

            // Validar que el usuario sea proveedor
            if (!$user->isProvider()) {
                $validator->errors()->add('role', 'Solo los proveedores pueden cancelar suscripciones.');
            }
        });
    }
} 