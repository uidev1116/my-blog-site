<?php

class SQL_Field extends SQL
{
    public $_field         = null;
    public $_scope         = null;

    function setField($fd)
    {
        $this->_field   = $fd;
        return true;
    }

    function setScope($scp)
    {
        $this->_scope   = $scp;
        return true;
    }

    function getField()
    {
        return $this->_field;
    }

    function getScope()
    {
        return $this->_scope;
    }

    function _field($dsn = null)
    {
        if (empty($this->_field)) {
            return false;
        }
        return (!empty($this->_scope) ? $this->_scope . '.' : '') . $this->_field;
    }

    function get($dsn = null)
    {
        return $this->_field($dsn);
    }
}

class SQL_Field_Function extends SQL_Field
{
//    public $_function  = null;
    public $_args  = null;

    function setFunction($args)
    {
        $this->_args = is_array($args) ? $args : func_get_args();
        return true;
    }

    function getFunction($func)
    {
        return $this->_args;
    }

    function _function($dsn = null)
    {
        $q  = SQL::isClass($this->_field, 'SQL_Field') ?
            $this->_field->get($dsn) :
        $this->_field($dsn);

        if (!empty($this->_args[0])) {
            switch (strtoupper($this->_args[0])) {
                case 'SUBSTR':
                    $func = 'SUBSTRING';
                    break;
                case 'RANDOM':
                    $func = 'RAND';
                    break;
                default:
                    $func = strtoupper($this->_args[0]);
            }
            switch ($func) {
                case 'DISTINCT':
                    $q  = 'DISTINCT ' . $q;
                    break;
                case 'SUBSTRING':
                    $q  = $func . '(' . $q;
                    if (array_key_exists(1, $this->_args)) {
                        $arg    = intval($this->_args[1]) + 1;
                        $q  .= ', ' . $arg;
                        if (array_key_exists(2, $this->_args)) {
                            $arg    = intval($this->_args[2]);
                            $q  .= ', ' . $arg;
                        }
                    }
                    $q  .=  ')';
                    break;
                default:
                    $q  = $func . '(' . $q;
                    for ($i = 1; array_key_exists($i, $this->_args); $i++) {
                        $arg    = $this->_args[$i];
                        if (is_null($arg)) {
                            $arg    = 'NULL';
                        } elseif (is_string($arg)) {
                            $arg    = DB::quote($arg);
                        }
                        $q  .= ', ' . $arg;
                    }
                    $q  .= ')';
            }
        }

        return $q;
    }

    function get($dsn = null)
    {
        return $this->_function($dsn);
    }
}

class SQL_Field_Operator extends SQL_Field_Function
{
    public $_value     = null;
    public $_operator  = null;

    function setValue($val)
    {
        $this->_value = $val;
        return true;
    }

    function getValue()
    {
        return $this->_value;
    }

    function setOperator($opr)
    {
        $this->_operator = $opr;
        return true;
    }

    function getOperator()
    {
        return $this->_operator;
    }

    function _right($dsn = null)
    {
        $val    = $this->_value;
        $opr    = $this->_operator;

        if (SQL::isClass($val, 'SQL')) {
            $val    = $val->get($dsn);
        } elseif (null === $val) {
            $val    = '';
            $opr    = ('=' == $opr) ? 'IS NULL' : 'IS NOT NULL';
        } elseif (is_string($val) && isset($dsn['charset'])) {
            $val  = DB::quote(mb_convert_encoding($val, $dsn['charset'], 'UTF-8'));
        }

        return ' ' . $opr . ' ' . $val;
    }

    function _operator($dsn = null)
    {
        $q  = '';
        $q  = SQL::isClass($this->_field, 'SQL_Field') ? $this->_field->get($dsn) : $this->_function($dsn);
        if (empty($q)) {
            return false;
        }

        if ($right = $this->_right($dsn)) {
            $q .= $right;
        }
        return $q;
    }

    function get($dsn = null)
    {
        return $this->_operator($dsn);
    }
}

class SQL_Field_Operator_In extends SQL_Field_Operator
{
//    function

    public $_not   = false;

    function setNot($not)
    {
        $this->_not = $not;
    }

    function getNot()
    {
        return $this->_not;
    }

    function _right($dsn = null)
    {
        $q  = '';
        $ope    = $this->_not ? 'NOT IN' : 'IN';
        if (SQL::isClass($this->_value, 'SQL_Select')) {
            $q  = ' ' . $ope . ' (' . "\n"
                . $this->_value->get($dsn)
            . "\n" . ')';
        } elseif (!empty($this->_value) and is_array($this->_value)) {
            $q  = ' ' . $ope . ' (';
            $isString   = is_string($this->_value[0]);
            foreach ($this->_value as $i => $val) {
                $q  .= (!empty($i) ? ', ' : '') . ($isString ? DB::quote(mb_convert_encoding($val, $dsn['charset'], 'UTF-8')) : $val);
            }
            $q  .= ')';
        } else {
            return false;
        }

        return $q;
    }
}

class SQL_Field_Operator_Exists extends SQL_Field_Operator
{
//    function

    public $_not   = false;

    function setNot($not)
    {
        $this->_not = $not;
    }

    function getNot()
    {
        return $this->_not;
    }

    function _right($dsn = null)
    {
        $q  = '';
        $ope    = $this->_not ? 'NOT EXISTS' : 'EXISTS';
        if (SQL::isClass($this->_value, 'SQL_Select')) {
            $q  = ' ' . $ope . ' (' . "\n"
                . $this->_value->get($dsn)
            . "\n" . ')';
        } else {
            return false;
        }

        return $q;
    }

    function _operator($dsn = null)
    {
        $q  = '';

        if ($right = $this->_right($dsn)) {
            $q .= $right;
        }
        return $q;
    }
}

class SQL_Field_Operator_Between extends SQL_Field_Operator
{
    public $_a = null;
    public $_b = null;

    function setBetween($a, $b)
    {
        $this->_a   = $a;
        $this->_b   = $b;
        return true;
    }

    function getBetween()
    {
        return [$this->_a, $this->_b];
    }

    function _right($dsn = null)
    {
        if (empty($this->_a) or empty($this->_b)) {
            return false;
        }
        return  ( is_string($this->_a) or is_string($this->_b) ) ?
            " BETWEEN " . DB::quote($this->_a) . " AND " . DB::quote($this->_b) :
        ' BETWEEN ' . $this->_a . ' AND ' . $this->_b;
    }
}

class SQL_Field_Case
{
    public $_cases     = [];
    public $_simple    = null;
    public $_else      = null;

    function setSimple($exp)
    {
        $this->_simple  = $exp;
    }

    function setElse($exp)
    {
        $this->_else    = $exp;
    }

