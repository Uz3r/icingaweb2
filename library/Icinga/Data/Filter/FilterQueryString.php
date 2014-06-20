<?php

namespace Icinga\Data\Filter;

class FilterQueryString
{
    protected $string;

    protected $pos;

    protected $length;
    
    protected function __construct()
    {
    }

    public static function parse($string)
    {
        $parser = new static();
        return $parser->parseQueryString($string);
    }

    protected function readNextKey()
    {
        $str = $this->readUnlessSpecialChar();

        if ($str === false) {
            return $str;
        }
        return rawurldecode($str);
    }

    protected function readNextValue()
    {
        if ($this->nextChar() === '(') {
            $this->readChar();
            $var = preg_split('~\|~', $this->readUnless(')'));
            if ($this->readChar() !== ')') {
                $this->parseError(null, 'Expected ")"');
            }
        } else {
            $var = rawurldecode($this->readUnless(array(')', '&', '|', '>', '<')));
        }
        return $var;
    }

    protected function readNextExpression()
    {

        if ('' === ($key = $this->readNextKey())) {
            return false;
        }

        $sign = $this->readChar();
        if ($sign === false) {
            return Filter::expression($key, '=', true);
        }

        if ($sign === '=') {
            $last = substr($key, -1);
            if ($last === '>' || $last === '<') {
                $sign = $last . $sign;
                $key = substr($key, 0, -1);
            }
        // TODO: Same as above for unescaped <> - do we really need this?
        } elseif ($sign === '>' || $sign === '<' || $sign === '!') {
            if ($this->nextChar() === '=') {
                $sign .= $this->readChar();
            }
        }

        $var = $this->readNextValue();

        return Filter::expression($key, $sign, $var);
    }

    protected function parseError($char = null, $extraMsg = null)
    {
        if ($extraMsg === null) {
            $extra = '';
        } else {
            $extra = ': ' . $extraMsg;
        }
        if ($char === null) {
            $char = $this->string[$this->pos];
        }

        throw new FilterParseException(sprintf(
            'Invalid filter "%s", unexpected %s at pos %d%s',
            $this->string,
            $char,
            $this->pos,
            $extra
        ));
    }

    protected function readFilters($nestingLevel = 0, $op = null)
    {
        $filters = array();
        while ($this->pos < $this->length) {

            if ($op === '!' && count($filters) === 1) {
                break;
            }
            $filter = $this->readNextExpression();
            $next = $this->readChar();


            if ($filter === false) {

                if ($next === '!') {
                    $not = $this->readFilters($nestingLevel + 1, '!');
                    $filters[] = $not;
                    continue;
                }

                if ($op === null && count($filters > 0) && ($next === '&' || $next === '|')) {
                    $op = $next;
                    $next = $this->readChar();
                }

                if ($next === false) {
                    // Nothing more to read
                    break;
                }

                if ($next === ')') {
                    if ($nestingLevel > 0) {
                        break;
                    }
                    $this->parseError($next);
                }
                if ($next === '(') {
                    $filters[] = $this->readFilters($nestingLevel + 1, null);
                    continue;
                }
                if ($next === $op) {
                    continue;
                }
                $this->parseError($next, "$op level $nestingLevel");

            } else {
                $filters[] = $filter;

                if ($next === false) {
                    // Got filter, nothing more to read
                    break;
                }

                if ($op === '!') {
                    $this->pos--;
                    break;
                }
                if ($next === $op) {
                    continue; // Break??
                }

                if ($next === ')') {
                    if ($nestingLevel > 0) {
                        break;
                    }
                    $this->parseError($next);
                }
                if ($op === null && in_array($next, array('&', '|'))) {
                    $op = $next;
                    continue;
                }
                $this->parseError($next);
            }
        }

        if ($nestingLevel === 0 && count($filters) === 1) {
            // There is only one filter expression, no chain
            return $filters[0];
        }
        if ($op === null && count($filters) === 1) {
            $op = '&';
        }

        switch ($op) {
            case '&': return Filter::matchAll($filters);
            case '|': return Filter::matchAny($filters);
            case '!': return Filter::not($filters);
            case null: return Filter::matchAll();
            default: $this->parseError($op);
        }
    }

    protected function parseQueryString($string)
    {
        $this->pos = 0;

        $this->string = $string;

        $this->length = strlen($string);
        return $this->readFilters();
    }

    protected function readUnless($char)
    {
        $buffer = '';
        while (false !== ($c = $this->readChar())) {
            if (is_array($char)) {
                if (in_array($c, $char)) {
                    $this->pos--;
                    break;
                }
            } else {
                if ($c === $char) {
                    $this->pos--;
                    break;
                }
            }
            $buffer .= $c;
        }

        return $buffer;
    }

    protected function readUnlessSpecialChar()
    {
        return $this->readUnless(array('=', '(', ')', '&', '|', '>', '<', '!'));
    }

    protected function readExpressionOperator()
    {
        return $this->readUnless(array('=', '>', '<', '!'));
    }

    protected function readChar()
    {
        if ($this->length > $this->pos) {
            return $this->string[$this->pos++];
        }
        return false;
    }

    protected function nextChar()
    {
        if ($this->length > $this->pos) {
            return $this->string[$this->pos];
        }
        return false;
    }
}
