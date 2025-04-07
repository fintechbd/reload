<?php

namespace Fintech\Reload\Http\Requests;

use Fintech\Core\Traits\RestApi\HasPaginateQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexDepositRequest extends FormRequest
{
    use HasPaginateQuery;

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
            'search' => ['string', 'nullable', 'max:255'],
            'per_page' => ['integer', 'nullable', 'min:10', 'max:500'],
            'page' => ['integer', 'nullable', 'min:1'],
            'paginate' => ['boolean'],
            'sort' => ['string', 'nullable', 'min:2', 'max:255'],
            'dir' => ['string', 'min:3', 'max:4'],
            'trashed' => ['boolean', 'nullable'],
            'order_id' => ['integer', 'nullable'],
            'parent_id' => ['integer', 'nullable'],
            'user_id' => ['integer', 'nullable'],
            'service_id' => ['integer', 'nullable'],
            'source_country_id' => ['integer', 'nullable'],
            'destination_country_id' => ['integer', 'nullable'],
            'bank_id' => ['integer', 'nullable'],
            'bank_branch_id' => ['integer', 'nullable'],
            'service_id_in' => ['array', 'nullable'],
            'service_id_in.*' => ['integer', 'nullable'],
            'sender_receiver_id' => ['integer', 'nullable'],
            'user_id_sender_receiver_id' => ['integer', 'nullable'],
            'service_type_slug' => ['string', 'nullable'],
            'status' => ['string', 'array', 'nullable'],
            'status_not_equal' => ['string', 'array', 'nullable'],
            'service_slug' => ['string', 'nullable'],
            'transaction_form_code' => ['string', 'nullable'],
            'transaction_form_id' => ['integer', 'nullable'],
            'transaction_form_id_in' => ['array', 'nullable'],
            'transaction_form_id_in.*' => ['integer', 'nullable'],
            'currency' => ['string', 'nullable', 'size:3'],
            'order_start_date_time' => ['string', 'nullable', 'date_format:Y-m-d H:i:s', 'date'],
            'order_end_date_time' => ['string', 'nullable', 'date_format:Y-m-d H:i:s', 'date'],
            'order_start_date' => ['string', 'nullable', 'date_format:Y-m-d', 'date'],
            'order_end_date' => ['string', 'nullable', 'date_format:Y-m-d', 'date'],
            'order_date' => ['string', 'nullable', 'date_format:Y-m-d', 'date'],
            'created_at_date' => ['string', 'nullable', 'date_format:Y-m-d', 'date'],
            'created_at_start_date_time' => ['string', 'nullable', 'date_format:Y-m-d H:i:s', 'date'],
            'created_at_end_date_time' => ['string', 'nullable', 'date_format:Y-m-d H:i:s', 'date'],
            'created_at_start_date' => ['string', 'nullable', 'date_format:Y-m-d', 'date'],
            'created_at_end_date' => ['string', 'nullable', 'date_format:Y-m-d', 'date'],
        ];
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