    function add($when, $then)
    {
        $this->_cases[] = [
            'when'  => $when,
            'then'  => $then,
        ];
        return true;
    }
    function set($when = null, $then = null)
    {
        $this->_cases   = [];
        if (!empty($when)) {
            $this->add($when, $then);
        }
        return true;
    }

    function _case($dsn = null)
    {
        if (empty($this->_cases)) {
            return false;
        }
        $q  = "\n CASE";
        if (!empty($this->_simple)) {
            $exp    = $this->_simple;
            $exp    = SQL::isClass($exp, 'SQL') ? $exp->get($dsn) : (is_string($exp) ? DB::quote($exp) : $exp);
            $q  .= ' ' . strval($exp);
        }
        foreach ($this->_cases as $case) {
            $when   = $case['when'];
            $then   = $case['then'];
            $when    = SQL::isClass($when, 'SQL') ? $when->get($dsn) : (is_string($when) ? DB::quote($when) : $when);
            $then    = SQL::isClass($then, 'SQL') ? $then->get($dsn) : (is_string($then) ? DB::quote($then) : $then);
            $q  .= "\n  WHEN " . strval($when) . ' THEN ' . strval($then);
        }

        if (!is_null($this->_else)) {
            $exp    = $this->_else;
            if (SQL::isClass($exp, 'SQL')) {
                $exp   = $exp->get($dsn);
            } elseif (is_string($exp)) {
                $exp    = 'NULL' == strtoupper($exp) ? 'NULL' : DB::quote($exp);
            }
            $q      .= "\n  ELSE " . strval($exp);
        }

        $q  .= "\n END";
        return $q;
    }

    function get($dsn = null)
    {
        return $this->_case($dsn);
    }
}

