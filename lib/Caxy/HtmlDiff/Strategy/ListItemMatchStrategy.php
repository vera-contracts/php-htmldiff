<?php

namespace Caxy\HtmlDiff\Strategy;

use Caxy\HtmlDiff\Preprocessor;
use Caxy\HtmlDiff\Util\MbStringUtil;

class ListItemMatchStrategy implements MatchStrategyInterface
{
    /**
     * @var MbStringUtil
     */
    protected $stringUtil;

    /**
     * @var float
     */
    protected $lengthRatioThreshold;

    /**
     * @var float
     */
    protected $commonTextRatioThreshold;

    /**
     * ListItemMatchStrategy constructor.
     *
     * @param MbStringUtil $stringUtil
     * @param float        $lengthRatioThreshold
     * @param float        $commonTextRatioThreshold
     */
    public function __construct($stringUtil, $lengthRatioThreshold = 0.1, $commonTextRatioThreshold = 0.6)
    {
        $this->stringUtil = $stringUtil;
        $this->lengthRatioThreshold = $lengthRatioThreshold;
        $this->commonTextRatioThreshold = $commonTextRatioThreshold;
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return bool
     */
    public function isMatch($a, $b)
    {
        // Short strings match better with a threshold of 100, while longer strings match better with a threshold of 60
        // See ADR #7 for more information
        $strlenA = $this->stringUtil->strlen($a);
        $strlenB = $this->stringUtil->strlen($b);
        $localSimilarityThreshold = $strlenA > 70 && $strlenB > 70
            ? 60
            : 100;

        $percentage = null;

        // Strip tags and check similarity
        $aStripped = strip_tags($a);
        $bStripped = strip_tags($b);
        similar_text($aStripped, $bStripped, $percentage);

        if ($percentage >= $localSimilarityThreshold) {
            return true;
        }

        // Check w/o stripped tags
        similar_text($a, $b, $percentage);
        if ($percentage >= $localSimilarityThreshold) {
            return true;
        }

        // Check common prefix/ suffix length
        $aCleaned = trim($aStripped);
        $bCleaned = trim($bStripped);
        if ($this->stringUtil->strlen($aCleaned) === 0 || $this->stringUtil->strlen($bCleaned) === 0) {
            $aCleaned = $a;
            $bCleaned = $b;
        }
        if ($this->stringUtil->strlen($aCleaned) === 0 || $this->stringUtil->strlen($bCleaned) === 0) {
            return false;
        }
        $prefixIndex = Preprocessor::diffCommonPrefix($aCleaned, $bCleaned, $this->stringUtil);
        $suffixIndex = Preprocessor::diffCommonSuffix($aCleaned, $bCleaned, $this->stringUtil);

        // Use shorter string, and see how much of it is leftover
        $len = min($this->stringUtil->strlen($aCleaned), $this->stringUtil->strlen($bCleaned));
        $remaining = $len - ($prefixIndex + $suffixIndex);
        $strLengthPercent = $len / max($this->stringUtil->strlen($a), $this->stringUtil->strlen($b));

        if ($remaining === 0 && $strLengthPercent > $this->lengthRatioThreshold) {
            return true;
        }

        $percentCommon = ($prefixIndex + $suffixIndex) / $len;

        if ($strLengthPercent > 0.1 && $percentCommon > $this->commonTextRatioThreshold) {
            return true;
        }

        return false;
    }
}
