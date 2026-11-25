<?php

namespace Sintattica\Atk\Security;

/**
 * Whitelist validator for SQL WHERE fragments that arrive in a request
 * (atkselector, atkfilter, atkprimkey).
 *
 * ATK passes record selectors and list filters around as SQL. Instead of
 * looking for known-bad keywords, this class only accepts a small grammar:
 *
 *   expression := term { (AND | OR) term }
 *   term       := [NOT] ( '(' expression ')' | predicate )
 *   predicate  := column ( comparison | like | in | between | is-null )
 *   comparison := ('=' | '!=' | '<>' | '<' | '<=' | '>' | '>=') ( literal | column )
 *   like       := [NOT] LIKE string
 *   in         := [NOT] IN '(' literal { ',' literal } ')'
 *   between    := [NOT] BETWEEN literal AND literal
 *   is-null    := IS [NOT] NULL
 *   column     := identifier [ '.' identifier [ '.' identifier ] ]
 *   literal    := string | number | NULL
 *
 * There are no function calls, sub-queries, comments, statement separators
 * or operators other than the ones listed. In "key" mode the grammar is
 * narrowed to what Node::primaryKey() produces: column = literal [AND ...].
 */
class WhereClauseValidator
{
    const MODE_OFF = 'off';
    const MODE_LOG = 'log';
    const MODE_ENFORCE = 'enforce';

    /** @var array<int, array{0: string, 1: string}> */
    private $tokens = [];

    /** @var int */
    private $pos = 0;

    /** @var bool */
    private $allowBackslashEscapes;

    /**
     * @param bool $allowBackslashEscapes Whether \' counts as an escaped quote
     *                                    inside a string literal (true for MySQL,
     *                                    false for databases with standard
     *                                    conforming strings such as PostgreSQL).
     */
    public function __construct($allowBackslashEscapes = true)
    {
        $this->allowBackslashEscapes = (bool) $allowBackslashEscapes;
    }

    /**
     * Is the given fragment an acceptable WHERE condition?
     *
     * @param mixed $clause
     *
     * @return bool
     */
    public function isValidCondition($clause)
    {
        return $this->run($clause, false);
    }

    /**
     * Is the given fragment an acceptable record key: one or more
     * "column = literal" pairs joined by AND?
     *
     * @param mixed $clause
     *
     * @return bool
     */
    public function isValidKey($clause)
    {
        return $this->run($clause, true);
    }

    private function run($clause, $keyOnly)
    {
        if (!is_string($clause)) {
            return false;
        }
        if (trim($clause) === '') {
            return true;
        }
        if (!$this->tokenize($clause)) {
            return false;
        }
        $this->pos = 0;
        $ok = $keyOnly ? $this->parseKey() : $this->parseExpression(0);

        return $ok && $this->pos === count($this->tokens);
    }

