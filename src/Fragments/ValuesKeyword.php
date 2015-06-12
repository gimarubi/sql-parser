<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * `VALUES` keyword parser.
 */
class ValuesKeyword extends Fragment
{

    /**
     * An array with the values of the row to be inserted.
     *
     * @var array
     */
    public $values;

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return ValuesKeyword
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = array();

        $expr = new ValuesKeyword();
        $value = '';

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ------------------------[ ( ]-----------------------> 1
         *
         *      1 ----------------------[ value ]---------------------> 2
         *
         *      2 ------------------------[ , ]-----------------------> 1
         *      2 ------------------------[ ) ]-----------------------> 3
         *
         *      3 ---------------------[ options ]--------------------> 4
         *
         * @var int
         */
        $state = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            // No keyword is expected.
            if ($token->type === Token::TYPE_KEYWORD) {
                break;
            }

            if ($token->type === Token::TYPE_OPERATOR) {
                if ($token->value === '(') {
                    $state = 1;
                    continue;
                } elseif ($token->value === ',') {
                    if ($state !== 3) {
                        $expr->values[] = $value;
                        $value = '';
                        $state = 1;
                    }
                    continue;
                } elseif ($token->value === ')') {
                    $state = 3;
                    $expr->values[] = $value;
                    $ret[] = $expr;
                    $value = '';
                    $expr = new ValuesKeyword();
                    continue;
                }

                // No other operator is expected.
                break;
            }

            $expr->tokens[] = $token;
            if ($state === 1) {
                $value .= $token->value;
                $state = 2;
            }

        }

        // Last iteration was not saved.
        if (!empty($expr->tokens)) {
            $ret[] = $expr;
        }

        --$list->idx;
        return $ret;
    }
}