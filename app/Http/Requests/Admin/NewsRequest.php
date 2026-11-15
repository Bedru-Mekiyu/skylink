<?php

namespace App\Http\Requests\Admin;

use App\Models\News;
use App\Support\ImageUploadRules;
use App\Support\NewsBody;
use App\Support\NewsSlugs;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Validation for the admin news form (create and edit). Routes are admin-only. */
class NewsRequest extends FormRequest
{
    public const DATETIME_FORMAT = 'Y-m-d\TH:i';

    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'language' => ['required', Rule::in(array_keys(News::LANGUAGES))],
            'url_title' => [
                'nullable', 'string', 'max:'.NewsSlugs::MAX_LENGTH,
                'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]*$/',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (preg_match(NewsSlugs::AUTO_PATTERN, (string) NewsSlugs::fromUrlTitle($value))) {
                        $fail('Addresses like "news-123" are reserved for automatic addresses. Please choose other words.');
                    }
                },
            ],
            'news_category_id' => ['required', 'integer', Rule::exists('news_categories', 'id')],
            'content' => [
                'required', 'string', 'max:'.NewsBody::MAX_LENGTH,
                function (string $attribute, mixed $value, Closure $fail) {
                    if (trim(NewsBody::toText(NewsBody::sanitize((string) $value))) === '') {
                        $fail('The article body is empty.');
                    }
                },
            ],
            'image' => ImageUploadRules::rules(),
            'is_featured' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(News::STATUSES)],
            'published_at' => [
                'nullable',
                'required_if:status,'.News::STATUS_SCHEDULED,
                'date_format:'.self::DATETIME_FORMAT,
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($this->input('status') !== News::STATUS_SCHEDULED || ! is_string($value)) {
                        return;
                    }
                    $time = self::parseAdminTime($value);
                    if ($time && $time->lte(now())) {
                        $fail('A scheduled article needs a publish time in the future.');
                    }
                },
            ],
            'confirm_similar' => ['nullable', 'boolean'],
            'form_token' => ['required', 'string', 'size:40'],
        ];
    }

    public function attributes(): array
    {
        return [
            'url_title' => 'English URL title',
            'news_category_id' => 'category',
            'content' => 'article body',
            'published_at' => 'publish date and time',
        ];
    }

    public function messages(): array
    {
        return [
            'url_title.regex' => 'The English URL title may contain only English letters, numbers, spaces and hyphens.',
            'published_at.required_if' => 'Choose the date and time when the scheduled article should appear.',
            'form_token.*' => 'The form has expired. Please reload the page and try again.',
        ];
    }

    /** A "Y-m-d\TH:i" value typed in the admin time zone, as an app-time-zone Carbon. */
    public static function parseAdminTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        $time = Carbon::createFromFormat(self::DATETIME_FORMAT, $value, config('news.admin_timezone'));

        return $time ? $time->setTimezone(config('app.timezone'))->startOfMinute() : null;
    }

    /** For the form input: an app-time-zone date shown in the admin time zone. */
    public static function formatAdminTime($time): ?string
    {
        return $time ? Carbon::parse($time)->setTimezone(config('news.admin_timezone'))->format(self::DATETIME_FORMAT) : null;
    }
}
