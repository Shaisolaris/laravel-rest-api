<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;
class RegisterTenantRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'tenant_name' => 'required|string|max:100',
            'tenant_slug' => ['required','string','max:50','unique:tenants,slug','regex:/^[a-z0-9-]+$/'],
            'first_name'  => 'required|string|max:50',
            'last_name'   => 'required|string|max:50',
            'email'       => 'required|email|max:255',
            'password'    => ['required','string','min:8','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/'],
        ];
    }
}
