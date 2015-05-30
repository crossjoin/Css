<?php
namespace Crossjoin\Css\Writer;

use Crossjoin\Css\Format\Rule\AtCharset\CharsetRule;
use Crossjoin\Css\Format\Rule\AtDocument\DocumentRule;
use Crossjoin\Css\Format\Rule\AtFontFace\FontFaceDeclaration;
use Crossjoin\Css\Format\Rule\AtFontFace\FontFaceRule;
use Crossjoin\Css\Format\Rule\AtImport\ImportRule;
use Crossjoin\Css\Format\Rule\AtKeyframes\KeyframesDeclaration;
use Crossjoin\Css\Format\Rule\AtKeyframes\KeyframesRule;
use Crossjoin\Css\Format\Rule\AtKeyframes\KeyframesRuleSet;
use Crossjoin\Css\Format\Rule\AtMedia\MediaQuery;
use Crossjoin\Css\Format\Rule\AtMedia\MediaRule;
use Crossjoin\Css\Format\Rule\AtNamespace\NamespaceRule;
use Crossjoin\Css\Format\Rule\AtPage\PageDeclaration;
use Crossjoin\Css\Format\Rule\AtPage\PageRule;
use Crossjoin\Css\Format\Rule\AtPage\PageSelector;
use Crossjoin\Css\Format\Rule\AtSupports\SupportsRule;
use Crossjoin\Css\Format\Rule\RuleAbstract;
use Crossjoin\Css\Format\Rule\Style\StyleDeclaration;
use Crossjoin\Css\Format\Rule\Style\StyleRuleSet;
use Crossjoin\Css\Format\StyleSheet\StyleSheet;
use Crossjoin\Css\Format\StyleSheet\TraitStyleSheet;
use Crossjoin\Css\Helper\Url;

abstract class WriterAbstract
{
    use TraitStyleSheet;

    /**
     * @param StyleSheet $styleSheet
     */
    public function __construct(StyleSheet $styleSheet)
    {
        $this->setStyleSheet($styleSheet);
    }

    /**
     * Gets the generated CSS content.
     *
     * @return string
     */
    public function getContent()
    {
        $rules = $this->getStyleSheet()->getRules();

        return $this->getRulesContent($rules);
    }

    /**
     * Gets the options for the CSS generation.
     *
     * @param int $level
     * @return array
     */
    abstract protected function getOptions($level);