    private function tokenize($sql)
    {
        $this->tokens = [];
        $len = strlen($sql);
        $i = 0;
        while ($i < $len) {
            $c = $sql[$i];

            if ($c === ' ' || $c === "\t" || $c === "\n" || $c === "\r") {
                ++$i;
                continue;
            }

            if ($c === "'") {
                $j = $i + 1;
                $closed = false;
                while ($j < $len) {
                    $d = $sql[$j];
                    if ($d === '\\') {
                        if (!$this->allowBackslashEscapes || $j + 1 >= $len) {
                            return false;
                        }
                        $j += 2;
                        continue;
                    }
                    if ($d === "'") {
                        if ($j + 1 < $len && $sql[$j + 1] === "'") {
                            $j += 2;
                            continue;
                        }
                        $closed = true;
                        break;
                    }
                    if ($d === "\0") {
                        return false;
                    }
                    ++$j;
                }
                if (!$closed) {
                    return false;
                }
                $this->tokens[] = ['str', ''];
                $i = $j + 1;
                continue;
            }

            if ($c === '`') {
                if (!preg_match('/\G`([A-Za-z_][A-Za-z0-9_]*)`/', $sql, $m, 0, $i)) {
                    return false;
                }
                $this->tokens[] = ['ident', $m[1]];
                $i += strlen($m[0]);
                continue;
            }

            if (preg_match('/\G-?(?:[0-9]+(?:\.[0-9]+)?)(?![A-Za-z0-9_.])/', $sql, $m, 0, $i)) {
                // a leading minus is only a sign directly after an operator, a comma or a parenthesis
                $prev = $this->tokens ? $this->tokens[count($this->tokens) - 1] : null;
                if ($m[0][0] === '-' && !($prev && ($prev[0] === 'op' || $prev[0] === '(' || $prev[0] === ',' || ($prev[0] === 'kw' && $prev[1] !== 'NULL')))) {
                    return false;
                }
                $this->tokens[] = ['num', $m[0]];
                $i += strlen($m[0]);
                continue;
            }

            if (preg_match('/\G[A-Za-z_][A-Za-z0-9_]*/', $sql, $m, 0, $i)) {
                $upper = strtoupper($m[0]);
                if (in_array($upper, ['AND', 'OR', 'NOT', 'IN', 'IS', 'NULL', 'LIKE', 'BETWEEN'], true)) {
                    $this->tokens[] = ['kw', $upper];
                } else {
                    $this->tokens[] = ['ident', $m[0]];
                }
                $i += strlen($m[0]);
                continue;
            }

            if (preg_match('/\G(?:<=|>=|<>|!=|=|<|>)/', $sql, $m, 0, $i)) {
                $this->tokens[] = ['op', $m[0]];
                $i += strlen($m[0]);
                continue;
            }

            if ($c === '(' || $c === ')' || $c === ',' || $c === '.') {
                $this->tokens[] = [$c, $c];
                ++$i;
                continue;
            }

            return false;
        }

        return true;
    }

    private function peek($type, $value = null)
    {
        if (!isset($this->tokens[$this->pos])) {
            return false;
        }
        $t = $this->tokens[$this->pos];

        return $t[0] === $type && ($value === null || $t[1] === $value);
    }

    private function accept($type, $value = null)
    {
        if ($this->peek($type, $value)) {
            ++$this->pos;

            return true;
        }

        return false;
    }

    private function parseKey()
    {
        do {
            if (!$this->parseColumn() || !$this->accept('op', '=') || !$this->parseScalar()) {
                return false;
            }
        } while ($this->accept('kw', 'AND'));

        return true;
    }

    private function parseExpression($depth)
    {
        if ($depth > 20) {
            return false;
        }
        do {
            if (!$this->parseTerm($depth)) {
                return false;
            }
        } while ($this->accept('kw', 'AND') || $this->accept('kw', 'OR'));

        return true;
    }

    private function parseTerm($depth)
    {
        $this->accept('kw', 'NOT');

        if ($this->accept('(')) {
            return $this->parseExpression($depth + 1) && $this->accept(')');
        }

        return $this->parsePredicate();
    }

    private function parsePredicate()
    {
        if (!$this->parseColumn()) {
            return false;
        }

        if ($this->accept('op')) {
            return $this->parseLiteral() || $this->parseColumn();
        }

        if ($this->accept('kw', 'IS')) {
            $this->accept('kw', 'NOT');

            return $this->accept('kw', 'NULL');
        }

        $this->accept('kw', 'NOT');

        if ($this->accept('kw', 'LIKE')) {
            return $this->accept('str');
        }

        if ($this->accept('kw', 'IN')) {
            if (!$this->accept('(')) {
                return false;
            }
            do {
                if (!$this->parseLiteral()) {
                    return false;
                }
            } while ($this->accept(','));

            return $this->accept(')');
        }

        if ($this->accept('kw', 'BETWEEN')) {
            return $this->parseScalar() && $this->accept('kw', 'AND') && $this->parseScalar();
        }

        return false;
    }

    private function parseColumn()
    {
        if (!$this->accept('ident')) {
            return false;
        }
        $parts = 1;
        while ($this->peek('.')) {
            ++$this->pos;
            if (!$this->accept('ident') || ++$parts > 3) {
                return false;
            }
        }

        return true;
    }

    private function parseScalar()
    {
        return $this->accept('str') || $this->accept('num');
    }

    private function parseLiteral()
    {
        return $this->parseScalar() || $this->accept('kw', 'NULL');
    }
}