/**
 * SQL_Where
 *
 * SQLヘルパのWhereメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Where extends SQL
{
    public $_wheres    = [];

    function addWhere($w, $gl = 'AND')
    {
        $this->_wheres[]    = [
            'where' => $w,
            'glue'  => $gl,
        ];
        return true;
    }
    function setWhere($w, $gl = 'AND')
    {
        $this->_wheres  = [];
        if (!empty($w)) {
            $this->addWhere($w, $gl);
        }
        return true;
    }

    function getWhereOpr($fd, $val, $opr = '=', $gl = 'AND', $scp = null, $func = null)
    {
        if (SQL::isClass($fd, 'SQL_Field_Function')) {
            $F  = $fd;
        } elseif (SQL::isClass($fd, 'SQL_Field')) {
            $F  = new SQL_Field_Function($fd);
            $F->setFunction($func);
        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
        }

        return  [
            'where' => SQL::newOpr($F, $val, $opr),
            'glue'  => $gl,
        ];
    }

    function getWhereIn($fd, $vals, $gl = 'AND', $scp = null, $func = null)
    {
        if (SQL::isClass($fd, 'SQL_Field_Function')) {
            $F  = $fd;
        } elseif (SQL::isClass($fd, 'SQL_Field')) {
            $F  = new SQL_Field_Function($fd);
            $F->setFunction($func);
        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
        }

        return [
            'where' => SQL::newOprIn($F, $vals),
            'glue'  => $gl,
        ];
    }

    function getWhereNotIn($fd, $vals, $gl = 'AND', $scp = null, $func = null)
    {
        if (SQL::isClass($fd, 'SQL_Field_Function')) {
            $F  = $fd;
        } elseif (SQL::isClass($fd, 'SQL_Field')) {
            $F  = new SQL_Field_Function($fd);
            $F->setFunction($func);
        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
        }

        return [
            'where' => SQL::newOprNotIn($F, $vals),
            'glue'  => $gl,
        ];
    }

    function getWhereExists($vals, $gl = 'AND')
    {
        return [
            'where' => SQL::newOprExists($vals),
            'glue'  => $gl,
        ];
    }

    function getWhereNotExists($vals, $gl = 'AND')
    {
        return [
            'where' => SQL::newOprNotExists($vals),
            'glue'  => $gl,
        ];
    }

    function getWhereBw($fd, $a, $b, $gl = 'AND', $scp = null, $func = null)
    {
        if (SQL::isClass($fd, 'SQL_Field_Function')) {
            $F  = $fd;
        } elseif (SQL::isClass($fd, 'SQL_Field')) {
            $F  = new SQL_Field_Function($fd);
            $F->setFunction($func);
        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
        }

        return [
            'where' => SQL::newOprBw($F, $a, $b),
            'glue'  => $gl,
        ];
    }

    /**
     * 指定されたfieldとvalueからWHERE句を生成する。<br>
     * $SQL->addWhereOpr('entry_id', 10, '=', 'OR', 'entry', 'count');<br>
     * WHERE 0 OR COUNT(entry.entry_id) = 10
     *
     * @param string $fd
     * @param string|int $val
     * @param string $opr
     * @param string $gl
     * @param string|null $scp
     * @param string|null $func
     * @return bool
     */
    function addWhereOpr($fd, $val, $opr = '=', $gl = 'AND', $scp = null, $func = null)
    {
        $this->_wheres[]    = $this->getWhereOpr($fd, $val, $opr, $gl, $scp, $func);
        return true;
    }

    /**
     * 指定されたfieldとvalue(配列)からIN句を生成する。<br>
     * $SQL->addWhereIn('entry_id', array(10, 20, 30), 'AND', 'entry');<br>
     * WHERE 1 AND entry.entry_id IN (10, 29, 30)
     *
     * @param string $fd
     * @param array $vals
     * @param string $gl
     * @param string|null $scp
     * @param string|null $func
     * @return bool
     */
    function addWhereIn($fd, $vals, $gl = 'AND', $scp = null, $func = null)
    {
        if (empty($vals)) {
            $vals = [-100];
        }
        $this->_wheres[]    = $this->getWhereIn($fd, $vals, $gl, $scp, $func);
        return true;
    }

    /**
     * 指定されたfieldとvalue(配列)からNOT IN句を生成する。<br>
     * $SQL->addWhereNotIn('entry_id', array(10, 20, 30), 'AND', 'entry');<br>
     * WHERE 1 AND entry.entry_id NOT IN (10, 29, 30)
     *
     * @param string $fd
     * @param array $vals
     * @param string $gl
     * @param string|null $scp
     * @param string|null $func
     * @return bool
     */
    function addWhereNotIn($fd, $vals, $gl = 'AND', $scp = null, $func = null)
    {
        $this->_wheres[]    = $this->getWhereNotIn($fd, $vals, $gl, $scp, $func);
        return true;
    }

    /**
     * 指定されたSQL_SelectオブジェクトからEXISTS句を生成する。<br>
     * $SQL->addWhereExists(SQL_SELECT);<br>
     * WHERE 1 AND EXISTS (SELECT * ...)
     *
     * @param array $vals
     * @param string $gl
     * @return bool
     */
    function addWhereExists($vals, $gl = 'AND')
    {
        $this->_wheres[]    = $this->getWhereExists($vals, $gl);
        return true;
    }

    /**
     * 指定されたSQL_SelectオブジェクトからNOT EXISTS句を生成する。<br>
     * $SQL->addWhereExists(SQL_SELECT);<br>
     * WHERE 1 AND NOT EXISTS (SELECT * ...)
     *
     * @param array $vals
     * @param string $gl
     * @return bool
     */
    function addWhereNotExists($vals, $gl = 'AND')
    {
        $this->_wheres[]    = $this->getWhereNotExists($vals, $gl);
        return true;
    }

    /**
     * 指定されたfieldとvalue(２つ)からBETWEEN句を生成する。<br>
     * $SQL->addWhereOpr('entry_id', 10, 20, 'AND', 'entry');<br>
     * WHERE 1 AND entry.entry_id BETWEEN 100 AND 200
     *
     * @param string $fd
     * @param string|int $a
     * @param string|int $b
     * @param string $gl
     * @param string|null $scp
     * @param string|null $func
     * @return bool
     */
    function addWhereBw($fd, $a, $b, $gl = 'AND', $scp = null, $func = null)
    {
        $this->_wheres[]    = $this->getWhereBw($fd, $a, $b, $gl, $scp, $func);
        return true;
    }

    function where($dsn = null)
    {
        $q  = '';
        if (!empty($this->_wheres)) {
            $q  = 'AND' == $this->_wheres[0]['glue'] ? '1' : '0';
            foreach ($this->_wheres as $where) {
                $w  = $where['where'];
                $gl = $where['glue'];
                $q  .= "\n  " . $gl;

                if (SQL::isClass($w, 'SQL_Where')) {
                    $w  = '( ' . $w->get($dsn) . "\n  )";
                } elseif (SQL::isClass($w, 'SQL')) {
                    $w  = $w->get($dsn);
                }
                $q  .= ' ' . $w;
            }
        }

        return $q;
    }

    function get($dsn = null)
    {
        return $this->where($dsn);
    }
}
/**
 * SQL_Select
 *
 * SQLヘルパのSelectメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Select extends SQL_Where
{
    public $_tables = [];
    public $_leftJoins = [];
    public $_innerJoins = [];
    public $_selects = [];
    public $_havings = [];
    public $_groups = [];
    public $_limit = null;
    public $_orders = [];
    public $_fdOrders = null;
    public $_where = null;
    public $_union = [];
    public $_straightJoin = false;

    function addTable($tb, $als = null, $straight_join = false)
    {
        $this->_straightJoin = $straight_join;
        $this->_tables[] = [
            'table' => $tb,
            'alias' => $als,
        ];
        return true;
    }
    function setTable($tb = null, $als = null, $straight_join = false)
    {
        $this->_tables  = [];
        if (!empty($tb)) {
            $this->addTable($tb, $als, $straight_join);
        }
        return true;
    }

    /**
     * 指定されたtableと条件からtableを結合する。<br>
     * $SQL->addLeftJoin('category', 'category_id', 'entry_category_id', 'category', 'entry');<br>
     * LEFT JOIN acms_category AS category ON category.category_id = entry.entry_category_id
     *
     * @param string $tb
     * @param string|int $a
     * @param string|int $b
     * @param string $aScp
     * @param string $bScp
     * @return bool
     */
    function addLeftJoin($tb, $a, $b, $aScp = null, $bScp = null, $where = null)
    {
        $A  = SQL::isClass($a, 'SQL_Field') ? $a : SQL::newField($a, $aScp);
        $B  = SQL::isClass($b, 'SQL_Field') ? $b : SQL::newField($b, $bScp);
        $this->_leftJoins[] = [
            'table'     => $tb,
            'a'         => $A,
            'b'         => $B,
            'where'     => $where,
        ];
        return true;
    }

    function setLeftJoin($tb = null, $a = null, $b = null, $aScp = null, $bScp = null, $where = null)
    {
        $this->_leftJoins   = [];
        if (!empty($tb) and !empty($a) and !empty($b)) {
            $this->addLeftJoin($tb, $a, $b, $aScp, $bScp);
        }
        return true;
    }

    /**
     * 指定されたtableと条件からINNER JOIN句を生成する。<br>
     * $SQL->addInnerJoin('category', 'category_id', 'entry_category_id', 'category', 'acms_entry');<br>
     * INNER JOIN acms_category AS category ON category.category_id = entry.entry_category_id
     *
     * @param string $tb
     * @param string|int $a
     * @param string|int $b
     * @param string $als
     * @param string $scp
     * @return bool
     */
    function addInnerJoin($tb, $a, $b, $als = null, $scp = null, $where = null)
    {
        //$A  = SQL::isClass($a, 'SQL_Field') ? $a : SQL::newField($a, $aScp);
        //$B  = SQL::isClass($b, 'SQL_Field') ? $b : SQL::newField($b, $bScp);
        $this->_innerJoins[] = [
            'table'     => $tb,
            'a'         => $a,
            'b'         => $b,
            'als'       => $als,
            'scp'       => $scp,
            'where'     => $where,
        ];
        return true;
    }

    function setInnerJoin($tb = null, $a = null, $b = null, $als = null, $scp = null)
    {
        $this->_innerJoins   = [];
        if (!empty($tb) and !empty($a) and !empty($b)) {
            $this->addInnerJoin($tb, $a, $b, $als, $scp);
        }
        return true;
    }

    function addUnion($select)
    {
        $this->_union[] = $select;
    }

    /**
     * 指定されたfieldを追加する。<br>
     * $SQL->addSelect('entry_id', 'entry_count', 'acms_entry', 'count');<br>
     * SELECT COUNT(acms_entry.entry_id) AS entry_count
     *
     * @param string $fd
     * @param string $als
     * @param string $scp
     * @param string $func
     * @return bool
     */
    function addSelect($fd, $als = null, $scp = null, $func = null)
    {
//        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
//            $F  = $fd;
//        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
//            $F  = new SQL_Field_Function($fd);
//            $F->setFunction($func);
//        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
//        }

        $this->_selects[]   = [
            'field' => $F,
            'alias' => $als,
        ];
        return true;
    }
    function setSelect($fd = null, $als = null, $scp = null, $func = null)
    {
        $this->_selects = [];
        if (!empty($fd)) {
            $this->addSelect($fd, $als, $scp, $func);
        }
        return true;
    }

    function addGeoDistance($fd, $lat, $lng, $als = null, $scp = null)
    {
        $select = "ROUND(" . G_LENGTH . "(" . GEOM_FROM_TEXT . "(CONCAT('LineString($lat $lng, ', " . POINT_X . "($fd),  ' ', " . POINT_Y . "($fd),')'))) * 111000)";
        $this->addSelect($select, $als, $scp);

        return true;
    }



    /**
     * 指定された条件式でHAVING句を生成する<br>
     * $SQL->addHaving('entry_id > 5', 'AND');<br>
     * HAVING ( 1 AND entry_id > 5 )
     *
     * @param string $h
     * @param string $gl
     * @return bool
     */
    function addHaving($h, $gl = 'AND')
    {
        $this->_havings[]   = [
            'having'    => $h,
            'glue'      => $gl,
        ];
        return true;
    }
    function setHaving($h = null, $gl = 'AND')
    {
        $this->_havings = [];
        if (!empty($h)) {
            $this->addHaving($h, $gl);
        }
        return true;
    }

    /**
     * 指定されたfieldでGROUP BY句を生成する<br>
     * $SQL->addGroup('blog_id', 'acms_blog');<br>
     * GROUP BY acms_blog.blog_id
     *
     * @param string $fd
     * @param string $scp
     * @return bool
     */
    function addGroup($fd, $scp = null)
    {
        $this->_groups[]    =
            SQL::isClass($fd, 'SQL_Field') ? $fd : SQL::newField($fd, $scp)
        ;
        return true;
    }
    function setGroup($fd = null, $scp = null)
    {
        $this->_groups  = [];
        if (!empty($fd)) {
            $this->addGroup($fd, $scp);
        }
        return true;
    }

    /**
     * 指定された数のレコードを返す<br>
     * $SQL->setLimit(30, 10);<br>
     * LIMIT 10, 30
     *
     * @param int $lmt
     * @param int $off
     * @return bool
     */
    function setLimit($lmt, $off = 0)
    {
        $this->_limit   = [
            'limit'     => intval($lmt),
            'offset'    => intval($off),
        ];
        return true;
    }

    function addOrder($fd, $ord = 'ASC', $scp = null)
    {
        $this->_orders[]    = [
            'order' => (strtoupper($ord) == 'ASC') ? 'ASC' : 'DESC',
            'field' => SQL::isClass($fd, 'SQL_Field') ? $fd : SQL::newField($fd, $scp),
        ];
        return true;
    }

    /**
     * 指定されたorderのSQLを生成する<br>
     * $SQL->setOrder('entry_id', 'ASC', 'acms_entry');<br>
     * LIMIT 10, 30
     *
     * @param string|null $fd
     * @param string $ord
     * @param string|null $scp
     * @return bool
     */
    function setOrder($fd = null, $ord = 'ASC', $scp = null)
    {
        $this->_orders  = [];
        if (!empty($fd)) {
            $this->addOrder($fd, $ord, $scp);
        }
        return true;
    }

    function setFieldOrder($fd = null, $values = [], $scp = null)
    {
        $this->_fdOrders    = [
            'fd'        => SQL::isClass($fd, 'SQL_Field') ? $fd : SQL::newField($fd, $scp),
            'values'    => $values,
        ];
    }

    function get($dsn = null)
    {
        if (empty($this->_tables)) {
            return false;
        }
        $tbPfx   = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        //--------
        // select
        $q  = 'SELECT';
        if ($this->_straightJoin) {
            $q  .= ' STRAIGHT_JOIN ';
        }
        $_q = ' *';
        if (!empty($this->_selects)) {
            $_q = '';
            foreach ($this->_selects as $i => $s) {
                $col = $s['field']->get($dsn);
                if ($col === '*') {
                    $_q = '*' . (!empty($i) ? ', ' : ' ') . $_q;
                } else {
                    $_q .= (!empty($i) ? ', ' : ' ') . $col
                        . (!empty($s['alias']) ? ' AS ' . $s['alias'] : '');
                }
            }
        }
        $q  .= $_q;

        //-------
        // table
        $q  .= "\n FROM";
        foreach ($this->_tables as $i => $t) {
            $q  .= !empty($i) ? ', ' : '';
            if (SQL::isClass($t['table'], 'SQL_Select')) {
                $q  .= " (\n";
                $q  .= $t['table']->get($dsn);
                $q  .= "\n)";
            } else {
                $q  .= ' ' . $tbPfx . $t['table'];
            }
            if (!empty($t['alias'])) {
                $q  .= ' AS ' . $t['alias'];
            }
        }

        //----------
        // leftJoin
        if (!empty($this->_leftJoins)) {
            foreach ($this->_leftJoins as $i => $lj) {
                $A  = $lj['a'];
                $B  = $lj['b'];
                $W  = $lj['where'];
                $q .= "\n LEFT JOIN";
                if (SQL::isClass($lj['table'], 'SQL_Select')) {
                    $q  .= " (\n";
                    $q  .= $lj['table']->get($dsn);
                    $q  .= "\n)";
                } else {
                    $q  .= ' ' . $tbPfx . $lj['table'];
                }

                if ($scp = $A->getScope()) {
                    $q  .= ' AS ' . $scp;
                }
                $where = is_null($W) ? '' : ' AND ' . $W->get($dsn);
                $q  .= ' ON ' . $A->get($dsn) . ' = ' . $B->get($dsn) . $where;
            }
        }

        //-----------
        // innerJoin
        if (!empty($this->_innerJoins)) {
            foreach ($this->_innerJoins as $i => $data) {
                $q  .= "\n INNER JOIN";
                if (SQL::isClass($data['table'], 'SQL_Select')) {
                    $q  .= " (\n";
                    $q  .= $data['table']->get($dsn);
                    $q  .= "\n)";
                } else {
                    $q  .= ' ' . $tbPfx . $data['table'];
                }

                if (!empty($data['als'])) {
                    $q  .= ' AS ' . $data['als'];
                }
                $where = is_null($data['where']) ? '' : ' AND ' . $data['where']->get($dsn);
                $q  .= ' ON '
                    . (!empty($data['als']) ? $data['als'] . '.' : '') . $data['a']
                    . ' = '
                    . (!empty($data['scp']) ? $data['scp'] . '.' : '') . $data['b']
                    . $where;
                ;
            }
        }

        //----------
        // union
        $q  .= "\n ";
        foreach ($this->_union as $val) {
            if (SQL::isClass($val, 'SQL_Select')) {
                $q .= "UNION (\n" . $val->get($dsn);
                $q .= "\n)";
            }
        }

        //-------
        // where
        if (!empty($this->_wheres)) {
            $q  .= "\n WHERE " . $this->where($dsn);
        }

        //-------
        // group
        if (!empty($this->_groups)) {
            $q  .= "\n GROUP BY";
            foreach ($this->_groups as $i => $g) {
                $q  .= (!empty($i) ? ', ' : ' ') . $g->get($dsn);
            }
        }

        //--------
        // having
        if (!empty($this->_havings)) {
            $q  .= "\n HAVING ( ";
            $q  .= ('AND' == $this->_havings[0]['glue'] ? '1' : '0');
            foreach ($this->_havings as $having) {
                $h  = $having['having'];
                $gl = $having['glue'];
                $q  .= "\n  " . $gl;
                if (SQL::isClass($h, 'SQL_Where')) {
                    $h  = '( 1' . $h->get($dsn) . "\n  )";
                } elseif (SQL::isClass($h, 'SQL')) {
                    $h  = $h->get($dsn);
                }
                $q  .= ' ' . $h;
            }
            $q  .= "\n )";
        }

        //-------
        // order
        if (!empty($this->_orders)) {
            $q  .= "\n ORDER BY";
            foreach ($this->_orders as $i => $order) {
                $ord    = $order['order'];
                $F      = $order['field'];
                $q  .= (!empty($i) ? ', ' : ' ') . $F->get($dsn) . ' ' . $ord;
            }
        } elseif (!empty($this->_fdOrders)) {
            $q  .= "\n ORDER BY FIELD(";
            $q  .= $this->_fdOrders['fd']->get($dsn) . ', ';
            $q  .= implode(', ', $this->_fdOrders['values']);
            $q  .= "\n )";
        }

        //-------
        // limit
        if (!empty($this->_limit)) {
            $q  .= "\n LIMIT " . $this->_limit['offset'] . ', ' . $this->_limit['limit'];
        }

        return $q;
    }
}

