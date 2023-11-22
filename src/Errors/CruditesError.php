<?php
namespace HexMakina\Crudites\Errors;

class CruditesError 
{
    protected string $message;
    protected string $state;
    protected int $code;

    protected string $table;
    protected array $columns = [];
    protected string $constraint;
    
    public function __construct($message)
    {
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

    public function import($something_PDO): self
    {
        $errorInfo = null;

        if(method_exists($something_PDO, 'errorInfo'))
            $errorInfo = $something_PDO->errorInfo();
        elseif(property_exists($something_PDO, 'errorInfo'))
            $errorInfo = $something_PDO->errorInfo;
        else
            throw new \InvalidArgumentException('INVALID_PDO_CLASS');

        // 0: the SQLSTATE associated with the last operation on the database handle
        $this->setState($errorInfo[0] ?? null);

        // 1: driver-specific error code.
        $this->setCode($errorInfo[1] ?? null);

        // 2: driver-specific error message
        $this->setMessage($errorInfo[2] ?? null);

        return $this;
    }


    public function setState(string $state): void {
        $this->state = $state;
    }

    public function getState(): string {
        return $this->state;
    }

    public function setCode(int $code): void {
        $this->code = $code;
    }

    public function getCode(): int {
        return $this->code;
    }
    

    public function setMessage(string $message): void {
        $this->message = $message;
    }

    public function getMessage(): string {
        return $this->message;
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
}
