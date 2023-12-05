<?php

declare(strict_types=1);

namespace helpers\html;

class TableRow
{
    use ElementTrait;

    protected mixed $data;
    protected array $class = [];
    protected array $operations = [];

    public function __construct(mixed $data)
    {
        $this->setData($data);
    }
    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
    public function setData(mixed $data): TableRow
    {
        $this->data = $data;
        return $this;
    }
    /**
     * @return mixed
     */
    public function __get(string $k): mixed
    {
        $data = $this->getData();
        return is_object($data) ?
            ($data->{$k} ?? null) :
            (is_array($data) && isset($data[$k]) ? $data[$k] : null);
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
    public function setOperations(array $operations): TableRow
    {
        $this->operations = [];
        foreach ($operations as $operation) {
            $this->addOperation($operation);
        }
        return $this;
    }
    public function addOperation(Button $operation): TableRow
    {
        $this->operations[$operation->getName()] = $operation;
        return $this;
    }
    public function hasOperation(string $name, bool $includeHidden = false): bool
    {
        return isset($this->operations[$name]) && ($includeHidden || !$this->operations[$name]->isHidden());
    }
    public function getOperation(string $name): ?Button
    {
        return $this->operations[$name] ?? null;
    }
    public function removeOperation(string $name): TableRow
    {
        unset($this->operations[$name]);
        return $this;
    }
}
