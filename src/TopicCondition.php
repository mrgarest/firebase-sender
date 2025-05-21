<?php

namespace MrGarest\FirebaseSender;

use MrGarest\FirebaseSender\Exceptions as Ex;

class TopicCondition
{
    protected array $parts = [];
    protected ?string $pendingOperator = null;

    public static function make(): static
    {
        return new static();
    }

    /**
     * Adds a topic condition
     *
     * @param string $name The name of the topic
     */
    public function topic(string $name): static
    {
        $condition = "'{$name}' in topics";

        $this->parts[] = [
            'type' => 'condition',
            'operator' => $this->pendingOperator,
            'value' => $condition,
        ];

        $this->pendingOperator = null;
        return $this;
    }

    /**
     * Sets the pending operator to logical AND (&&)
     */
    public function and(): static
    {
        $this->pendingOperator = '&&';
        return $this;
    }

    /**
     * Sets the pending operator to logical OR (||)
     */
    public function or(): static
    {
        $this->pendingOperator = '||';
        return $this;
    }

    /**
     * Adds a grouped sub-condition. The callback receives a new TopicCondition instance.
     *
     * @param callable $callback A function that defines the grouped condition
     */
    public function group(callable $callback): static
    {
        $builder = new static();
        $callback($builder);

        $this->parts[] = [
            'type' => 'group',
            'operator' => $this->pendingOperator,
            'builder' => $builder,
        ];

        $this->pendingOperator = null;
        return $this;
    }

    /**
     * Converts the built condition tree to a string expression suitable for FCM conditions.
     *
     * @throws \InvalidArgumentException if less than two topic conditions are defined
     * @throws Ex\MissingTopicConditionOperatorException if the condition operator is missing
     */
    public function toCondition(): string
    {
        $count = $this->countConditions();

        if ($count < 2) {
            throw new \InvalidArgumentException("Condition must contain at least two topics, {$count} given.");
        }

        foreach ($this->parts as $index => $part) {
            if ($index > 0 && is_null($part['operator'])) {
                throw new Ex\MissingTopicConditionOperatorException();
            }
            if ($part['type'] === 'group') {
                $part['builder']->validateOperators();
            }
        }

        $expression = '';
        foreach ($this->parts as $index => $part) {
            $prefix = ($index === 0 || is_null($part['operator'])) ? '' : " {$part['operator']} ";

            if ($part['type'] === 'condition') {
                $expression .= $prefix . $part['value'];
            } elseif ($part['type'] === 'group') {
                $expression .= $prefix . '(' . $part['builder']->toCondition() . ')';
            }
        }
        return $expression;
    }

    /**
     * Recursively counts how many topic() conditions are defined
     */
    protected function countConditions(): int
    {
        $count = 0;

        foreach ($this->parts as $part) {
            if ($part['type'] === 'condition') {
                $count++;
            } elseif ($part['type'] === 'group') {
                $count += $part['builder']->countConditions();
            }
        }

        return $count;
    }

    /**
     * Validates that all parts except the first have an operator set.
     *
     * @throws \LogicException if a part (except first) has missing operator
     */
    protected function validateOperators(): void
    {
        foreach ($this->parts as $index => $part) {
            if ($index > 0 && is_null($part['operator'])) {
                throw new \LogicException("Missing logical operator between conditions or groups at part index {$index}.");
            }
            if ($part['type'] === 'group') {
                // Recursively validate nested groups
                $part['builder']->validateOperators();
            }
        }
    }


    public function __toString(): string
    {
        return $this->toCondition();
    }
}
