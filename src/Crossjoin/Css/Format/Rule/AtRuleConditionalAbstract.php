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
            throw new \InvalidArgumentException(
                "Invalid rule instance. Instance of 'RuleGroupableInterface' expected, " .
                "because only nested statements can be added to conditional group rules"
            );
        }

        return $this;
    }
}