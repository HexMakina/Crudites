<?php

namespace HexMakina\Crudites\Interfaces;

interface TableDescriptionInterface
{
    // getters
    public function connection(): ConnectionInterface;
    public function name(): string;

    //introspection
    public function add_column(TableColumnInterface $column);
    public function columns(): array;
    public function column($name);

    public function add_primary_key(TableColumnInterface $column);
    public function primary_keys($with_values = null): array;
    public function primary_keys_match($dat_ass): array;

    public function add_foreign_key(TableColumnInterface $column);
    public function foreign_keys_by_name(): array;
    public function foreign_keys_by_table(): array;
    public function single_foreign_key_to($other_table);

    public function add_unique_key($constraint_name, $columns);
    public function unique_keys_by_name(): array;

    public function auto_incremented_primary_key(TableColumnInterface $setter = null);

    //EOF introspection
}
