@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'description' => null,
    'error' => null,
    'value' => null,
    'placeholder' => null,
])

@php
    $fieldId = $attributes->get('id', $name);
    $descriptionId = "{$fieldId}-description";
    $errorId = "{$fieldId}-error";
    
    $ariaDescribedBy = [];
    if ($description) {
        $ariaDescribedBy[] = $descriptionId;
    }
    if ($error) {
        $ariaDescribedBy[] = $errorId;
    }
@endphp

<div class="form-field-group">
    <label 
        for="{{ $fieldId }}" 
        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 {{ $required ? 'required-field' : '' }}"
    >
        {{ $label }}
        @if($required)
            <span class="sr-only">{{ __('a11y.forms.required_field') }}</span>
        @else
            <span class="text-gray-500 dark:text-gray-400 text-xs ml-1">({{ __('a11y.forms.optional_field') }})</span>
        @endif
    </label>
    
    @if($description)
        <p id="{{ $descriptionId }}" class="field-description text-sm text-gray-600 dark:text-gray-400 mb-2">
            {{ $description }}
        </p>
    @endif
    
    @if($type === 'textarea')
        <textarea
            id="{{ $fieldId }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $ariaDescribedBy ? 'aria-describedby=' . implode(' ', $ariaDescribedBy) : '' }}
            {{ $error ? 'aria-invalid=true' : '' }}
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white']) }}
        >{{ old($name, $value) }}</textarea>
    @else
        <input
            type="{{ $type }}"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            {{ $required ? 'required' : '' }}
            {{ $ariaDescribedBy ? 'aria-describedby=' . implode(' ', $ariaDescribedBy) : '' }}
            {{ $error ? 'aria-invalid=true' : '' }}
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge(['class' => 'w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white']) }}
        >
    @endif
    
    @if($error)
        <p id="{{ $errorId }}" class="field-error" role="alert">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            {{ $error }}
        </p>
    @endif
</div>
