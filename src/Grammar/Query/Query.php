<?php

namespace HexMakina\Crudites\Grammar\Query;

use HexMakina\BlackBox\Database\{QueryInterface, ClauseInterface};

abstract class Query implements QueryInterface
{
    protected array $bindings = [];
    
    protected string $table;
    protected ?string $alias = null;
    
    
    protected array $clauses = [];
    
    abstract public function statement(): string;
    
    /**
     * Provides debugging information about the object.
     * This method returns an array of object properties and their values,
     * excluding properties that are not set.
     */
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

    public function table(): string
    {
        return $this->table;
    }

    public function alias(): ?string
    {
        return $this->alias;
    }

    public function base(): string
    {
        return $this->alias ?? $this->table;
    }

    public function clause(string $name): ?ClauseInterface
    {
        return $this->clauses[$name] ?? null;
    }

    public function add(ClauseInterface $clause): self
    {
        $this->clauses[$clause->name()] = $clause;
        return $this;
    }

    public function set(ClauseInterface $clause): self
    {
        unset($this->clauses[$clause->name()]);
        return $this->add($clause);
    }

    //------------------------------------------------------------  PREP::FIELDS
    public function tableLabel(string $force = null)
    {
        return $force ?? $this->tableAlias();
    }

    public function tableAlias($setter = null): string
    {
        if ($setter !== null) {
            $this->alias = $setter;
        }

        return $this->alias ?? $this->table;
    }

    public function bindings(): array
    {
        if(empty($this->clauses)){
            return $this->bindings;
        }

        return array_merge($this->bindings, array_reduce($this->clauses, function ($carry, $clause) {
            return array_merge($carry, $clause->bindings());
        }, []));
    }
    
    public function addBinding($field, $value, $table_name = null, $bind_label = null): string
    {
        $bind_label ??= $this->bindLabel($field, $table_name);

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

        if (!empty(array_diff($this->bindings(), $query->bindings()))) {
            return 'bindings';
        }
        if (!empty(array_diff($query->bindings(), $this->bindings()))) {
            return 'bindings';
        }

        return true;
    }
}