/**
 * SQL_Insert
 *
 * SQLヘルパのInsertメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Insert extends SQL
{
    public $_insert    = null;
    public $_table     = null;

    /**
     * 指定されたfieldにINSERT句を生成する。<br>
     * $SQL->addInsert('entry_code', 'abc');<br>
     * INSERT INTO acms_entry (entry_code) VALUES ('abc')
     *
     * @param string $fd
     * @param string|int $val
     * @return bool
     */
    function addInsert($fd, $val)
    {
        if (!is_string($fd)) {
            return false;
        }
        $this->_insert[$fd] = $val;
        return true;
    }
    function setInsert($fd = null, $val = null)
    {
        if (SQL::isClass($fd, 'SQL_Select')) {
            $this->_insert = $fd;
        } elseif (!is_string($fd)) {
            return false;
        }

        $this->_insert = [];
        if (!empty($fd)) {
            $this->addInsert($fd, $val);
        }
        return true;
    }

    function setTable($tb)
    {
        $this->_table   = $tb;
    }

    function get($dsn = null)
    {
        if (empty($this->_table)) {
            return false;
        }
        if (empty($this->_insert)) {
            return false;
        }
        $tbPfx  = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        $q  = 'INSERT INTO ' . $tbPfx . $this->_table;
        if (SQL::isClass($this->_insert, 'SQL_Select')) {
            $q  .= ' ' . $this->_insert->get($dsn);
        } elseif (!is_array($this->_insert)) {
            return false;
        } else {
            $fds   = [];
            $vals   = [];
            foreach ($this->_insert as $fd => $val) {
                $fds[] = $fd;
                if (is_null($val)) {
                    $val    = 'NULL';
                } elseif (is_string($val)) {
                    $_val   = mb_convert_encoding($val, $dsn['charset'], 'UTF-8');
                    $val    = ($val === mb_convert_encoding($_val, 'UTF-8', $dsn['charset'])) ?
                        DB::quote($_val) : '0x' . bin2hex($val)
                    ;
                } elseif (SQL::isClass($val, 'SQL_Field_Function')) {
                    $val = $val->get($dsn);
                }
                $vals[] = $val;
            }
            $q  .= ' (' . join(', ', $fds) . ') '
                . "\n" . ' VALUES (' . join(', ', $vals) . ')'
            ;
        }

        return $q;
    }
}