    /**
     * Generates the content for the given rules, depending on the set format options.
     *
     * @param RuleAbstract[] $rules
     * @param int $level
     * @return string
     */
    protected function getRulesContent(array $rules, $level = 0)
    {
        // Get format options
        $options = $this->getOptions($level);

        $content = "";
        foreach ($rules as $rule) {
            if ($options["BaseAddComments"] === true) {
                if (!($rule instanceof CharsetRule)) {
                    $comments = $rule->getComments();
                    foreach ($comments as $comment) {
                        $content .= $options["CommentIntend"] . $comment . $options["CommentLineBreak"];
                    }
                }
            }

            // Skip invalid rules
            if ($rule->getIsValid() === false) {
                continue;
            }

            if ($rule instanceof CharsetRule) {
                // @charset must be at the beginning of the content
                if ($content === "") {
                    $content .= "@charset \"" . $rule->getValue() . "\";" . $options["CharsetLineBreak"];
                }
            } elseif ($rule instanceof ImportRule) {
                $content .= $options["BaseIntend"];
                $content .= "@import ";
                $content .= "url(\"" . Url::escapeUrl($rule->getUrl()) . "\")";

                $mediaQueryConcat = "";
                foreach ($rule->getQueries() as $mediaQuery) {
                    $content .= $mediaQueryConcat;
                    if ($mediaQuery->getIsOnly() === true) {
                        $content .= " only";
                    } else if ($mediaQuery->getIsNot() === true) {
                        $content .= " not";
                    }
                    // Filter default type "all"
                    if ($mediaQuery->getType() !== MediaQuery::TYPE_ALL) {
                        $content .= " " . $mediaQuery->getType();
                    }
                    $conditions = $mediaQuery->getConditions();
                    if (count($conditions) > 0) {
                        if ($mediaQuery->getType() !== MediaQuery::TYPE_ALL) {
                            $content .= " and";
                        }
                        $content .= " (";
                        $mediaConditionConcat = "";
                        foreach ($conditions as $condition) {
                            if ($condition->getIsValid() === true) {
                                $content .= $mediaConditionConcat . $condition->getValue();
                                $mediaConditionConcat = " and ";
                            }
                        }
                        $content .= ")";
                    }
                    $mediaQueryConcat = ",";
                }
                $content .= ";" . $options["ImportLineBreak"];
            } elseif ($rule instanceof NamespaceRule) {
                $content .= $options["BaseIntend"];
                $content .= "@namespace";
                $prefix = $rule->getPrefix();
                if (!empty($prefix)) {
                    $content .= " " . $prefix;
                }
                $content .= " url(\"" . Url::escapeUrl($rule->getName()) . "\")";
                $content .= ";" . $options["NamespaceLineBreak"];
            } elseif ($rule instanceof DocumentRule) {
                // Prepare rule start content
                $ruleStartContent = $options["BaseIntend"];
                $ruleStartContent .= "@document";

                // Prepare rule filter content
                $concat = " ";
                $url = $rule->getUrl();
                if (!empty($url)) {
                    $ruleStartContent .= $concat . "url($url)";
                    $concat = $options["DocumentFilterSeparator"];
                }
                $urlPrefix = $rule->getUrlPrefix();
                if (!empty($urlPrefix)) {
                    $ruleStartContent .= $concat . "url-prefix($urlPrefix)";
                    $concat = $options["DocumentFilterSeparator"];
                }
                $domain = $rule->getDomain();
                if (!empty($domain)) {
                    $ruleStartContent .= $concat . "domain($domain)";
                    $concat = $options["DocumentFilterSeparator"];
                }
                $regexp = $rule->getRegexp();
                if (!empty($regexp)) {
                    $ruleStartContent .= $concat . "regexp($regexp)";
                }
                $ruleStartContent .= $options["DocumentRuleSetOpen"];

                // Prepare rule content
                $ruleRuleContent = $this->getRulesContent($rule->getRules(), $level + 1);

                // Only add the content if valid rules set
                if ($ruleRuleContent !== "") {
                    $content .= $ruleStartContent;
                    $content .= $ruleRuleContent;
                    $content .= $options["DocumentRuleSetClose"];
                }
            } elseif ($rule instanceof FontFaceRule) {
                // Prepare rule start content
                $ruleStartContent = $options["BaseIntend"];
                $ruleStartContent .= "@font-face";
                $ruleStartContent .= $options["FontFaceRuleSetOpen"];

                // Prepare rule declarations content
                /** @var FontFaceDeclaration[] $declarations */
                $declarations = $rule->getDeclarations();
                for ($i = 0, $j = count($declarations); $i < $j; $i++) {
                    if ($declarations[$i]->getIsValid() === true) {
                        if ($ruleStartContent !== "") {
                            $content .= $ruleStartContent;
                            $ruleStartContent = "";
                        }
                        if ($options["BaseAddComments"] === true) {
                            $comments = $declarations[$i]->getComments();
                            foreach ($comments as $comment) {
                                $content .= $options["FontFaceRuleSetIntend"] . $comment . $options["FontFaceCommentLineBreak"];
                            }
                        }
                        $content .= $options["FontFaceDeclarationIntend"] . $declarations[$i]->getProperty();
                        $content .= $options["FontFaceDeclarationSeparator"] . $declarations[$i]->getValue();
                        if ($options["BaseLastDeclarationSemicolon"] === true || $i < ($j - 1)) {
                            $content .= ";";
                        }
                        $content .= $options["FontFaceDeclarationLineBreak"];
                    }
                }

                // Only add the content if valid declarations set
                if ($ruleStartContent === "") {
                    $content .= $options["FontFaceRuleSetClose"];
                }
            } elseif ($rule instanceof KeyframesRule) {
                // Prepare rule content
                $ruleStartContent = $options["BaseIntend"];
                $ruleStartContent .= "@keyframes " ;
                $ruleStartContent .= $rule->getIdentifier();
                $ruleStartContent .= $options["KeyframesRuleSetOpen"];
                $ruleRulesContent = $this->getRulesContent($rule->getRules(), $level + 1);

                // Only add the content if valid rules set
                if ($ruleRulesContent !== "") {
                    $content .= $ruleStartContent;
                    $content .= $ruleRulesContent;
                    $content .= $options["KeyframesRuleSetClose"];
                }
            } elseif ($rule instanceof MediaRule) {
                // Prepare rule content
                $ruleStartContent = $options["BaseIntend"];
                $ruleStartContent .= "@media";
                $mediaQueryConcat = " ";
                foreach ($rule->getQueries() as $mediaQuery) {
                    $ruleStartContent .= $mediaQueryConcat;
                    if ($mediaQuery->getIsOnly() === true) {
                        $ruleStartContent .= "only ";
                    } elseif ($mediaQuery->getIsNot() === true) {
                        $ruleStartContent .= "not ";
                    }

                    if ($mediaQuery->getType() !== MediaQuery::TYPE_ALL) {
                        $ruleStartContent .= $mediaQuery->getType();
                    }
                    $conditions = $mediaQuery->getConditions();
                    if (count($conditions) > 0) {
                        if ($mediaQuery->getType() !== MediaQuery::TYPE_ALL) {
                            $ruleStartContent .= " and ";
                        }
                        $ruleStartContent .= "(";
                        $mediaConditionConcat = "";
                        foreach ($conditions as $condition) {
                            if ($condition->getIsValid() === true) {
                                $ruleStartContent .= $mediaConditionConcat . $condition->getValue();
                                $mediaConditionConcat = ") and (";
                            }
                        }
                        $ruleStartContent .= ")";
                    }
                    $mediaQueryConcat = $options["MediaQuerySeparator"];
                }
                $ruleStartContent .= $options["MediaRuleSetOpen"];
                $ruleRulesContent = $this->getRulesContent($rule->getRules(), $level + 1);

                // Only add the content if valid rules set
                if ($ruleRulesContent !== "") {
                    $content .= $ruleStartContent;
                    $content .= $ruleRulesContent;
                    $content .= $options["MediaRuleSetClose"];
                }
            } elseif ($rule instanceof PageRule) {
                $content .= $options["BaseIntend"];
                $content .= "@page";

                $selector = $rule->getSelector()->getValue();
                if ($selector !== PageSelector::SELECTOR_ALL) {
                    $content .= " " . $selector;
                }
                $content .= $options["PageRuleSetOpen"];

                /** @var PageDeclaration[] $declarations */
                $declarations = $rule->getDeclarations();
                for ($i = 0, $j = count($declarations); $i < $j; $i++) {
                    if ($declarations[$i]->getIsValid() === true) {
                        if ($options["BaseAddComments"] === true) {
                            $comments = $declarations[$i]->getComments();
                            foreach ($comments as $comment) {
                                $content .= $options["PageDeclarationIntend"] . $comment . $options["PageCommentLineBreak"];
                            }
                        }
                        $content .= $options["PageDeclarationIntend"] . $declarations[$i]->getProperty();
                        $content .= $options["PageDeclarationSeparator"] . $declarations[$i]->getValue();
                        if ($options["BaseLastDeclarationSemicolon"] === true || $i < ($j - 1)) {
                            $content .= ";";
                        }
                        $content .= $options["PageDeclarationLineBreak"];
                    }
                }

                $content .= $options["PageRuleSetClose"];
            } elseif ($rule instanceof SupportsRule) {
                // Prepare rule content
                $ruleStartContent = $options["BaseIntend"];
                $ruleStartContent .= "@supports ";
                $conditions = $rule->getConditions();
                if (count($conditions) > 0) {
                    $ruleStartContent .= "(";
                    $supportConditionConcat = "";
                    foreach ($conditions as $condition) {
                        if ($condition->getIsValid() === true) {
                            $ruleStartContent .= $supportConditionConcat . $condition->getValue();
                            $supportConditionConcat = ") and (";
                        }
                    }
                    $ruleStartContent .= ")";
                }
                $ruleStartContent .= $options["SupportsRuleSetOpen"];
                $ruleRulesContent = $this->getRulesContent($rule->getRules(), $level + 1);

                // Only add the content if valid rules set
                if ($ruleRulesContent !== "") {
                    $content .= $ruleStartContent;
                    $content .= $ruleRulesContent;
                    $content .= $options["SupportsRuleSetClose"];
                }
            } elseif ($rule instanceof StyleRuleSet) {
                // Prepare rule content
                $ruleStartContent = $options["BaseIntend"];
                $ruleSelectorContent = "";
                $concat = "";
                foreach ($rule->getSelectors() as $selector) {
                    if ($selector->getIsValid() === true) {
                        $ruleSelectorContent .= $concat . $selector->getValue();
                        $concat = $options["StyleSelectorSeparator"];
                    }
                }

                // Only add the content if valid selectors set
                if ($ruleSelectorContent !== "") {
                    $ruleStartContent .= $ruleSelectorContent;
                    $ruleStartContent .= $options["StyleDeclarationsOpen"];

                    // Prepare rule declaration content
                    /** @var StyleDeclaration[] $declarations */
                    $ruleDeclarationContent = "";
                    $declarations = $rule->getDeclarations();
                    for ($i = 0, $j = count($declarations); $i < $j; $i++) {
                        if ($declarations[$i]->getIsValid() === true) {
                            if ($options["BaseAddComments"] === true) {
                                $comments = $declarations[$i]->getComments();
                                foreach ($comments as $comment) {
                                    $ruleDeclarationContent .= $options["StyleDeclarationIntend"] . $comment . $options["StyleCommentLineBreak"];
                                }
                            }
                            $important = $declarations[$i]->getIsImportant() ? " !important" : "";
                            $ruleDeclarationContent .= $options["StyleDeclarationIntend"] . $declarations[$i]->getProperty();
                            $ruleDeclarationContent .= $options["StyleDeclarationSeparator"] . $declarations[$i]->getValue() . $important;
                            if ($options["BaseLastDeclarationSemicolon"] === true || $i < ($j - 1)) {
                                $ruleDeclarationContent .= ";";
                            }
                            $ruleDeclarationContent .= $options["StyleDeclarationLineBreak"];
                        }
                    }

                    // Only add the content if valid declarations set
                    if ($ruleDeclarationContent !== "") {
                        $content .= $ruleStartContent;
                        $content .= $ruleDeclarationContent;
                        $content .= $options["StyleDeclarationsClose"];
                    }
                }
            } elseif ($rule instanceof KeyframesRuleSet) {
                // Prepare rule content
                $ruleStartContent = $options["BaseIntend"];
                $ruleSelectorContent = "";
                $concat = "";
                foreach ($rule->getKeyframes() as $keyframe) {
                    if ($keyframe->getIsValid() === true) {
                        $ruleSelectorContent .= $concat . $keyframe->getValue();
                        $concat = $options["KeyframesSelectorSeparator"];
                    }
                }

                // Only add the content if valid selectors set
                if ($ruleSelectorContent !== "") {
                    $ruleStartContent .= $ruleSelectorContent;
                    $ruleStartContent .= $options["KeyframesDeclarationsOpen"];

                    /** @var KeyframesDeclaration[] $declarations */
                    $ruleDeclarationContent = "";
                    $declarations = $rule->getDeclarations();
                    for ($i = 0, $j = count($declarations); $i < $j; $i++) {
                        if ($declarations[$i]->getIsValid() === true) {
                            $ruleDeclarationContent .= $options["KeyframesDeclarationIntend"] . $declarations[$i]->getProperty();
                            $ruleDeclarationContent .= $options["KeyframesDeclarationSeparator"] . $declarations[$i]->getValue();
                            if ($options["BaseLastDeclarationSemicolon"] === true || $i < ($j - 1)) {
                                $ruleDeclarationContent .= ";";
                            }
                            $ruleDeclarationContent .= $options["KeyframesDeclarationLineBreak"];
                        }
                    }

                    // Only add the content if valid declarations set
                    if ($ruleDeclarationContent !== "") {
                        $content .= $ruleStartContent;
                        $content .= $ruleDeclarationContent;
                        $content .= $options["KeyframesDeclarationsClose"];
                    }
                }
            }
        }

        return $content;
    }
}