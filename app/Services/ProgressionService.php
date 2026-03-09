<?php

namespace App\Services;

use App\Models\PlayerProfile;

class ProgressionService
{
    /**
     * @return array{xp_awarded:int,total_xp:int,level:int,next_level_xp:int,level_progress_percent:int}
     */
    public function award(PlayerProfile $profile, int $baseXp, string $difficulty = 'normal'): array
    {
        $tierMultiplier = match ($profile->experience_tier) {
            'beginner' => 1.25,
            'intermediate' => 1.0,
            'expert' => 0.9,
            default => 1.0,
        };

        $difficultyMultiplier = match ($difficulty) {
            'easy' => 1.0,
            'normal' => 1.1,
            'hard' => 1.35,
            default => 1.0,
        };

        $xpAwarded = (int) max(1, round($baseXp * $tierMultiplier * $difficultyMultiplier));
        $profile->xp += $xpAwarded;
        $profile->level = $this->levelFromXp($profile->xp);
        $profile->save();

        $nextLevelXp = $this->xpForLevel($profile->level + 1);
        $currentLevelFloor = $this->xpForLevel($profile->level);
        $progress = $nextLevelXp > $currentLevelFloor
            ? (int) floor((($profile->xp - $currentLevelFloor) / ($nextLevelXp - $currentLevelFloor)) * 100)
            : 100;

        return [
            'xp_awarded' => $xpAwarded,
            'total_xp' => (int) $profile->xp,
            'level' => (int) $profile->level,
            'next_level_xp' => $nextLevelXp,
            'level_progress_percent' => max(0, min(100, $progress)),
        ];
    }

    public function levelFromXp(int $xp): int
    {
        $level = 1;
        while ($xp >= $this->xpForLevel($level + 1) && $level < 100) {
            $level++;
        }

        return $level;
    }

    public function xpForLevel(int $level): int
    {
        $level = max(1, $level);
        return (int) floor(80 * (($level - 1) ** 1.55));
    }

    /**
     * @return array<string, int|string>
     */
    public function profilePayload(PlayerProfile $profile): array
    {
        $nextLevelXp = $this->xpForLevel($profile->level + 1);
        $currentLevelFloor = $this->xpForLevel($profile->level);
        $progress = $nextLevelXp > $currentLevelFloor
            ? (int) floor((($profile->xp - $currentLevelFloor) / ($nextLevelXp - $currentLevelFloor)) * 100)
            : 100;

        return [
            'session_id' => $profile->session_id,
            'nickname' => $profile->nickname,
            'experience_tier' => $profile->experience_tier,
            'xp' => (int) $profile->xp,
            'level' => (int) $profile->level,
            'games_played' => (int) $profile->games_played,
            'wins' => (int) $profile->wins,
            'next_level_xp' => $nextLevelXp,
            'level_progress_percent' => max(0, min(100, $progress)),
        ];
    }
}
