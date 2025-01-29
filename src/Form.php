<?php

declare(strict_types=1);

namespace vakata\html;

use Exception;
use vakata\validation\Validator;

class Form
{
    use ElementTrait;

    protected ?Validator $validator = null;
    protected ?FormLayout $layout = null;
    protected array $context = [];
    /**
     * @var array<int,Field>
     */
    protected array $fields = [];
    /**
     * @var array<string,Field>
     */
    protected array $fieldmap = [];

    public function __clone()
    {
        foreach ($this->fields as $k => $v) {
            $this->fields[$k] = clone $v;
            $this->fieldmap[$this->fields[$k]->getName()] = $this->fields[$k];
            $this->fields[$k]->setForm($this);
        }
    }

    public function refreshFieldMap(): void
    {
        $this->fieldmap = [];
        foreach ($this->fields as $v) {
            $this->fieldmap[$v->getName()] = $v;
        }
    }

    public function addField(Field $field): Form
    {
        $this->fields[] = $field;
        $field->setForm($this);
        $this->fieldmap[$field->getName()] = $field;
        return $this;
    }
    public function removeField(string $name): Form
    {
        foreach ($this->fields as $k => $v) {
            if ($v->getName() === $name) {
                $v->setForm(null);
                unset($this->fields[$k]);
                unset($this->fieldmap[$name]);
            }
        }
        return $this;
    }
    /**
     * @return array<Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
    public function setFields(array $fields): Form
    {
        $this->fields = [];
        $this->fieldmap = [];
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }
    public function hasField(string $name): bool
    {
        if (isset($this->fieldmap[$name])) {
            return true;
        }
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return true;
            }
        }
        return false;
    }
    public function getField(string $name): Field
    {
        if (isset($this->fieldmap[$name])) {
            return $this->fieldmap[$name];
        }
        foreach ($this->fields as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }
        throw new Exception('Field not found');
    }

    public function hasLayout(): bool
    {
        return $this->layout !== null;
    }
    public function getLayout(bool $createDefault = false): ?FormLayout
    {
        if (!$this->hasLayout() && $createDefault) {
            $this->createDefaultLayout();
        }
        return $this->layout;
    }
    public function getLayoutArray(bool $createDefault = false): array
    {
        if (!$this->hasLayout() && $createDefault) {
            $this->createDefaultLayout();
        }
        return $this->layout ? $this->layout->toArray() : [];
    }
    public function setLayout(FormLayout|array $layout = null): self
    {
        $this->layout = is_array($layout) ? FormLayout::fromArray($this, $layout) : $layout;
        return $this;
    }
    public function createDefaultLayout(): self
    {
        $layout = [];
        foreach ($this->getFields() as $field) {
            $layout[] = [ $field->getName() ];
        }
        return $this->setLayout($layout);
    }

    public function enable(): self
    {
        foreach ($this->fields as $field) {
            $field->enable();
        }
        return $this;
    }
    public function disable(): self
    {
        foreach ($this->fields as $field) {
            $field->disable();
        }
        return $this;
    }
    public function populate(mixed $data): self
    {
        foreach ($this->fields as $field) {
            $name = $field->getName();
            if ($name) {
                $name = str_replace(['][', ']'], ['[', ''], $name);
                $name = array_filter(
                    explode('[', $name),
                    function ($v) {
                        return $v === '0' || !empty($v);
                    }
                );
                $temp = $data;
                foreach ($name as $part) {
                    if (is_array($temp) && isset($temp[$part])) {
                        $temp = $temp[$part];
                    } elseif (is_object($temp) && ($temp->{$part} ?? null) !== null) {
                        $temp = $temp->{$part};
                    } else {
                        $temp = null;
                        break;
                    }
                }
                if ($temp !== null) {
                    $field->setValue($temp);
                }
            }
        }
        return $this;
    }
    public function getValidator(): ?Validator
    {
        return $this->validator;
    }
    public function hasValidator(): bool
    {
        return isset($this->validator);
    }
    public function setValidator(Validator $validator): self
    {
        $this->removeValidator();
        $this->validator = $validator;
        $validator = json_decode(json_encode($validator, JSON_THROW_ON_ERROR), true);
        foreach ($validator as $key => $data) {
            if ($this->hasField($key)) {
                $field = $this->getField($key);
                $field->setAttr('data-validate', array_values($data));
            }
            $tmp = explode('.', $key);
            $tmp = implode('', array_map(function ($v, $k) {
                return $k ? '[' . ($v === '*' ? '' : $v) . ']' : $v;
            }, $tmp, array_keys($tmp)));
            if ($this->hasField($tmp)) {
                $field = $this->getField($tmp);
                $field->setAttr('data-validate', array_values($data));
            }
            if (strpos($key, '.*.')) {
                $key = explode('.*.', $key);
                if ($this->hasField($key[0])) {
                    $form = $this->getField($key[0])->getOption('form');
                    if ($form && $form->hasField($key[1])) {
                        $form->getField($key[1])->setAttr('data-validate', array_values($data));
                    }
                    $form = $this->getField($key[0])->getOption('create');
                    if ($form && $form->hasField($key[1])) {
                        $form->getField($key[1])->setAttr('data-validate', array_values($data));
                    }
                }
            }
        }
        return $this;
    }
    public function removeValidator(): self
    {
        foreach ($this->getFields() as $field) {
            $field->delAttr('data-validate');
        }
        $this->validator = null;
        return $this;
    }
    /**
     * @param Validator|null $validator
     * @return Form
     * @throws Exception
     * @deprecated
     */
    public function validate(Validator $validator = null): self
    {
        if (!$validator) {
            $this->removeValidator();
        } else {
            $this->setValidator($validator);
        }
        return $this;
    }
    public function setContext(mixed $key, mixed $value = null): self
    {
        if ($value === null && is_array($key)) {
            $this->context = $key;
        } else {
            $this->context[$key] = $value;
        }
        return $this;
    }
    /**
     * @return mixed
     */
    public function getContext(string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->context : ($this->context[$key] ?? $default);
    }
    public function removeContext(string $key): self
    {
        unset($this->context[$key]);
        return $this;
    }
}
