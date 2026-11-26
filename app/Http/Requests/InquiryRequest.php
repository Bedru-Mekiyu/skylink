<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for every public message form. The form says which kind it is
 * (hidden "type", or the topic select on /contact) and where on the page it
 * sits ("form_anchor"), so errors and the thank-you message are shown right
 * at the form, not at the top of a long page.
 */
class InquiryRequest extends FormRequest
{
    /** Form positions on the site pages (the URL fragment after a redirect). */
    public const ANCHORS = ['contact-form', 'quote-form', 'service-form', 'subscribe-form', 'apply-modal', 'product-form'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');
        $phone = ['string', 'max:30', 'regex:/^[0-9+()\-\s.]{6,30}$/'];

        return [
            'type' => ['required', Rule::in(array_keys(Inquiry::TYPES))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'max:190', 'email:rfc'],
            'phone' => in_array($type, ['quote', 'service', 'subscription'], true)
                ? ['required', ...$phone]
                : ['nullable', ...$phone],
            'company' => ['nullable', 'string', 'max:190'],
            'service' => in_array($type, ['quote', 'service'], true)
                ? ['required', 'string', 'max:120']
                : ['nullable', 'string', 'max:120'],
            'item_type' => ['nullable', 'string', Rule::in(['service', 'product'])],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'plan' => $type === 'subscription'
                ? ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/']
                : ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'billing_term' => ['nullable', 'string', Rule::in(['monthly', 'quarterly', 'semi-annually', 'annually', 'project-based', 'one-time'])],
            'estimated_price' => ['nullable', 'string', 'max:60'],
            'message' => $type === 'subscription'
                ? ['nullable', 'string', 'max:5000']
                : ['required', 'string', 'min:5', 'max:5000'],
            'form_anchor' => ['nullable', Rule::in(self::ANCHORS)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'email' => 'email address',
            'phone' => 'phone number',
            'service' => 'service',
            'message' => 'message',
            'type' => 'topic',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number (digits, spaces, +, - and brackets).',
            'plan.regex' => 'The selected plan is not valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach (['name', 'email', 'phone', 'company', 'service', 'item_type', 'plan', 'billing_term', 'estimated_price', 'message'] as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = trim($this->input($field));
            }
        }
        $this->merge($clean);
    }

    /** Back to the form itself, with the error list visible. */
    protected function getRedirectUrl(): string
    {
        return self::backTo(self::anchorFor($this->input('form_anchor')));
    }

    /** The previous page, at the given form (e.g. https://site/contact#contact-form). */
    public static function backTo(string $anchor): string
    {
        $previous = strtok(url()->previous(), '#');
        if (parse_url($previous, PHP_URL_PATH) === null) {
            $previous .= '/'; // "https://site" -> "https://site/"
        }

        return $previous.'#'.$anchor;
    }

    public static function anchorFor(mixed $anchor): string
    {
        return in_array($anchor, self::ANCHORS, true) ? $anchor : 'contact-form';
    }
}
