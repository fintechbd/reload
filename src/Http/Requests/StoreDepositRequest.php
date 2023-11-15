<?php

namespace Fintech\Reload\Http\Requests;

use Fintech\Auth\Enums\RiskProfile;
use Fintech\Transaction\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'source_country_id' => ['required', 'integer', 'min:1'],
            'destination_country_id' => ['required', 'integer', 'min:1', 'same:source_country_id'],
            'sender_receiver_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['required', 'integer', 'min:1'],
            'service_id' => ['required', 'integer', 'min:1'],
            'transaction_form_id' => ['required', 'integer', 'min:1'],
            'ordered_at' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:'.date('Y-m-d')],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'risk' => ['required', 'string'],
            'is_refunded' => ['required', 'boolean'],
            'order_data' => ['nullable', 'array'],
            'status' => ['required', 'string'],
            'slip' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        $data = [
            'transaction_form_id' => 1,
            'user_id' => $user->getKey(),
            'sender_receiver_id' => $user->getKey(),
            'is_refunded' => false,
            'order_data' => [],
            'status' => OrderStatus::Processing->value,
            'risk' => RiskProfile::Low->value,
        ];

        $this->merge($data);
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
