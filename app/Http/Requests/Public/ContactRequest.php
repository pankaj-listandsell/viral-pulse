<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ContactRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            // Deliberately not email:dns - a live MX lookup makes every submit
            // wait on the network and rejects perfectly valid addresses whose
            // DNS is slow or temporarily unreachable.
            'email' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Honeypot: a real person never fills this in because it is hidden.
            'website' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $body = $this->string('message')->toString();

            // A wall of links is the cheapest spam signal there is.
            if (substr_count(strtolower($body), 'http') > 3) {
                $validator->errors()->add('message', 'Please remove some of the links from your message.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.prohibited' => 'Your message looks automated. Please try again.',
            'email.email' => 'Enter an email address we can actually reply to.',
        ];
    }
}
