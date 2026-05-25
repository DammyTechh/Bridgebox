<?php

namespace App\Support;

class InstallMode
{
    private ?array $lock = null;

    public function get(): string
    {
        return $this->data()['mode'] ?? 'school';
    }

    public function isGeneric(): bool
    {
        return $this->get() === 'generic';
    }

    public function isSchool(): bool
    {
        return $this->get() === 'school';
    }

    private function data(): array
    {
        if ($this->lock === null) {
            $path = storage_path('app/installed.lock');
            $this->lock = file_exists($path)
                ? (json_decode(file_get_contents($path), true) ?: [])
                : [];
        }

        return $this->lock;
    }
}