/**
 * SQL_Replace
 *
 * SQLヘルパのReplaceメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Replace extends SQL
{
    public $_replace    = null;
    public $_table     = null;

    /**
     * 指定されたfieldにREPLACE句を生成する。<br>
     * $SQL->addRepace('entry_code', 'abc');<br>
     * REPLACE INTO acms_entry (entry_code) VALUES ('abc')
     *
     * @param string $fd
     * @param string|int $val
     * @return bool
     */
    function addReplace($fd, $val)
    {
        if (!is_string($fd)) {
            return false;
        }
        $this->_replace[$fd] = $val;
        return true;
    }
    function setReplace($fd = null, $val = null)
    {
        if (SQL::isClass($fd, 'SQL_Select')) {
            $this->_replace = $fd;
        } elseif (!is_string($fd)) {
            return false;
        }

        $this->_replace = [];
        if (!empty($fd)) {
            $this->addReplace($fd, $val);
        }
        return true;
    }

    function setTable($tb)
    {
        $this->_table   = $tb;
    }

    function get($dsn = null)
    {
        if (empty($this->_table)) {
            return false;
        }
        if (empty($this->_replace)) {
            return false;
        }
        $tbPfx  = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        $q  = 'REPLACE INTO ' . $tbPfx . $this->_table;
        if (SQL::isClass($this->_replace, 'SQL_Select')) {
            $q  .= ' ' . $this->_replace->get($dsn);
        } elseif (!is_array($this->_replace)) {
            return false;
        } else {
            $fds   = [];
            $vals   = [];
            foreach ($this->_replace as $fd => $val) {
                $fds[] = $fd;
                if (is_null($val)) {
                    $val    = 'NULL';
                } elseif (is_string($val)) {
                    $_val   = mb_convert_encoding($val, $dsn['charset'], 'UTF-8');
                    $val    = ($val === mb_convert_encoding($_val, 'UTF-8', $dsn['charset'])) ?
                        DB::quote($_val) : '0x' . bin2hex($val)
                    ;
                }
                $vals[] = $val;
            }
            $q  .= ' (' . join(', ', $fds) . ') '
                . "\n" . ' VALUES (' . join(', ', $vals) . ')'
            ;
        }

        return $q;
    }
}

