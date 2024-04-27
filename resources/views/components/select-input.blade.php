<!-- resources/views/components/select-input.blade.php -->

@props(['id', 'name', 'class' => '', 'required' => false, 'autofocus' => false])

<select {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm' . ($class ? ' ' . $class : ''), 'id' => $id, 'name' => $name, 'required' => $required, 'autofocus' => $autofocus]) }}>
    {{ $slot }}
</select>
