<?php

declare(strict_types=1);

namespace Brick\Math\Exception;

use Brick\Math\NumberSyntax;
use RuntimeException;

use function ord;
use function preg_match;
use function sprintf;
use function strlen;
use function strtr;
use function substr;

/**
 * Exception thrown when attempting to create a number from a string with an invalid format.
 */
final class NumberFormatException extends RuntimeException implements MathException
{
    /**
     * Invisible characters commonly found in copy-pasted numbers, escaped even in valid UTF-8:
     * kept as-is, they would misleadingly look valid in the message.
     */
    private const INVISIBLE_CHAR_ESCAPES = [
        "\u{00A0}" => '\u{00A0}', // no-break space
        "\u{202F}" => '\u{202F}', // narrow no-break space
        "\u{200B}" => '\u{200B}', // zero-width space
        "\u{FEFF}" => '\u{FEFF}', // zero-width no-break space (BOM)
    ];

    /**
     * @internal
     *
     * @pure
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function invalidFormat(string $value): self
    {
        return new self(sprintf(
            'Value %s does not represent a valid number.',
            self::valueToString($value),
        ));
    }

    /**
     * @internal
     *
     * @param string $char The failing character.
     *
     * @pure
     */
    public static function charNotInAlphabet(string $char): self
    {
        return new self(sprintf(
            'Character %s is not valid in the given alphabet.',
            self::charToString($char),
        ));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function charNotValidInBase(string $char, int $base): self
    {
        return new self(sprintf(
            'Character %s is not valid in base %d.',
            self::charToString($char),
            $base,
        ));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function emptyNumber(): self
    {
        return new self('The number must not be empty.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function emptyByteString(): self
    {
        return new self('The byte string must not be empty.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function exponentTooLarge(): self
    {
        return new self('The exponent is too large to be represented as an integer.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function tooManyDigits(int $maxDigits): self
    {
        return new self(sprintf(
            'The number exceeds the maximum number of %d digits.',
            $maxDigits,
        ));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function syntaxNotAllowed(NumberSyntax $syntax): self
    {
        return new self(sprintf('The %s syntax is not allowed.', match ($syntax) {
            NumberSyntax::DecimalPoint => 'decimal point',
            NumberSyntax::Exponent => 'exponent',
            NumberSyntax::Fraction => 'fraction',
        }));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function zeroDenominator(): self
    {
        return new self('The denominator of a rational number must not be zero.');
    }

    /**
     * Renders a value in a form safe to embed in an exception message: the value is truncated, printable
     * ASCII and valid UTF-8 text are kept, and everything else is escaped: `\t`, `\n` and `\r` for the
     * common whitespace controls, `\\` and `\"` for the backslash and the double quote, `\xHH` for other
     * bytes, and `\u{XXXX}` for a few invisible characters commonly found in copy-pasted numbers, which
     * would misleadingly look valid if kept. When the value is not valid UTF-8, every non-ASCII byte is
     * escaped.
     *
     * @pure
     */
    private static function valueToString(string $value): string
    {
        if (strlen($value) > 40) {
            $value = substr($value, 0, 40);

            // If the cut falls inside a multibyte sequence, drop that sequence's leading bytes as well.
            for ($i = 0; $i < 3 && ! self::isUtf8($value); $i++) {
                $value = substr($value, 0, -1);
            }

            $value .= '...';
        }

        $isUtf8 = self::isUtf8($value);

        $escaped = '';
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            $escaped .= $isUtf8 && ord($char) >= 0x80 ? $char : self::escapeChar($char);
        }

        return '"' . strtr($escaped, self::INVISIBLE_CHAR_ESCAPES) . '"';
    }

    /**
     * @param string $char The failing character.
     *
     * @pure
     */
    private static function charToString(string $char): string
    {
        return '"' . self::escapeChar($char) . '"';
    }

    /**
     * @pure
     */
    private static function escapeChar(string $char): string
    {
        $ord = ord($char);

        return match (true) {
            $char === "\t" => '\t',
            $char === "\n" => '\n',
            $char === "\r" => '\r',
            $char === '\\' => '\\\\',
            $char === '"' => '\"',
            $ord < 32 || $ord > 126 => sprintf('\x%02X', $ord),
            default => $char,
        };
    }

    /**
     * An empty pattern with the /u modifier matches if, and only if, the subject is valid UTF-8.
     *
     * @pure
     */
    private static function isUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }
}
