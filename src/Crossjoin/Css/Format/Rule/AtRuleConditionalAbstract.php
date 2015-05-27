<?php
namespace Crossjoin\Css\Format\Rule;

abstract class AtRuleConditionalAbstract
extends AtRuleAbstract
implements HasRulesInterface, RuleGroupableInterface
{
    use TraitRules;

    public function addRule(RuleAbstract $rule)
    {
        // Check for allowed instances
        if ($rule instanceof RuleGroupableInterface) {
            $this->rules[] = $rule;
        } else {
            throw new \Exception("Only nested statements can be added to conditional group rules.");
        }

        return $this;
    }
}