/**
 * SQL_Update
 *
 * SQLヘルパのUpdateメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Update extends SQL_Where
{
    public $_update    = [];
    public $_table     = null;

    /**
     * 指定されたfieldにUPDATE句を生成する。<br>
     * $SQL->addUpdate('entry_code', 'abc');<br>
     * UPDATE acms_entry SET entry_code = 'abc'
     *
     * @param string $fd
     * @param string|int $val
     * @return bool
     */
    function addUpdate($fd, $val)
    {
        if (!is_string($fd)) {
            return false;
        }
        $this->_update[$fd] = $val;
        return true;
    }

    function setUpdate($fd = null, $val = null)
    {
        $this->_update  = [];
        if (!empty($fd)) {
            $this->addUpdate($fd, $val);
        }
        return true;
    }

    function setTable($tb)
    {
        $this->_table   = $tb;
    }

    function get($dsn = null)
    {
        if (empty($this->_table)) {
            return false;
        }
        if (empty($this->_update)) {
            return false;
        }
        $tbPfx  = !empty($dsn['prefix']) ? $dsn['prefix'] : '';
        $q  = 'UPDATE ' . $tbPfx . $this->_table . ' SET';
        $i  = 0;
        foreach ($this->_update as $fd => $val) {
            $q  .= !empty($i) ? "\n, " : "\n ";
            if (is_null($val)) {
                $val    = 'NULL';
            } elseif (SQL::isClass($val, 'SQL')) {
                $val    = "(\n" . $val->get($dsn) . "\n)";
            } elseif (is_string($val)) {
                $_val   = mb_convert_encoding($val, $dsn['charset'], 'UTF-8');
                $val    = ($val === mb_convert_encoding($_val, 'UTF-8', $dsn['charset'])) ?
                    DB::quote($_val) : '0x' . bin2hex($val)
                ;
            }
            $q  .= $fd . ' = ' . $val;
            $i++;
        }

        //-------
        // where
        if (!empty($this->_wheres)) {
            $q  .= "\n WHERE " . $this->where($dsn);
        }

        return $q;
    }
}

/**
 * SQL_InsertOrUpdate
 *
 * SQLヘルパの INSERT ON DUPLICATE KEY UPDATE メソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_InsertOrUpdate extends SQL_Insert
{
    public $_insert    = null;
    public $_update    = null;
    public $_table     = null;

    /**
     * 指定されたfieldにON DUPLICATE KEY UPDATE句を生成する。<br>
     * $SQL->addUpdate('entry_code', 'abc');<br>
     * ... ON DUPLICATE KEY UPDATE entry_code = 'abc'
     *
     * @param string $fd
     * @param string|int $val
     * @return bool
     */
    function addUpdate($fd, $val)
    {
        if (!is_string($fd)) {
            return false;
        }
        $this->_update[$fd] = $val;
        return true;
    }

    function setUpdate($fd = null, $val = null)
    {
        $this->_update  = [];
        if (!empty($fd)) {
            $this->addUpdate($fd, $val);
        }
        return true;
    }

    function setTable($tb)
    {
        $this->_table   = $tb;
    }

    function get($dsn = null)
    {
        if (empty($this->_table)) {
            return false;
        }
        if (empty($this->_insert)) {
            return false;
        }
        $tbPfx  = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        $q  = 'INSERT INTO ' . $tbPfx . $this->_table;
        if (SQL::isClass($this->_insert, 'SQL_Select')) {
            $q  .= ' ' . $this->_insert->get($dsn);
        } elseif (!is_array($this->_insert)) {
            return false;
        } else {
            $fds   = [];
            $vals   = [];
            foreach ($this->_insert as $fd => $val) {
                $fds[] = $fd;
                if (is_null($val)) {
                    $val    = 'NULL';
                } elseif (is_string($val)) {
                    $_val   = mb_convert_encoding($val, $dsn['charset'], 'UTF-8');
                    $val    = ($val === mb_convert_encoding($_val, 'UTF-8', $dsn['charset'])) ? DB::quote($_val) : '0x' . bin2hex($val)
                    ;
                }
                $vals[] = $val;
            }
            $q  .= ' (' . join(', ', $fds) . ') '
                . "\n" . ' VALUES (' . join(', ', $vals) . ')'
            ;

            if (empty($this->_update)) {
                return $q;
            }


            $q  .= ' ON DUPLICATE KEY UPDATE ';
            $i  = 0;
            foreach ($this->_update as $fd => $val) {
                $q  .= !empty($i) ? "\n, " : "\n ";
                if (is_null($val)) {
                    $val    = 'NULL';
                } elseif (SQL::isClass($val, 'SQL')) {
                    $val    = "(\n" . $val->get($dsn) . "\n)";
                } elseif (is_string($val)) {
                    $_val   = mb_convert_encoding($val, $dsn['charset'], 'UTF-8');
                    $val    = ($val === mb_convert_encoding($_val, 'UTF-8', $dsn['charset'])) ?
                        DB::quote($_val) : '0x' . bin2hex($val)
                    ;
                }
                $q  .= $fd . ' = ' . $val;
                $i++;
            }
        }

        return $q;
    }
}

/**
 * SQL_Delete
 *
 * SQLヘルパのDeleteメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Delete extends SQL_Where
{
    public $_table  = null;

    function setTable($tb)
    {
        $this->_table   = $tb;
    }

    function get($dsn = null)
    {
        if (empty($this->_table)) {
            return false;
        }
        $tbPfx  = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        $q  = 'DELETE FROM ' . $tbPfx . $this->_table;

        //-------
        // where
        if (!empty($this->_wheres)) {
            $q  .= "\n WHERE " . $this->where($dsn);
        }

        return $q;
    }
}

/**
 * SQL_ShowTable
 *
 * SQLヘルパのSHOW TABLEメソッド群です。
 *
 * @package php
 */
class SQL_ShowTable extends SQL
{
    public $_table  = null;

    function setTable($tb)
    {
        $this->_table = $tb;
    }

    function get($dsn = null)
    {
        $tbPfx = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        if (empty($this->_table)) {
            $q  = 'SHOW TABLES';
        } else {
            $q  = 'SHOW TABLES LIKE \'' . $tbPfx . $this->_table . '\'';
        }
        return $q;
    }
}

/**
 * SQL_Where
 *
 * SQLヘルパのSequenceメソッド群です。
 *
 * @package php
 */
class SQL_Sequence extends SQL
{
    public $_method    = 'nextval';
    public $_sequence  = null;
    public $_value     = null;
    public $_plugin    = false;

    function setSequence($seq)
    {
        $this->_sequence    = $seq;
        return true;
    }

    function setMethod($method)
    {
        $this->_method  = $method;
        return true;
    }

    function setValue($val)
    {
        $this->_value   = $val;
        return true;
    }

    function setPluginFlag($plugin)
    {
        $this->_plugin  = $plugin;
        return true;
    }

