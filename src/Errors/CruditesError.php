<?php
namespace HexMakina\Crudites\Errors;

class CruditesError {

    protected string $table;
    protected array $columns = [];
    protected string $constraint;
    protected string $message;
    protected int $sqlErrorCode;

    public function __construct(string $message) {
        $this->message = $message;
    }

    public function __toString(){
        $ret = $this->message;
        
        if(!empty($this->table))
            $ret .= sprintf(' on table "%s"', $this->table);
        if(!empty($this->columns))
            $ret .= sprintf(' on column(s) "%s"', implode('", "', $this->columns));

        return $ret;
    }

    public function setTable(string $table): void {
        $this->table = $table;
    }

    public function getTable(): string {
        return $this->table;
    }

    public function setColumns(array $columns): void {
        $this->columns = $columns;
    }

    public function getColumns(): array {
        return $this->columns;
    }

    public function setConstraint(string $constraint): void {
        $this->constraint = $constraint;
    }

    public function getConstraint(): string {
        return $this->constraint;
    }

    public function setMessage(string $message): void {
        $this->message = $message;
    }

    public function getUserMessage(): string {
        return $this->message;
    }

    public function setSqlErrorCode(int $sqlErrorCode): void {
        $this->sqlErrorCode = $sqlErrorCode;
    }

    public function getSqlErrorCode(): int {
        return $this->sqlErrorCode;
    }
}
