<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->is_admin; // Chỉ admin mới được phép thực hiện yêu cầu này
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:in_progress,completed,cancelled', // Trạng thái bắt buộc
            'driver_id' => 'nullable|exists:drivers,id', // ID của tài xế (nếu có)
            'admin_notes' => 'nullable|string', // Ghi chú của admin
        ];
    }

    /**
     * Customize the error messages for validation.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Trạng thái là bắt buộc.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'driver_id.exists' => 'ID tài xế không tồn tại.',
            'admin_notes.string' => 'Ghi chú phải là chuỗi ký tự.',
        ];
    }
}