    function get($dsn = null)
    {
        if (empty($this->_sequence)) {
            return false;
        }
        $tb = ($this->_plugin) ? 'sequence_plugin' : 'sequence';
        $fd = 'sequence_' . $this->_sequence;

        $q  = '';
        switch ($this->_method) {
            case 'optimize':
                $table = substr($this->_sequence, 0, -3);
                $SUB = SQL::newSelect($table);
                $SUB->setSelect($this->_sequence);
                $SUB->setLimit(1);
                $SUB->setOrder($this->_sequence, 'DESC');
                $SQL = SQL::newUpdate($tb);
                if ($this->_plugin) {
                    $SQL->addUpdate('sequence_plugin_value', $SUB);
                    $SQL->addWhereOpr('sequence_plugin_key', $fd);
                } else {
                    $SQL->setUpdate($fd, $SUB);
                }
                $q = $SQL->get($dsn);
                break;
            case 'currval':
                $SQL    = SQL::newSelect($tb);
                if ($this->_plugin) {
                    $SQL->setSelect('sequence_plugin_value');
                    $SQL->addWhereOpr('sequence_plugin_key', $fd);
                } else {
                    $SQL->setSelect($fd);
                }
                $q  = $SQL->get($dsn);
                break;
            case 'setval':
                $SQL    = SQL::newUpdate($tb);
                if ($this->_plugin) {
                    $SQL->addUpdate('sequence_plugin_value', $this->_value);
                    $SQL->addWhereOpr('sequence_plugin_key', $fd);
                } else {
                    $SQL->setUpdate($fd, $this->_value);
                }
                $q  = $SQL->get($dsn);
                break;
            case 'nextval':
            default:
                $SQL    = SQL::newUpdate($tb);
                if ($this->_plugin) {
                    $SQL->addUpdate('sequence_plugin_value', SQL::newFunction(SQL::newOpr('sequence_plugin_value', 1, '+'), 'LAST_INSERT_ID'));
                    $SQL->addWhereOpr('sequence_plugin_key', $fd);
                } else {
                    $SQL->setUpdate(
                        $fd, //SQL::newOpr($fd, 1, '+')
                        SQL::newFunction(SQL::newOpr($fd, 1, '+'), 'LAST_INSERT_ID')
                    );
                }
                $q  = $SQL->get($dsn);
                break;
        }

        return $q;
    }
}

class SQL_Binary
{
    public $_value = null;

    function __construct($val = null)
    {
        $this->set($val);
    }

    function set($val)
    {
        $this->_value   = $val;
        return true;
    }

    function get($dsn = null)
    {
        return $this->_value;
    }
}

/**
 * SQL
 *
 * SQLヘルパのメソッド群です。
 *
 * @package php
 */
class SQL
{
    public function __construct($SQL = null)
    {
        if (SQL::isClass($SQL, 'SQL')) {
            foreach (get_object_vars($SQL) as $key => $value) {
                $this->$key = $value; // @phpstan-ignore-line
            }
        }
    }

    public function get($dsn = null)
    {
        throw new Exception('SQL::get() is not implemented.');
    }

    public static function isClass(&$obj, $className)
    {
        return (1
            and 'object' == gettype($obj)
            and 0 === strpos(strtoupper(get_class($obj)), strtoupper($className))
        );
    }

    public static function newSeq($seq, $method = 'nextval', $val = null)
    {
        $Obj    = new SQL_Sequence();
        $Obj->setSequence($seq);
        $Obj->setMethod($method);
        $Obj->setValue($val);
        return $Obj;
    }

    /**
     * 指定されたsequence fieldのシーケンス番号を最適化する<br>
     * SQL::optimizeSeq('entry_id', dsn())<br>
     * UPDATE acms_sequence SET sequence_entry_id = ( LAST_INSERT_ID(sequence_entry_id + 1) )
     *
     * @static
     * @param string|SQL_Sequence $seq
     * @param null $dsn
     * @param bool $plugin
     * @return int
     */
    public static function optimizeSeq($seq, $dsn = null, $plugin = false)
    {
        if (SQL::isClass($seq, 'SQL_Sequence')) {
            $Seq = $seq;
            $Seq->setMethod('optimize');
        } else {
            $Seq = SQL::newSeq($seq, 'optimize');
        }
        if ($plugin) {
            $Seq->setPluginFlag($plugin);
        }
        return $Seq->get($dsn);
    }

    /**
     * 指定されたsequence fieldのシーケンス番号を１進めてその値を返す<br>
     * SQL::nextval('entry_id', dsn())<br>
     * UPDATE acms_sequence SET sequence_entry_id = ( LAST_INSERT_ID(sequence_entry_id + 1) )
     *
     * @static
     * @param string|SQL_Sequence $seq
     * @param null $dsn
     * @return int
     */
    public static function nextval($seq, $dsn = null, $plugin = false)
    {
        if (SQL::isClass($seq, 'SQL_Sequence')) {
            $Seq    = $seq;
            $Seq->setMethod('nextval');
        } else {
            $Seq    = SQL::newSeq($seq, 'nextval');
        }
        if ($plugin) {
            $Seq->setPluginFlag($plugin);
        }
        return $Seq->get($dsn);
    }

    /**
     * 指定されたsequence fieldの現在のシーケンス番号を返す<br>
     * SQL::currval('entry_id', dsn())<br>
     * SELECT sequence_entry_id FROM acms_sequence
     *
     * @static
     * @param string|SQL_Sequence $seq
     * @param null $dsn
     * @return int
     */
    public static function currval($seq, $dsn = null, $plugin = false)
    {
        if (SQL::isClass($seq, 'SQL_Sequence')) {
            $Seq    = $seq;
            $Seq->setMethod('currval');
        } else {
            $Seq    = SQL::newSeq($seq, 'currval');
        }
        if ($plugin) {
            $Seq->setPluginFlag($plugin);
        }
        return $Seq->get($dsn);
    }

    /**
     * 指定されたsequence fieldを指定された値にセットする<br>
     * SQL::setval('entry_id', 10, dsn())<br>
     * UPDATE acms_sequence SET sequence_entry_id = 10
     *
     * @static
     * @param string|SQL_Sequence $seq
     * @param null $dsn
     * @return int
     */
    public static function setval($seq, $val, $dsn = null, $plugin = false)
    {
        if (SQL::isClass($seq, 'SQL_Sequence')) {
            $Seq    = $seq;
            $Seq->setMethod('setval');
            $Seq->setValue($val);
        } else {
            $Seq    = SQL::newSeq($seq, 'setval', $val);
        }
        if ($plugin) {
            $Seq->setPluginFlag($plugin);
        }
        return $Seq->get($dsn);
    }

    public static function newField($fd, $scp = null)
    {
        $Obj    = new SQL_Field();
        $Obj->setField($fd);
        $Obj->setScope($scp);
        return $Obj;
    }

