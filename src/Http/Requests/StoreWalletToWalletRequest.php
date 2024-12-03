<?php

namespace Fintech\Reload\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWalletToWalletRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'min:1'],
            'source_country_id' => ['required', 'integer', 'min:1'],
            'destination_country_id' => ['required', 'integer', 'min:1'],
            'service_id' => ['required', 'integer', 'min:1'],
            'ordered_at' => ['required', 'date', 'date_format:Y-m-d H:i:s', 'before_or_equal:'.date('Y-m-d H:i:s', strtotime('+3 seconds'))],
            'amount' => ['required', 'numeric'],
            'reverse' => ['nullable', 'boolean'],
            'currency' => ['required', 'string', 'size:3'],
            'converted_currency' => ['required', 'string', 'size:3'],
            'order_data' => ['nullable', 'array'],
            'order_data.request_from' => ['string', 'required'],
            'order_data.serving_country_id' => ['required', 'integer', 'min:1'],
            'order_data.recipient_id' => ['required', 'integer', 'min:1'],
            'order_data.recipient_name' => ['nullable', 'string'],
            'order_data.recipient_mobile' => ['nullable', 'string'],
            'order_data.recipient_email' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation()
    {
        $order_data = $this->input('order_data');
        $order_data['request_from'] = request()->platform()->value;
        $this->merge(['order_data' => $order_data]);
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
