<?php

namespace HexMakina\Crudites\Queries;

trait Join
{
	protected $selection = [];
	protected $table_alias = null;
	protected $join = [];
	protected $joined_tables = [];
	protected $tables_classes = [];

	protected $group = [];
	protected $having = [];
	protected $order = [];

	protected $limit = null;
	protected $limit_number = null;
	protected $limit_offset = 0;
}
