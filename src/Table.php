<?php

declare(strict_types=1);

namespace helpers\html;

use RuntimeException;

class Table
{
    use ElementTrait;

    protected array $columns = [];
    protected array $rows = [];
    protected array $operations = [];

    public function __construct(array $columns = [], array $rows = [])
    {
        $this->setColumns($columns);
        $this->setRows($rows);
    }

    public function addColumn(TableColumn $column): Table
    {
        $this->columns[$column->getName()] = $column;
        return $this;
    }
    public function hasColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }
    public function getColumn(string $name): TableColumn
    {
        if (!isset($this->columns[$name])) {
            throw new RuntimeException("Invalid column name");
        }
        return $this->columns[$name];
    }
    public function removeColumn(string $name): Table
    {
        unset($this->columns[$name]);
        return $this;
    }
    public function getColumns(): array
    {
        return $this->columns;
    }
    public function setColumns(array $columns): Table
    {
        $this->columns = [];
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
        return $this;
    }
    public function addRow(TableRow $row): Table
    {
        $this->rows[] = $row;
        return $this;
    }
    public function getRows(): array
    {
        return $this->rows;
    }
    public function setRows(array $rows): Table
    {
        $this->rows = [];
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }
    public function getOperations(bool $includeHidden = false): array
    {
        if ($includeHidden) {
            return $this->operations;
        }
        return array_filter($this->operations, function ($v) {
            return !$v->isHidden();
        });
    }
    public function setOperations(array $operations): Table
    {
        $this->operations = [];
        foreach ($operations as $operation) {
            $this->addOperation($operation);
        }
        return $this;
    }
    public function addOperation(Button $operation): Table
    {
        $this->operations[$operation->getName()] = $operation;
        return $this;
    }
    public function removeOperation(string $name): Table
    {
        unset($this->operations[$name]);
        return $this;
    }
    public function getOperation(string $name): Button
    {
        return $this->operations[$name];
    }
    public function hasOperation(string $name, bool $includeHidden = false): bool
    {
        return isset($this->operations[$name]) && ($includeHidden || !$this->operations[$name]->isHidden());
    }
    public function setOrder(array $order): self
    {
        $temp = [];
        foreach ($order as $column) {
            if (isset($this->columns[$column])) {
                $temp[$column] = $this->columns[$column];
            }
        }
        $this->columns = $temp;
        return $this;
    }
    public function getOrder(): array
    {
        return array_keys($this->columns);
    }
}
