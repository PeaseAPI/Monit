<?php

namespace App\Services\Seo;

/**
 * 评分器：加权得分 + 类别仪表 + 三档 issues 计数
 *
 * 计分：score = Σ(通过测试权重) / Σ(全部测试权重) × 100
 * 分数带：good > 79 / decent 50-79 / poor < 50
 */
class AuditScore
{
    /**
     * @param  array<string, array{passed:bool, importance:string, category:string}>  $results
     * @return array{score:int, category_scores:array<string,int>, major:int, moderate:int, minor:int, passed:int}
     */
    public static function calculate(array $results): array
    {
        $totalWeight = 0;
        $passedWeight = 0;
        $categoryWeight = [];
        $categoryPassed = [];
        $issues = ['major' => 0, 'moderate' => 0, 'minor' => 0];
        $passed = 0;

        foreach ($results as $row) {
            $weight = AuditTestRegistry::weightOf($row['importance'] ?? 'minor');
            $totalWeight += $weight;

            $category = $row['category'] ?? 'misc';
            $categoryWeight[$category] = ($categoryWeight[$category] ?? 0) + $weight;

            if (! empty($row['passed'])) {
                $passedWeight += $weight;
                $categoryPassed[$category] = ($categoryPassed[$category] ?? 0) + $weight;
                $passed++;

                continue;
            }

            $issues[$row['importance']] = ($issues[$row['importance']] ?? 0) + 1;
        }

        $score = $totalWeight > 0 ? (int) round($passedWeight / $totalWeight * 100) : 0;

        $categoryScores = [];
        foreach ($categoryWeight as $category => $weight) {
            $categoryScores[$category] = (int) round(($categoryPassed[$category] ?? 0) / $weight * 100);
        }

        return [
            'score' => $score,
            'category_scores' => $categoryScores,
            'major' => $issues['major'],
            'moderate' => $issues['moderate'],
            'minor' => $issues['minor'],
            'passed' => $passed,
        ];
    }

    public static function band(int $score): string
    {
        return $score > 79 ? 'good' : ($score >= 50 ? 'decent' : 'poor');
    }
}
