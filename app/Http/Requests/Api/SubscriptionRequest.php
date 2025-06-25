<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends FormRequest
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
            'plan_type' => ['required', 'string', Rule::in(['basic', 'premium', 'enterprise'])],
            'billing_cycle' => ['sometimes', 'string', Rule::in(['monthly', 'quarterly', 'yearly'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_type.required' => 'El tipo de plan es requerido.',
            'plan_type.in' => 'El tipo de plan debe ser: basic, premium o enterprise.',
            'billing_cycle.in' => 'El ciclo de facturación debe ser: monthly, quarterly o yearly.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();

            // Validar que el usuario no tenga suscripción activa
            if ($user->hasActiveSubscription()) {
                $validator->errors()->add('subscription', 'Ya tienes una suscripción activa.');
            }

            // Validar que el usuario tenga método de pago por defecto
            if (!$user->hasDefaultPaymentMethod()) {
                $validator->errors()->add('payment_method', 'Debes tener un método de pago configurado antes de suscribirte.');
            }

            // Validar que el usuario sea proveedor
            if (!$user->isProvider()) {
                $validator->errors()->add('role', 'Solo los proveedores pueden suscribirse a planes.');
            }
        });
    }
} 