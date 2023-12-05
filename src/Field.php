<?php

declare(strict_types=1);

namespace helpers\html;

class Field
{
    use ElementTrait;

    protected ?Form $form = null;
    protected array $options = [];

    public function __construct(string $type = "text", array $attr = [], array $options = [])
    {
        $this->attr = $attr;
        $this->attr['type'] = $type;
        $this->options = $options;
    }

    public function getType(string $default = 'text'): string
    {
        return $this->getAttr('type', $default);
    }
    public function setType(string $type): self
    {
        return $this->setAttr('type', $type);
    }

    public function getName(string $default = ''): string
    {
        return $this->getAttr('name', $default);
    }
    public function setName(string $value): self
    {
        return $this->setAttr('name', $value);
    }
    /**
     * @param mixed $default
     * @return mixed
     */
    public function getValue(mixed $default = null): mixed
    {
        return $this->getAttr('value', $default);
    }
    /**
     * @param mixed $value
     * @return self
     */
    public function setValue(mixed $value): self
    {
        return $this->setAttr('value', $value);
    }

    public function enable(): self
    {
        if ($this->hasAttr('readonly')) {
            $this->delAttr('readonly');
        }
        if ($this->hasAttr('disabled')) {
            $this->delAttr('disabled');
        }
        return $this;
    }
    public function disable(): self
    {
        if (in_array($this->getType(), ['select', 'multipleselect', 'tags'])) {
            return $this->setAttr('disabled', 'disabled');
        }
        return $this->setAttr('readonly', 'readonly');
    }

    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }
    /**
     * @return mixed
     */
    public function getOption(string $key, mixed $default = null)
    {
        $opt = $this->options[$key] ?? $default;
        if ($opt instanceof \Closure) {
            $opt = call_user_func($opt, $this);
        }
        return $opt;
    }
    public function getOptions(): array
    {
        $opts = [];
        foreach (array_keys($this->options) as $k) {
            $opts[$k] = $this->getOption((string)$k);
        }
        return $opts;
    }
    public function setOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }
    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }
    public function delOption(string $option): self
    {
        unset($this->options[$option]);
        return $this;
    }
    public function delOptions(): self
    {
        $this->options = [];
        return $this;
    }
    public function setForm(Form $form = null): self
    {
        $this->form = $form;
        return $this;
    }
    public function getForm(): ?Form
    {
        return $this->form;
    }
    public function getLayout(bool $createDefault = false): ?FormLayout
    {
        return $this->getForm()?->getLayout($createDefault);
    }
    public function getRow(bool $createDefault = false): ?FormLayoutRow
    {
        $layout = $this->getLayout($createDefault);
        if (!$layout) {
            return null;
        }
        foreach ($layout->getRows() as $row) {
            if ($row->hasField($this)) {
                return $row;
            }
        }
        return null;
    }
    public function getWidth(): ?int
    {
        return $this->getRow()?->getFieldWidth($this->getName());
    }
    public function setWidth(int $width): self
    {
        $this->getRow()?->setFieldWidth($this, $width);
        return $this;
    }
    public function index(): ?int
    {
        return $this->getRow()?->getFieldIndex($this->getName());
    }

    public function moveBefore(Field $ref): self
    {
        $row = $this->getRow();
        if ($row && $row === $ref->getRow()) {
            $row->moveField($this, (int)$ref->index());
        }
        return $this;
    }
    public function moveAfter(Field $ref): self
    {
        $row = $this->getRow();
        if ($row && $row === $ref->getRow()) {
            $row->moveField($this, $ref->index() + 1);
        }
        return $this;
    }
    public function move(int $position): self
    {
        $row = $this->getRow();
        if ($row) {
            $row->moveField($this, $position);
        }
        return $this;
    }
    public function moveFirst(): self
    {
        $this->move(0);
        return $this;
    }
    public function moveLast(): self
    {
        $row = $this->getRow();
        if ($row) {
            $this->move(count($row->getFields()));
        }
        return $this;
    }
}
