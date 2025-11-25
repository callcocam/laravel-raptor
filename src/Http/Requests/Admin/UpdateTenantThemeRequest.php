<?php

namespace Callcocam\LaravelRaptor\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantThemeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(sprintf('%s.tenants.update', request()->getContext()));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'color' => [
                'sometimes',
                'string',
                'in:default,blue,green,amber,rose,purple,orange,teal,red,yellow,violet',
            ],
            'font' => [
                'sometimes',
                'string',
                'in:default,inter,noto-sans,nunito-sans,figtree',
            ],
            'rounded' => [
                'sometimes',
                'string',
                'in:none,small,medium,large,full',
            ],
            'variant' => [
                'sometimes',
                'string',
                'in:default,mono,scaled',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color.in' => 'The selected color is invalid. Choose from: default, blue, green, amber, rose, purple, orange, teal, red, yellow, or violet.',
            'font.in' => 'The selected font is invalid. Choose from: default, inter, noto-sans, nunito-sans, or figtree.',
            'rounded.in' => 'The selected rounded option is invalid. Choose from: none, small, medium, large, or full.',
            'variant.in' => 'The selected variant is invalid. Choose from: default, mono, or scaled.',
        ];
    }
}
