<?php
namespace Crossjoin\Css\Format\Rule\AtPage;

use Crossjoin\Css\Format\Rule\SelectorAbstract;
use Crossjoin\Css\Format\StyleSheet\StyleSheet;

class PageSelector
extends SelectorAbstract
{
    const SELECTOR_ALL   = '';
    const SELECTOR_FIRST = ':first';
    const SELECTOR_LEFT  = ':left';
    const SELECTOR_RIGHT = ':right';
    const SELECTOR_BLANK = ':blank';

    public function __construct($value, StyleSheet $styleSheet = null)
    {
        parent::__construct($value, $styleSheet);
    }

    public function checkValue(&$value)
    {
        if (parent::checkValue($value) === true) {
            $value = trim($value);
            if (in_array($value,
                [
                    self::SELECTOR_ALL, self::SELECTOR_FIRST, self::SELECTOR_LEFT,
                    self::SELECTOR_RIGHT, self::SELECTOR_BLANK
                ])) {
                return true;
            } else {
                throw new \InvalidArgumentException("Invalid value '" . $value . "' for argument 'value'.");
            }
        }

        return false;
    }
}