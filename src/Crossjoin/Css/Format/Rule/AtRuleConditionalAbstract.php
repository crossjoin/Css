<?php
namespace Crossjoin\Css\Format\Rule;

abstract class AtRuleConditionalAbstract
extends AtRuleAbstract
implements HasRulesInterface, RuleGroupableInterface
{
    use TraitRules;

    /**
     * Adds a rule.
     *
     * @param RuleGroupableInterface $rule
     * @return $this
     * @throws \Exception
     */
    public function addRule(RuleAbstract $rule)
    {
        // Check for allowed instances
        if ($rule instanceof RuleGroupableInterface) {
            $this->rules[] = $rule;
        } else {
            $parentClassName = get_class($this);
            $childClassName = get_class($rule);
            throw new \InvalidArgumentException(
                "Invalid rule instance. Instance of 'RuleGroupableInterface' expected, " .
                "because only nested statements can be added to conditional group rules. " .
                "Tried to add rule of type '$childClassName' to '$parentClassName'."
            );
        }

        return $this;
    }
}