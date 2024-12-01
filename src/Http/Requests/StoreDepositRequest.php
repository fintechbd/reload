<?php

namespace Fintech\Reload\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'min:1'],
            'source_country_id' => ['required', 'integer', 'min:1'],
            'destination_country_id' => ['required', 'integer', 'min:1', 'same:source_country_id'],
            'service_id' => ['required', 'integer', 'min:1'],
            'ordered_at' => ['required', 'date', 'date_format:Y-m-d H:i:s', 'before_or_equal:'.date('Y-m-d H:i:s', strtotime('+3 seconds'))],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'order_data' => ['nullable', 'array'],
            'order_data.request_from' => ['string', 'required'],
            'order_data.interac_email' => ['nullable', 'email:rfc,dns', 'min:5', 'max:255'],
            'slip' => ['nullable', 'string', 'base64:image/jpg,image/jpeg,image/png,image/svg+xml,image/gif,application/pdf'],
        ];
    }

    protected function prepareForValidation()
    {
        $order_data = $this->input('order_data');
        $order_data['request_from'] = request()->platform()->value;
        $this->merge(['order_data' => $order_data]);
    }
}
