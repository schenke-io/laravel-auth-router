<?php

namespace SchenkeIo\LaravelAuthRouter\Auth;

use SchenkeIo\LaravelAuthRouter\Enums\ErrorCategory;

readonly class ErrorContext
{
    public function __construct(
        public string $type,
        public ErrorCategory $category,
        public string $reference,
        public string $info,
        public string $message
    ) {}

    public static function fromSession(): ?self
    {
        $type = session(SessionKey::ERROR_TYPE);
        $category = session(SessionKey::ERROR_CATEGORY);
        $reference = session(SessionKey::ERROR_REFERENCE);
        $info = session(SessionKey::ERROR_INFO);
        $message = session(SessionKey::ERROR_MESSAGE);

        if (! $type || ! $category || ! $reference) {
            return null;
        }

        return new self(
            $type,
            ErrorCategory::from($category),
            $reference,
            (string) $info,
            (string) $message
        );
    }

    public function recommendation(): string
    {
        /** @phpstan-ignore-next-line  */
        return $this->category->recommendation();
    }
}