    public static function newFunction($fd, $func = null, $scp = null)
    {
        $Obj    = new SQL_Field_Function();
        $Obj->setField($fd);
        $Obj->setFunction($func);
        $Obj->setScope($scp);
        return $Obj;
    }

    public static function newGeometry($lat, $lng, $scp = null)
    {
        $fd     = '\'POINT(' . $lng . ' ' . $lat . ')\'';
        $Obj    = new SQL_Field_Function();
        $Obj->setField($fd);
        $Obj->setFunction(GEOM_FROM_TEXT);
        $Obj->setScope($scp);
        return $Obj;
    }

    public static function newOpr($fd, $val = null, $opr = '=', $scp = null, $func = null)
    {
        if (SQL::isClass($fd, 'SQL_Field_Function')) {
            $Obj    = new SQL_Field_Operator($fd);
        } elseif (SQL::isClass($fd, 'SQL_Field')) {
            $Obj    = new SQL_Field_Operator($fd);
            $Obj->setFunction($func);
        } else {
            $Obj    = new SQL_Field_Operator();
            $Obj->setField($fd);
            $Obj->setScope($scp);
            $Obj->setFunction($func);
        }
        $Obj->setValue($val);
        $Obj->setOperator($opr);

        return $Obj;
    }

    public static function newOprIn($fd, $val, $scp = null, $func = null)
    {
        if (SQL::isClass($fd, 'SQL_Field_Function')) {
            $Obj    = new SQL_Field_Operator_In($fd);
        } elseif (SQL::isClass($fd, 'SQL_Field')) {
            $Obj    = new SQL_Field_Operator_In($fd);
            $Obj->setFunction($func);
        } else {
            $Obj    = new SQL_Field_Operator_In();
            $Obj->setField($fd);
            $Obj->setScope($scp);
            $Obj->setFunction($func);
        }
        $Obj->setValue($val);

        return $Obj;
    }

    public static function newOprNotIn($fd, $val, $scp = null, $func = null)
    {
        if (SQL::isClass($fd, 'SQL_Field_Function')) {
            $Obj    = new SQL_Field_Operator_In($fd);
        } elseif (SQL::isClass($fd, 'SQL_Field')) {
            $Obj    = new SQL_Field_Operator_In($fd);
            $Obj->setFunction($func);
        } else {
            $Obj    = new SQL_Field_Operator_In();
            $Obj->setField($fd);
            $Obj->setScope($scp);
            $Obj->setFunction($func);
        }
        $Obj->setValue($val);
        $Obj->setNot(true);

        return $Obj;
    }

    public static function newOprExists($val, $scp = null)
    {
        $Obj = new SQL_Field_Operator_Exists();
        $Obj->setScope($scp);
        $Obj->setValue($val);

        return $Obj;
    }

    public static function newOprNotExists($val, $scp = null)
    {
        $Obj = new SQL_Field_Operator_Exists();
        $Obj->setValue($val);
        $Obj->setNot(true);

        return $Obj;
    }

    public static function newOprBw($fd, $a, $b, $scp = null, $func = null)
    {
        if (SQL::isClass($fd, 'SQL_Field_Function')) {
            $Obj    = new SQL_Field_Operator_Between($fd);
        } elseif (SQL::isClass($fd, 'SQL_Field')) {
            $Obj    = new SQL_Field_Operator_Between($fd);
            $Obj->setFunction($func);
        } else {
            $Obj    = new SQL_Field_Operator_Between();
            $Obj->setField($fd);
            $Obj->setScope($scp);
            $Obj->setFunction($func);
        }
        $Obj->setBetween($a, $b);

        return $Obj;
    }

    public static function newCase($simple = null)
    {
        $Obj    = new SQL_Field_Case();
        $Obj->setSimple($simple);
        return $Obj;
    }

    public static function newWhere()
    {
        $Obj    = new SQL_Where();
        return $Obj;
    }

    /**
     * TABLEを指定してSELECT句を生成する為のSQL_Selectを返す
     *
     * @static
     * @param string|null $tb
     * @param string|null $als
     * @return SQL_Select
     */
    public static function newSelect($tb = null, $als = null, $straight_join = false)
    {
        $Obj    = new SQL_Select();
        if (!empty($tb)) {
            $Obj->setTable($tb, $als, $straight_join);
        }
        return $Obj;
    }

    /**
     * TABLEを指定してINSERT句を生成する為のSQL_Insertを返す
     *
     * @static
     * @param string|null $tb
     * @return SQL_Insert
     */
    public static function newInsert($tb = null)
    {
        $Obj    = new SQL_Insert();
        if (!empty($tb)) {
            $Obj->setTable($tb);
        }
        return $Obj;
    }

    /**
     * TABLEを指定してREPLACE句を生成する為のSQL_Replaceを返す
     *
     * @static
     * @param string|null $tb
     * @return SQL_Replace
     */
    public static function newReplace($tb = null)
    {
        $Obj    = new SQL_Replace();
        if (!empty($tb)) {
            $Obj->setTable($tb);
        }
        return $Obj;
    }

    /**
     * TABLEを指定してUPDATE句を生成する為のSQL_Updateを返す
     *
     * @static
     * @param string|null $tb
     * @return SQL_Update
     */
    public static function newUpdate($tb = null)
    {
        $Obj    = new SQL_Update();
        if (!empty($tb)) {
            $Obj->setTable($tb);
        }
        return $Obj;
    }

    /**
     * TABLEを指定してINSERT ON DUPLICATE KEY UPDATE句を生成する為のSQL_InsertOrUpdateを返す
     *
     * @static
     * @param string|null $tb
     * @param string|null $als
     * @return SQL_InsertOrUpdate
     */
    public static function newInsertOrUpdate($tb = null, $als = null)
    {
        $Obj    = new SQL_InsertOrUpdate();
        if (!empty($tb)) {
            $Obj->setTable($tb);
        }
        return $Obj;
    }

    /**
     * TABLEを指定してDELETE句を生成する為のSQL_Deleteを返す
     *
     * @static
     * @param string|null $tb
     * @return SQL_Delete
     */
    public static function newDelete($tb = null)
    {
        $Obj    = new SQL_Delete();
        if (!empty($tb)) {
            $Obj->setTable($tb);
        }
        return $Obj;
    }

    public static function delete($tb, $w = null, $dsn = null)
    {
        $Obj    = SQL::newDelete($tb);
        if (!empty($w)) {
            $Obj->setWhere($w);
        }
        return $Obj->get($dsn);
    }

    public static function showTable($tb = null)
    {
        $obj = new SQL_ShowTable();
        if (!empty($tb)) {
            $obj->setTable($tb);
        }
        return $obj;
    }
}
