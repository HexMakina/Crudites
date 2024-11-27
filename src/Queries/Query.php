<?php

namespace HexMakina\Crudites\Queries;

use HexMakina\BlackBox\Database\QueryInterface;

use HexMakina\Crudites\CruditesException;
use HexMakina\Crudites\Queries\Clauses\Join;

abstract class Query implements QueryInterface
{
    protected array $bindings = [];
    protected array $binding_names = [];

    protected string $table;
    protected $table_alias = null;

    protected array $joined_tables = [];
    protected $clauses;

    //------------------------------------------------------------  DEBUG
    public function __debugInfo(): array
    {
        $dbg = get_object_vars($this);

        foreach (array_keys($dbg) as $k) {
            if (!isset($dbg[$k])) {
                unset($dbg[$k]);
            }
        }

        $dbg['statement()'] = $this->statement();

        
        if (empty($this->bindings)) {
            unset($dbg['bindings']);
        }
        else{
            $dbg['bindings'] = json_encode($dbg['bindings']);
        }

        return $dbg;
    }

    public function __toString()
    {
        return $this->statement();
    }


    public function join(Join $join): self
    {
        if (isset($this->joined_tables[$join->alias()]) && $this->joined_tables[$join->alias()] !== $join->table()) {
            throw new CruditesException(sprintf(__FUNCTION__ . '(): ALIAS `%s` ALREADY ALLOCATED FOR TABLE  `%s`', $join->alias(), $join->table()));
        }

        $this->joined_tables[$join->alias()] = $join->table();

        $this->addClause('join', $join);

        return $this;
    }
    
    public function table(string $table = null): string
    {
        return $table === null ? $this->table : ($this->table = $table);
    }

    public function addClause(string $clause, $argument): self
    {
        if (!is_array($argument))
            $argument = [$argument];

        $this->clauses[$clause] ??= [];
        $this->clauses[$clause] = array_unique(array_merge($this->clauses[$clause], $argument), SORT_REGULAR);

        return $this;
    }

    public function setClause($clause, $argument = null): self
    {
        if ($argument === null) {
            unset($this->clauses[$clause]);
        } else {
            $this->clauses[$clause] = [];
            $this->addClause($clause, $argument);
        }

        return $this;
    }

    public function clause($clause): array
    {
        return $this->clauses[$clause] ?? [];
    }

    //------------------------------------------------------------  PREP::FIELDS
    public function tableLabel(string $force = null)
    {
        return $force ?? $this->tableAlias();
    }

    public function tableAlias($setter = null): string
    {
        if ($setter !== null) {
            $this->table_alias = $setter;
        }

        return $this->table_alias ?? $this->table;
    }

    public function backTick($field_name, $table_name = null): string
    {
        return sprintf('`%s`.`%s`', $this->tableLabel($table_name), $field_name);
    }

 

    public function setBindings($dat_ass): void
    {
        $this->bindings = $dat_ass;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function getBindingNames(): array
    {
        return $this->binding_names;
    }

    public function addBinding($field, $value, $table_name = null, $bind_label = null): string
    {
        $table_label = $this->tableLabel($table_name);
        $bind_label ??= $this->bindLabel($field, $table_name);

        $this->binding_names[$table_label] ??= [];
        $this->binding_names[$table_label][$field] = $bind_label;
        $this->bindings[$bind_label] = $value;

        return $bind_label;
    }

    public function bindLabel($field, $table_name = null): string
    {
        return ':' . $this->tableLabel($table_name) . '_' . $field;
    }

    public function addBindings($assoc_data): array
    {
        $ret = [];
        foreach ($assoc_data as $column_name => $value) {
            $ret[$column_name] = $this->addBinding($column_name, $value, $this->table);
        }
        return $ret;
    }

    public function compare($query)
    {
        if ($this->statement() !== $query->statement()) {
            return 'statement';
        }

        if (!empty(array_diff($this->getBindings(), $query->getBindings()))) {
            return 'bindings';
        }
        if (!empty(array_diff($query->getBindings(), $this->getBindings()))) {
            return 'bindings';
        }

        return true;
    }

}
